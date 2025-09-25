<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/license/osl-3-0-php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@qloapps.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to a newer
 * versions in the future. If you wish to customize this module for your needs
 * please refer to https://store.webkul.com/customisation-guidelines for more information.
 *
 * @author Webkul IN
 * @copyright Since 2010 Webkul
 * @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
 */

class KLQuotePricingEngine
{
    /**
     * Build a quote summary from the provided payload. This low-level helper
     * performs all arithmetic in minor units so callers can unit test pricing
     * logic without bootstrapping a PrestaShop context.
     *
     * Expected payload structure:
     * - currency_iso_code: string ISO 4217 currency code
     * - pricing_method: string (nightly|weekly|package)
     * - nights: int number of nights in the stay (>=0)
     * - base_line: array describing the base charge with keys:
     *     - code: identifier for the line (string)
     *     - label: human readable description (string)
     *     - quantity: int quantity multiplier
     *     - unit_net_minor: int price per unit excl. tax (minor units)
     *     - unit_gross_minor: int price per unit incl. tax (minor units)
     *     - metadata: optional associative array passed through untouched
     * - base_nightly_net_minor / base_nightly_gross_minor: ints representing the
     *   per-night base price used to evaluate seasonal adjustments. For weekly
     *   or package pricing the caller should already normalise these values.
     * - season_definitions: array of associative arrays with keys:
     *     - code, label: identifiers for reporting
     *     - method: 'fixed' or 'percent'
     *     - adjustment_amount_minor: int (used when method === 'fixed')
     *     - adjustment_percent_basis_points: int (used when method === 'percent')
     *     - dates: array of ISO date strings representing each night covered
     *       by the rule (e.g. ['2024-01-01', '2024-01-02'])
     * - package_components: array of associative arrays describing optional
     *   add-ons. Each entry may include:
     *     - code, label: identifiers for the component
     *     - quantity: multiplier (int|float)
     *     - unit_net_minor / unit_gross_minor: unit pricing in minor units
     *     - is_optional: bool flag
     *     - selected: bool flag indicating whether the optional component has
     *       been selected for inclusion (ignored when is_optional === false)
     *     - metadata: optional associative array passed through untouched
     * - warnings: optional array of strings bubbled up to the caller
     * - metadata: optional associative array merged into the response
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public static function buildQuote(array $payload)
    {
        $currencyIso = isset($payload['currency_iso_code']) ? (string) $payload['currency_iso_code'] : '';
        $pricingMethod = isset($payload['pricing_method']) ? (string) $payload['pricing_method'] : '';
        $nights = isset($payload['nights']) ? (int) $payload['nights'] : 0;

        $baseLine = isset($payload['base_line']) && is_array($payload['base_line'])
            ? $payload['base_line']
            : array();

        $lineItems = array();
        $netTotal = 0;
        $grossTotal = 0;

        if (!empty($baseLine)) {
            $baseLineSanitised = self::formatLineItem($baseLine);
            $lineItems[] = $baseLineSanitised;
            $netTotal += $baseLineSanitised['total_net_minor'];
            $grossTotal += $baseLineSanitised['total_gross_minor'];
        }

        $baseNightlyNet = isset($payload['base_nightly_net_minor']) ? (int) $payload['base_nightly_net_minor'] : 0;
        $baseNightlyGross = isset($payload['base_nightly_gross_minor']) ? (int) $payload['base_nightly_gross_minor'] : 0;

        $taxRatio = self::resolveTaxRatio($baseLine, $baseNightlyNet, $baseNightlyGross);

        $seasonDefinitions = isset($payload['season_definitions']) && is_array($payload['season_definitions'])
            ? $payload['season_definitions']
            : array();

        foreach ($seasonDefinitions as $season) {
            if (!is_array($season)) {
                continue;
            }

            $seasonLine = self::buildSeasonLine($season, $baseNightlyNet, $baseNightlyGross, $taxRatio);
            if ($seasonLine === null) {
                continue;
            }

            $lineItems[] = $seasonLine;
            $netTotal += $seasonLine['total_net_minor'];
            $grossTotal += $seasonLine['total_gross_minor'];
        }

        $components = isset($payload['package_components']) && is_array($payload['package_components'])
            ? $payload['package_components']
            : array();

        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }

            $isOptional = !empty($component['is_optional']);
            $selected = isset($component['selected']) ? (bool) $component['selected'] : true;
            if ($isOptional && !$selected) {
                continue;
            }

            $componentLine = self::buildComponentLine($component, $taxRatio);
            if ($componentLine === null) {
                continue;
            }

            $lineItems[] = $componentLine;
            $netTotal += $componentLine['total_net_minor'];
            $grossTotal += $componentLine['total_gross_minor'];
        }

        $warnings = isset($payload['warnings']) && is_array($payload['warnings']) ? $payload['warnings'] : array();
        $metadata = isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : array();

        $result = array(
            'currency_iso_code' => $currencyIso,
            'pricing_method' => $pricingMethod,
            'nights' => $nights,
            'line_items' => $lineItems,
            'net_total_minor' => (int) $netTotal,
            'gross_total_minor' => (int) $grossTotal,
            'tax_total_minor' => (int) ($grossTotal - $netTotal),
            'warnings' => $warnings,
            'metadata' => $metadata,
        );

        return $result;
    }

    /**
     * Build a fully fledged quote by fetching the rate plan, resource profile
     * and optional package configuration. This orchestrator prepares the
     * payload expected by {@see buildQuote()} and returns the enriched result
     * (including plan/package/resource metadata) for storage or UI display.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public static function generateQuote(array $options)
    {
        $idRatePlan = isset($options['id_kl_rate_plan']) ? (int) $options['id_kl_rate_plan'] : 0;
        $idPackage = isset($options['id_kl_package']) ? (int) $options['id_kl_package'] : 0;
        $idResourceProfile = isset($options['id_kl_resource_profile']) ? (int) $options['id_kl_resource_profile'] : 0;
        $checkIn = isset($options['check_in']) ? (string) $options['check_in'] : '';
        $checkOut = isset($options['check_out']) ? (string) $options['check_out'] : '';

        if (!$idRatePlan && !$idPackage) {
            throw new PrestaShopException('Either id_kl_rate_plan or id_kl_package must be provided.');
        }
        if (!$idResourceProfile) {
            throw new PrestaShopException('id_kl_resource_profile is required to generate a quote.');
        }
        if (!$checkIn || !$checkOut) {
            throw new PrestaShopException('check_in and check_out dates are required.');
        }

        $dateIn = self::normaliseDate($checkIn);
        $dateOut = self::normaliseDate($checkOut);
        if ($dateIn >= $dateOut) {
            throw new PrestaShopException('check_out must be after check_in.');
        }

        $nights = self::calculateNights($dateIn, $dateOut);

        $idLang = isset($options['id_lang']) ? (int) $options['id_lang'] : (int) Configuration::get('PS_LANG_DEFAULT');

        $plan = null;
        if ($idRatePlan) {
            $plan = new KLRatePlan($idRatePlan, $idLang);
            if (!Validate::isLoadedObject($plan)) {
                throw new PrestaShopException('Invalid rate plan selected.');
            }
        }

        $package = null;
        if ($idPackage) {
            $package = new KLPackage($idPackage, $idLang);
            if (!Validate::isLoadedObject($package)) {
                throw new PrestaShopException('Invalid package selected.');
            }
            if (!$plan && $package->id_kl_rate_plan) {
                $plan = new KLRatePlan((int) $package->id_kl_rate_plan, $idLang);
            }
        }

        if (!$plan || !Validate::isLoadedObject($plan)) {
            throw new PrestaShopException('Unable to resolve the rate plan used for pricing.');
        }

        $resource = new KLResourceProfile($idResourceProfile);
        if (!Validate::isLoadedObject($resource)) {
            throw new PrestaShopException('Resource profile not found.');
        }

        $occupancy = self::resolveOccupancy($options, $resource);
        $leadDays = self::calculateLeadDays($dateIn);

        $contextCurrency = Context::getContext() && Context::getContext()->currency
            ? Context::getContext()->currency
            : Currency::getCurrencyInstance((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        $planCurrency = self::resolvePlanCurrency($plan, $contextCurrency);

        $productInfo = self::resolveProductPricing($planCurrency, $contextCurrency, $plan, $resource, $nights);

        $seasonDefinitions = self::prepareSeasonDefinitions($plan, $dateIn, $dateOut, $nights, $occupancy, $leadDays);

        $componentDefinitions = self::prepareComponentDefinitions($package, $options, $productInfo['tax_ratio']);

        $warnings = self::collectScopeWarnings($plan, $package, $resource, $options);

        $baseLine = self::buildBaseLine($plan, $nights, $productInfo);

        $payload = array(
            'currency_iso_code' => $planCurrency->iso_code,
            'pricing_method' => (string) $plan->pricing_method,
            'nights' => $nights,
            'base_line' => $baseLine,
            'base_nightly_net_minor' => $productInfo['base_nightly_net_minor'],
            'base_nightly_gross_minor' => $productInfo['base_nightly_gross_minor'],
            'season_definitions' => $seasonDefinitions,
            'package_components' => $componentDefinitions,
            'warnings' => $warnings,
            'metadata' => array(
                'check_in' => $dateIn,
                'check_out' => $dateOut,
                'lead_days' => $leadDays,
                'occupancy' => $occupancy,
                'resource_kind' => (string) $resource->resource_kind,
                'plan_code' => (string) $plan->plan_code,
                'plan_name' => isset($plan->name) ? (string) $plan->name : '',
                'package_code' => $package ? (string) $package->package_code : null,
                'package_name' => $package && isset($package->name) ? (string) $package->name : null,
            ),
        );

        $quote = self::buildQuote($payload);

        $quote['plan'] = array(
            'id' => (int) $plan->id,
            'plan_code' => (string) $plan->plan_code,
            'name' => isset($plan->name) ? (string) $plan->name : '',
            'pricing_method' => (string) $plan->pricing_method,
            'currency_iso_code' => $planCurrency->iso_code,
        );

        $quote['resource'] = array(
            'id' => (int) $resource->id,
            'resource_code' => (string) $resource->resource_code,
            'resource_kind' => (string) $resource->resource_kind,
        );

        if ($package && Validate::isLoadedObject($package)) {
            $quote['package'] = array(
                'id' => (int) $package->id,
                'package_code' => (string) $package->package_code,
                'name' => isset($package->name) ? (string) $package->name : '',
            );
        } else {
            $quote['package'] = null;
        }

        $quote['season_applications'] = $seasonDefinitions;
        $quote['component_applications'] = $componentDefinitions;

        return $quote;
    }

    /**
     * Persist the given quote payload to the kl_quote table while storing the
     * detailed breakdown as JSON inside the model payload column.
     *
     * @param array<string, mixed> $quoteData result from {@see generateQuote()}
     * @param int $idInquiry
     * @param int|null $idEmployee
     * @param array<string, mixed> $overrides optional overrides for status/validity
     *
     * @return KLQuote
     */
    public static function persistQuote(array $quoteData, $idInquiry, $idEmployee = null, array $overrides = array())
    {
        $idInquiry = (int) $idInquiry;
        if ($idInquiry <= 0) {
            throw new PrestaShopException('Cannot persist quote without an inquiry id.');
        }

        $quote = new KLQuote();
        $quote->id_inquiry = $idInquiry;
        $quote->id_employee_author = $idEmployee ? (int) $idEmployee : null;

        $quote->id_kl_rate_plan = isset($quoteData['plan']['id']) ? (int) $quoteData['plan']['id'] : null;
        $quote->id_kl_package = isset($quoteData['package']['id']) ? (int) $quoteData['package']['id'] : null;

        $quote->currency_iso_code = isset($quoteData['currency_iso_code']) ? (string) $quoteData['currency_iso_code'] : '';
        $quote->net_total_minor = isset($quoteData['net_total_minor']) ? (int) $quoteData['net_total_minor'] : 0;
        $quote->tax_total_minor = isset($quoteData['tax_total_minor']) ? (int) $quoteData['tax_total_minor'] : 0;
        $quote->gross_total_minor = isset($quoteData['gross_total_minor']) ? (int) $quoteData['gross_total_minor'] : 0;

        if (!empty($overrides['status'])) {
            $quote->status = (string) $overrides['status'];
        }

        if (!empty($overrides['valid_from'])) {
            $quote->valid_from = self::normaliseDateTime($overrides['valid_from']);
        }
        if (!empty($overrides['valid_until'])) {
            $quote->valid_until = self::normaliseDateTime($overrides['valid_until']);
        }

        $payload = $quoteData;
        $payload['persisted_at'] = date(DATE_ATOM);
        $quote->setPayload($payload);

        if (!$quote->add()) {
            throw new PrestaShopException('Unable to persist quote.');
        }

        return $quote;
    }

    /**
     * Normalise a line entry ensuring numeric totals are available.
     *
     * @param array<string, mixed> $line
     *
     * @return array<string, mixed>
     */
    protected static function formatLineItem(array $line)
    {
        $quantity = isset($line['quantity']) ? (float) $line['quantity'] : 0.0;
        $unitNet = isset($line['unit_net_minor']) ? (int) $line['unit_net_minor'] : 0;
        $unitGross = isset($line['unit_gross_minor']) ? (int) $line['unit_gross_minor'] : 0;

        $totalNet = (int) round($quantity * $unitNet);
        $totalGross = (int) round($quantity * $unitGross);

        $sanitised = array(
            'code' => isset($line['code']) ? (string) $line['code'] : '',
            'label' => isset($line['label']) ? (string) $line['label'] : '',
            'quantity' => $quantity,
            'unit_net_minor' => $unitNet,
            'unit_gross_minor' => $unitGross,
            'total_net_minor' => $totalNet,
            'total_gross_minor' => $totalGross,
        );

        if (isset($line['metadata']) && is_array($line['metadata'])) {
            $sanitised['metadata'] = $line['metadata'];
        }

        return $sanitised;
    }

    /**
     * @param array<string, mixed> $season
     *
     * @return array<string, mixed>|null
     */
    protected static function buildSeasonLine(array $season, $baseNightlyNet, $baseNightlyGross, $taxRatio)
    {
        $dates = isset($season['dates']) && is_array($season['dates']) ? $season['dates'] : array();
        $quantity = count($dates);
        if ($quantity <= 0) {
            return null;
        }

        $method = isset($season['method']) ? (string) $season['method'] : '';
        $unitNet = 0;
        $unitGross = 0;

        if ($method === 'fixed') {
            $unitNet = isset($season['adjustment_amount_minor']) ? (int) $season['adjustment_amount_minor'] : 0;
            $unitGross = self::convertNetToGross($unitNet, $taxRatio);
        } elseif ($method === 'percent') {
            $basisPoints = isset($season['adjustment_percent_basis_points'])
                ? (int) $season['adjustment_percent_basis_points']
                : 0;

            $unitNet = (int) round($baseNightlyNet * $basisPoints / 10000);
            $unitGross = (int) round($baseNightlyGross * $basisPoints / 10000);
        } else {
            return null;
        }

        $line = array(
            'code' => isset($season['code']) ? (string) $season['code'] : '',
            'label' => isset($season['label']) ? (string) $season['label'] : '',
            'quantity' => $quantity,
            'unit_net_minor' => $unitNet,
            'unit_gross_minor' => $unitGross,
            'total_net_minor' => (int) round($quantity * $unitNet),
            'total_gross_minor' => (int) round($quantity * $unitGross),
            'metadata' => array(
                'method' => $method,
                'dates' => $dates,
            ),
        );

        return $line;
    }

    /**
     * @param array<string, mixed> $component
     *
     * @return array<string, mixed>|null
     */
    protected static function buildComponentLine(array $component, $taxRatio)
    {
        $quantity = isset($component['quantity']) ? (float) $component['quantity'] : 0.0;
        if ($quantity <= 0) {
            $quantity = 1.0;
        }

        $unitNet = isset($component['unit_net_minor']) ? (int) $component['unit_net_minor'] : 0;
        $unitGross = isset($component['unit_gross_minor']) ? (int) $component['unit_gross_minor'] : null;
        if ($unitGross === null) {
            $unitGross = self::convertNetToGross($unitNet, $taxRatio);
        }

        $line = array(
            'code' => isset($component['code']) ? (string) $component['code'] : '',
            'label' => isset($component['label']) ? (string) $component['label'] : '',
            'quantity' => $quantity,
            'unit_net_minor' => $unitNet,
            'unit_gross_minor' => $unitGross,
            'total_net_minor' => (int) round($quantity * $unitNet),
            'total_gross_minor' => (int) round($quantity * $unitGross),
        );

        if (isset($component['metadata']) && is_array($component['metadata'])) {
            $line['metadata'] = $component['metadata'];
        }

        return $line;
    }

    protected static function resolveTaxRatio($baseLine, $baseNightlyNet, $baseNightlyGross)
    {
        if ($baseNightlyNet > 0 && $baseNightlyGross > 0) {
            return $baseNightlyGross / $baseNightlyNet;
        }

        if (isset($baseLine['unit_net_minor']) && isset($baseLine['unit_gross_minor'])) {
            $unitNet = (int) $baseLine['unit_net_minor'];
            $unitGross = (int) $baseLine['unit_gross_minor'];
            if ($unitNet > 0 && $unitGross > 0) {
                return $unitGross / $unitNet;
            }
        }

        return 1.0;
    }

    protected static function convertNetToGross($netMinor, $taxRatio)
    {
        if ($netMinor === 0) {
            return 0;
        }

        if ($taxRatio <= 0) {
            return (int) $netMinor;
        }

        return (int) round($netMinor * $taxRatio);
    }

    protected static function normaliseDate($value)
    {
        $timestamp = strtotime($value);
        if (!$timestamp) {
            throw new PrestaShopException('Invalid date supplied: '.$value);
        }

        return date('Y-m-d', $timestamp);
    }

    protected static function normaliseDateTime($value)
    {
        $timestamp = strtotime($value);
        if (!$timestamp) {
            throw new PrestaShopException('Invalid datetime supplied: '.$value);
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    protected static function calculateNights($dateIn, $dateOut)
    {
        $in = new DateTime($dateIn.' 00:00:00');
        $out = new DateTime($dateOut.' 00:00:00');
        $interval = $in->diff($out);

        return max(0, (int) $interval->format('%a'));
    }

    protected static function calculateLeadDays($dateIn)
    {
        $now = new DateTime(date('Y-m-d').' 00:00:00');
        $arrival = new DateTime($dateIn.' 00:00:00');
        $diff = $now->diff($arrival);

        $days = (int) $diff->format('%r%a');
        return $days < 0 ? 0 : $days;
    }

    protected static function resolveOccupancy(array $options, KLResourceProfile $resource)
    {
        $adults = 0;
        $children = 0;

        if (isset($options['occupancy']) && is_array($options['occupancy'])) {
            $adults = isset($options['occupancy']['adults']) ? (int) $options['occupancy']['adults'] : 0;
            $children = isset($options['occupancy']['children']) ? (int) $options['occupancy']['children'] : 0;
        }

        $total = $adults + $children;
        if ($total > 0) {
            return array(
                'adults' => $adults,
                'children' => $children,
                'total' => $total,
            );
        }

        $capacity = KLResourceCapacity::loadByProfileId((int) $resource->id);
        if ($capacity && Validate::isLoadedObject($capacity)) {
            $adults = (int) $capacity->capacity_adults;
            $children = (int) $capacity->capacity_children;
            $total = (int) $capacity->capacity_total;

            if ($total <= 0) {
                $total = $adults + $children;
            }

            if ($total > 0) {
                return array(
                    'adults' => $adults,
                    'children' => $children,
                    'total' => $total,
                );
            }
        }

        return array('adults' => 0, 'children' => 0, 'total' => 0);
    }

    protected static function resolvePlanCurrency(KLRatePlan $plan, Currency $fallback)
    {
        $iso = (string) $plan->currency_iso_code;
        if ($iso === '') {
            return $fallback;
        }

        $idCurrency = Currency::getIdByIsoCode($iso, Context::getContext() && Context::getContext()->shop ? (int) Context::getContext()->shop->id : null);
        if (!$idCurrency) {
            return $fallback;
        }

        return Currency::getCurrencyInstance($idCurrency);
    }

    protected static function resolveProductPricing(Currency $planCurrency, Currency $contextCurrency, KLRatePlan $plan, KLResourceProfile $resource, $nights)
    {
        $idProduct = self::resolveProductIdForResource($resource);
        if (!$idProduct) {
            throw new PrestaShopException('Resource profile is not linked to a room type product.');
        }

        $baseNet = Product::getPriceStatic($idProduct, false, null, 6, null, false, true, 1);
        $baseGross = Product::getPriceStatic($idProduct, true, null, 6, null, false, true, 1);

        if ($contextCurrency->id !== $planCurrency->id) {
            $baseNet = Tools::convertPriceFull($baseNet, $contextCurrency, $planCurrency, true);
            $baseGross = Tools::convertPriceFull($baseGross, $contextCurrency, $planCurrency, true);
        }

        $baseNetMinor = (int) round(Tools::ps_round($baseNet, 2) * 100);
        $baseGrossMinor = (int) round(Tools::ps_round($baseGross, 2) * 100);

        $taxRatio = ($baseNetMinor > 0 && $baseGrossMinor > 0) ? $baseGrossMinor / $baseNetMinor : 1.0;

        if ($plan->pricing_method === 'package' && $nights > 0) {
            $perNightNet = (int) round($baseNetMinor / $nights);
            $perNightGross = (int) round($baseGrossMinor / $nights);
        } else {
            $perNightNet = $baseNetMinor;
            $perNightGross = $baseGrossMinor;
        }

        return array(
            'base_unit_net_minor' => $baseNetMinor,
            'base_unit_gross_minor' => $baseGrossMinor,
            'base_nightly_net_minor' => $perNightNet,
            'base_nightly_gross_minor' => $perNightGross,
            'tax_ratio' => $taxRatio,
        );
    }

    protected static function resolveProductIdForResource(KLResourceProfile $resource)
    {
        if ($resource->id_room_type) {
            $query = new DbQuery();
            $query->select('`id_product`');
            $query->from('htl_room_type');
            $query->where('`id` = '.(int) $resource->id_room_type);

            $idProduct = (int) Db::getInstance()->getValue($query);
            if ($idProduct) {
                return $idProduct;
            }
        }

        return 0;
    }

    protected static function prepareSeasonDefinitions(KLRatePlan $plan, $dateIn, $dateOut, $nights, array $occupancy, $leadDays)
    {
        $seasons = KLRatePlanSeason::getSeasonsForPlan((int) $plan->id);
        if (empty($seasons)) {
            return array();
        }

        $dates = self::buildNightlyDates($dateIn, $dateOut);
        $definitions = array();

        foreach ($seasons as $season) {
            if (!self::seasonMatches($season, $nights, $occupancy, $leadDays)) {
                continue;
            }

            $seasonDates = self::clipDatesToSeason($dates, $season);
            if (empty($seasonDates)) {
                continue;
            }

            $definitions[] = array(
                'code' => 'season-'.$season['id_kl_rate_plan_season'],
                'label' => !empty($season['internal_label']) ? (string) $season['internal_label'] : $plan->plan_code.' season',
                'method' => (string) $season['adjustment_method'],
                'adjustment_amount_minor' => isset($season['adjustment_amount_minor']) ? (int) $season['adjustment_amount_minor'] : 0,
                'adjustment_percent_basis_points' => isset($season['adjustment_percent_basis_points']) ? (int) $season['adjustment_percent_basis_points'] : 0,
                'dates' => $seasonDates,
                'season' => $season,
            );
        }

        return $definitions;
    }

    protected static function buildNightlyDates($dateIn, $dateOut)
    {
        $dates = array();

        $cursor = new DateTime($dateIn.' 00:00:00');
        $end = new DateTime($dateOut.' 00:00:00');

        while ($cursor < $end) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->modify('+1 day');
        }

        return $dates;
    }

    protected static function seasonMatches(array $season, $nights, array $occupancy, $leadDays)
    {
        $minNights = isset($season['min_stay_nights']) ? (int) $season['min_stay_nights'] : 0;
        if ($minNights > 0 && $nights < $minNights) {
            return false;
        }

        $maxNights = isset($season['max_stay_nights']) ? (int) $season['max_stay_nights'] : 0;
        if ($maxNights > 0 && $nights > $maxNights) {
            return false;
        }

        $minOcc = isset($season['min_occupancy']) ? (int) $season['min_occupancy'] : 0;
        if ($minOcc > 0 && $occupancy['total'] > 0 && $occupancy['total'] < $minOcc) {
            return false;
        }

        $maxOcc = isset($season['max_occupancy']) ? (int) $season['max_occupancy'] : 0;
        if ($maxOcc > 0 && $occupancy['total'] > 0 && $occupancy['total'] > $maxOcc) {
            return false;
        }

        $minLead = isset($season['min_lead_days']) ? (int) $season['min_lead_days'] : 0;
        if ($minLead > 0 && $leadDays < $minLead) {
            return false;
        }

        $maxLead = isset($season['max_lead_days']) ? (int) $season['max_lead_days'] : 0;
        if ($maxLead > 0 && $leadDays > $maxLead) {
            return false;
        }

        return true;
    }

    protected static function clipDatesToSeason(array $dates, array $season)
    {
        $start = !empty($season['date_from']) ? strtotime($season['date_from']) : false;
        $end = !empty($season['date_to']) ? strtotime($season['date_to']) : false;
        if ($start === false || $end === false) {
            return array();
        }

        $end += 86400; // inclusive of end date

        $filtered = array();
        foreach ($dates as $date) {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                continue;
            }
            if ($timestamp < $start || $timestamp >= $end) {
                continue;
            }
            $filtered[] = $date;
        }

        return $filtered;
    }

    protected static function prepareComponentDefinitions($package, array $options, $taxRatio)
    {
        if (!$package || !Validate::isLoadedObject($package)) {
            return array();
        }

        $selectedOptionalIds = array();
        if (!empty($options['include_optional_components'])) {
            if (is_array($options['include_optional_components'])) {
                foreach ($options['include_optional_components'] as $idComponent) {
                    $selectedOptionalIds[(int) $idComponent] = true;
                }
            } else {
                $selectedOptionalIds[(int) $options['include_optional_components']] = true;
            }
        }

        $components = $package->getComponents();
        if (empty($components)) {
            return array();
        }

        $definitions = array();
        foreach ($components as $component) {
            if (!$component instanceof KLPackageComponent || !Validate::isLoadedObject($component)) {
                continue;
            }

            $idComponent = (int) $component->id;
            $isOptional = (bool) $component->is_optional;
            $selected = !$isOptional || isset($selectedOptionalIds[$idComponent]);

            $definitions[] = array(
                'code' => 'component-'.$idComponent,
                'label' => (string) $component->component_type,
                'quantity' => $component->quantity !== null ? (float) $component->quantity : 1.0,
                'unit_net_minor' => $component->price_minor !== null ? (int) $component->price_minor : 0,
                'unit_gross_minor' => $component->price_minor !== null ? self::convertNetToGross((int) $component->price_minor, $taxRatio) : 0,
                'is_optional' => $isOptional,
                'selected' => $selected,
                'metadata' => array(
                    'component_type' => (string) $component->component_type,
                    'reference_code' => (string) $component->reference_code,
                    'id_kl_rate_plan' => (int) $component->id_kl_rate_plan,
                ),
            );
        }

        return $definitions;
    }

    protected static function collectScopeWarnings(KLRatePlan $plan, $package, KLResourceProfile $resource, array $options)
    {
        $warnings = array();

        $resourceKind = (string) $resource->resource_kind;

        $planKinds = $plan->getResourceKindScope();
        if (!empty($planKinds) && !in_array($resourceKind, $planKinds, true)) {
            $warnings[] = sprintf(
                Tools::displayError('The selected plan is scoped to %s and may not apply to %s resources.'),
                implode(', ', $planKinds),
                $resourceKind
            );
        }

        $planSegments = $plan->getAudienceSegmentScope();
        if (!empty($planSegments)) {
            $audienceSegments = array();
            if (!empty($options['audience_segments']) && is_array($options['audience_segments'])) {
                foreach ($options['audience_segments'] as $segment) {
                    $audienceSegments[] = Tools::strtoupper(trim((string) $segment));
                }
            }

            if (empty(array_intersect($planSegments, $audienceSegments))) {
                $warnings[] = sprintf(
                    Tools::displayError('Plan %s expects audience segments [%s].'),
                    $plan->plan_code,
                    implode(', ', $planSegments)
                );
            }
        }

        if ($package && Validate::isLoadedObject($package)) {
            $packageKinds = $package->getResourceKindScope();
            if (!empty($packageKinds) && !in_array($resourceKind, $packageKinds, true)) {
                $warnings[] = sprintf(
                    Tools::displayError('Package %s is scoped to %s resources and may not fit %s.'),
                    $package->package_code,
                    implode(', ', $packageKinds),
                    $resourceKind
                );
            }
        }

        return $warnings;
    }

    protected static function buildBaseLine(KLRatePlan $plan, $nights, array $productInfo)
    {
        $method = (string) $plan->pricing_method;
        $label = isset($plan->name) ? (string) $plan->name : $plan->plan_code;

        if ($method === 'weekly') {
            $weeks = max(1, (int) ceil($nights / 7));
            $unitNet = (int) ($productInfo['base_nightly_net_minor'] * 7);
            $unitGross = (int) ($productInfo['base_nightly_gross_minor'] * 7);

            return array(
                'code' => 'base_rate',
                'label' => $label.' (per week)',
                'quantity' => $weeks,
                'unit_net_minor' => $unitNet,
                'unit_gross_minor' => $unitGross,
                'metadata' => array(
                    'unit' => 'week',
                    'charged_weeks' => $weeks,
                    'stay_nights' => $nights,
                ),
            );
        }

        if ($method === 'package') {
            return array(
                'code' => 'base_rate',
                'label' => $label.' (per stay)',
                'quantity' => 1,
                'unit_net_minor' => (int) $productInfo['base_unit_net_minor'],
                'unit_gross_minor' => (int) $productInfo['base_unit_gross_minor'],
                'metadata' => array(
                    'unit' => 'stay',
                    'stay_nights' => $nights,
                ),
            );
        }

        return array(
            'code' => 'base_rate',
            'label' => $label.' (per night)',
            'quantity' => $nights,
            'unit_net_minor' => (int) $productInfo['base_nightly_net_minor'],
            'unit_gross_minor' => (int) $productInfo['base_nightly_gross_minor'],
            'metadata' => array(
                'unit' => 'night',
                'stay_nights' => $nights,
            ),
        );
    }
}

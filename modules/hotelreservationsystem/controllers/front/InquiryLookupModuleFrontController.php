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
 */

require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLResourceProfile.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLPackage.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLQuotePricingEngine.php';

class HotelReservationSystemInquiryLookupModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        if (!$this->ajax) {
            header('Content-Type: application/json');
        }

        $action = Tools::getValue('action');
        $payload = array();
        $status = 200;

        try {
            switch ($action) {
                case 'resources':
                    $payload = $this->getResourceSuggestions();
                    break;
                case 'packages':
                    $payload = $this->getPackageSuggestions();
                    break;
                case 'quote':
                    $payload = $this->getQuotePreview();
                    break;
                default:
                    $status = 400;
                    $payload = array(
                        'error' => 'Unknown action',
                    );
            }
        } catch (PrestaShopException $exception) {
            $status = 500;
            $payload = array(
                'error' => $exception->getMessage(),
            );
            PrestaShopLogger::addLog('Inquiry lookup failed: '.$exception->getMessage(), 2, null, 'InquiryLookup', null, true);
        }

        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($status);
        }

        die(json_encode($payload));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getResourceSuggestions()
    {
        $resources = KLResourceProfile::getPublishedProfilesWithDetails($this->context->language->id, $this->context->shop->id);
        $results = array();

        foreach ($resources as $resource) {
            $results[] = array(
                'id' => (int) $resource['id_kl_resource_profile'],
                'code' => $resource['resource_code'],
                'kind' => $resource['resource_kind'],
                'name' => $resource['room_type_name'],
                'capacity' => $resource['capacity'],
                'is_bookable' => (bool) $resource['is_bookable'],
                'is_published' => (bool) $resource['is_published'],
            );
        }

        return array(
            'resources' => $results,
            'generated_at' => date(DATE_ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPackageSuggestions()
    {
        $resourceKind = Tools::getValue('resource_kind');
        $idLang = (int) $this->context->language->id;

        $query = new DbQuery();
        $query->select('p.`id_kl_package`, p.`package_code`, p.`resource_kind_scope`, p.`audience_segment_scope`, p.`is_featured`');
        $query->select('pl.`name`, pl.`tagline`');
        $query->from('kl_package', 'p');
        $query->innerJoin('kl_package_lang', 'pl', 'pl.`id_kl_package` = p.`id_kl_package` AND pl.`id_lang` = '.(int) $idLang);
        $query->where('p.`is_active` = 1');
        $query->orderBy('p.`is_featured` DESC, pl.`name` ASC');

        $rows = Db::getInstance()->executeS($query);

        $packages = array();
        foreach ($rows as $row) {
            if ($resourceKind) {
                $scope = $row['resource_kind_scope'] ? json_decode($row['resource_kind_scope'], true) : array();
                if ($scope && is_array($scope) && !in_array($resourceKind, $scope)) {
                    continue;
                }
            }

            $packages[] = array(
                'id' => (int) $row['id_kl_package'],
                'code' => $row['package_code'],
                'name' => $row['name'],
                'tagline' => $row['tagline'],
                'is_featured' => (bool) $row['is_featured'],
            );
        }

        return array(
            'packages' => $packages,
            'generated_at' => date(DATE_ATOM),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getQuotePreview()
    {
        $idResource = (int) Tools::getValue('id_resource');
        $idRatePlan = (int) Tools::getValue('id_rate_plan');
        $idPackage = (int) Tools::getValue('id_package');
        $checkIn = Tools::getValue('check_in');
        $checkOut = Tools::getValue('check_out');

        if (!$idResource || !$idRatePlan || !$checkIn || !$checkOut) {
            throw new PrestaShopException('Missing parameters for quote preview.');
        }

        $idLang = (int) $this->context->language->id;

        $options = array(
            'id_kl_resource_profile' => $idResource,
            'id_kl_rate_plan' => $idRatePlan,
            'id_kl_package' => $idPackage ?: 0,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'id_lang' => $idLang,
        );

        $partyAdults = (int) Tools::getValue('party_adults');
        $partyChildren = (int) Tools::getValue('party_children');
        if ($partyAdults > 0 || $partyChildren > 0) {
            $options['occupancy'] = array(
                'adults' => max(0, $partyAdults),
                'children' => max(0, $partyChildren),
            );
        }

        $quote = KLQuotePricingEngine::generateQuote($options);

        return array(
            'quote' => $quote,
            'generated_at' => date(DATE_ATOM),
        );
    }
}

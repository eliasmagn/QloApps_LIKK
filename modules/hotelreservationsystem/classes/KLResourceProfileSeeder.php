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

class KLResourceProfileSeeder
{
    /**
     * Create resource profiles (and matching capacity rows) for room types that
     * do not yet have taxonomy metadata.
     *
     * @param int|null $idEmployee optional employee id recorded on the profile
     *
     * @return array<string, int> summary counters for logging/CLI use
     */
    public static function seedFromRoomTypes($idEmployee = null)
    {
        $summary = array(
            'created_profiles' => 0,
            'created_capacities' => 0,
            'patched_capacities' => 0,
            'skipped_profiles' => 0,
        );

        $existingProfiles = Db::getInstance()->executeS(
            'SELECT `id_kl_resource_profile`, `id_room_type`, `resource_code`'
            .' FROM `'._DB_PREFIX_.'kl_resource_profile`'
        );

        $existingByRoomType = array();
        $usedCodes = array();
        if (is_array($existingProfiles)) {
            foreach ($existingProfiles as $profileRow) {
                $idRoomType = (int) $profileRow['id_room_type'];
                if ($idRoomType) {
                    $existingByRoomType[$idRoomType] = (int) $profileRow['id_kl_resource_profile'];
                }
                if (!empty($profileRow['resource_code'])) {
                    $usedCodes[$profileRow['resource_code']] = true;
                }
            }
        }

        $roomTypes = Db::getInstance()->executeS(self::buildRoomTypeQuery());
        if (!is_array($roomTypes) || empty($roomTypes)) {
            return $summary;
        }

        foreach ($roomTypes as $roomType) {
            $idRoomType = (int) $roomType['id_room_type'];
            if (isset($existingByRoomType[$idRoomType])) {
                $summary['skipped_profiles']++;
                $profileId = (int) $existingByRoomType[$idRoomType];
                if (!KLResourceCapacity::loadByProfileId($profileId)) {
                    $capacity = self::buildCapacityModel($profileId, $roomType);
                    if ($capacity && $capacity->add()) {
                        $summary['patched_capacities']++;
                    }
                }
                continue;
            }

            $profile = new KLResourceProfile();
            $profile->resource_code = self::buildResourceCode($roomType, $usedCodes);
            $profile->id_room_type = $idRoomType ?: null;
            $profile->resource_kind = KLResourceProfile::RESOURCE_KIND_ROOM;
            $profile->display_order = KLResourceProfile::getNextDisplayOrder(KLResourceProfile::RESOURCE_KIND_ROOM);
            $profile->is_bookable = 1;
            $profile->is_published = 1;
            $profile->timezone = self::resolveTimezone();
            $profile->created_by = $idEmployee ?: null;
            $profile->updated_by = $idEmployee ?: null;

            if (!$profile->add()) {
                continue;
            }

            $summary['created_profiles']++;
            $profileId = (int) $profile->id;

            $capacity = self::buildCapacityModel($profileId, $roomType);
            if ($capacity && $capacity->add()) {
                $summary['created_capacities']++;
            }
        }

        return $summary;
    }

    /**
     * Build the SQL query fetching room types that should be converted into
     * resource profiles.
     *
     * @return DbQuery
     */
    protected static function buildRoomTypeQuery()
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $idShop = (int) Configuration::get('PS_SHOP_DEFAULT');

        $defaultNameQuery = '(SELECT pl.`name` FROM `'._DB_PREFIX_.'product_lang` pl'
            .' WHERE pl.`id_product` = hrt.`id_product`'
            .($idLang ? ' AND pl.`id_lang` = '.(int) $idLang : '')
            .($idShop ? ' AND pl.`id_shop` = '.(int) $idShop : '')
            .' ORDER BY pl.`id_shop` DESC, pl.`id_lang` DESC LIMIT 1)';

        $fallbackNameQuery = '(SELECT pl.`name` FROM `'._DB_PREFIX_.'product_lang` pl'
            .' WHERE pl.`id_product` = hrt.`id_product`'
            .' ORDER BY pl.`id_lang` ASC LIMIT 1)';

        $query = new DbQuery();
        $query->select('hrt.`id` AS id_room_type');
        $query->select('hrt.`id_product`');
        $query->select('hrt.`max_adults`');
        $query->select('hrt.`max_children`');
        $query->select('hrt.`max_guests`');
        $query->select('hrt.`adults`');
        $query->select('hrt.`children`');
        $query->select('hrt.`comment`');
        $query->select('COALESCE('.$defaultNameQuery.', '.$fallbackNameQuery.', p.`reference`, CONCAT("ROOM-TYPE-", hrt.`id`)) AS room_type_name');
        $query->from('htl_room_type', 'hrt');
        $query->innerJoin('product', 'p', 'p.`id_product` = hrt.`id_product`');

        return $query;
    }

    /**
     * Generate a stable resource code derived from the room type name while
     * guaranteeing uniqueness within the resource profile catalogue.
     *
     * @param array<string, mixed> $roomType
     * @param array<string, bool>  $usedCodes reference list to avoid duplicates
     *
     * @return string
     */
    protected static function buildResourceCode(array $roomType, array &$usedCodes)
    {
        $base = '';
        if (!empty($roomType['room_type_name'])) {
            $base = Tools::strtoupper(str_replace('-', '_', Tools::link_rewrite($roomType['room_type_name'])));
        }

        if ($base === '') {
            $base = 'ROOM_TYPE_'.$roomType['id_room_type'];
        }

        $base = preg_replace('/[^A-Z0-9_]+/', '_', $base);
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'ROOM_TYPE_'.$roomType['id_room_type'];
        }

        $base = Tools::substr($base, 0, 48);
        $code = $base;
        $suffix = 1;
        while (isset($usedCodes[$code])) {
            $suffix++;
            $code = Tools::substr($base, 0, 48).'-'.$suffix;
        }

        $usedCodes[$code] = true;

        return $code;
    }

    /**
     * Build a capacity model instance pre-populated from the legacy room type.
     *
     * @param int                       $profileId
     * @param array<string, mixed>      $roomType
     *
     * @return KLResourceCapacity|null
     */
    protected static function buildCapacityModel($profileId, array $roomType)
    {
        $capacity = new KLResourceCapacity();
        $capacity->id_kl_resource_profile = (int) $profileId;
        $capacity->capacity_adults = self::normaliseCapacityValue($roomType, 'max_adults', 'adults');
        $capacity->capacity_children = self::normaliseCapacityValue($roomType, 'max_children', 'children');
        $capacity->capacity_total = self::normaliseTotalCapacity($roomType);
        $capacity->notes = !empty($roomType['comment']) ? Tools::substr($roomType['comment'], 0, 1000) : null;

        $hasData = false;
        foreach (array('capacity_adults', 'capacity_children', 'capacity_total') as $field) {
            if ($capacity->{$field} !== null) {
                $hasData = true;
                break;
            }
        }

        if (!$hasData && $capacity->notes === null) {
            return null;
        }

        return $capacity;
    }

    /**
     * Normalise adult/children capacities from room type columns.
     *
     * @param array<string, mixed> $roomType
     * @param string               $primaryKey
     * @param string               $fallbackKey
     *
     * @return int|null
     */
    protected static function normaliseCapacityValue(array $roomType, $primaryKey, $fallbackKey)
    {
        $candidates = array();
        if (isset($roomType[$primaryKey])) {
            $candidates[] = (int) $roomType[$primaryKey];
        }
        if (isset($roomType[$fallbackKey])) {
            $candidates[] = (int) $roomType[$fallbackKey];
        }

        foreach ($candidates as $value) {
            if ($value > 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Determine total capacity from the available room type fields.
     *
     * @param array<string, mixed> $roomType
     *
     * @return int|null
     */
    protected static function normaliseTotalCapacity(array $roomType)
    {
        if (isset($roomType['max_guests']) && (int) $roomType['max_guests'] > 0) {
            return (int) $roomType['max_guests'];
        }

        $adult = self::normaliseCapacityValue($roomType, 'max_adults', 'adults');
        $children = self::normaliseCapacityValue($roomType, 'max_children', 'children');

        $total = 0;
        if ($adult) {
            $total += (int) $adult;
        }
        if ($children) {
            $total += (int) $children;
        }

        return $total > 0 ? $total : null;
    }

    /**
     * Resolve the timezone that should be applied to newly seeded profiles.
     *
     * @return string|null
     */
    protected static function resolveTimezone()
    {
        $timezone = (string) Configuration::get('PS_TIMEZONE');
        if ($timezone) {
            return $timezone;
        }

        $context = Context::getContext();
        if ($context && $context->shop && !empty($context->shop->timezone)) {
            return $context->shop->timezone;
        }

        return @date_default_timezone_get();
    }
}

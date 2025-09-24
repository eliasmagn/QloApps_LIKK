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

class KLResourceProfile extends ObjectModel
{
    const RESOURCE_KIND_ROOM = 'room';
    const RESOURCE_KIND_ATELIER = 'atelier';
    const RESOURCE_KIND_SEMINAR = 'seminar';
    const RESOURCE_KIND_GASTRONOMY = 'gastronomy';

    /** @var string */
    public $resource_code;

    /** @var int */
    public $id_room_type;

    /** @var string */
    public $external_reference;

    /** @var string */
    public $resource_kind;

    /** @var int */
    public $display_order;

    /** @var bool */
    public $is_bookable;

    /** @var bool */
    public $is_published;

    /** @var string */
    public $timezone;

    /** @var int */
    public $created_by;

    /** @var int */
    public $updated_by;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_resource_profile',
        'primary' => 'id_kl_resource_profile',
        'fields' => array(
            'resource_code' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 64),
            'id_room_type' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'external_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64),
            'resource_kind' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32),
            'display_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'is_bookable' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'is_published' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'timezone' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 64),
            'created_by' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'updated_by' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Convenience helper for assigning resource codes in a predictable order.
     *
     * @param string $resourceKind
     * @return int next order value for the kind
     */
    public static function getNextDisplayOrder($resourceKind)
    {
        $query = new DbQuery();
        $query->select('MAX(`display_order`)');
        $query->from('kl_resource_profile');
        $query->where("`resource_kind` = '".pSQL($resourceKind)."'");

        $max = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        if (!$max) {
            return 1;
        }

        return (int) $max + 1;
    }

    /**
     * @return array<string> supported resource kinds
     */
    public static function getSupportedResourceKinds()
    {
        return array(
            self::RESOURCE_KIND_ROOM,
            self::RESOURCE_KIND_ATELIER,
            self::RESOURCE_KIND_SEMINAR,
            self::RESOURCE_KIND_GASTRONOMY,
        );
    }

    /**
     * Returns published resource profiles enriched with capacity metrics and optional storytelling data.
     *
     * @param int $idLang
     * @param int|null $idShop
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getPublishedProfilesWithDetails($idLang, $idShop = null)
    {
        $idLang = (int) $idLang;
        if ($idShop === null) {
            $context = Context::getContext();
            $idShop = $context && $context->shop ? (int) $context->shop->id : 0;
        }
        $idShop = (int) $idShop;

        $query = new DbQuery();
        $query->select('p.`id_kl_resource_profile`');
        $query->select('p.`resource_code`');
        $query->select('p.`external_reference`');
        $query->select('p.`resource_kind`');
        $query->select('p.`display_order`');
        $query->select('p.`is_bookable`');
        $query->select('p.`is_published`');
        $query->select('p.`timezone`');
        $query->select('cap.`capacity_adults`, cap.`capacity_children`, cap.`capacity_total`, cap.`capacity_seated`, cap.`capacity_standing`, cap.`floor_area_sqm`, cap.`ceiling_height_m`, cap.`notes` AS capacity_notes');
        $query->select('pl.`name` AS room_type_name');
        $query->from('kl_resource_profile', 'p');
        $query->leftJoin('kl_resource_capacity', 'cap', 'cap.`id_kl_resource_profile` = p.`id_kl_resource_profile`');
        $query->leftJoin(
            'htl_room_type',
            'hrt',
            'hrt.`id` = p.`id_room_type`'
        );
        $query->leftJoin(
            'product_lang',
            'pl',
            'pl.`id_product` = hrt.`id_product`'
            .' AND pl.`id_lang` = '.(int) $idLang
            .' AND pl.`id_shop` = '.(int) $idShop
        );
        $query->where('p.`is_published` = 1');
        $query->orderBy('p.`resource_kind` ASC, p.`display_order` ASC, p.`resource_code` ASC');

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (!$rows) {
            return array();
        }

        $profiles = array();
        foreach ($rows as $row) {
            $idProfile = (int) $row['id_kl_resource_profile'];
            $story = KLResourceStory::getByProfileAndLang($idProfile, $idLang);

            $profiles[] = array(
                'id_kl_resource_profile' => $idProfile,
                'resource_code' => $row['resource_code'],
                'external_reference' => $row['external_reference'],
                'resource_kind' => $row['resource_kind'],
                'display_order' => (int) $row['display_order'],
                'is_bookable' => (bool) $row['is_bookable'],
                'is_published' => (bool) $row['is_published'],
                'timezone' => $row['timezone'],
                'room_type_name' => $row['room_type_name'],
                'capacity' => array(
                    'adults' => $row['capacity_adults'] !== null ? (int) $row['capacity_adults'] : null,
                    'children' => $row['capacity_children'] !== null ? (int) $row['capacity_children'] : null,
                    'total' => $row['capacity_total'] !== null ? (int) $row['capacity_total'] : null,
                    'seated' => $row['capacity_seated'] !== null ? (int) $row['capacity_seated'] : null,
                    'standing' => $row['capacity_standing'] !== null ? (int) $row['capacity_standing'] : null,
                    'floor_area_sqm' => $row['floor_area_sqm'] !== null ? (float) $row['floor_area_sqm'] : null,
                    'ceiling_height_m' => $row['ceiling_height_m'] !== null ? (float) $row['ceiling_height_m'] : null,
                ),
                'capacity_notes' => $row['capacity_notes'],
                'story' => $story ?: null,
            );
        }

        return $profiles;
    }
}

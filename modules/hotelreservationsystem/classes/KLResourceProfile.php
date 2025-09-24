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
}

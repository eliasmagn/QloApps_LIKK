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

class KLAmenity extends ObjectModel
{
    /** @var string */
    public $amenity_code;

    /** @var string */
    public $category_code;

    /** @var string */
    public $icon;

    /** @var string */
    public $translation_domain;

    /** @var bool */
    public $is_active;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_resource_amenity',
        'primary' => 'id_kl_resource_amenity',
        'fields' => array(
            'amenity_code' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 64),
            'category_code' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 64),
            'icon' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255),
            'translation_domain' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 128),
            'is_active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * Returns a map of amenity_code => id for quick lookups.
     *
     * @return array
     */
    public static function getCodeIndex()
    {
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `id_kl_resource_amenity`, `amenity_code` FROM `'._DB_PREFIX_.'kl_resource_amenity`'
        );

        $index = array();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $index[$row['amenity_code']] = (int) $row['id_kl_resource_amenity'];
            }
        }

        return $index;
    }
}

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

class KLResourceCapacity extends ObjectModel
{
    /** @var int */
    public $id_kl_resource_profile;

    /** @var int */
    public $capacity_adults;

    /** @var int */
    public $capacity_children;

    /** @var int */
    public $capacity_total;

    /** @var int */
    public $capacity_seated;

    /** @var int */
    public $capacity_standing;

    /** @var float */
    public $floor_area_sqm;

    /** @var float */
    public $ceiling_height_m;

    /** @var string */
    public $notes;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_resource_capacity',
        'primary' => 'id_kl_resource_capacity',
        'fields' => array(
            'id_kl_resource_profile' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'capacity_adults' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'capacity_children' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'capacity_total' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'capacity_seated' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'capacity_standing' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'floor_area_sqm' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'ceiling_height_m' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'notes' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    /**
     * @param int $idProfile
     *
     * @return KLResourceCapacity|null
     */
    public static function loadByProfileId($idProfile)
    {
        $idProfile = (int) $idProfile;
        if (!$idProfile) {
            return null;
        }

        $idCapacity = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('`id_kl_resource_capacity`')
                ->from('kl_resource_capacity')
                ->where('`id_kl_resource_profile` = '.(int) $idProfile)
        );

        if (!$idCapacity) {
            return null;
        }

        return new self($idCapacity);
    }
}

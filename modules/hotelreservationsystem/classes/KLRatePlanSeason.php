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

class KLRatePlanSeason extends ObjectModel
{
    /** @var int */
    public $id_kl_rate_plan;

    /** @var string */
    public $internal_label;

    /** @var string */
    public $date_from;

    /** @var string */
    public $date_to;

    /** @var string */
    public $adjustment_method;

    /** @var int */
    public $adjustment_amount_minor;

    /** @var int */
    public $adjustment_percent_basis_points;

    /** @var int */
    public $min_stay_nights;

    /** @var int */
    public $max_stay_nights;

    /** @var int */
    public $min_occupancy;

    /** @var int */
    public $max_occupancy;

    /** @var int */
    public $min_lead_days;

    /** @var int */
    public $max_lead_days;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_rate_plan_season',
        'primary' => 'id_kl_rate_plan_season',
        'fields' => array(
            'id_kl_rate_plan' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'internal_label' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 128),
            'date_from' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_to' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'adjustment_method' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 16),
            'adjustment_amount_minor' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'adjustment_percent_basis_points' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'min_stay_nights' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'max_stay_nights' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'min_occupancy' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'max_occupancy' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'min_lead_days' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'max_lead_days' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
        ),
    );

    public function add($autodate = true, $null_values = false)
    {
        $this->date_add = date('Y-m-d H:i:s');
        $this->date_upd = $this->date_add;

        return parent::add($autodate, $null_values);
    }

    public function update($null_values = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');
        return parent::update($null_values);
    }

    /**
     * @param int $idRatePlan
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getSeasonsForPlan($idRatePlan)
    {
        $idRatePlan = (int) $idRatePlan;
        if ($idRatePlan <= 0) {
            return array();
        }

        $query = new DbQuery();
        $query->select('*');
        $query->from('kl_rate_plan_season');
        $query->where('`id_kl_rate_plan` = '.(int) $idRatePlan);
        $query->orderBy('`date_from` ASC, `date_to` ASC, `id_kl_rate_plan_season` ASC');

        $rows = Db::getInstance()->executeS($query);
        if (!$rows) {
            return array();
        }

        foreach ($rows as &$row) {
            $row['id_kl_rate_plan_season'] = (int) $row['id_kl_rate_plan_season'];
            $row['id_kl_rate_plan'] = (int) $row['id_kl_rate_plan'];
        }

        return $rows;
    }
}

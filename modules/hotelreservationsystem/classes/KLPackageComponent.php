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

class KLPackageComponent extends ObjectModel
{
    /** @var int */
    public $id_kl_package;

    /** @var string */
    public $component_type;

    /** @var string */
    public $reference_code;

    /** @var float */
    public $quantity;

    /** @var string */
    public $unit;

    /** @var bool */
    public $is_optional;

    /** @var int */
    public $price_minor;

    /** @var int */
    public $id_kl_rate_plan;

    /** @var int */
    public $sort_order;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_package_component',
        'primary' => 'id_kl_package_component',
        'fields' => array(
            'id_kl_package' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'component_type' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 32),
            'reference_code' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64),
            'quantity' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'unit' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 16),
            'is_optional' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'price_minor' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_kl_rate_plan' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'sort_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
        ),
    );

    public function add($autodate = true, $null_values = false)
    {
        $this->date_add = date('Y-m-d H:i:s');
        $this->date_upd = $this->date_add;

        if ($this->is_optional === null) {
            $this->is_optional = 0;
        }

        return parent::add($autodate, $null_values);
    }

    public function update($null_values = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');
        return parent::update($null_values);
    }

    /**
     * @param int $idPackage
     *
     * @return array<int, KLPackageComponent>
     */
    public static function getComponentsForPackage($idPackage)
    {
        $idPackage = (int) $idPackage;
        if ($idPackage <= 0) {
            return array();
        }

        $query = new DbQuery();
        $query->select('`id_kl_package_component`');
        $query->from('kl_package_component');
        $query->where('`id_kl_package` = '.(int) $idPackage);
        $query->orderBy('`sort_order` ASC, `id_kl_package_component` ASC');

        $ids = Db::getInstance()->executeS($query);
        if (!$ids) {
            return array();
        }

        $components = array();
        foreach ($ids as $row) {
            $component = new self((int) $row['id_kl_package_component']);
            if (Validate::isLoadedObject($component)) {
                $components[] = $component;
            }
        }

        return $components;
    }
}

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

class KLQuote extends ObjectModel
{
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';

    /** @var int */
    public $id_inquiry;

    /** @var int */
    public $id_employee_author;

    /** @var int */
    public $id_kl_rate_plan;

    /** @var int */
    public $id_kl_package;

    /** @var string */
    public $status;

    /** @var string */
    public $currency_iso_code;

    /** @var int */
    public $net_total_minor;

    /** @var int */
    public $tax_total_minor;

    /** @var int */
    public $gross_total_minor;

    /** @var string */
    public $payload;

    /** @var string */
    public $notes_internal;

    /** @var string */
    public $valid_from;

    /** @var string */
    public $valid_until;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_quote',
        'primary' => 'id_kl_quote',
        'fields' => array(
            'id_inquiry' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_employee_author' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_kl_rate_plan' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_kl_package' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32),
            'currency_iso_code' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 3),
            'net_total_minor' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'tax_total_minor' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'gross_total_minor' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'payload' => array('type' => self::TYPE_HTML, 'validate' => 'isString'),
            'notes_internal' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'valid_from' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'valid_until' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
        ),
    );

    public function add($autodate = true, $null_values = false)
    {
        if (!$this->status) {
            $this->status = self::STATUS_DRAFT;
        }

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
     * @return array<string, string>
     */
    public static function getStatusLabels()
    {
        $module = Module::getInstanceByName('hotelreservationsystem');

        return array(
            self::STATUS_DRAFT => $module->l('Draft', 'KLQuote'),
            self::STATUS_SENT => $module->l('Sent to guest', 'KLQuote'),
            self::STATUS_APPROVED => $module->l('Approved', 'KLQuote'),
            self::STATUS_DECLINED => $module->l('Declined', 'KLQuote'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload()
    {
        if (!$this->payload) {
            return array();
        }

        $decoded = json_decode($this->payload, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return void
     */
    public function setPayload(array $payload)
    {
        if (empty($payload)) {
            $this->payload = null;
            return;
        }

        $this->payload = json_encode($payload);
    }
}

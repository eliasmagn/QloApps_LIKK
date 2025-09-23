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

class HotelInquiry extends ObjectModel
{
    const STATUS_NEW = 'new';
    const STATUS_REVIEW = 'review';
    const STATUS_PENDING = 'awaiting_guest';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_DECLINED = 'declined';

    const STAGE_INBOX = 'inbox';
    const STAGE_QUALIFYING = 'qualifying';
    const STAGE_AWAITING = 'awaiting';
    const STAGE_SCHEDULED = 'scheduled';
    const STAGE_ARCHIVED = 'archived';

    /** @var string */
    public $reference;
    /** @var string */
    public $subject;
    /** @var string */
    public $status;
    /** @var string */
    public $stage;
    /** @var int */
    public $assigned_to;
    /** @var string */
    public $requester_name;
    /** @var string */
    public $requester_email;
    /** @var string */
    public $requester_phone;
    /** @var string */
    public $check_in;
    /** @var string */
    public $check_out;
    /** @var string */
    public $resource_request;
    /** @var string */
    public $internal_notes;
    /** @var string */
    public $reminder_at;
    /** @var string */
    public $last_note_at;
    /** @var string */
    public $date_add;
    /** @var string */
    public $date_upd;

    public static $definition = array(
        'table' => 'htl_inquiry',
        'primary' => 'id_inquiry',
        'fields' => array(
            'reference' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32),
            'subject' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 255),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32),
            'stage' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32),
            'assigned_to' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'requester_name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255),
            'requester_email' => array('type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 255),
            'requester_phone' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64),
            'check_in' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'check_out' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'resource_request' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'internal_notes' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'reminder_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'last_note_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public function add($autodate = true, $null_values = false)
    {
        if (empty($this->reference)) {
            $this->reference = self::generateReference();
        }
        if (empty($this->status)) {
            $this->status = self::STATUS_NEW;
        }
        if (empty($this->stage)) {
            $this->stage = self::STAGE_INBOX;
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

    public static function getStageDefinitions()
    {
        $module = Module::getInstanceByName('hotelreservationsystem');
        return array(
            self::STAGE_INBOX => array(
                'label' => $module->l('Inbox', 'HotelInquiry'),
                'default_status' => self::STATUS_NEW,
            ),
            self::STAGE_QUALIFYING => array(
                'label' => $module->l('Qualifying', 'HotelInquiry'),
                'default_status' => self::STATUS_REVIEW,
            ),
            self::STAGE_AWAITING => array(
                'label' => $module->l('Awaiting reply', 'HotelInquiry'),
                'default_status' => self::STATUS_PENDING,
            ),
            self::STAGE_SCHEDULED => array(
                'label' => $module->l('Scheduled', 'HotelInquiry'),
                'default_status' => self::STATUS_CONFIRMED,
            ),
            self::STAGE_ARCHIVED => array(
                'label' => $module->l('Archived', 'HotelInquiry'),
                'default_status' => self::STATUS_DECLINED,
            ),
        );
    }

    public static function getStatusDefinitions()
    {
        $module = Module::getInstanceByName('hotelreservationsystem');
        return array(
            self::STATUS_NEW => $module->l('New', 'HotelInquiry'),
            self::STATUS_REVIEW => $module->l('Under review', 'HotelInquiry'),
            self::STATUS_PENDING => $module->l('Awaiting guest', 'HotelInquiry'),
            self::STATUS_CONFIRMED => $module->l('Confirmed', 'HotelInquiry'),
            self::STATUS_DECLINED => $module->l('Declined', 'HotelInquiry'),
        );
    }

    public static function generateReference()
    {
        $prefix = 'INQ';
        $sequence = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        return $prefix.'-'.date('ymd').'-'.$sequence;
    }

    public static function getBoardDataset(?array $stageKeys = null)
    {
        if ($stageKeys === null) {
            $stageKeys = array_keys(self::getStageDefinitions());
        }

        $dataset = array();
        foreach ($stageKeys as $stage) {
            $dataset[$stage] = array();
        }

        $query = new DbQuery();
        $query->select('*');
        $query->from('htl_inquiry');
        if (!empty($stageKeys)) {
            $escapedStages = array();
            foreach ($stageKeys as $stageKey) {
                $escapedStages[] = '\''.pSQL($stageKey).'\'';
            }
            $query->where('`stage` IN ('.implode(', ', $escapedStages).')');
        }
        $query->orderBy('`date_add` DESC');

        $rows = Db::getInstance()->executeS($query);
        if (!$rows) {
            return $dataset;
        }

        foreach ($rows as $row) {
            $row['id_inquiry'] = (int) $row['id_inquiry'];
            $row['assigned_to'] = $row['assigned_to'] ? (int) $row['assigned_to'] : null;
            if (!isset($dataset[$row['stage']])) {
                $dataset[$row['stage']] = array();
            }
            $dataset[$row['stage']][] = $row;
        }

        return $dataset;
    }

    public static function updateStage($idInquiry, $stage, $status = null, $assignedTo = null)
    {
        if (!in_array($stage, array_keys(self::getStageDefinitions()), true)) {
            return false;
        }

        $data = array(
            'stage' => pSQL($stage),
            'date_upd' => date('Y-m-d H:i:s'),
        );

        if ($status !== null) {
            $data['status'] = pSQL($status);
        } else {
            $definitions = self::getStageDefinitions();
            if (isset($definitions[$stage]['default_status'])) {
                $data['status'] = pSQL($definitions[$stage]['default_status']);
            }
        }

        if ($assignedTo !== null) {
            $data['assigned_to'] = (int) $assignedTo ?: null;
        }

        return Db::getInstance()->update('htl_inquiry', $data, 'id_inquiry='.(int) $idInquiry);
    }

    public static function scheduleReminder($idInquiry, $dateTime)
    {
        $value = $dateTime ? date('Y-m-d H:i:s', strtotime($dateTime)) : null;
        return Db::getInstance()->update(
            'htl_inquiry',
            array(
                'reminder_at' => $value ? pSQL($value) : null,
                'date_upd' => date('Y-m-d H:i:s'),
            ),
            'id_inquiry='.(int) $idInquiry
        );
    }

    public static function touchNoteTimestamp($idInquiry)
    {
        return Db::getInstance()->update(
            'htl_inquiry',
            array(
                'last_note_at' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ),
            'id_inquiry='.(int) $idInquiry
        );
    }

    public static function findById($idInquiry)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('htl_inquiry');
        $query->where('id_inquiry='.(int) $idInquiry);

        return Db::getInstance()->getRow($query);
    }
}

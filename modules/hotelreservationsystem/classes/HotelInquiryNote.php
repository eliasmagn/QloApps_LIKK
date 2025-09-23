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

class HotelInquiryNote extends ObjectModel
{
    /** @var int */
    public $id_inquiry;
    /** @var int */
    public $id_employee;
    /** @var string */
    public $note;
    /** @var bool */
    public $is_mail;
    /** @var string */
    public $date_add;

    public static $definition = array(
        'table' => 'htl_inquiry_note',
        'primary' => 'id_inquiry_note',
        'fields' => array(
            'id_inquiry' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'note' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true),
            'is_mail' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public function add($autodate = true, $null_values = false)
    {
        $this->date_add = date('Y-m-d H:i:s');
        $this->is_mail = (int) $this->is_mail;
        return parent::add($autodate, $null_values);
    }

    public static function getInquiryNotes($idInquiry)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('htl_inquiry_note');
        $query->where('id_inquiry='.(int) $idInquiry);
        $query->orderBy('date_add DESC');

        $notes = Db::getInstance()->executeS($query);
        if (!$notes) {
            return array();
        }

        foreach ($notes as &$note) {
            $note['id_inquiry_note'] = (int) $note['id_inquiry_note'];
            $note['id_inquiry'] = (int) $note['id_inquiry'];
            $note['id_employee'] = (int) $note['id_employee'];
            $note['is_mail'] = (int) $note['is_mail'];
        }

        return $notes;
    }

    public static function addNote($idInquiry, $content, $idEmployee = null, $isMail = false)
    {
        $note = new self();
        $note->id_inquiry = (int) $idInquiry;
        $note->note = $content;
        $note->id_employee = $idEmployee ? (int) $idEmployee : null;
        $note->is_mail = (bool) $isMail;

        if ($note->add()) {
            HotelInquiry::touchNoteTimestamp($idInquiry);
            return $note;
        }

        return false;
    }
}

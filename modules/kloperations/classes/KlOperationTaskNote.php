<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/license/osl-3-0-php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@qloapps.com so we can send you a copy immediately.
 */

class KlOperationTaskNote extends ObjectModel
{
    public $id_kl_operation_task_note;
    public $id_kl_operation_task;
    public $id_employee;
    public $note_type;
    public $content;
    public $attachments;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_operation_task_note',
        'primary' => 'id_kl_operation_task_note',
        'fields' => array(
            'id_kl_operation_task' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'note_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
            'content' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true),
            'attachments' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );
}

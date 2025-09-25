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

class KlOperationTask extends ObjectModel
{
    public $id_kl_operation_task;
    public $id_kl_operation_run;
    public $reference;
    public $task_type;
    public $status;
    public $resource_type;
    public $id_resource;
    public $context_type;
    public $context_id;
    public $scheduled_for;
    public $due_end;
    public $timezone;
    public $payload;
    public $unique_key;
    public $priority;
    public $created_by;
    public $completed_by;
    public $completed_at;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_operation_task',
        'primary' => 'id_kl_operation_task',
        'fields' => array(
            'id_kl_operation_run' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'reference' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'task_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
            'resource_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32),
            'id_resource' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'context_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32),
            'context_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'scheduled_for' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true),
            'due_end' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'timezone' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64),
            'payload' => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'),
            'unique_key' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128),
            'priority' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'created_by' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'completed_by' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'completed_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public static function getIdByUniqueKey($uniqueKey)
    {
        $sql = new DbQuery();
        $sql->select('`id_kl_operation_task`');
        $sql->from(self::$definition['table']);
        $sql->where('`unique_key` = "' . pSQL($uniqueKey) . '"');

        $id = Db::getInstance()->getValue($sql);

        return $id ? (int) $id : 0;
    }
}

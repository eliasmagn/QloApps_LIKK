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

class KlOperationTaskAssignment extends ObjectModel
{
    public $id_kl_operation_task_assignment;
    public $id_kl_operation_task;
    public $id_employee;
    public $assignee_type;
    public $assignee_reference;
    public $assignee_label;
    public $status;
    public $acknowledged_at;
    public $completed_at;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'kl_operation_task_assignment',
        'primary' => 'id_kl_operation_task_assignment',
        'fields' => array(
            'id_kl_operation_task' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'assignee_type' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
            'assignee_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64),
            'assignee_label' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 128),
            'status' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
            'acknowledged_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'completed_at' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
        ),
    );

    public static function getAssignmentsForTasks(array $taskIds)
    {
        $taskIds = array_filter(array_map('intval', $taskIds));
        if (empty($taskIds)) {
            return array();
        }

        $query = new DbQuery();
        $query->select('a.*, e.`firstname`, e.`lastname`, e.`email`');
        $query->from(self::$definition['table'], 'a');
        $query->leftJoin('employee', 'e', 'e.`id_employee` = a.`id_employee`');
        $query->where('a.`id_kl_operation_task` IN (' . implode(',', $taskIds) . ')');
        $query->orderBy('a.`date_add` ASC');

        return Db::getInstance()->executeS($query) ?: array();
    }
}

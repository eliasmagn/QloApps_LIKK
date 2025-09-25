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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__DIR__, 2) . '/classes/KlOperationTask.php';
require_once dirname(__DIR__, 2) . '/classes/KlOperationRun.php';
require_once dirname(__DIR__, 2) . '/classes/KlOperationTaskNote.php';
require_once dirname(__DIR__, 2) . '/classes/KlOperationTaskAssignment.php';

class AdminKlOperationTasksController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = KlOperationTask::$definition['table'];
        $this->className = 'KlOperationTask';
        $this->identifier = 'id_kl_operation_task';
        $this->bootstrap = true;
        $this->lang = false;
        $this->allow_export = true;
        $this->_orderBy = 'scheduled_for';
        $this->_orderWay = 'ASC';
        $this->tpl_view = 'task_view.tpl';

        parent::__construct();

        $this->fields_list = array(
            'id_kl_operation_task' => array(
                'title' => $this->l('ID'),
                'type' => 'int',
                'align' => 'text-center',
                'class' => 'fixed-width-sm',
            ),
            'reference' => array(
                'title' => $this->l('Reference'),
            ),
            'task_type' => array(
                'title' => $this->l('Task type'),
            ),
            'status' => array(
                'title' => $this->l('Status'),
            ),
            'resource_type' => array(
                'title' => $this->l('Resource'),
            ),
            'id_resource' => array(
                'title' => $this->l('Resource ID'),
                'align' => 'text-center',
            ),
            'scheduled_for' => array(
                'title' => $this->l('Scheduled for'),
                'type' => 'datetime',
            ),
            'due_end' => array(
                'title' => $this->l('Due end'),
                'type' => 'datetime',
            ),
            'priority' => array(
                'title' => $this->l('Priority'),
                'align' => 'text-center',
            ),
        );

        $this->bulk_actions = array(
            'completeTasks' => array(
                'text' => $this->l('Mark as completed'),
                'icon' => 'icon-check',
            ),
        );
    }

    public function initProcess()
    {
        if (Tools::isSubmit('submitBulkcompleteTasks' . $this->table)) {
            $this->action = 'completeTasks';
        }

        parent::initProcess();
    }

    public function processCompleteTasks()
    {
        $selected = $this->boxes;
        if (!$selected || !is_array($selected)) {
            $this->errors[] = $this->l('Please select at least one task.');

            return false;
        }

        foreach ($selected as $taskId) {
            $task = new KlOperationTask((int) $taskId);
            if (!Validate::isLoadedObject($task)) {
                continue;
            }

            $task->status = 'completed';
            $task->completed_at = date('Y-m-d H:i:s');
            $task->completed_by = (int) $this->context->employee->id;
            $task->date_upd = date('Y-m-d H:i:s');
            $task->update();
        }

        Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);
    }

    public function renderView()
    {
        $task = $this->loadObject(true);
        if (!Validate::isLoadedObject($task)) {
            return parent::renderView();
        }

        $payload = array();
        if (!empty($task->payload)) {
            $payload = json_decode($task->payload, true);
            if (!is_array($payload)) {
                $payload = array();
            }
        }

        $this->tpl_view_vars = array(
            'task' => $task,
            'payload' => $payload,
            'notes' => $this->getTaskNotes($task->id),
        );

        return parent::renderView();
    }

    private function getTaskNotes($taskId)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(KlOperationTaskNote::$definition['table']);
        $query->where('`id_kl_operation_task` = ' . (int) $taskId);
        $query->orderBy('`date_add` ASC');

        return Db::getInstance()->executeS($query) ?: array();
    }
}

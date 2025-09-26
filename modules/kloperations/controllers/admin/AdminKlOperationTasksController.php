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
        $this->addRowAction('view');

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
            'last_reminded_at' => array(
                'title' => $this->l('Last reminder'),
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

        if (Tools::getIsset('export_tasks_csv')) {
            $this->action = 'exportCsv';
        }

        if (Tools::getIsset('export_tasks_ics')) {
            $this->action = 'exportIcs';
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
            'payload_pretty' => empty($payload) ? '' : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'notes' => $this->getTaskNotes($task->id),
        );

        return parent::renderView();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $link = self::$currentIndex . '&token=' . $this->token;
        if (!Tools::isSubmit('add' . $this->table)) {
            $this->page_header_toolbar_btn['new_task'] = array(
                'href' => $link . '&add' . $this->table . '&manual=1',
                'desc' => $this->l('Add manual task'),
                'icon' => 'process-icon-new',
            );
        }
        $this->page_header_toolbar_btn['export_tasks_csv'] = array(
            'href' => $link . '&export_tasks_csv=1',
            'desc' => $this->l('Export CSV'),
            'icon' => 'process-icon-export',
        );
        $this->page_header_toolbar_btn['export_tasks_ics'] = array(
            'href' => $link . '&export_tasks_ics=1',
            'desc' => $this->l('Export ICS'),
            'icon' => 'process-icon-calendar',
        );
    }

    public function renderForm()
    {
        $this->show_form_cancel_button = true;
        $timezone = $this->resolveTimezone();
        $defaultDate = new DateTime('now', $timezone);
        $defaultDate->modify('+1 hour');
        $defaultDate->setTime((int) $defaultDate->format('H'), 0);

        if (!Tools::getIsset('priority')) {
            $this->fields_value['priority'] = 3;
        }
        if (!Tools::getIsset('status')) {
            $this->fields_value['status'] = 'pending';
        }
        if (!Tools::getIsset('scheduled_for')) {
            $this->fields_value['scheduled_for'] = $defaultDate->format('Y-m-d H:00:00');
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Manual task'),
                'icon' => 'icon-tasks',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Reference'),
                    'name' => 'reference',
                    'required' => true,
                    'hint' => $this->l('Short label staff will recognise in exports and digests.'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Task type'),
                    'name' => 'task_type',
                    'required' => true,
                    'options' => array(
                        'query' => $this->getTaskTypeOptions(),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'hint' => $this->l('Choose the checklist template or categorisation that best fits the work.'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Status'),
                    'name' => 'status',
                    'required' => true,
                    'options' => array(
                        'query' => $this->getStatusOptions(),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Priority'),
                    'name' => 'priority',
                    'required' => true,
                    'options' => array(
                        'query' => $this->getPriorityOptions(),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Resource type'),
                    'name' => 'resource_type',
                    'hint' => $this->l('e.g. room, atelier, facility or leave blank for general checklists.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Resource ID'),
                    'name' => 'id_resource',
                    'hint' => $this->l('Link to a specific resource when follow-up should appear in exports and filters.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Context type'),
                    'name' => 'context_type',
                    'hint' => $this->l('Optional reference such as booking, inquiry or maintenance ticket.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Context ID'),
                    'name' => 'context_id',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Scheduled for'),
                    'name' => 'scheduled_for',
                    'required' => true,
                    'hint' => $this->l('Use the format YYYY-MM-DD HH:MM (24h).'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Due end'),
                    'name' => 'due_end',
                    'hint' => $this->l('Optional deadline in the format YYYY-MM-DD HH:MM (24h).'),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Payload (JSON)'),
                    'name' => 'payload',
                    'rows' => 6,
                    'hint' => $this->l('Store structured checklists or metadata. Leave blank to skip.'),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Initial note'),
                    'name' => 'note_content',
                    'rows' => 4,
                    'hint' => $this->l('Optional note logged against the task for context when it goes live.'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        return parent::renderForm();
    }

    public function processAdd()
    {
        if (!$this->isFormValid()) {
            return false;
        }

        $task = new KlOperationTask();
        $task->reference = Tools::getValue('reference');
        $task->task_type = Tools::getValue('task_type');
        $task->status = Tools::getValue('status');
        $task->priority = (int) Tools::getValue('priority');
        $task->resource_type = $this->nullIfEmpty(Tools::getValue('resource_type'));
        $idResource = Tools::getValue('id_resource');
        $task->id_resource = $idResource === '' ? null : (int) $idResource;
        $task->context_type = $this->nullIfEmpty(Tools::getValue('context_type'));
        $contextId = Tools::getValue('context_id');
        $task->context_id = $contextId === '' ? null : (int) $contextId;
        $task->scheduled_for = $this->normaliseDateTime(Tools::getValue('scheduled_for'));
        $dueEnd = Tools::getValue('due_end');
        $task->due_end = $dueEnd ? $this->normaliseDateTime($dueEnd) : null;
        $task->timezone = $this->resolveTimezone()->getName();
        $payload = trim((string) Tools::getValue('payload'));
        $task->payload = $payload === '' ? null : $this->encodePayload($payload);
        $task->unique_key = $this->generateManualUniqueKey($task);
        $task->created_by = (int) $this->context->employee->id;
        $task->date_add = date('Y-m-d H:i:s');
        $task->date_upd = $task->date_add;

        if (!$task->add()) {
            $this->errors[] = $this->l('Failed to save the task. Please try again.');

            return false;
        }

        $noteContent = Tools::getValue('note_content');
        if (Tools::strlen(trim($noteContent)) > 0) {
            $note = new KlOperationTaskNote();
            $note->id_kl_operation_task = (int) $task->id;
            $note->id_employee = (int) $this->context->employee->id;
            $note->note_type = 'comment';
            $note->content = nl2br(trim($noteContent));
            $note->date_add = date('Y-m-d H:i:s');
            $note->date_upd = $note->date_add;
            $note->add();
        }

        Tools::redirectAdmin(self::$currentIndex . '&conf=3&token=' . $this->token);

        return true;
    }

    public function processExportCsv()
    {
        $rangeDays = max(1, (int) Tools::getValue('range_days', 7));
        $timezone = $this->resolveTimezone();
        $from = new DateTimeImmutable('today', $timezone);
        $to = $from->add(new DateInterval('P' . $rangeDays . 'D'));

        $tasks = $this->module->getExportService()->fetchTasks($from, $to, array('pending', 'in_progress'));
        $csv = $this->module->getExportService()->generateCsv($tasks);

        $filename = sprintf('kloperations-tasks-%s-%s.csv', $from->format('Ymd'), $to->format('Ymd'));
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . Tools::strlen($csv));
        echo $csv;
        exit;
    }

    public function processExportIcs()
    {
        $rangeDays = max(1, (int) Tools::getValue('range_days', 7));
        $timezone = $this->resolveTimezone();
        $from = new DateTimeImmutable('today', $timezone);
        $to = $from->add(new DateInterval('P' . $rangeDays . 'D'));

        $tasks = $this->module->getExportService()->fetchTasks($from, $to, array('pending', 'in_progress'));
        $ics = $this->module->getExportService()->generateIcs($tasks, $timezone);

        $filename = sprintf('kloperations-tasks-%s-%s.ics', $from->format('Ymd'), $to->format('Ymd'));
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . Tools::strlen($ics));
        echo $ics;
        exit;
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

    private function resolveTimezone()
    {
        $timezoneName = (string) Configuration::get('PS_TIMEZONE');
        if (!$timezoneName) {
            $timezoneName = @date_default_timezone_get();
        }

        try {
            return new DateTimeZone($timezoneName);
        } catch (Exception $exception) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }

    private function getTaskTypeOptions()
    {
        return array(
            array('id' => 'housekeeping_arrival', 'name' => $this->l('Housekeeping – arrival')),
            array('id' => 'housekeeping_checkout', 'name' => $this->l('Housekeeping – checkout')),
            array('id' => 'maintenance_start', 'name' => $this->l('Maintenance – start block')),
            array('id' => 'maintenance_release', 'name' => $this->l('Maintenance – release space')),
            array('id' => 'custom', 'name' => $this->l('Custom / other')), 
        );
    }

    private function getStatusOptions()
    {
        return array(
            array('id' => 'pending', 'name' => $this->l('Pending')),
            array('id' => 'in_progress', 'name' => $this->l('In progress')),
            array('id' => 'completed', 'name' => $this->l('Completed')),
            array('id' => 'cancelled', 'name' => $this->l('Cancelled')),
        );
    }

    private function getPriorityOptions()
    {
        return array(
            array('id' => 1, 'name' => $this->l('1 – Critical')),
            array('id' => 2, 'name' => $this->l('2 – High')),
            array('id' => 3, 'name' => $this->l('3 – Standard')),
            array('id' => 4, 'name' => $this->l('4 – Low')),
            array('id' => 5, 'name' => $this->l('5 – Informational')),
        );
    }

    private function isFormValid()
    {
        $reference = trim((string) Tools::getValue('reference'));
        if ($reference === '') {
            $this->errors[] = $this->l('Reference is required.');
        }

        $taskType = (string) Tools::getValue('task_type');
        $validTypes = array_column($this->getTaskTypeOptions(), 'id');
        if (!in_array($taskType, $validTypes)) {
            $this->errors[] = $this->l('Select a valid task type.');
        }

        $status = (string) Tools::getValue('status');
        $validStatuses = array_column($this->getStatusOptions(), 'id');
        if (!in_array($status, $validStatuses)) {
            $this->errors[] = $this->l('Select a valid status.');
        }

        $priority = (int) Tools::getValue('priority');
        $validPriorities = array_column($this->getPriorityOptions(), 'id');
        if (!in_array($priority, $validPriorities)) {
            $this->errors[] = $this->l('Select a valid priority.');
        }

        $scheduledFor = Tools::getValue('scheduled_for');
        if (!$this->isValidDateTime($scheduledFor)) {
            $this->errors[] = $this->l('Scheduled for must follow YYYY-MM-DD HH:MM.');
        }

        $dueEnd = Tools::getValue('due_end');
        if ($dueEnd && !$this->isValidDateTime($dueEnd)) {
            $this->errors[] = $this->l('Due end must follow YYYY-MM-DD HH:MM.');
        }

        $idResource = Tools::getValue('id_resource');
        if ($idResource !== '' && !Validate::isUnsignedInt($idResource)) {
            $this->errors[] = $this->l('Resource ID must be a positive integer.');
        }

        $contextId = Tools::getValue('context_id');
        if ($contextId !== '' && !Validate::isUnsignedInt($contextId)) {
            $this->errors[] = $this->l('Context ID must be a positive integer.');
        }

        $payload = trim((string) Tools::getValue('payload'));
        if ($payload !== '' && !$this->isValidJson($payload)) {
            $this->errors[] = $this->l('Payload must be valid JSON.');
        }

        return empty($this->errors);
    }

    private function isValidDateTime($value)
    {
        if (!$value) {
            return false;
        }

        $timestamp = strtotime($value);

        return $timestamp !== false;
    }

    private function normaliseDateTime($value)
    {
        return date('Y-m-d H:i:00', strtotime($value));
    }

    private function isValidJson($payload)
    {
        json_decode($payload, true);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function encodePayload($payload)
    {
        $decoded = json_decode($payload, true);

        return json_encode($decoded);
    }

    private function generateManualUniqueKey(KlOperationTask $task)
    {
        $resource = $task->resource_type ? $task->resource_type . ':' . (int) $task->id_resource : 'general';

        return sprintf(
            'manual:%s',
            sha1(implode('|', array(
                microtime(true),
                $task->reference,
                $task->task_type,
                $task->scheduled_for,
                $resource,
            )))
        );
    }

    private function nullIfEmpty($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return $value;
    }
}

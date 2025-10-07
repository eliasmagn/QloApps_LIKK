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
require_once dirname(__DIR__, 2) . '/services/KlOperationExportService.php';
require_once _PS_MODULE_DIR_ . 'hotelreservationsystem/classes/HotelInquiry.php';

class AdminKlOperationTasksController extends ModuleAdminController
{
    /** @var bool */
    private $mobileView = false;

    /** @var array|null */
    private $exportFilters = null;

    /** @var array|null */
    private $resourceFilterOptions = null;

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
            'inquiry_context' => array(
                'title' => $this->l('Inquiry'),
                'orderby' => false,
                'search' => false,
                'callback' => 'renderInquiryContextColumn',
            ),
            'last_reminded_at' => array(
                'title' => $this->l('Last reminder'),
                'type' => 'datetime',
            ),
            'priority' => array(
                'title' => $this->l('Priority'),
                'align' => 'text-center',
            ),
            'assignment_summary' => array(
                'title' => $this->l('Assignments'),
                'orderby' => false,
                'search' => false,
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
        if (Tools::getIsset('mobile_view')) {
            $this->mobileView = true;
            $this->display = 'view';
            $this->lite_display = true;
        }

        if (Tools::isSubmit('submitBulkcompleteTasks' . $this->table)) {
            $this->action = 'completeTasks';
        }

        if (Tools::isSubmit('submitAssignEmployee')) {
            $this->action = 'assignEmployee';
        }

        if (Tools::isSubmit('submitAssignTeam')) {
            $this->action = 'assignTeam';
        }

        if (Tools::isSubmit('submitUpdateAssignmentStatus')) {
            $this->action = 'updateAssignmentStatus';
        }

        if (Tools::isSubmit('submitDeleteAssignment')) {
            $this->action = 'deleteAssignment';
        }

        if (Tools::isSubmit('submitClaimTask')) {
            $this->action = 'claimTask';
        }

        if (Tools::getIsset('export_tasks_csv')) {
            $this->action = 'exportCsv';
        }

        if (Tools::getIsset('export_tasks_ics')) {
            $this->action = 'exportIcs';
        }

        parent::initProcess();
    }

    public function initContent()
    {
        parent::initContent();

        if ($this->ajax) {
            return;
        }

        if ($this->display && $this->display !== 'list') {
            return;
        }

        $this->content = $this->renderExportFiltersPanel() . $this->content;
    }

    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        if (empty($this->_list)) {
            return;
        }

        $taskIds = array();
        $inquiryIds = array();
        foreach ($this->_list as $row) {
            if (isset($row['id_kl_operation_task'])) {
                $taskIds[] = (int) $row['id_kl_operation_task'];
            }
            if (isset($row['context_type'], $row['context_id']) && $row['context_type'] === 'inquiry' && (int) $row['context_id']) {
                $inquiryIds[] = (int) $row['context_id'];
            }
        }

        $assignments = $this->fetchAssignmentsIndexed($taskIds);
        $inquiries = $this->fetchInquiriesIndexed($inquiryIds);

        foreach ($this->_list as &$row) {
            $taskId = isset($row['id_kl_operation_task']) ? (int) $row['id_kl_operation_task'] : 0;
            $row['assignment_summary'] = $this->formatAssignmentSummaryForList($assignments, $taskId);

            if (isset($row['context_type'], $row['context_id']) && $row['context_type'] === 'inquiry' && (int) $row['context_id']) {
                $contextId = (int) $row['context_id'];
                if (isset($inquiries[$contextId])) {
                    $row['inquiry_reference'] = $inquiries[$contextId]['reference'];
                    $row['inquiry_subject'] = $inquiries[$contextId]['subject'];
                    $row['inquiry_link'] = $this->context->link->getAdminLink(
                        'AdminHotelInquiries',
                        true,
                        array(),
                        array('focus_inquiry' => $contextId)
                    );
                }
            }
        }
    }

    private function renderExportFiltersPanel()
    {
        $filters = $this->getExportFilters();
        $statusOptions = $this->getStatusOptions();
        $resourceOptions = $this->getResourceFilterOptions();
        $teamOptions = $this->getTeamOptions();

        $this->context->smarty->assign(array(
            'kloperations_export_filters' => array(
                'action' => $this->buildLink(array('token' => $this->token)),
                'from' => $filters['from']->format('Y-m-d'),
                'to' => $filters['to']->format('Y-m-d'),
                'status_options' => $statusOptions,
                'selected_statuses' => $filters['statuses'],
                'resource_type_options' => $resourceOptions,
                'selected_resource_types' => $filters['resource_types'],
                'team_options' => $teamOptions,
                'selected_teams' => $filters['team_references'],
                'summary' => $this->module->getExportService()->summariseFilters($filters),
                'has_team_options' => !empty($teamOptions),
            ),
        ));

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/export_filters.tpl');
    }

    public function renderInquiryContextColumn($value, $row)
    {
        if (!isset($row['context_type']) || $row['context_type'] !== 'inquiry') {
            return '';
        }

        $labelParts = array();
        if (!empty($row['inquiry_reference'])) {
            $labelParts[] = Tools::safeOutput($row['inquiry_reference']);
        }
        if (!empty($row['inquiry_subject'])) {
            $labelParts[] = Tools::safeOutput($row['inquiry_subject']);
        }

        $label = trim(implode(' — ', $labelParts));
        if ($label === '' && isset($row['context_id'])) {
            $label = $this->l('Inquiry') . ' #' . (int) $row['context_id'];
        }

        if (!empty($row['inquiry_link'])) {
            return sprintf(
                '<a href="%s" target="_blank" rel="noopener">%s</a>',
                Tools::safeOutput($row['inquiry_link']),
                $label
            );
        }

        return $label;
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

    public function processAssignEmployee()
    {
        $taskId = (int) Tools::getValue('id_kl_operation_task');
        $employeeId = (int) Tools::getValue('assignment_id_employee');

        if (!$taskId) {
            $this->errors[] = $this->l('Missing task identifier.');

            return false;
        }

        if (!$employeeId) {
            $this->errors[] = $this->l('Select an employee to assign.');

            return false;
        }

        $employee = new Employee($employeeId);
        if (!Validate::isLoadedObject($employee)) {
            $this->errors[] = $this->l('The selected employee could not be loaded.');

            return false;
        }

        if ($this->assignmentExists($taskId, 'employee', $employeeId)) {
            $this->confirmations[] = $this->l('The employee is already assigned to this task.');
            $this->redirectAfterAction($taskId);

            return true;
        }

        $assignment = new KlOperationTaskAssignment();
        $assignment->id_kl_operation_task = $taskId;
        $assignment->id_employee = $employeeId;
        $assignment->assignee_type = 'employee';
        $assignment->assignee_reference = null;
        $assignment->assignee_label = trim($employee->firstname . ' ' . $employee->lastname);
        $assignment->status = 'pending';
        $assignment->acknowledged_at = null;
        $assignment->completed_at = null;
        $assignment->date_add = date('Y-m-d H:i:s');
        $assignment->date_upd = $assignment->date_add;

        if (!$assignment->add()) {
            $this->errors[] = $this->l('Failed to assign the employee. Please try again.');

            return false;
        }

        $this->syncTaskStatus($taskId);
        $this->confirmations[] = $this->l('Assignment saved.');
        $this->redirectAfterAction($taskId);

        return true;
    }

    public function processAssignTeam()
    {
        $taskId = (int) Tools::getValue('id_kl_operation_task');
        $reference = trim((string) Tools::getValue('assignment_team_reference'));
        $label = trim((string) Tools::getValue('assignment_team_label'));

        if (!$taskId) {
            $this->errors[] = $this->l('Missing task identifier.');

            return false;
        }

        if ($reference === '' || $label === '') {
            $this->errors[] = $this->l('Provide both a team reference and label.');

            return false;
        }

        if ($this->assignmentExists($taskId, 'team', null, $reference)) {
            $this->confirmations[] = $this->l('This team is already assigned to the task.');
            $this->redirectAfterAction($taskId);

            return true;
        }

        $assignment = new KlOperationTaskAssignment();
        $assignment->id_kl_operation_task = $taskId;
        $assignment->id_employee = null;
        $assignment->assignee_type = 'team';
        $assignment->assignee_reference = $reference;
        $assignment->assignee_label = $label;
        $assignment->status = 'pending';
        $assignment->acknowledged_at = null;
        $assignment->completed_at = null;
        $assignment->date_add = date('Y-m-d H:i:s');
        $assignment->date_upd = $assignment->date_add;

        if (!$assignment->add()) {
            $this->errors[] = $this->l('Failed to assign the team. Please try again.');

            return false;
        }

        $this->syncTaskStatus($taskId);
        $this->confirmations[] = $this->l('Team assignment saved.');
        $this->redirectAfterAction($taskId);

        return true;
    }

    public function processUpdateAssignmentStatus()
    {
        $taskId = (int) Tools::getValue('id_kl_operation_task');
        $assignmentId = (int) Tools::getValue('id_assignment');
        $status = (string) Tools::getValue('assignment_status');

        if (!$taskId || !$assignmentId) {
            $this->errors[] = $this->l('Missing assignment context.');

            return false;
        }

        if (!$this->isValidAssignmentStatus($status)) {
            $this->errors[] = $this->l('Select a valid assignment status.');

            return false;
        }

        $assignment = new KlOperationTaskAssignment($assignmentId);
        if (!Validate::isLoadedObject($assignment) || (int) $assignment->id_kl_operation_task !== $taskId) {
            $this->errors[] = $this->l('Assignment record not found.');

            return false;
        }

        $now = date('Y-m-d H:i:s');
        $assignment->status = $status;
        $assignment->date_upd = $now;

        if (in_array($status, array('acknowledged', 'in_progress', 'completed'))) {
            if (empty($assignment->acknowledged_at)) {
                $assignment->acknowledged_at = $now;
            }
        } else {
            $assignment->acknowledged_at = null;
        }

        if ($status === 'completed') {
            $assignment->completed_at = $now;
        } else {
            $assignment->completed_at = null;
        }

        if (!$assignment->update()) {
            $this->errors[] = $this->l('Failed to update the assignment status.');

            return false;
        }

        $this->syncTaskStatus($taskId);
        $this->redirectAfterAction($taskId);

        return true;
    }

    public function processDeleteAssignment()
    {
        $taskId = (int) Tools::getValue('id_kl_operation_task');
        $assignmentId = (int) Tools::getValue('id_assignment');

        if (!$taskId || !$assignmentId) {
            $this->errors[] = $this->l('Missing assignment context.');

            return false;
        }

        $assignment = new KlOperationTaskAssignment($assignmentId);
        if (!Validate::isLoadedObject($assignment) || (int) $assignment->id_kl_operation_task !== $taskId) {
            $this->errors[] = $this->l('Assignment record not found.');

            return false;
        }

        if (!$assignment->delete()) {
            $this->errors[] = $this->l('Failed to remove the assignment.');

            return false;
        }

        $this->syncTaskStatus($taskId);
        $this->confirmations[] = $this->l('Assignment removed.');
        $this->redirectAfterAction($taskId);

        return true;
    }

    public function processClaimTask()
    {
        $taskId = (int) Tools::getValue('id_kl_operation_task');
        if (!$taskId) {
            $this->errors[] = $this->l('Missing task identifier.');

            return false;
        }

        $employee = $this->context->employee;
        if (!Validate::isLoadedObject($employee)) {
            $this->errors[] = $this->l('Only logged-in employees can claim tasks.');

            return false;
        }

        if ($this->assignmentExists($taskId, 'employee', (int) $employee->id)) {
            $this->confirmations[] = $this->l('You are already assigned to this task.');
            $this->redirectAfterAction($taskId, array('mobile_range_days' => (int) Tools::getValue('mobile_range_days')));

            return true;
        }

        $assignment = new KlOperationTaskAssignment();
        $assignment->id_kl_operation_task = $taskId;
        $assignment->id_employee = (int) $employee->id;
        $assignment->assignee_type = 'employee';
        $assignment->assignee_reference = null;
        $assignment->assignee_label = trim($employee->firstname . ' ' . $employee->lastname);
        $assignment->status = 'in_progress';
        $assignment->acknowledged_at = date('Y-m-d H:i:s');
        $assignment->completed_at = null;
        $assignment->date_add = $assignment->acknowledged_at;
        $assignment->date_upd = $assignment->acknowledged_at;

        if (!$assignment->add()) {
            $this->errors[] = $this->l('Failed to claim the task.');

            return false;
        }

        $this->syncTaskStatus($taskId);
        $this->confirmations[] = $this->l('Task claimed.');
        $this->redirectAfterAction($taskId, array('mobile_range_days' => (int) Tools::getValue('mobile_range_days')));

        return true;
    }

    public function renderView()
    {
        if ($this->mobileView) {
            $this->show_toolbar = false;
            $this->show_page_header_toolbar = false;

            return $this->renderMobileView();
        }

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

        $inquiryContext = null;
        if ($task->context_type === 'inquiry' && $task->context_id) {
            $inquiryRow = HotelInquiry::findById((int) $task->context_id);
            if ($inquiryRow) {
                $inquiryContext = array(
                    'id' => (int) $task->context_id,
                    'reference' => $inquiryRow['reference'],
                    'subject' => $inquiryRow['subject'],
                    'link' => $this->context->link->getAdminLink(
                        'AdminHotelInquiries',
                        true,
                        array(),
                        array('focus_inquiry' => (int) $task->context_id)
                    ),
                );
            } else {
                $inquiryContext = array(
                    'id' => (int) $task->context_id,
                );
            }
        }

        $assignments = $this->getTaskAssignments((int) $task->id);

        $this->tpl_view_vars = array(
            'task' => $task,
            'payload' => $payload,
            'payload_pretty' => empty($payload) ? '' : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'notes' => $this->getTaskNotes($task->id),
            'assignments' => $assignments,
            'assignment_status_options' => $this->getAssignmentStatusOptions(),
            'assignment_status_labels' => $this->getAssignmentStatusLabels(),
            'employees' => $this->getEmployeeOptions(),
            'team_options' => $this->getTeamOptions(),
            'form_action' => $this->buildLink(array('token' => $this->token)),
            'mobile_view_link' => $this->buildLink(array('token' => $this->token, 'mobile_view' => 1)),
            'current_employee_id' => (int) $this->context->employee->id,
            'inquiry_context' => $inquiryContext,
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
        $filters = $this->getExportFilters();
        $tasks = $this->module->getExportService()->fetchTasks($filters['from'], $filters['to'], $filters);
        $csv = $this->module->getExportService()->generateCsv($tasks, $filters);

        $filename = $this->module->getExportService()->buildExportFilename('csv', $filters['from'], $filters['to'], $filters);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . Tools::strlen($csv));
        echo $csv;
        exit;
    }

    public function processExportIcs()
    {
        $filters = $this->getExportFilters();
        $timezone = $filters['timezone'] instanceof DateTimeZone ? $filters['timezone'] : $this->resolveTimezone();

        $tasks = $this->module->getExportService()->fetchTasks($filters['from'], $filters['to'], $filters);
        $ics = $this->module->getExportService()->generateIcs($tasks, $timezone, $filters);

        $filename = $this->module->getExportService()->buildExportFilename('ics', $filters['from'], $filters['to'], $filters);
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

    public function loadObject($opt = false)
    {
        if ($this->mobileView) {
            return true;
        }

        return parent::loadObject($opt);
    }

    private function redirectAfterAction($taskId = null, array $extra = array())
    {
        $params = array_merge(array('token' => $this->token), $extra);
        if ($this->isMobileReturn()) {
            $params['mobile_view'] = 1;
            $range = (int) Tools::getValue('mobile_range_days');
            if ($range > 0) {
                $params['mobile_range_days'] = $range;
            }
        } elseif ($taskId) {
            $params['id_kl_operation_task'] = (int) $taskId;
            $params['view' . $this->table] = 1;
        }

        Tools::redirectAdmin($this->buildLink($params));
    }

    private function isMobileReturn()
    {
        return $this->mobileView || Tools::getValue('return') === 'mobile';
    }

    private function buildLink(array $params = array())
    {
        $base = self::$currentIndex;
        $separator = strpos($base, '?') === false ? '?' : '&';
        if (empty($params)) {
            return $base;
        }

        return $base . $separator . http_build_query($params);
    }

    private function assignmentExists($taskId, $type, $employeeId = null, $reference = null)
    {
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from(KlOperationTaskAssignment::$definition['table']);
        $query->where('`id_kl_operation_task` = ' . (int) $taskId);
        $query->where('`assignee_type` = "' . pSQL($type) . '"');

        if ($type === 'employee') {
            $query->where('`id_employee` = ' . (int) $employeeId);
        } else {
            $query->where('`assignee_reference` = "' . pSQL((string) $reference) . '"');
        }

        return (bool) Db::getInstance()->getValue($query);
    }

    private function isValidAssignmentStatus($status)
    {
        $valid = array();
        foreach ($this->getAssignmentStatusOptions() as $option) {
            $valid[$option['id']] = true;
        }

        return isset($valid[$status]);
    }

    private function getAssignmentStatusOptions()
    {
        return array(
            array('id' => 'pending', 'name' => $this->l('Pending')),
            array('id' => 'acknowledged', 'name' => $this->l('Acknowledged')),
            array('id' => 'in_progress', 'name' => $this->l('In progress')),
            array('id' => 'completed', 'name' => $this->l('Completed')),
            array('id' => 'declined', 'name' => $this->l('Declined')),
        );
    }

    private function getAssignmentStatusLabels()
    {
        $labels = array();
        foreach ($this->getAssignmentStatusOptions() as $option) {
            $labels[$option['id']] = $option['name'];
        }

        return $labels;
    }

    private function fetchAssignmentsIndexed(array $taskIds)
    {
        $rows = KlOperationTaskAssignment::getAssignmentsForTasks($taskIds);
        if (!$rows) {
            return array();
        }

        $indexed = array();
        foreach ($rows as $row) {
            $taskId = (int) $row['id_kl_operation_task'];
            if (!isset($indexed[$taskId])) {
                $indexed[$taskId] = array();
            }
            $indexed[$taskId][] = $this->normaliseAssignmentRow($row);
        }

        return $indexed;
    }

    private function fetchInquiriesIndexed(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return array();
        }

        $query = new DbQuery();
        $query->select('`id_inquiry`, `reference`, `subject`');
        $query->from(HotelInquiry::$definition['table']);
        $query->where('`id_inquiry` IN (' . implode(',', $ids) . ')');

        $rows = Db::getInstance()->executeS($query);
        if (!$rows) {
            return array();
        }

        $indexed = array();
        foreach ($rows as $row) {
            $indexed[(int) $row['id_inquiry']] = array(
                'reference' => $row['reference'],
                'subject' => $row['subject'],
            );
        }

        return $indexed;
    }

    private function normaliseAssignmentRow(array $row)
    {
        $displayName = '';
        if ($row['assignee_type'] === 'employee') {
            $displayName = trim((string) $row['firstname'] . ' ' . (string) $row['lastname']);
            if ($displayName === '') {
                $displayName = (string) $row['assignee_label'];
            }
            if ($displayName === '' && !empty($row['email'])) {
                $displayName = (string) $row['email'];
            }
            if ($displayName === '') {
                $displayName = $this->l('Employee');
            }
        } else {
            $displayName = (string) $row['assignee_label'];
            if ($displayName === '') {
                $displayName = (string) $row['assignee_reference'];
            }
            if ($displayName === '') {
                $displayName = $this->l('Team');
            }
        }

        return array(
            'id_assignment' => (int) $row['id_kl_operation_task_assignment'],
            'id_kl_operation_task' => (int) $row['id_kl_operation_task'],
            'id_employee' => isset($row['id_employee']) ? (int) $row['id_employee'] : null,
            'assignee_type' => (string) $row['assignee_type'],
            'assignee_reference' => (string) $row['assignee_reference'],
            'assignee_label' => (string) $row['assignee_label'],
            'display_name' => $displayName,
            'status' => (string) $row['status'],
            'acknowledged_at' => $row['acknowledged_at'],
            'completed_at' => $row['completed_at'],
            'date_add' => $row['date_add'],
            'date_upd' => $row['date_upd'],
        );
    }

    private function formatAssignmentSummaryForList(array $assignments, $taskId)
    {
        if (!isset($assignments[$taskId]) || empty($assignments[$taskId])) {
            return $this->l('Unassigned');
        }

        $parts = array();
        foreach ($assignments[$taskId] as $assignment) {
            $parts[] = sprintf(
                '%s (%s)',
                $assignment['display_name'],
                $this->translateAssignmentStatus($assignment['status'])
            );
        }

        return implode(', ', $parts);
    }

    private function translateAssignmentStatus($status)
    {
        $labels = $this->getAssignmentStatusLabels();

        if (isset($labels[$status])) {
            return $labels[$status];
        }

        return Tools::ucfirst(str_replace('_', ' ', (string) $status));
    }

    private function getTaskAssignments($taskId)
    {
        $indexed = $this->fetchAssignmentsIndexed(array((int) $taskId));

        return isset($indexed[$taskId]) ? $indexed[$taskId] : array();
    }

    private function getEmployeeOptions()
    {
        $query = new DbQuery();
        $query->select('`id_employee`, `firstname`, `lastname`, `email`');
        $query->from('employee');
        $query->where('`active` = 1');
        $query->orderBy('`firstname` ASC, `lastname` ASC');

        $rows = Db::getInstance()->executeS($query) ?: array();
        $options = array();
        foreach ($rows as $row) {
            $name = trim($row['firstname'] . ' ' . $row['lastname']);
            if ($name === '') {
                $name = $row['email'];
            }
            $options[] = array(
                'id' => (int) $row['id_employee'],
                'name' => $name,
            );
        }

        return $options;
    }

    private function getTeamOptions()
    {
        $raw = trim((string) Configuration::get('KLOPERATIONS_TEAMS'));
        if ($raw === '') {
            return array();
        }

        return $this->parseTeamConfig($raw);
    }

    private function parseTeamConfig($raw)
    {
        $decoded = json_decode($raw, true);
        $teams = array();

        if (is_array($decoded)) {
            foreach ($decoded as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $id = isset($entry['id']) ? trim((string) $entry['id']) : '';
                $label = isset($entry['label']) ? trim((string) $entry['label']) : '';
                if ($id === '' || $label === '') {
                    continue;
                }
                $teams[] = array('id' => $id, 'label' => $label);
            }

            return $teams;
        }

        $lines = preg_split('/[\r\n]+/', $raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode(':', $line, 2);
            $id = trim($parts[0]);
            $label = isset($parts[1]) ? trim($parts[1]) : $id;
            if ($id === '' || $label === '') {
                continue;
            }
            $teams[] = array('id' => $id, 'label' => $label);
        }

        return $teams;
    }

    private function syncTaskStatus($taskId)
    {
        $task = new KlOperationTask((int) $taskId);
        if (!Validate::isLoadedObject($task)) {
            return;
        }

        if ($task->status === 'cancelled') {
            return;
        }

        $assignments = $this->getTaskAssignments($taskId);
        if (empty($assignments)) {
            if ($task->status !== 'pending' && $task->status !== 'completed') {
                $task->status = 'pending';
                $task->completed_at = null;
                $task->completed_by = null;
                $task->date_upd = date('Y-m-d H:i:s');
                $task->update();
            }

            return;
        }

        $counts = array();
        foreach ($assignments as $assignment) {
            $status = $assignment['status'];
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status]++;
        }

        $totalAssignments = array_sum($counts);
        $now = date('Y-m-d H:i:s');
        $updated = false;

        if (!empty($counts['completed']) && $counts['completed'] === $totalAssignments) {
            if ($task->status !== 'completed') {
                $task->status = 'completed';
                $task->completed_at = $now;
                $task->completed_by = (int) $this->context->employee->id;
                $updated = true;
            }
        } elseif (!empty($counts['in_progress']) || !empty($counts['acknowledged'])) {
            if ($task->status !== 'in_progress') {
                $task->status = 'in_progress';
                $task->completed_at = null;
                $task->completed_by = null;
                $updated = true;
            }
        } elseif (!empty($counts['pending'])) {
            if ($task->status !== 'pending') {
                $task->status = 'pending';
                $task->completed_at = null;
                $task->completed_by = null;
                $updated = true;
            }
        }

        if ($updated) {
            $task->date_upd = $now;
            $task->update();
        }
    }

    private function renderMobileView()
    {
        $rangeDays = max(1, (int) Tools::getValue('mobile_range_days', 3));
        $timezone = $this->resolveTimezone();
        $start = new DateTimeImmutable('today', $timezone)->sub(new DateInterval('P1D'));
        $end = $start->add(new DateInterval('P' . ($rangeDays + 1) . 'D'));

        $tasks = $this->module->getExportService()->fetchTasks($start, $end, array('statuses' => array('pending', 'in_progress')));
        $taskIds = array();
        foreach ($tasks as $task) {
            if (isset($task['id_kl_operation_task'])) {
                $taskIds[] = (int) $task['id_kl_operation_task'];
            }
        }

        $assignments = $this->fetchAssignmentsIndexed($taskIds);
        $currentEmployeeId = (int) $this->context->employee->id;
        $statusLabels = $this->getStatusLabelMap();
        $assignmentLabels = $this->getAssignmentStatusLabels();

        $myTasks = array();
        $unassignedTasks = array();

        foreach ($tasks as $task) {
            $taskId = (int) $task['id_kl_operation_task'];
            $taskAssignments = isset($assignments[$taskId]) ? $assignments[$taskId] : array();
            $task['assignments'] = $taskAssignments;
            $task['status_label'] = isset($statusLabels[$task['status']]) ? $statusLabels[$task['status']] : $task['status'];
            $task['assignment_summary'] = $this->formatAssignmentSummaryForList($assignments, $taskId);

            $currentAssignment = null;
            foreach ($taskAssignments as $assignment) {
                if ($assignment['assignee_type'] === 'employee' && (int) $assignment['id_employee'] === $currentEmployeeId) {
                    $currentAssignment = $assignment;
                    break;
                }
            }

            if ($currentAssignment) {
                $task['current_assignment'] = $currentAssignment;
                $task['current_assignment']['status_label'] = isset($assignmentLabels[$currentAssignment['status']]) ? $assignmentLabels[$currentAssignment['status']] : $currentAssignment['status'];
                $myTasks[] = $task;
            } elseif (empty($taskAssignments)) {
                $unassignedTasks[] = $task;
            }
        }

        $this->context->smarty->assign(array(
            'my_tasks' => $myTasks,
            'unassigned_tasks' => $unassignedTasks,
            'mobile_form_action' => $this->buildLink(array(
                'token' => $this->token,
                'mobile_view' => 1,
                'mobile_range_days' => $rangeDays,
            )),
            'mobile_refresh_link' => $this->buildLink(array(
                'token' => $this->token,
                'mobile_view' => 1,
            )),
            'assignment_status_options' => $this->getAssignmentStatusOptions(),
            'assignment_status_labels' => $assignmentLabels,
            'current_employee_id' => $currentEmployeeId,
            'mobile_range_days' => $rangeDays,
            'status_labels' => $statusLabels,
            'token' => $this->token,
        ));

        return $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/task_mobile.tpl');
    }

    private function getExportFilters()
    {
        if ($this->exportFilters !== null) {
            return $this->exportFilters;
        }

        $timezone = $this->resolveTimezone();
        $defaultFrom = (new DateTimeImmutable('today', $timezone))->setTime(0, 0, 0);
        $defaultTo = $defaultFrom->add(new DateInterval('P7D'))->setTime(23, 59, 59);

        $statusOptions = $this->getStatusOptions();
        $statusLabels = array();
        foreach ($statusOptions as $option) {
            $statusLabels[$option['id']] = $option['name'];
        }

        $resourceOptions = $this->getResourceFilterOptions();
        $resourceLabels = array();
        foreach ($resourceOptions as $option) {
            $resourceLabels[$option['id']] = $option['name'];
        }

        $teamOptions = $this->getTeamOptions();
        $teamLabels = array();
        foreach ($teamOptions as $option) {
            $teamLabels[$option['id']] = $option['label'];
        }

        $selectedStatuses = $this->filterSelection(Tools::getValue('export_statuses'), array_keys($statusLabels));
        if (empty($selectedStatuses)) {
            $selectedStatuses = array('pending', 'in_progress');
        }

        $selectedResourceTypes = $this->filterSelection(Tools::getValue('export_resource_types'), array_keys($resourceLabels));
        $selectedTeams = $this->filterSelection(Tools::getValue('export_teams'), array_keys($teamLabels));

        $from = $this->parseExportDate(Tools::getValue('export_from'), $defaultFrom, $timezone, true);
        $to = $this->parseExportDate(Tools::getValue('export_to'), $defaultTo, $timezone, false);

        if ($from > $to) {
            $to = $from->setTime(23, 59, 59);
        }

        $this->exportFilters = array(
            'from' => $from->setTime(0, 0, 0),
            'to' => $to->setTime(23, 59, 59),
            'timezone' => $timezone,
            'statuses' => $selectedStatuses,
            'status_labels' => $statusLabels,
            'resource_types' => $selectedResourceTypes,
            'resource_type_labels' => $resourceLabels,
            'team_references' => $selectedTeams,
            'team_labels' => $teamLabels,
        );

        return $this->exportFilters;
    }

    private function getResourceFilterOptions()
    {
        if ($this->resourceFilterOptions !== null) {
            return $this->resourceFilterOptions;
        }

        $options = array(
            array(
                'id' => KlOperationExportService::RESOURCE_TYPE_NONE,
                'name' => $this->l('General (no resource)'),
            ),
        );

        $query = new DbQuery();
        $query->select('DISTINCT `resource_type`');
        $query->from(KlOperationTask::$definition['table']);
        $query->orderBy('`resource_type` ASC');

        $rows = Db::getInstance()->executeS($query) ?: array();
        $seen = array();

        foreach ($rows as $row) {
            $value = trim((string) $row['resource_type']);
            if ($value === '' || isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $options[] = array(
                'id' => $value,
                'name' => Tools::ucfirst(str_replace('_', ' ', $value)),
            );
        }

        $this->resourceFilterOptions = $options;

        return $this->resourceFilterOptions;
    }

    private function filterSelection($values, array $allowed)
    {
        if ($values === null) {
            $values = array();
        }

        if (!is_array($values)) {
            $values = array($values);
        }

        $allowedMap = array_flip($allowed);
        $selected = array();
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '' || !isset($allowedMap[$value])) {
                continue;
            }
            $selected[$value] = true;
        }

        return array_keys($selected);
    }

    private function parseExportDate($value, DateTimeImmutable $fallback, DateTimeZone $timezone, $isStart)
    {
        $value = trim((string) $value);
        if ($value !== '') {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $value, $timezone);
            if ($date instanceof DateTimeImmutable) {
                return $isStart ? $date->setTime(0, 0, 0) : $date->setTime(23, 59, 59);
            }
        }

        return $fallback;
    }

    private function getStatusLabelMap()
    {
        $map = array();
        foreach ($this->getStatusOptions() as $option) {
            $map[$option['id']] = $option['name'];
        }

        return $map;
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
            array('id' => 'housekeeping_followup', 'name' => $this->l('Housekeeping – follow-up')),
            array('id' => 'maintenance_start', 'name' => $this->l('Maintenance – start block')),
            array('id' => 'maintenance_release', 'name' => $this->l('Maintenance – release space')),
            array('id' => 'maintenance_followup', 'name' => $this->l('Maintenance – follow-up')),
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

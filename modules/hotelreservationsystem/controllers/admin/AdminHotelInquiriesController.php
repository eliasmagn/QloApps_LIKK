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

require_once _PS_MODULE_DIR_ . 'hotelreservationsystem/classes/HotelInquiryOperationsBridge.php';
require_once _PS_MODULE_DIR_ . 'hotelreservationsystem/classes/QuotePdfGenerator.php';

class AdminHotelInquiriesController extends ModuleAdminController
{
    protected $boardEmployees = array();
    protected $statusDefinitions = array();
    protected $stageDefinitions = array();
    protected $quoteStatusLabels = array();

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->quoteStatusLabels = KLQuote::getStatusLabels();
    }

    public function initContent()
    {
        parent::initContent();

        $this->stageDefinitions = HotelInquiry::getStageDefinitions();
        $this->statusDefinitions = HotelInquiry::getStatusDefinitions();
        $this->boardEmployees = $this->getEmployeesForBoard();
        $dataset = HotelInquiry::getBoardDataset(array_keys($this->stageDefinitions));

        $this->context->smarty->assign(array(
            'stage_definitions' => $this->stageDefinitions,
            'status_definitions' => $this->statusDefinitions,
            'inquiry_dataset' => $dataset,
            'board_employees' => $this->boardEmployees,
            'operations_enabled' => HotelInquiryOperationsBridge::isAvailable(),
        ));

        $this->setTemplate('inquiries/kanban.tpl');
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJquery();
        $this->addJqueryUI('ui.sortable');
        $this->addJqueryUI('ui.draggable');
        $this->addJqueryUI('ui.droppable');
        $this->addJS($this->module->getPathUri().'views/js/admin/inquiries_board.js');
        $this->addCSS($this->module->getPathUri().'views/css/admin/inquiries_board.css');

        if (!$this->stageDefinitions) {
            $this->stageDefinitions = HotelInquiry::getStageDefinitions();
        }
        if (!$this->statusDefinitions) {
            $this->statusDefinitions = HotelInquiry::getStatusDefinitions();
        }

        Media::addJsDef(array(
            'hotelInquiryBoardConfig' => array(
                'ajaxUrl' => $this->context->link->getAdminLink('AdminHotelInquiries'),
                'messages' => array(
                    'createSuccess' => $this->l('Inquiry created successfully.'),
                    'updateError' => $this->l('Unable to update the inquiry. Please try again.'),
                    'notePlaceholder' => $this->l('Add a note that will be logged for the team.', null, true),
                    'mailNotePlaceholder' => $this->l('Add a note and notify the guest via email.', null, true),
                    'reminderSaved' => $this->l('Reminder updated.'),
                    'reminderCleared' => $this->l('Reminder cleared.'),
                    'reminderPrompt' => $this->l('Enter reminder timestamp (YYYY-MM-DD HH:MM) or leave blank to clear.'),
                    'noteSaved' => $this->l('Note saved.'),
                    'mailNoteSent' => $this->l('Mail note sent to the requester.'),
                    'mailNoteFailed' => $this->l('Note saved but the email could not be sent.'),
                    'assignmentSaved' => $this->l('Assignment updated.'),
                    'noNotes' => $this->l('No notes yet.'),
                    'operationsUnavailable' => $this->l('The operations console is not available.'),
                    'operationsTaskCreated' => $this->l('Operations follow-up created.'),
                    'operationsTaskFailed' => $this->l('Unable to create the operations follow-up.'),
                    'operationsNoTasks' => $this->l('No operations follow-ups yet.'),
                    'operationsHousekeepingReference' => $this->l('Housekeeping follow-up for %s'),
                    'operationsMaintenanceReference' => $this->l('Maintenance follow-up for %s'),
                    'operationsNoteHeader' => $this->l('Inquiry %s — %s'),
                    'operationsNoteRequester' => $this->l('Requester: %s'),
                    'operationsNoteDates' => $this->l('Stay window: %s → %s'),
                    'operationsNoteResources' => $this->l('Requested: %s'),
                    'operationsViewTask' => $this->l('View task'),
                    'quotesEmpty' => $this->l('No quotes have been generated yet.'),
                    'quoteValidUntil' => $this->l('Valid until %s'),
                    'quoteDownload' => $this->l('Download PDF'),
                    'quoteEmail' => $this->l('Email to guest'),
                    'quoteDownloadFailed' => $this->l('Unable to download the quote PDF.'),
                    'quoteEmailSuccess' => $this->l('Quote emailed to the guest successfully.'),
                    'quoteEmailFailed' => $this->l('Unable to send the quote email.'),
                ),
                'stageStatuses' => array_map(function ($definition) {
                    return isset($definition['default_status']) ? $definition['default_status'] : null;
                }, $this->stageDefinitions),
                'stageLabels' => array_map(function ($definition) {
                    return isset($definition['label']) ? $definition['label'] : null;
                }, $this->stageDefinitions),
                'statusLabels' => $this->statusDefinitions,
                'operationsEnabled' => HotelInquiryOperationsBridge::isAvailable(),
                'operationsConsoleUrl' => $this->context->link->getAdminLink('AdminKlOperationTasks'),
                'focusInquiryId' => (int) Tools::getValue('focus_inquiry'),
            ),
        ));
    }

    public function ajaxProcessCreateInquiry()
    {
        $response = array('success' => false);
        if ($this->tabAccess['add'] != '1') {
            $response['message'] = $this->l('You do not have permission to create inquiries.');
            return $this->ajaxDie(json_encode($response));
        }

        $subject = Tools::getValue('subject');
        if (!$subject) {
            $response['message'] = $this->l('Subject is required.');
            return $this->ajaxDie(json_encode($response));
        }

        $inquiry = new HotelInquiry();
        $inquiry->subject = $subject;
        $inquiry->requester_name = Tools::getValue('requester_name');
        $inquiry->requester_email = Tools::getValue('requester_email');
        $inquiry->requester_phone = Tools::getValue('requester_phone');
        $inquiry->resource_request = Tools::getValue('resource_request');
        $inquiry->internal_notes = Tools::getValue('internal_notes');
        $inquiry->check_in = $this->normalizeDate(Tools::getValue('check_in'));
        $inquiry->check_out = $this->normalizeDate(Tools::getValue('check_out'));
        $assignedValue = Tools::getValue('assigned_to');
        $inquiry->assigned_to = $assignedValue === '' ? null : (int) $assignedValue;

        if ($inquiry->add()) {
            $row = HotelInquiry::findById($inquiry->id);
            $response['success'] = true;
            $response['inquiry'] = $row;
            $response['card_html'] = $this->renderInquiryCard($row);
        } else {
            $response['message'] = $this->l('Could not create inquiry. Please review the data.');
        }

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessUpdateInquiryStage()
    {
        $response = array('success' => false);
        if ($this->tabAccess['edit'] != '1') {
            $response['message'] = $this->l('You do not have permission to update inquiries.');
            return $this->ajaxDie(json_encode($response));
        }

        $idInquiry = (int) Tools::getValue('id_inquiry');
        $stage = Tools::getValue('stage');
        $assigned = Tools::getValue('assigned_to');

        if (!$idInquiry || !$stage) {
            $response['message'] = $this->l('Missing inquiry information.');
            return $this->ajaxDie(json_encode($response));
        }

        $statusOverride = Tools::getValue('status');
        if ($statusOverride === '') {
            $statusOverride = null;
        }

        if (HotelInquiry::updateStage($idInquiry, $stage, $statusOverride, $assigned === '' ? null : (int) $assigned)) {
            $row = HotelInquiry::findById($idInquiry);
            $response['success'] = true;
            $response['inquiry'] = $row;
            $response['card_html'] = $this->renderInquiryCard($row);
            $response['operations'] = array(
                'enabled' => HotelInquiryOperationsBridge::isAvailable(),
                'tasks' => HotelInquiryOperationsBridge::fetchTasksForInquiry($idInquiry),
                'list_link' => HotelInquiryOperationsBridge::buildTaskListLink($idInquiry),
            );
        } else {
            $response['message'] = $this->l('Unable to update the inquiry stage.');
        }

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessUpdateInquiryAssignment()
    {
        $response = array('success' => false);
        if ($this->tabAccess['edit'] != '1') {
            $response['message'] = $this->l('You do not have permission to update inquiries.');
            return $this->ajaxDie(json_encode($response));
        }

        $idInquiry = (int) Tools::getValue('id_inquiry');
        $assigned = Tools::getValue('assigned_to');
        if (!$idInquiry) {
            $response['message'] = $this->l('Missing inquiry information.');
            return $this->ajaxDie(json_encode($response));
        }

        if (!$row = HotelInquiry::findById($idInquiry)) {
            $response['message'] = $this->l('Inquiry not found.');
            return $this->ajaxDie(json_encode($response));
        }

        if (HotelInquiry::updateStage($idInquiry, $row['stage'], null, $assigned === '' ? null : (int) $assigned)) {
            $row = HotelInquiry::findById($idInquiry);
            $response['success'] = true;
            $response['inquiry'] = $row;
            $response['card_html'] = $this->renderInquiryCard($row);
            $response['operations'] = array(
                'enabled' => HotelInquiryOperationsBridge::isAvailable(),
                'tasks' => HotelInquiryOperationsBridge::fetchTasksForInquiry($idInquiry),
                'list_link' => HotelInquiryOperationsBridge::buildTaskListLink($idInquiry),
            );
        } else {
            $response['message'] = $this->l('Unable to update assignment.');
        }

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessAddInquiryNote()
    {
        $response = array('success' => false);
        if ($this->tabAccess['edit'] != '1') {
            $response['message'] = $this->l('You do not have permission to add notes.');
            return $this->ajaxDie(json_encode($response));
        }

        $idInquiry = (int) Tools::getValue('id_inquiry');
        $note = Tools::getValue('note');
        $isMail = (bool) Tools::getValue('is_mail');

        if (!$idInquiry || !$note) {
            $response['message'] = $this->l('A note body is required.');
            return $this->ajaxDie(json_encode($response));
        }

        if (!$inquiry = HotelInquiry::findById($idInquiry)) {
            $response['message'] = $this->l('Inquiry not found.');
            return $this->ajaxDie(json_encode($response));
        }

        if ($noteObject = HotelInquiryNote::addNote($idInquiry, $note, $this->context->employee->id, $isMail)) {
            $response['success'] = true;
            $response['note'] = $noteObject;
            $response['notes'] = HotelInquiryNote::getInquiryNotes($idInquiry);

            if ($isMail) {
                $mailStatus = $this->dispatchInquiryMailNote($inquiry, $note);
                $response['mail_sent'] = $mailStatus['sent'];
                if (isset($mailStatus['message'])) {
                    $response['mail_error'] = $mailStatus['message'];
                }
            }

            if (Tools::getValue('operation_follow_up', '0') !== '0') {
                $this->handleOperationsFollowUpFromNote($response, $inquiry, $idInquiry, $note);
            }
        } else {
            $response['message'] = $this->l('Unable to save the note.');
        }

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessCreateInquiryOperationTask()
    {
        $response = array('success' => false);

        if ($this->tabAccess['edit'] != '1') {
            $response['message'] = $this->l('You do not have permission to create operations follow-ups.');

            return $this->ajaxDie(json_encode($response));
        }

        if (!HotelInquiryOperationsBridge::isAvailable()) {
            $response['message'] = $this->l('The operations console is not available.');

            return $this->ajaxDie(json_encode($response));
        }

        $idInquiry = (int) Tools::getValue('id_inquiry');
        $taskType = (string) Tools::getValue('task_type');
        $reference = trim((string) Tools::getValue('reference'));
        $priority = (int) Tools::getValue('priority', 3);
        $scheduledFor = $this->normalizeDateTimeValue(Tools::getValue('scheduled_for'));
        $dueEndRaw = Tools::getValue('due_end');
        $dueEnd = $dueEndRaw !== '' ? $this->normalizeDateTimeValue($dueEndRaw) : null;
        $note = trim((string) Tools::getValue('note'));
        $resourceType = trim((string) Tools::getValue('resource_type'));
        $idResource = Tools::getValue('id_resource');
        $logNote = Tools::getValue('log_note', '1') !== '0';

        if (!$idInquiry || !$taskType || $reference === '' || !$scheduledFor) {
            $response['message'] = $this->l('Missing required information for the operations follow-up.');

            return $this->ajaxDie(json_encode($response));
        }

        if (!in_array($taskType, $this->getOperationTaskTypeWhitelist(), true)) {
            $response['message'] = $this->l('Select a valid operations follow-up type.');

            return $this->ajaxDie(json_encode($response));
        }

        if ($priority < 1 || $priority > 5) {
            $priority = 3;
        }

        if ($dueEndRaw !== '' && !$dueEnd) {
            $response['message'] = $this->l('Invalid due date. Use YYYY-MM-DD HH:MM.');

            return $this->ajaxDie(json_encode($response));
        }

        if (!$inquiry = HotelInquiry::findById($idInquiry)) {
            $response['message'] = $this->l('Inquiry not found.');

            return $this->ajaxDie(json_encode($response));
        }

        $task = HotelInquiryOperationsBridge::createTaskForInquiry($inquiry, array(
            'task_type' => $taskType,
            'reference' => $reference,
            'priority' => $priority,
            'scheduled_for' => $scheduledFor,
            'due_end' => $dueEnd,
            'resource_type' => $resourceType,
            'id_resource' => $idResource === '' ? null : (int) $idResource,
            'note' => $note,
        ));

        if (!$task) {
            $response['message'] = $this->l('Unable to create the operations follow-up.');

            return $this->ajaxDie(json_encode($response));
        }

        $taskLink = HotelInquiryOperationsBridge::buildTaskViewLink((int) $task->id);
        $taskSummary = array(
            'id' => (int) $task->id,
            'reference' => $task->reference,
            'view_link' => $taskLink,
            'status' => $task->status,
            'task_type' => $task->task_type,
        );

        $response['success'] = true;
        $response['task'] = $taskSummary;

        if ($logNote) {
            $noteLines = array(
                sprintf($this->l('Raised operations follow-up: %s'), $task->reference),
            );
            if ($taskLink) {
                $noteLines[] = sprintf($this->l('View task: %s'), $taskLink);
            }
            if ($note !== '') {
                $noteLines[] = $note;
            }

            $noteBody = implode("\n", $noteLines);

            if ($noteObject = HotelInquiryNote::addNote($idInquiry, $noteBody, $this->context->employee ? (int) $this->context->employee->id : null, false)) {
                $response['notes'] = HotelInquiryNote::getInquiryNotes($idInquiry);
            }
        }

        $operations = array(
            'tasks' => HotelInquiryOperationsBridge::fetchTasksForInquiry($idInquiry),
            'list_link' => HotelInquiryOperationsBridge::buildTaskListLink($idInquiry),
        );
        $response['operations'] = $operations;

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessScheduleInquiryReminder()
    {
        $response = array('success' => false);
        if ($this->tabAccess['edit'] != '1') {
            $response['message'] = $this->l('You do not have permission to schedule reminders.');
            return $this->ajaxDie(json_encode($response));
        }

        $idInquiry = (int) Tools::getValue('id_inquiry');
        $reminderAt = Tools::getValue('reminder_at');
        if (!$idInquiry) {
            $response['message'] = $this->l('Missing inquiry information.');
            return $this->ajaxDie(json_encode($response));
        }

        if (HotelInquiry::scheduleReminder($idInquiry, $reminderAt)) {
            $response['success'] = true;
            $response['reminder_at'] = $reminderAt ? date('Y-m-d H:i:s', strtotime($reminderAt)) : null;
        } else {
            $response['message'] = $this->l('Unable to schedule the reminder.');
        }

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessGetInquiryDetails()
    {
        $idInquiry = (int) Tools::getValue('id_inquiry');
        $response = array('success' => false);
        if (!$idInquiry) {
            $response['message'] = $this->l('Missing inquiry information.');
            return $this->ajaxDie(json_encode($response));
        }

        if ($row = HotelInquiry::findById($idInquiry)) {
            $response['success'] = true;
            $response['inquiry'] = $row;
            $response['notes'] = HotelInquiryNote::getInquiryNotes($idInquiry);
            $response['card_html'] = $this->renderInquiryCard($row);
            $response['operations'] = array(
                'enabled' => HotelInquiryOperationsBridge::isAvailable(),
                'tasks' => HotelInquiryOperationsBridge::fetchTasksForInquiry($idInquiry),
                'list_link' => HotelInquiryOperationsBridge::buildTaskListLink($idInquiry),
            );
            $response['quotes'] = $this->formatQuoteSummaries(KLQuote::getSummariesForInquiry($idInquiry), $row);
            $response['quote_permissions'] = $this->buildQuotePermissions($row);
        } else {
            $response['message'] = $this->l('Inquiry not found.');
        }

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessDownloadQuotePdf()
    {
        $response = array('success' => false);
        if (!$this->access('view')) {
            $response['message'] = $this->l('You do not have permission to download quotes.');
            return $this->ajaxDie(json_encode($response));
        }

        $idQuote = (int) Tools::getValue('id_quote');
        if (!$idQuote) {
            $response['message'] = $this->l('Missing quote identifier.');
            return $this->ajaxDie(json_encode($response));
        }

        $quote = new KLQuote($idQuote);
        if (!Validate::isLoadedObject($quote)) {
            $response['message'] = $this->l('Quote not found.');
            return $this->ajaxDie(json_encode($response));
        }

        $inquiry = HotelInquiry::findById((int) $quote->id_inquiry);
        if (!$inquiry) {
            $response['message'] = $this->l('Inquiry not found for this quote.');
            return $this->ajaxDie(json_encode($response));
        }

        $generator = new QuotePdfGenerator();
        $pdf = $generator->generate($quote, array('inquiry' => $inquiry));
        $filename = $generator->buildFilename($quote, array('inquiry' => $inquiry));

        $response['success'] = true;
        $response['filename'] = $filename;
        $response['mime'] = 'application/pdf';
        $response['content_base64'] = base64_encode($pdf);
        $response['quotes'] = $this->formatQuoteSummaries(KLQuote::getSummariesForInquiry((int) $quote->id_inquiry), $inquiry);
        $response['quote_permissions'] = $this->buildQuotePermissions($inquiry);

        return $this->ajaxDie(json_encode($response));
    }

    public function ajaxProcessEmailQuotePdf()
    {
        $response = array('success' => false);
        if (!$this->access('edit')) {
            $response['message'] = $this->l('You do not have permission to email quotes.');
            return $this->ajaxDie(json_encode($response));
        }

        $idQuote = (int) Tools::getValue('id_quote');
        if (!$idQuote) {
            $response['message'] = $this->l('Missing quote identifier.');
            return $this->ajaxDie(json_encode($response));
        }

        $quote = new KLQuote($idQuote);
        if (!Validate::isLoadedObject($quote)) {
            $response['message'] = $this->l('Quote not found.');
            return $this->ajaxDie(json_encode($response));
        }

        $inquiry = HotelInquiry::findById((int) $quote->id_inquiry);
        if (!$inquiry) {
            $response['message'] = $this->l('Inquiry not found for this quote.');
            return $this->ajaxDie(json_encode($response));
        }

        $email = isset($inquiry['requester_email']) ? trim($inquiry['requester_email']) : '';
        if (!$email || !Validate::isEmail($email)) {
            $response['message'] = $this->l('The inquiry is missing a valid guest email address.');
            return $this->ajaxDie(json_encode($response));
        }

        $generator = new QuotePdfGenerator();
        $pdf = $generator->generate($quote, array('inquiry' => $inquiry));
        $filename = $generator->buildFilename($quote, array('inquiry' => $inquiry));

        $statusLabels = $this->quoteStatusLabels;
        $quoteStatus = isset($statusLabels[$quote->status]) ? $statusLabels[$quote->status] : $quote->status;
        $langId = $this->context && $this->context->language ? (int) $this->context->language->id : null;

        $templateVars = array(
            '{guest_name}' => isset($inquiry['requester_name']) && $inquiry['requester_name'] !== '' ? $inquiry['requester_name'] : $this->l('there'),
            '{inquiry_reference}' => isset($inquiry['reference']) ? $inquiry['reference'] : '',
            '{inquiry_subject}' => isset($inquiry['subject']) ? $inquiry['subject'] : '',
            '{quote_status}' => $quoteStatus,
            '{quote_valid_until}' => $quote->valid_until ? Tools::displayDate($quote->valid_until, $langId, true) : $this->l('Until further notice'),
            '{quote_total}' => $this->formatQuoteMoney($quote->gross_total_minor, $quote->currency_iso_code),
            '{contact_email}' => Configuration::get('PS_SHOP_EMAIL'),
            '{contact_phone}' => Configuration::get('PS_SHOP_PHONE') ?: '',
            '{brand_name}' => Configuration::get('PS_SHOP_NAME') ?: 'Kunstort Lehnin',
        );

        $subject = sprintf($this->l('Residency quote %s'), isset($inquiry['reference']) ? $inquiry['reference'] : '#'.$quote->id_kl_quote);

        $sent = Mail::Send(
            $langId ?: (int) Configuration::get('PS_LANG_DEFAULT'),
            'kl_quote_guest',
            $subject,
            $templateVars,
            $email,
            isset($inquiry['requester_name']) && $inquiry['requester_name'] !== '' ? $inquiry['requester_name'] : null,
            null,
            null,
            array(array('content' => $pdf, 'name' => $filename, 'mime' => 'application/pdf')),
            null,
            _PS_MODULE_DIR_.'hotelreservationsystem/mails/'
        );

        if ($sent && $quote->status === KLQuote::STATUS_DRAFT) {
            $quote->status = KLQuote::STATUS_SENT;
            $quote->update();
        }

        if ($sent) {
            $response['success'] = true;
            $response['message'] = $this->l('Quote emailed to the guest successfully.');
        } else {
            $response['message'] = $this->l('Unable to send the quote email.');
        }

        $response['quotes'] = $this->formatQuoteSummaries(KLQuote::getSummariesForInquiry((int) $quote->id_inquiry), $inquiry);
        $response['quote_permissions'] = $this->buildQuotePermissions($inquiry);

        return $this->ajaxDie(json_encode($response));
    }

    /**
     * @param array<string, mixed> $inquiry
     *
     * @return array<string, bool>
     */
    protected function buildQuotePermissions(array $inquiry)
    {
        $email = isset($inquiry['requester_email']) ? trim((string) $inquiry['requester_email']) : '';

        return array(
            'can_download' => (bool) $this->access('view'),
            'can_email' => (bool) $this->access('edit') && $email !== '' && Validate::isEmail($email),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $inquiry
     *
     * @return array<int, array<string, mixed>>
     */
    protected function formatQuoteSummaries(array $rows, array $inquiry)
    {
        if (empty($rows)) {
            return array();
        }

        $formatted = array();
        foreach ($rows as $row) {
            $formatted[] = $this->formatQuoteSummaryFromModel(
                $this->hydrateQuoteFromRow($row),
                $inquiry,
                array('author_name' => isset($row['author_name']) ? $row['author_name'] : '')
            );
        }

        return $formatted;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return KLQuote
     */
    protected function hydrateQuoteFromRow(array $row)
    {
        $quote = new KLQuote();
        $quote->id = isset($row['id_kl_quote']) ? (int) $row['id_kl_quote'] : 0;
        $quote->id_kl_quote = $quote->id;
        $quote->id_inquiry = isset($row['id_inquiry']) ? (int) $row['id_inquiry'] : 0;
        $quote->id_employee_author = isset($row['id_employee_author']) ? (int) $row['id_employee_author'] : null;
        $quote->status = isset($row['status']) ? (string) $row['status'] : KLQuote::STATUS_DRAFT;
        $quote->currency_iso_code = isset($row['currency_iso_code']) ? (string) $row['currency_iso_code'] : '';
        $quote->net_total_minor = isset($row['net_total_minor']) ? (int) $row['net_total_minor'] : 0;
        $quote->tax_total_minor = isset($row['tax_total_minor']) ? (int) $row['tax_total_minor'] : 0;
        $quote->gross_total_minor = isset($row['gross_total_minor']) ? (int) $row['gross_total_minor'] : 0;
        $quote->valid_from = isset($row['valid_from']) ? $row['valid_from'] : null;
        $quote->valid_until = isset($row['valid_until']) ? $row['valid_until'] : null;
        $quote->date_add = isset($row['date_add']) ? $row['date_add'] : null;
        $quote->date_upd = isset($row['date_upd']) ? $row['date_upd'] : null;
        if (isset($row['payload']) && is_array($row['payload'])) {
            $quote->setPayload($row['payload']);
        }

        return $quote;
    }

    /**
     * @param KLQuote $quote
     * @param array<string, mixed> $inquiry
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    protected function formatQuoteSummaryFromModel(KLQuote $quote, array $inquiry, array $extra = array())
    {
        $langId = $this->context && $this->context->language ? (int) $this->context->language->id : null;
        $status = isset($this->quoteStatusLabels[$quote->status]) ? $this->quoteStatusLabels[$quote->status] : $quote->status;

        return array(
            'id_kl_quote' => (int) ($quote->id ? $quote->id : $quote->id_kl_quote),
            'status' => $quote->status,
            'status_label' => $status,
            'gross_total_minor' => (int) $quote->gross_total_minor,
            'currency_iso_code' => $quote->currency_iso_code,
            'total_display' => $this->formatQuoteMoney($quote->gross_total_minor, $quote->currency_iso_code),
            'valid_until' => $quote->valid_until,
            'valid_until_display' => $quote->valid_until ? Tools::displayDate($quote->valid_until, $langId, true) : '',
            'date_add' => $quote->date_add,
            'date_add_display' => $quote->date_add ? Tools::displayDate($quote->date_add, $langId, true) : '',
            'author_name' => isset($extra['author_name']) ? (string) $extra['author_name'] : '',
            'filename' => $this->buildQuoteFilenameForDisplay($quote, $inquiry),
        );
    }

    /**
     * @param KLQuote $quote
     * @param array<string, mixed> $inquiry
     *
     * @return string
     */
    protected function buildQuoteFilenameForDisplay(KLQuote $quote, array $inquiry)
    {
        $generator = new QuotePdfGenerator();

        return $generator->buildFilename($quote, array('inquiry' => $inquiry));
    }

    /**
     * @param int $amountMinor
     * @param string $currencyIso
     *
     * @return string
     */
    protected function formatQuoteMoney($amountMinor, $currencyIso)
    {
        $value = ((int) $amountMinor) / 100;

        return $currencyIso.' '.number_format($value, 2, '.', ',');
    }

    protected function handleOperationsFollowUpFromNote(array &$response, array $inquiry, $idInquiry, $note)
    {
        if (!HotelInquiryOperationsBridge::isAvailable()) {
            $response['operations_error'] = $this->l('The operations console is not available.');

            return;
        }

        $taskType = (string) Tools::getValue('operation_task_type');
        if (!$taskType) {
            $taskType = 'housekeeping_followup';
        }

        if (!in_array($taskType, $this->getOperationTaskTypeWhitelist(), true)) {
            $response['operations_error'] = $this->l('Select a valid operations follow-up type.');

            return;
        }

        $scheduledFor = $this->normalizeDateTimeValue(Tools::getValue('operation_scheduled_for'));
        if (!$scheduledFor) {
            $response['operations_error'] = $this->l('Missing schedule for the operations follow-up.');

            return;
        }

        $dueEndRaw = Tools::getValue('operation_due_end');
        $dueEnd = $dueEndRaw !== '' ? $this->normalizeDateTimeValue($dueEndRaw) : null;
        if ($dueEndRaw !== '' && !$dueEnd) {
            $response['operations_error'] = $this->l('Invalid due date. Use YYYY-MM-DD HH:MM.');

            return;
        }

        $priority = (int) Tools::getValue('operation_priority', 3);
        if ($priority < 1 || $priority > 5) {
            $priority = 3;
        }

        $reference = trim((string) Tools::getValue('operation_reference'));
        if ($reference === '') {
            $reference = $this->buildOperationReference($taskType, $inquiry);
        }

        $operationNote = trim((string) Tools::getValue('operation_note'));
        $combinedNote = trim($operationNote !== '' ? ($operationNote . "\n\n" . trim((string) $note)) : trim((string) $note));

        $task = HotelInquiryOperationsBridge::createTaskForInquiry($inquiry, array(
            'task_type' => $taskType,
            'reference' => $reference,
            'priority' => $priority,
            'scheduled_for' => $scheduledFor,
            'due_end' => $dueEnd,
            'note' => $combinedNote,
        ));

        if (!$task) {
            $response['operations_error'] = $this->l('Unable to create the operations follow-up.');

            return;
        }

        $taskSummary = array(
            'id' => (int) $task->id,
            'reference' => $task->reference,
            'view_link' => HotelInquiryOperationsBridge::buildTaskViewLink((int) $task->id),
            'status' => $task->status,
            'task_type' => $task->task_type,
        );

        $response['operations_follow_up'] = $taskSummary;
        $response['operations'] = array(
            'tasks' => HotelInquiryOperationsBridge::fetchTasksForInquiry($idInquiry),
            'list_link' => HotelInquiryOperationsBridge::buildTaskListLink($idInquiry),
        );

        $logBody = $this->buildOperationsLogNote($taskSummary);
        if ($logBody !== '' && HotelInquiryNote::addNote($idInquiry, $logBody, $this->context->employee ? (int) $this->context->employee->id : null, false)) {
            $response['notes'] = HotelInquiryNote::getInquiryNotes($idInquiry);
        }
    }

    protected function getEmployeesForBoard()
    {
        $employees = Employee::getEmployees(false);
        $list = array();
        foreach ($employees as $employee) {
            $list[] = array(
                'id_employee' => (int) $employee['id_employee'],
                'name' => trim($employee['firstname'].' '.$employee['lastname']),
            );
        }

        return $list;
    }

    protected function normalizeDate($value)
    {
        if (!$value) {
            return null;
        }
        $timestamp = strtotime($value);
        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    protected function normalizeDateTimeValue($value)
    {
        if (!$value) {
            return null;
        }

        $value = trim((string) $value);
        $value = str_replace('T', ' ', $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }

        $timestamp = strtotime($value);
        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    protected function getOperationTaskTypeWhitelist()
    {
        return array(
            'housekeeping_followup',
            'maintenance_followup',
            'housekeeping_arrival',
            'housekeeping_checkout',
            'maintenance_start',
            'maintenance_release',
            'custom',
        );
    }

    protected function buildOperationReference($taskType, array $inquiry)
    {
        $reference = isset($inquiry['reference']) && $inquiry['reference'] ? $inquiry['reference'] : $this->l('Inquiry follow-up');

        if ($taskType === 'housekeeping_followup') {
            return sprintf($this->l('Housekeeping follow-up for %s'), $reference);
        }
        if ($taskType === 'maintenance_followup') {
            return sprintf($this->l('Maintenance follow-up for %s'), $reference);
        }

        return trim($reference . ' ' . $taskType);
    }

    protected function buildOperationsLogNote(array $taskSummary)
    {
        $lines = array();
        if (!empty($taskSummary['reference'])) {
            $lines[] = sprintf($this->l('Raised operations follow-up: %s'), $taskSummary['reference']);
        }
        if (!empty($taskSummary['view_link'])) {
            $lines[] = sprintf($this->l('View task: %s'), $taskSummary['view_link']);
        }

        return implode("\n", $lines);
    }

    protected function renderInquiryCard($row)
    {
        if (!$this->stageDefinitions) {
            $this->stageDefinitions = HotelInquiry::getStageDefinitions();
        }
        if (!$this->statusDefinitions) {
            $this->statusDefinitions = HotelInquiry::getStatusDefinitions();
        }
        if (!$this->boardEmployees) {
            $this->boardEmployees = $this->getEmployeesForBoard();
        }

        $this->context->smarty->assign(array(
            'inquiry' => $row,
            'stage_definitions' => $this->stageDefinitions,
            'status_definitions' => $this->statusDefinitions,
            'board_employees' => $this->boardEmployees,
        ));

        return $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/inquiries/_partials/card.tpl');
    }

    protected function dispatchInquiryMailNote(array $inquiry, $note)
    {
        $result = array('sent' => false);
        $email = isset($inquiry['requester_email']) ? trim($inquiry['requester_email']) : '';
        if (!$email || !Validate::isEmail($email)) {
            $result['message'] = $this->l('Note saved but no valid requester email was available.');
            return $result;
        }

        $notePlain = trim(strip_tags($note));
        if ($notePlain === '') {
            $notePlain = trim($note);
        }
        $noteHtml = Tools::nl2br(Tools::safeOutput($notePlain));

        $employeeName = '';
        if ($this->context->employee) {
            $employeeName = trim($this->context->employee->firstname.' '.$this->context->employee->lastname);
        }
        if ($employeeName === '') {
            $employeeName = $this->l('Residency team');
        }

        $recipientName = isset($inquiry['requester_name']) ? trim($inquiry['requester_name']) : '';
        $displayName = $recipientName !== '' ? $recipientName : $this->l('there');

        $subject = sprintf($this->l('Update on your residency inquiry %s'), $inquiry['reference']);
        $templateVars = array(
            '{requester_display_name}' => $displayName,
            '{inquiry_reference}' => $inquiry['reference'],
            '{inquiry_subject}' => $inquiry['subject'],
            '{note_plain}' => $notePlain,
            '{note_html}' => $noteHtml,
            '{employee_name}' => $employeeName,
            '{contact_email}' => Configuration::get('PS_SHOP_EMAIL'),
        );

        $sent = Mail::Send(
            (int) $this->context->language->id,
            'inquiry_note',
            $subject,
            $templateVars,
            $email,
            $recipientName !== '' ? $recipientName : null
        );

        if ($sent) {
            $result['sent'] = true;
        } else {
            $result['message'] = $this->l('Note saved but the email could not be sent.');
        }

        return $result;
    }
}

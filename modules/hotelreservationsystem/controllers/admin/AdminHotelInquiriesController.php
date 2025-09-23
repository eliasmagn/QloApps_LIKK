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

class AdminHotelInquiriesController extends ModuleAdminController
{
    protected $boardEmployees = array();
    protected $statusDefinitions = array();
    protected $stageDefinitions = array();

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
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
                ),
                'stageStatuses' => array_map(function ($definition) {
                    return isset($definition['default_status']) ? $definition['default_status'] : null;
                }, $this->stageDefinitions),
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
        } else {
            $response['message'] = $this->l('Unable to save the note.');
        }

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
        } else {
            $response['message'] = $this->l('Inquiry not found.');
        }

        return $this->ajaxDie(json_encode($response));
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

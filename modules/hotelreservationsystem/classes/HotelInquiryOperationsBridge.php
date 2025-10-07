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
 */

class HotelInquiryOperationsBridge
{
    /** @var bool */
    private static $bootstrapped = false;

    public static function isAvailable()
    {
        return self::bootstrap();
    }

    public static function createTaskForInquiry(array $inquiry, array $data)
    {
        if (!self::bootstrap()) {
            return null;
        }

        $idInquiry = (int) $inquiry['id_inquiry'];
        $task = new KlOperationTask();
        $task->reference = (string) $data['reference'];
        $task->task_type = (string) $data['task_type'];
        $task->status = isset($data['status']) && $data['status'] ? (string) $data['status'] : 'pending';
        $task->priority = isset($data['priority']) ? (int) $data['priority'] : 3;
        $task->scheduled_for = (string) $data['scheduled_for'];
        $task->due_end = isset($data['due_end']) && $data['due_end'] ? (string) $data['due_end'] : null;
        $task->timezone = self::resolveTimezone()->getName();
        $task->resource_type = isset($data['resource_type']) && $data['resource_type'] !== '' ? (string) $data['resource_type'] : null;
        $task->id_resource = isset($data['id_resource']) && $data['id_resource'] ? (int) $data['id_resource'] : null;
        $task->context_type = 'inquiry';
        $task->context_id = $idInquiry;
        $task->payload = self::encodePayload(self::buildPayload($inquiry, $data));
        $task->unique_key = self::generateUniqueKey($idInquiry);
        $task->created_by = Context::getContext()->employee ? (int) Context::getContext()->employee->id : null;
        $task->date_add = date('Y-m-d H:i:s');
        $task->date_upd = $task->date_add;

        if (!$task->add()) {
            return null;
        }

        if (!empty($data['note'])) {
            $note = new KlOperationTaskNote();
            $note->id_kl_operation_task = (int) $task->id;
            $note->id_employee = Context::getContext()->employee ? (int) Context::getContext()->employee->id : null;
            $note->note_type = 'comment';
            $note->content = nl2br(trim((string) $data['note']));
            $note->date_add = date('Y-m-d H:i:s');
            $note->date_upd = $note->date_add;
            $note->add();
        }

        return $task;
    }

    public static function fetchTasksForInquiry($idInquiry)
    {
        if (!self::bootstrap()) {
            return array();
        }

        $query = new DbQuery();
        $query->select('`id_kl_operation_task`, `reference`, `status`, `task_type`, `scheduled_for`, `due_end`, `priority`');
        $query->from(KlOperationTask::$definition['table']);
        $query->where('`context_type` = "inquiry"');
        $query->where('`context_id` = ' . (int) $idInquiry);
        $query->orderBy('`scheduled_for` ASC, `id_kl_operation_task` DESC');

        $rows = Db::getInstance()->executeS($query);
        if (!$rows) {
            return array();
        }

        $tasks = array();
        foreach ($rows as $row) {
            $taskId = (int) $row['id_kl_operation_task'];
            $tasks[] = array(
                'id' => $taskId,
                'reference' => $row['reference'],
                'status' => $row['status'],
                'task_type' => $row['task_type'],
                'scheduled_for' => $row['scheduled_for'],
                'due_end' => $row['due_end'],
                'priority' => isset($row['priority']) ? (int) $row['priority'] : null,
                'view_link' => self::buildTaskViewLink($taskId),
            );
        }

        return $tasks;
    }

    public static function buildTaskViewLink($taskId)
    {
        if (!self::bootstrap()) {
            return null;
        }

        return Context::getContext()->link->getAdminLink(
            'AdminKlOperationTasks',
            true,
            array(),
            array(
                'viewkl_operation_task' => 1,
                'id_kl_operation_task' => (int) $taskId,
            )
        );
    }

    public static function buildTaskListLink($idInquiry)
    {
        if (!self::bootstrap()) {
            return null;
        }

        $table = KlOperationTask::$definition['table'];

        return Context::getContext()->link->getAdminLink(
            'AdminKlOperationTasks',
            true,
            array(),
            array(
                'submitFilter' . $table => 1,
                $table . 'Filter_context_type' => 'inquiry',
                $table . 'Filter_context_id' => (int) $idInquiry,
            )
        );
    }

    private static function bootstrap()
    {
        if (self::$bootstrapped) {
            return true;
        }

        if (!Module::isInstalled('kloperations') || !Module::isEnabled('kloperations')) {
            return false;
        }

        $taskPath = _PS_MODULE_DIR_ . 'kloperations/classes/KlOperationTask.php';
        $notePath = _PS_MODULE_DIR_ . 'kloperations/classes/KlOperationTaskNote.php';

        if (!file_exists($taskPath) || !file_exists($notePath)) {
            return false;
        }

        require_once $taskPath;
        require_once $notePath;

        self::$bootstrapped = true;

        return true;
    }

    private static function resolveTimezone()
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

    private static function buildPayload(array $inquiry, array $data)
    {
        $payload = array(
            'origin' => 'inquiry',
            'inquiry_reference' => isset($inquiry['reference']) ? $inquiry['reference'] : null,
            'inquiry_subject' => isset($inquiry['subject']) ? $inquiry['subject'] : null,
            'requester_name' => isset($inquiry['requester_name']) ? $inquiry['requester_name'] : null,
            'requester_email' => isset($inquiry['requester_email']) ? $inquiry['requester_email'] : null,
            'requester_phone' => isset($inquiry['requester_phone']) ? $inquiry['requester_phone'] : null,
            'check_in' => isset($inquiry['check_in']) ? $inquiry['check_in'] : null,
            'check_out' => isset($inquiry['check_out']) ? $inquiry['check_out'] : null,
        );

        if (!empty($data['note'])) {
            $payload['note'] = trim((string) $data['note']);
        }

        if (!empty($data['resource_type'])) {
            $payload['resource_type_hint'] = $data['resource_type'];
        }
        if (!empty($data['id_resource'])) {
            $payload['resource_id_hint'] = (int) $data['id_resource'];
        }

        return $payload;
    }

    private static function encodePayload($payload)
    {
        if (!$payload) {
            return null;
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        return $encoded !== false ? $encoded : null;
    }

    private static function generateUniqueKey($idInquiry)
    {
        return sprintf('inquiry:%d:%s', (int) $idInquiry, sha1(microtime(true) . Tools::passwdGen()));
    }
}

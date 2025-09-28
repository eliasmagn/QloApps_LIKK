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

require_once dirname(__DIR__) . '/classes/KlOperationTaskAssignment.php';

class KlOperationExportService
{
    /**
     * @var Module
     */
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Fetch tasks scheduled between the provided boundaries.
     *
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @param array $statuses
     *
     * @return array
     */
    public function fetchTasks(DateTimeImmutable $from, DateTimeImmutable $to, array $statuses = array())
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(KlOperationTask::$definition['table']);
        $query->where('`scheduled_for` >= "' . pSQL($from->format('Y-m-d H:i:s')) . '"');
        $query->where('`scheduled_for` <= "' . pSQL($to->format('Y-m-d H:i:s')) . '"');
        if (!empty($statuses)) {
            $escaped = array();
            foreach ($statuses as $status) {
                $escaped[] = '"' . pSQL($status) . '"';
            }
            $query->where('`status` IN (' . implode(',', $escaped) . ')');
        }
        $query->orderBy('`scheduled_for` ASC, `priority` ASC');

        $rows = Db::getInstance()->executeS($query) ?: array();

        return $this->hydrateTasks($rows);
    }

    /**
     * Normalise raw task rows.
     *
     * @param array $rows
     *
     * @return array
     */
    public function hydrateTasks(array $rows)
    {
        $hydrated = array();
        foreach ($rows as $row) {
            if (!isset($row['payload_data'])) {
                $row['payload_data'] = array();
            }
            if (!empty($row['payload'])) {
                $decoded = json_decode($row['payload'], true);
                if (is_array($decoded)) {
                    $row['payload_data'] = $decoded;
                }
            }
            $hydrated[] = $row;
        }

        return $this->attachAssignments($hydrated);
    }

    /**
     * Render a CSV export for the provided tasks.
     *
     * @param array $tasks
     *
     * @return string
     */
    public function generateCsv(array $tasks)
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array(
            'Reference',
            'Type',
            'Status',
            'Resource',
            'Resource ID',
            'Scheduled For',
            'Due End',
            'Priority',
            'Context',
            'Context ID',
            'Assignments',
            'Summary',
        ));

        foreach ($tasks as $task) {
            fputcsv($handle, array(
                $task['reference'],
                $task['task_type'],
                $task['status'],
                $task['resource_type'],
                $task['id_resource'],
                $task['scheduled_for'],
                $task['due_end'],
                $task['priority'],
                $task['context_type'],
                $task['context_id'],
                $this->summariseAssignments($task),
                $this->summariseTask($task),
            ));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Render an ICS export for the provided tasks.
     *
     * @param array $tasks
     * @param DateTimeZone $defaultTimezone
     *
     * @return string
     */
    public function generateIcs(array $tasks, DateTimeZone $defaultTimezone)
    {
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kunstort Lehnin//Operations//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-TIMEZONE:' . $defaultTimezone->getName(),
        );

        foreach ($tasks as $task) {
            $lines = array_merge($lines, $this->buildIcsEvent($task, $defaultTimezone));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Produce a readable summary for a task payload.
     *
     * @param array $task
     *
     * @return string
     */
    public function summariseTask(array $task)
    {
        $parts = array();
        $payload = array();
        if (isset($task['payload_data']) && is_array($task['payload_data'])) {
            $payload = $task['payload_data'];
        }

        if (isset($payload['guest_name']) && $payload['guest_name']) {
            $parts[] = $this->module->l('Guest', 'KlOperationExportService') . ': ' . $payload['guest_name'];
        }

        if (isset($payload['reason']) && $payload['reason']) {
            $parts[] = $this->module->l('Reason', 'KlOperationExportService') . ': ' . $payload['reason'];
        }

        if (isset($payload['stay']) && is_array($payload['stay'])) {
            $from = isset($payload['stay']['from']) ? $payload['stay']['from'] : '';
            $to = isset($payload['stay']['to']) ? $payload['stay']['to'] : '';
            if ($from || $to) {
                $parts[] = $this->module->l('Stay', 'KlOperationExportService') . ': ' . trim($from . ' → ' . $to);
            }
        }

        if (isset($payload['room']) && is_array($payload['room'])) {
            $roomParts = array();
            if (!empty($payload['room']['number'])) {
                $roomParts[] = '#' . $payload['room']['number'];
            }
            if (!empty($payload['room_type'])) {
                $roomParts[] = $payload['room_type'];
            }
            if ($roomParts) {
                $parts[] = $this->module->l('Space', 'KlOperationExportService') . ': ' . implode(' ', $roomParts);
            }
        }

        if (isset($payload['task_hint']) && $payload['task_hint']) {
            $parts[] = $payload['task_hint'];
        }

        if (!empty($task['assignments'])) {
            $parts[] = $this->module->l('Assigned', 'KlOperationExportService') . ': ' . $this->summariseAssignments($task);
        }

        if (!$parts) {
            $parts[] = $this->module->l('No additional context provided.', 'KlOperationExportService');
        }

        return implode(' | ', $parts);
    }

    private function buildIcsEvent(array $task, DateTimeZone $defaultTimezone)
    {
        $timezoneName = !empty($task['timezone']) ? $task['timezone'] : $defaultTimezone->getName();
        try {
            $eventTimezone = new DateTimeZone($timezoneName);
        } catch (Exception $exception) {
            $eventTimezone = $defaultTimezone;
            $timezoneName = $defaultTimezone->getName();
        }

        $start = new DateTimeImmutable($task['scheduled_for'], $eventTimezone);
        if (!empty($task['due_end'])) {
            $end = new DateTimeImmutable($task['due_end'], $eventTimezone);
        } else {
            $end = $start->add(new DateInterval('PT1H'));
        }

        $lines = array(
            'BEGIN:VEVENT',
            'UID:' . $this->escapeIcs('kloperations-' . (int) $task['id_kl_operation_task'] . '@kunstortlehnin.local'),
            'SUMMARY:' . $this->escapeIcs($task['reference'] . ' - ' . $task['task_type']),
            'DESCRIPTION:' . $this->escapeIcs($this->summariseTask($task)),
            'DTSTAMP:' . $start->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z'),
            'DTSTART;TZID=' . $this->escapeIcs($timezoneName) . ':' . $start->format('Ymd\THis'),
            'DTEND;TZID=' . $this->escapeIcs($timezoneName) . ':' . $end->format('Ymd\THis'),
            'END:VEVENT',
        );

        return $lines;
    }

    private function escapeIcs($value)
    {
        $value = str_replace(array("\\", ";", ",", "\r", "\n"), array('\\\\', '\\;', '\\,', '', '\\n'), $value);

        return $value;
    }

    private function attachAssignments(array $tasks)
    {
        if (empty($tasks)) {
            return $tasks;
        }

        $ids = array();
        foreach ($tasks as $task) {
            if (isset($task['id_kl_operation_task'])) {
                $ids[] = (int) $task['id_kl_operation_task'];
            }
        }

        $rows = KlOperationTaskAssignment::getAssignmentsForTasks($ids);
        $grouped = array();
        foreach ($rows as $row) {
            $taskId = (int) $row['id_kl_operation_task'];
            if (!isset($grouped[$taskId])) {
                $grouped[$taskId] = array();
            }
            $grouped[$taskId][] = $this->normaliseAssignmentRow($row);
        }

        foreach ($tasks as &$task) {
            $taskId = (int) $task['id_kl_operation_task'];
            $task['assignments'] = isset($grouped[$taskId]) ? $grouped[$taskId] : array();
        }

        return $tasks;
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
                $displayName = $this->module->l('Employee', 'KlOperationExportService');
            }
        } else {
            $displayName = (string) $row['assignee_label'];
            if ($displayName === '') {
                $displayName = (string) $row['assignee_reference'];
            }
            if ($displayName === '') {
                $displayName = $this->module->l('Team', 'KlOperationExportService');
            }
        }

        return array(
            'id_assignment' => (int) $row['id_kl_operation_task_assignment'],
            'assignee_type' => (string) $row['assignee_type'],
            'id_employee' => isset($row['id_employee']) ? (int) $row['id_employee'] : null,
            'assignee_reference' => (string) $row['assignee_reference'],
            'assignee_label' => (string) $row['assignee_label'],
            'display_name' => $displayName,
            'status' => (string) $row['status'],
            'acknowledged_at' => $row['acknowledged_at'],
            'completed_at' => $row['completed_at'],
        );
    }

    private function summariseAssignments(array $task)
    {
        if (empty($task['assignments'])) {
            return $this->module->l('Unassigned', 'KlOperationExportService');
        }

        $parts = array();
        foreach ($task['assignments'] as $assignment) {
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
        $map = array(
            'pending' => $this->module->l('Pending', 'KlOperationExportService'),
            'acknowledged' => $this->module->l('Acknowledged', 'KlOperationExportService'),
            'in_progress' => $this->module->l('In progress', 'KlOperationExportService'),
            'completed' => $this->module->l('Completed', 'KlOperationExportService'),
            'declined' => $this->module->l('Declined', 'KlOperationExportService'),
        );

        return isset($map[$status]) ? $map[$status] : Tools::ucfirst(str_replace('_', ' ', (string) $status));
    }
}

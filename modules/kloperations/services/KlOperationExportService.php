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
    const RESOURCE_TYPE_NONE = '__none__';

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
    public function fetchTasks(DateTimeImmutable $from, DateTimeImmutable $to, array $filters = array())
    {
        $filters = $this->normaliseFilters($filters);

        $query = new DbQuery();
        $query->select('t.*');
        $query->from(KlOperationTask::$definition['table'], 't');
        $query->where('t.`scheduled_for` >= "' . pSQL($from->format('Y-m-d H:i:s')) . '"');
        $query->where('t.`scheduled_for` <= "' . pSQL($to->format('Y-m-d H:i:s')) . '"');

        if (!empty($filters['statuses'])) {
            $escaped = array();
            foreach ($filters['statuses'] as $status) {
                $escaped[] = '"' . pSQL($status) . '"';
            }
            $query->where('t.`status` IN (' . implode(',', $escaped) . ')');
        }

        if (!empty($filters['resource_types'])) {
            $conditions = array();
            $values = array();
            $includeGeneral = false;

            foreach ($filters['resource_types'] as $resourceType) {
                if ($resourceType === self::RESOURCE_TYPE_NONE) {
                    $includeGeneral = true;
                    continue;
                }
                $values[] = '"' . pSQL($resourceType) . '"';
            }

            if (!empty($values)) {
                $conditions[] = 't.`resource_type` IN (' . implode(',', $values) . ')';
            }

            if ($includeGeneral) {
                $conditions[] = '(t.`resource_type` IS NULL OR t.`resource_type` = "")';
            }

            if (!empty($conditions)) {
                $query->where('(' . implode(' OR ', $conditions) . ')');
            }
        }

        if (!empty($filters['team_references'])) {
            $query->innerJoin(
                KlOperationTaskAssignment::$definition['table'],
                'a',
                'a.`id_kl_operation_task` = t.`id_kl_operation_task` AND a.`assignee_type` = "team"'
            );

            $escapedTeams = array();
            foreach ($filters['team_references'] as $reference) {
                $escapedTeams[] = '"' . pSQL($reference) . '"';
            }

            if (!empty($escapedTeams)) {
                $query->where('a.`assignee_reference` IN (' . implode(',', $escapedTeams) . ')');
            }

            $query->groupBy('t.`id_kl_operation_task`');
        }

        $query->orderBy('t.`scheduled_for` ASC, t.`priority` ASC, t.`id_kl_operation_task` ASC');

        $rows = Db::getInstance()->executeS($query) ?: array();

        return $this->hydrateTasks($rows);
    }

    private function normaliseFilters(array $filters)
    {
        $normalised = array(
            'from' => isset($filters['from']) && $filters['from'] instanceof DateTimeImmutable ? $filters['from'] : null,
            'to' => isset($filters['to']) && $filters['to'] instanceof DateTimeImmutable ? $filters['to'] : null,
            'timezone' => isset($filters['timezone']) && $filters['timezone'] instanceof DateTimeZone ? $filters['timezone'] : null,
            'statuses' => $this->normaliseFilterList(isset($filters['statuses']) ? $filters['statuses'] : array()),
            'resource_types' => $this->normaliseFilterList(isset($filters['resource_types']) ? $filters['resource_types'] : array()),
            'team_references' => $this->normaliseFilterList(isset($filters['team_references']) ? $filters['team_references'] : array()),
            'status_labels' => isset($filters['status_labels']) && is_array($filters['status_labels']) ? $filters['status_labels'] : array(),
            'resource_type_labels' => isset($filters['resource_type_labels']) && is_array($filters['resource_type_labels']) ? $filters['resource_type_labels'] : array(),
            'team_labels' => isset($filters['team_labels']) && is_array($filters['team_labels']) ? $filters['team_labels'] : array(),
        );

        return $normalised;
    }

    private function normaliseFilterList($values)
    {
        if (!is_array($values)) {
            $values = array($values);
        }

        $filtered = array();
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $filtered[$value] = true;
        }

        return array_keys($filtered);
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
    public function generateCsv(array $tasks, array $filters = array())
    {
        $handle = fopen('php://temp', 'r+');
        $headers = array(
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
        );

        $summary = $this->summariseFilters($filters);
        if ($summary !== '') {
            $filterRow = array($this->module->l('Filters', 'KlOperationExportService'), $summary);
            while (count($filterRow) < count($headers)) {
                $filterRow[] = '';
            }
            fputcsv($handle, $filterRow);
        }

        fputcsv($handle, $headers);

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
    public function generateIcs(array $tasks, DateTimeZone $defaultTimezone, array $filters = array())
    {
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Kunstort Lehnin//Operations//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-TIMEZONE:' . $defaultTimezone->getName(),
        );

        $summary = $this->summariseFilters($filters);
        if ($summary !== '') {
            $lines[] = 'X-QLO-FILTERS:' . $this->escapeIcs($summary);
        }

        foreach ($tasks as $task) {
            $lines = array_merge($lines, $this->buildIcsEvent($task, $defaultTimezone, $summary));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Build a human-readable summary of active export filters.
     *
     * @param array $filters
     *
     * @return string
     */
    public function summariseFilters(array $filters)
    {
        $filters = $this->normaliseFilters($filters);
        $parts = array();

        if ($filters['from'] instanceof DateTimeImmutable && $filters['to'] instanceof DateTimeImmutable) {
            $windowLabel = $this->module->l('Window', 'KlOperationExportService');
            if ($filters['timezone'] instanceof DateTimeZone) {
                $windowLabel .= ' (' . $filters['timezone']->getName() . ')';
            }

            $parts[] = sprintf(
                '%s: %s → %s',
                $windowLabel,
                $filters['from']->format('Y-m-d'),
                $filters['to']->format('Y-m-d')
            );
        }

        $parts[] = sprintf(
            '%s: %s',
            $this->module->l('Statuses', 'KlOperationExportService'),
            $this->formatFilterList(
                $filters['statuses'],
                $filters['status_labels'],
                $this->module->l('All statuses', 'KlOperationExportService')
            )
        );

        $parts[] = sprintf(
            '%s: %s',
            $this->module->l('Resource types', 'KlOperationExportService'),
            $this->formatResourceFilterList(
                $filters['resource_types'],
                $filters['resource_type_labels']
            )
        );

        $parts[] = sprintf(
            '%s: %s',
            $this->module->l('Teams', 'KlOperationExportService'),
            $this->formatFilterList(
                $filters['team_references'],
                $filters['team_labels'],
                $this->module->l('All teams', 'KlOperationExportService')
            )
        );

        return implode('; ', array_filter($parts));
    }

    /**
     * Build a slugged filename that reflects the current filter scope.
     *
     * @param string $format
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @param array $filters
     *
     * @return string
     */
    public function buildExportFilename($format, DateTimeImmutable $from, DateTimeImmutable $to, array $filters = array())
    {
        $filters = $this->normaliseFilters($filters);
        $format = Tools::strtolower(trim((string) $format));
        if ($format === '') {
            $format = 'csv';
        }

        $segments = array();
        $segments[] = $from->format('Ymd') . '-' . $to->format('Ymd');
        $segments[] = 'status-' . $this->buildFilterSlugSegment($filters['statuses'], 'all');
        $segments[] = 'resources-' . $this->buildFilterSlugSegment($this->normaliseResourceSlugValues($filters['resource_types']), 'all');
        $segments[] = 'teams-' . $this->buildFilterSlugSegment($filters['team_references'], 'all');

        $slug = implode('-', array_filter($segments));
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', Tools::strtolower($slug));
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');

        if ($slug === '') {
            $slug = $from->format('Ymd') . '-' . $to->format('Ymd');
        }

        return sprintf('kloperations-tasks-%s.%s', $slug, $format);
    }

    private function formatFilterList(array $values, array $labels, $fallback)
    {
        if (empty($values)) {
            return $fallback;
        }

        $resolved = array();
        foreach ($values as $value) {
            $resolved[] = isset($labels[$value]) ? $labels[$value] : $value;
        }

        return implode(', ', $resolved);
    }

    private function formatResourceFilterList(array $values, array $labels)
    {
        if (empty($values)) {
            return $this->module->l('All resource types', 'KlOperationExportService');
        }

        $resolved = array();
        foreach ($values as $value) {
            $key = $value === self::RESOURCE_TYPE_NONE ? self::RESOURCE_TYPE_NONE : $value;
            $resolved[] = isset($labels[$key]) ? $labels[$key] : $value;
        }

        return implode(', ', $resolved);
    }

    private function buildFilterSlugSegment(array $values, $fallback)
    {
        if (empty($values)) {
            return $fallback;
        }

        $slugs = array();
        foreach ($values as $value) {
            $slug = $this->slugifyFilterValue($value);
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        if (empty($slugs)) {
            return $fallback;
        }

        return implode('_', $slugs);
    }

    private function slugifyFilterValue($value)
    {
        $value = Tools::strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim(preg_replace('/-+/', '-', $value), '-');

        return $value;
    }

    private function normaliseResourceSlugValues(array $resourceTypes)
    {
        $normalised = array();
        foreach ($resourceTypes as $resourceType) {
            if ($resourceType === self::RESOURCE_TYPE_NONE) {
                $normalised[] = 'general';
            } else {
                $normalised[] = $resourceType;
            }
        }

        return $normalised;
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

    private function buildIcsEvent(array $task, DateTimeZone $defaultTimezone, $filterSummary = '')
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

        $description = $this->summariseTask($task);
        if ($filterSummary !== '') {
            $description .= '\\n' . $this->module->l('Filters', 'KlOperationExportService') . ': ' . $filterSummary;
        }

        $lines = array(
            'BEGIN:VEVENT',
            'UID:' . $this->escapeIcs('kloperations-' . (int) $task['id_kl_operation_task'] . '@kunstortlehnin.local'),
            'SUMMARY:' . $this->escapeIcs($task['reference'] . ' - ' . $task['task_type']),
            'DESCRIPTION:' . $this->escapeIcs($description),
            'DTSTAMP:' . $start->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z'),
            'DTSTART;TZID=' . $this->escapeIcs($timezoneName) . ':' . $start->format('Ymd\THis'),
            'DTEND;TZID=' . $this->escapeIcs($timezoneName) . ':' . $end->format('Ymd\THis'),
        );

        if ($filterSummary !== '') {
            $lines[] = 'X-QLO-FILTERS:' . $this->escapeIcs($filterSummary);
        }

        $lines[] = 'END:VEVENT';

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

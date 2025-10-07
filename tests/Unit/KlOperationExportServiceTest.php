<?php

use PHPUnit\Framework\TestCase;

if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', dirname(dirname(__DIR__)));
}
if (!defined('_PS_MODULE_DIR_')) {
    define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');
}
if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.0.0');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}
if (!defined('_DB_SERVER_')) {
    define('_DB_SERVER_', 'localhost');
}
if (!defined('_DB_USER_')) {
    define('_DB_USER_', 'root');
}
if (!defined('_DB_PASSWD_')) {
    define('_DB_PASSWD_', '');
}
if (!defined('_DB_NAME_')) {
    define('_DB_NAME_', 'prestashop');
}

require_once _PS_ROOT_DIR_ . '/classes/db/Db.php';
require_once _PS_ROOT_DIR_ . '/classes/db/DbQuery.php';
require_once _PS_ROOT_DIR_ . '/classes/Tools.php';
require_once _PS_ROOT_DIR_ . '/classes/module/Module.php';
require_once _PS_ROOT_DIR_ . '/classes/ObjectModel.php';
require_once dirname(dirname(__DIR__)) . '/modules/kloperations/classes/KlOperationTask.php';
require_once dirname(dirname(__DIR__)) . '/modules/kloperations/classes/KlOperationTaskAssignment.php';
require_once dirname(dirname(__DIR__)) . '/modules/kloperations/services/KlOperationExportService.php';

class StubModuleForExport extends Module
{
    public function __construct()
    {
    }

    public function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        return $string;
    }
}

class StubDbForExport
{
    /** @var array<int, array<string, mixed>> */
    public $taskRows = array();

    /** @var array<int, array<string, mixed>> */
    public $assignmentRows = array();

    /**
     * @param DbQuery|string $query
     *
     * @return array<int, array<string, mixed>>
     */
    public function executeS($query)
    {
        $sql = (string) $query;

        if (strpos($sql, '`' . _DB_PREFIX_ . 'kl_operation_task`') !== false) {
            return $this->filterTasksForQuery($sql);
        }

        if (strpos($sql, '`' . _DB_PREFIX_ . 'kl_operation_task_assignment`') !== false) {
            return $this->filterAssignmentsForQuery($sql);
        }

        if (strpos($sql, 'DISTINCT `resource_type`') !== false) {
            return $this->extractDistinctResourceTypes();
        }

        return array();
    }

    /**
     * @param string $value
     * @param bool $htmlOK
     *
     * @return string
     */
    public function escape($value, $htmlOK = false)
    {
        return addslashes($value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractDistinctResourceTypes()
    {
        $seen = array();
        $results = array();

        foreach ($this->taskRows as $row) {
            $value = isset($row['resource_type']) ? trim((string) $row['resource_type']) : '';
            if ($value === '' || isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $results[] = array('resource_type' => $value);
        }

        return $results;
    }

    /**
     * @param string $sql
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterAssignmentsForQuery($sql)
    {
        if (!preg_match('/IN \(([^)]+)\)/', $sql, $matches)) {
            return $this->assignmentRows;
        }

        $rawIds = explode(',', $matches[1]);
        $ids = array();
        foreach ($rawIds as $raw) {
            $ids[] = (int) trim($raw, "'\" ");
        }

        $filtered = array();
        foreach ($this->assignmentRows as $row) {
            if (in_array((int) $row['id_kl_operation_task'], $ids, true)) {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    /**
     * @param string $sql
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterTasksForQuery($sql)
    {
        $rows = $this->taskRows;

        if (preg_match('/`scheduled_for` >= "([^"]+)"/', $sql, $match)) {
            $from = strtotime($match[1]);
            $rows = array_filter($rows, function ($row) use ($from) {
                return strtotime($row['scheduled_for']) >= $from;
            });
        }

        if (preg_match('/`scheduled_for` <= "([^"]+)"/', $sql, $match)) {
            $to = strtotime($match[1]);
            $rows = array_filter($rows, function ($row) use ($to) {
                return strtotime($row['scheduled_for']) <= $to;
            });
        }

        if (preg_match('/`status` IN \(([^)]+)\)/', $sql, $match)) {
            $raw = array_map('trim', explode(',', $match[1]));
            $statuses = array();
            foreach ($raw as $value) {
                $statuses[] = trim($value, "'\"");
            }
            $rows = array_filter($rows, function ($row) use ($statuses) {
                return in_array($row['status'], $statuses, true);
            });
        }

        $includeGeneral = strpos($sql, 'resource_type` IS NULL') !== false;
        if (preg_match('/`resource_type` IN \(([^)]+)\)/', $sql, $match)) {
            $raw = array_map('trim', explode(',', $match[1]));
            $types = array();
            foreach ($raw as $value) {
                $types[] = trim($value, "'\"");
            }
            $rows = array_filter($rows, function ($row) use ($types, $includeGeneral) {
                $type = isset($row['resource_type']) ? trim((string) $row['resource_type']) : '';
                if ($type === '' && $includeGeneral) {
                    return true;
                }

                return in_array($type, $types, true);
            });
        } elseif (!$includeGeneral) {
            // If no explicit filter but includeGeneral is false, keep all rows.
        }

        if (preg_match('/a.`assignee_reference` IN \(([^)]+)\)/', $sql, $match)) {
            $raw = array_map('trim', explode(',', $match[1]));
            $teamRefs = array();
            foreach ($raw as $value) {
                $teamRefs[] = trim($value, "'\"");
            }

            $rows = array_filter($rows, function ($row) use ($teamRefs) {
                foreach ($this->assignmentRows as $assignment) {
                    if ((int) $assignment['id_kl_operation_task'] !== (int) $row['id_kl_operation_task']) {
                        continue;
                    }
                    if ($assignment['assignee_type'] === 'team' && in_array($assignment['assignee_reference'], $teamRefs, true)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $rows = array_values($rows);
        usort($rows, function ($a, $b) {
            $scheduled = strcmp($a['scheduled_for'], $b['scheduled_for']);
            if ($scheduled !== 0) {
                return $scheduled;
            }
            $priorityA = isset($a['priority']) ? (int) $a['priority'] : 0;
            $priorityB = isset($b['priority']) ? (int) $b['priority'] : 0;
            if ($priorityA !== $priorityB) {
                return $priorityA - $priorityB;
            }

            return (int) $a['id_kl_operation_task'] - (int) $b['id_kl_operation_task'];
        });

        return $rows;
    }
}

class KlOperationExportServiceTest extends TestCase
{
    /** @var StubDbForExport */
    private $db;

    /** @var KlOperationExportService */
    private $service;

    protected function setUp(): void
    {
        $this->db = new StubDbForExport();
        Db::setInstanceForTesting($this->db);

        $module = new StubModuleForExport();
        $this->service = new KlOperationExportService($module);
    }

    protected function tearDown(): void
    {
        Db::deleteTestingInstance();
    }

    public function testFetchTasksAppliesFilters()
    {
        $this->db->taskRows = array(
            array(
                'id_kl_operation_task' => 1,
                'reference' => 'HK-1',
                'task_type' => 'housekeeping_arrival',
                'status' => 'pending',
                'resource_type' => 'room',
                'id_resource' => 5,
                'scheduled_for' => '2024-06-01 08:00:00',
                'priority' => 2,
                'payload' => null,
                'timezone' => 'Europe/Berlin',
            ),
            array(
                'id_kl_operation_task' => 2,
                'reference' => 'HK-2',
                'task_type' => 'housekeeping_arrival',
                'status' => 'completed',
                'resource_type' => 'room',
                'id_resource' => 6,
                'scheduled_for' => '2024-06-01 12:00:00',
                'priority' => 3,
                'payload' => null,
                'timezone' => 'Europe/Berlin',
            ),
            array(
                'id_kl_operation_task' => 3,
                'reference' => 'MT-1',
                'task_type' => 'maintenance_start',
                'status' => 'in_progress',
                'resource_type' => 'atelier',
                'id_resource' => 2,
                'scheduled_for' => '2024-06-02 09:00:00',
                'priority' => 1,
                'payload' => null,
                'timezone' => 'Europe/Berlin',
            ),
            array(
                'id_kl_operation_task' => 4,
                'reference' => 'GEN-1',
                'task_type' => 'custom',
                'status' => 'pending',
                'resource_type' => '',
                'id_resource' => null,
                'scheduled_for' => '2024-06-03 10:00:00',
                'priority' => 1,
                'payload' => null,
                'timezone' => 'Europe/Berlin',
            ),
        );

        $this->db->assignmentRows = array(
            array(
                'id_kl_operation_task_assignment' => 10,
                'id_kl_operation_task' => 1,
                'assignee_type' => 'team',
                'assignee_reference' => 'hk',
                'assignee_label' => 'Housekeeping',
                'status' => 'pending',
                'firstname' => '',
                'lastname' => '',
                'email' => '',
                'acknowledged_at' => null,
                'completed_at' => null,
            ),
            array(
                'id_kl_operation_task_assignment' => 11,
                'id_kl_operation_task' => 3,
                'assignee_type' => 'team',
                'assignee_reference' => 'maint',
                'assignee_label' => 'Maintenance',
                'status' => 'pending',
                'firstname' => '',
                'lastname' => '',
                'email' => '',
                'acknowledged_at' => null,
                'completed_at' => null,
            ),
        );

        $from = new DateTimeImmutable('2024-06-01 00:00:00');
        $to = new DateTimeImmutable('2024-06-03 23:59:59');
        $filters = array(
            'statuses' => array('pending', 'in_progress'),
            'resource_types' => array('room'),
            'team_references' => array('hk'),
        );

        $tasks = $this->service->fetchTasks($from, $to, $filters);

        $this->assertCount(1, $tasks);
        $this->assertEquals(1, $tasks[0]['id_kl_operation_task']);
        $this->assertNotEmpty($tasks[0]['assignments']);
        $this->assertEquals('hk', $tasks[0]['assignments'][0]['assignee_reference']);
    }

    public function testGenerateCsvEmbedsFilterSummaryRow()
    {
        $tasks = array(
            array(
                'reference' => 'HK-1',
                'task_type' => 'housekeeping_arrival',
                'status' => 'pending',
                'resource_type' => 'room',
                'id_resource' => 5,
                'scheduled_for' => '2024-06-01 08:00:00',
                'due_end' => null,
                'priority' => 2,
                'context_type' => '',
                'context_id' => '',
                'assignments' => array(),
                'payload_data' => array(),
                'summary' => '',
            ),
        );

        $filters = array(
            'from' => new DateTimeImmutable('2024-06-01 00:00:00'),
            'to' => new DateTimeImmutable('2024-06-02 23:59:59'),
            'timezone' => new DateTimeZone('Europe/Berlin'),
            'statuses' => array('pending'),
            'status_labels' => array('pending' => 'Pending'),
            'resource_types' => array('room'),
            'resource_type_labels' => array('room' => 'Rooms'),
            'team_references' => array(),
            'team_labels' => array(),
        );

        $csv = $this->service->generateCsv($tasks, $filters);
        $lines = preg_split('/\r?\n/', trim($csv));
        $this->assertNotEmpty($lines);

        $firstRow = str_getcsv($lines[0]);
        $this->assertEquals('Filters', $firstRow[0]);
        $this->assertStringContainsString('Window (Europe/Berlin): 2024-06-01', $firstRow[1]);
        $this->assertStringContainsString('Statuses: Pending', $firstRow[1]);
    }

    public function testGenerateIcsIncludesFilterMetadata()
    {
        $tasks = array(
            array(
                'id_kl_operation_task' => 1,
                'reference' => 'HK-1',
                'task_type' => 'housekeeping_arrival',
                'status' => 'pending',
                'resource_type' => 'room',
                'scheduled_for' => '2024-06-01 08:00:00',
                'due_end' => null,
                'timezone' => 'Europe/Berlin',
                'assignments' => array(),
                'payload_data' => array(),
            ),
        );

        $filters = array(
            'from' => new DateTimeImmutable('2024-06-01 00:00:00'),
            'to' => new DateTimeImmutable('2024-06-02 23:59:59'),
            'timezone' => new DateTimeZone('Europe/Berlin'),
            'statuses' => array('pending'),
            'status_labels' => array('pending' => 'Pending'),
            'resource_types' => array('room'),
            'resource_type_labels' => array('room' => 'Rooms'),
            'team_references' => array(),
            'team_labels' => array(),
        );

        $ics = $this->service->generateIcs($tasks, new DateTimeZone('Europe/Berlin'), $filters);

        $this->assertStringContainsString('X-QLO-FILTERS:Window (Europe/Berlin): 2024-06-01', $ics);
        $this->assertStringContainsString('Filters: Window (Europe/Berlin): 2024-06-01', $ics);
        $this->assertSame(2, substr_count($ics, 'X-QLO-FILTERS:'));
    }

    public function testBuildExportFilenameReflectsFilters()
    {
        $filters = array(
            'statuses' => array('pending'),
            'resource_types' => array('room', KlOperationExportService::RESOURCE_TYPE_NONE),
            'team_references' => array('hk'),
        );

        $from = new DateTimeImmutable('2024-06-01 00:00:00');
        $to = new DateTimeImmutable('2024-06-03 23:59:59');

        $filename = $this->service->buildExportFilename('csv', $from, $to, $filters);

        $this->assertEquals('kloperations-tasks-20240601-20240603-status-pending-resources-room-general-teams-hk.csv', $filename);
    }
}


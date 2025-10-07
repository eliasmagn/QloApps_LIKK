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

class KlOperationTimelineSummaryService
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
     * Build an aggregated view of operations tasks for the booking timeline widget.
     *
     * @param DateTimeImmutable|null $reference
     *
     * @return array
     */
    public function buildSummary(?DateTimeImmutable $reference = null)
    {
        $timezone = $this->resolveTimezone();
        if ($reference instanceof DateTimeImmutable) {
            $reference = $reference->setTimezone($timezone);
        } else {
            $reference = new DateTimeImmutable('now', $timezone);
        }

        $startOfDay = $reference->setTime(0, 0, 0);
        $endOfDay = $startOfDay->add(new DateInterval('P1D'))->sub(new DateInterval('PT1S'));
        $startOfTomorrow = $startOfDay->add(new DateInterval('P1D'));
        $endOfTomorrow = $startOfTomorrow->add(new DateInterval('P1D'))->sub(new DateInterval('PT1S'));
        $statuses = array('pending', 'in_progress');

        $exportService = $this->module->getExportService();
        $filter = array('statuses' => $statuses);
        $todayTasks = $exportService->fetchTasks($startOfDay, $endOfDay, $filter);
        $tomorrowTasks = $exportService->fetchTasks($startOfTomorrow, $endOfTomorrow, $filter);
        $overdueTasks = $this->fetchOverdueTasks($startOfDay, $statuses);

        $resources = array();
        $totals = array(
            'overdue' => 0,
            'today' => 0,
            'tomorrow' => 0,
        );

        $this->accumulateTasks($resources, $totals, $overdueTasks, 'overdue');
        $this->accumulateTasks($resources, $totals, $todayTasks, 'today');
        $this->accumulateTasks($resources, $totals, $tomorrowTasks, 'tomorrow');

        uasort($resources, array($this, 'sortResources'));

        return array(
            'generated_at' => $reference->format('Y-m-d H:i:s'),
            'timezone' => $timezone->getName(),
            'resources' => array_values($resources),
            'totals' => $totals,
        );
    }

    private function accumulateTasks(array &$resources, array &$totals, array $tasks, $bucket)
    {
        foreach ($tasks as $task) {
            $resourceKey = !empty($task['resource_type']) ? (string) $task['resource_type'] : 'general';
            if (!isset($resources[$resourceKey])) {
                $resources[$resourceKey] = $this->initialiseResource($resourceKey);
            }

            $resources[$resourceKey]['counts'][$bucket] += 1;
            $resources[$resourceKey]['total'] += 1;
            $totals[$bucket] += 1;
        }
    }

    private function initialiseResource($resourceKey)
    {
        return array(
            'key' => $resourceKey,
            'label' => $this->formatResourceLabel($resourceKey),
            'counts' => array(
                'overdue' => 0,
                'today' => 0,
                'tomorrow' => 0,
            ),
            'total' => 0,
        );
    }

    private function formatResourceLabel($resourceKey)
    {
        $labels = array(
            'general' => $this->module->l('General', 'KlOperationTimelineSummaryService'),
            'room' => $this->module->l('Rooms', 'KlOperationTimelineSummaryService'),
            'atelier' => $this->module->l('Ateliers', 'KlOperationTimelineSummaryService'),
            'facility' => $this->module->l('Facilities', 'KlOperationTimelineSummaryService'),
        );

        if (isset($labels[$resourceKey])) {
            return $labels[$resourceKey];
        }

        return Tools::ucfirst(str_replace('_', ' ', (string) $resourceKey));
    }

    private function fetchOverdueTasks(DateTimeImmutable $cutoff, array $statuses)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(KlOperationTask::$definition['table']);

        if (!empty($statuses)) {
            $escaped = array();
            foreach ($statuses as $status) {
                $escaped[] = '"' . pSQL($status) . '"';
            }
            $query->where('`status` IN (' . implode(',', $escaped) . ')');
        }

        $cutoffValue = pSQL($cutoff->format('Y-m-d H:i:s'));
        $query->where('((`due_end` IS NOT NULL AND `due_end` < "' . $cutoffValue . '") OR (`due_end` IS NULL AND `scheduled_for` < "' . $cutoffValue . '"))');
        $query->orderBy('`scheduled_for` ASC');

        $rows = Db::getInstance()->executeS($query) ?: array();

        return $this->module->getExportService()->hydrateTasks($rows);
    }

    private function resolveTimezone()
    {
        $timezoneName = (string) Configuration::get('PS_TIMEZONE');
        if (!$timezoneName) {
            $timezoneName = @date_default_timezone_get();
        }

        try {
            return new DateTimeZone($timezoneName ?: 'UTC');
        } catch (Exception $exception) {
            return new DateTimeZone('UTC');
        }
    }

    private function sortResources($a, $b)
    {
        return strcasecmp($a['label'], $b['label']);
    }
}

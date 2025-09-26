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

class KlOperationNotificationService
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var KlOperationExportService
     */
    private $exportService;

    /**
     * @var int
     */
    private $langId;

    public function __construct(Module $module)
    {
        $this->module = $module;
        $this->exportService = new KlOperationExportService($module);
        $this->langId = (int) Configuration::get('PS_LANG_DEFAULT');
    }

    /**
     * Send the daily digest email summarising today's workload.
     *
     * @param DateTimeImmutable $targetDate
     * @param array $runMetadata
     *
     * @return int
     */
    public function sendDailyDigest(DateTimeImmutable $targetDate, array $runMetadata = array())
    {
        $recipients = $this->getRecipients();
        if (!$recipients) {
            return 0;
        }

        $startOfDay = $targetDate->setTime(0, 0, 0);
        $endOfDay = $targetDate->setTime(23, 59, 59);

        $upcoming = $this->exportService->fetchTasks($startOfDay, $endOfDay, array('pending', 'in_progress'));
        $overdue = $this->getOverdueTasks($targetDate, false);

        if (empty($upcoming) && empty($overdue)) {
            return 0;
        }

        $counts = array();
        if (isset($runMetadata['task_counts']) && is_array($runMetadata['task_counts'])) {
            $counts = $runMetadata['task_counts'];
        } else {
            $counts = $this->countByType($upcoming);
        }

        $countsSummaryText = $this->formatCountsSummary($counts);
        $templateVars = array_merge($this->getBaseTemplateVars(), array(
            '{digest_date}' => $targetDate->format('Y-m-d'),
            '{counts_summary_text}' => $countsSummaryText,
            '{counts_summary_html}' => $this->convertNewlinesToHtml($countsSummaryText),
            '{upcoming_text}' => $this->renderTasksText($upcoming),
            '{upcoming_html}' => $this->renderTasksHtml($upcoming),
            '{overdue_text}' => $this->renderTasksText($overdue),
            '{overdue_html}' => $this->renderTasksHtml($overdue),
        ));

        $subject = sprintf(
            '%s %s',
            $this->module->l('Daily operations digest', 'KlOperationNotificationService'),
            $targetDate->format('Y-m-d')
        );

        $sent = 0;
        foreach ($recipients as $email) {
            if ($this->sendMail('kloperations_daily_digest', $subject, $templateVars, $email)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send reminders for overdue tasks that have not been nudged recently.
     *
     * @param DateTimeImmutable $now
     *
     * @return int
     */
    public function sendOverdueReminders(DateTimeImmutable $now)
    {
        $recipients = $this->getRecipients();
        if (!$recipients) {
            return 0;
        }

        $overdue = $this->getOverdueTasks($now, true);
        if (!$overdue) {
            return 0;
        }

        $templateVars = array_merge($this->getBaseTemplateVars(), array(
            '{generated_at}' => $now->format('Y-m-d H:i'),
            '{overdue_text}' => $this->renderTasksText($overdue),
            '{overdue_html}' => $this->renderTasksHtml($overdue),
        ));

        $subject = $this->module->l('Overdue operations reminder', 'KlOperationNotificationService');

        $sent = 0;
        foreach ($recipients as $email) {
            if ($this->sendMail('kloperations_overdue_reminder', $subject, $templateVars, $email)) {
                $sent++;
            }
        }

        if ($sent > 0) {
            $ids = array();
            foreach ($overdue as $task) {
                $ids[] = (int) $task['id_kl_operation_task'];
            }
            if ($ids) {
                $nowString = $now->format('Y-m-d H:i:s');
                Db::getInstance()->update(
                    KlOperationTask::$definition['table'],
                    array(
                        'last_reminded_at' => pSQL($nowString),
                        'date_upd' => pSQL($nowString),
                    ),
                    '`id_kl_operation_task` IN (' . implode(',', $ids) . ')'
                );
            }
        }

        return $sent;
    }

    private function getRecipients()
    {
        $raw = trim((string) Configuration::get('KLOPERATIONS_DIGEST_RECIPIENTS'));
        if ($raw === '') {
            return array();
        }

        $parts = preg_split('/[\s,;]+/', $raw);
        $emails = array();
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part && Validate::isEmail($part)) {
                $emails[$part] = $part;
            }
        }

        return array_values($emails);
    }

    private function countByType(array $tasks)
    {
        $counts = array();
        foreach ($tasks as $task) {
            $type = isset($task['task_type']) ? $task['task_type'] : 'unknown';
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
            $counts[$type]++;
        }

        return $counts;
    }

    private function formatCountsSummary(array $counts)
    {
        if (empty($counts)) {
            return $this->module->l('No tasks were generated today.', 'KlOperationNotificationService');
        }

        $lines = array();
        foreach ($counts as $type => $count) {
            $lines[] = sprintf('%s: %d', Tools::ucfirst(str_replace('_', ' ', $type)), (int) $count);
        }

        return implode("\n", $lines);
    }

    private function renderTasksText(array $tasks)
    {
        if (empty($tasks)) {
            return $this->module->l('None', 'KlOperationNotificationService');
        }

        $lines = array();
        foreach ($tasks as $task) {
            $lines[] = sprintf(
                '%s (%s) – %s @ %s',
                $task['reference'],
                $task['task_type'],
                $this->exportService->summariseTask($task),
                $task['scheduled_for']
            );
        }

        return implode("\n", $lines);
    }

    private function renderTasksHtml(array $tasks)
    {
        if (empty($tasks)) {
            return '<p>' . Tools::safeOutput($this->module->l('None', 'KlOperationNotificationService')) . '</p>';
        }

        $items = array();
        foreach ($tasks as $task) {
            $items[] = sprintf(
                '<li><strong>%s</strong> <em>(%s)</em><br />%s<br /><small>%s</small></li>',
                Tools::safeOutput($task['reference']),
                Tools::safeOutput($task['task_type']),
                Tools::safeOutput($this->exportService->summariseTask($task)),
                Tools::safeOutput($task['scheduled_for'])
            );
        }

        return '<ul>' . implode('', $items) . '</ul>';
    }

    private function convertNewlinesToHtml($value)
    {
        $safe = Tools::safeOutput($value);

        return nl2br($safe, false);
    }

    private function getBaseTemplateVars()
    {
        return array(
            '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
        );
    }

    private function sendMail($template, $subject, array $templateVars, $recipient)
    {
        return Mail::Send(
            $this->langId,
            $template,
            $subject,
            $templateVars,
            $recipient,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'kloperations/mails/',
            false,
            (int) $this->module->id
        );
    }

    private function getOverdueTasks(DateTimeImmutable $reference, $forReminder)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from(KlOperationTask::$definition['table']);
        $query->where('`status` IN ("pending", "in_progress")');
        $query->where('`due_end` IS NOT NULL');
        $query->where('`due_end` < "' . pSQL($reference->format('Y-m-d H:i:s')) . '"');

        if ($forReminder) {
            $threshold = $reference->sub(new DateInterval('PT12H'))->format('Y-m-d H:i:s');
            $query->where('(`last_reminded_at` IS NULL OR `last_reminded_at` < "' . pSQL($threshold) . '")');
        }

        $query->orderBy('`due_end` ASC');

        $rows = Db::getInstance()->executeS($query) ?: array();

        return $this->exportService->hydrateTasks($rows);
    }
}

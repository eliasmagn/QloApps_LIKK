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
    const EVENT_DAILY_DIGEST = 'operations.daily_digest';
    const EVENT_OVERDUE_REMINDER = 'operations.overdue_reminder';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_EMAIL_DIGEST = 'email_digest';
    const CHANNEL_LEGACY_EMAIL = 'legacy_email';

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

    /**
     * @var DateTimeZone
     */
    private $defaultTimezone;

    public function __construct(Module $module)
    {
        $this->module = $module;
        $this->exportService = new KlOperationExportService($module);
        $this->langId = (int) Configuration::get('PS_LANG_DEFAULT');
        $this->defaultTimezone = $this->resolveTimezone((string) Configuration::get('PS_TIMEZONE'));
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
        $now = new DateTimeImmutable('now', $this->defaultTimezone);
        $this->processQueuedDeliveries($now);

        $subscriptions = $this->fetchSubscriptions(self::EVENT_DAILY_DIGEST);
        $legacyRecipients = $this->getLegacyRecipients();
        if (empty($subscriptions) && empty($legacyRecipients)) {
            return 0;
        }

        $startOfDay = $targetDate->setTime(0, 0, 0);
        $endOfDay = $targetDate->setTime(23, 59, 59);

        $upcoming = $this->exportService->fetchTasks($startOfDay, $endOfDay, array('statuses' => array('pending', 'in_progress')));
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

        $event = $this->createEvent(
            self::EVENT_DAILY_DIGEST,
            $subject,
            'kloperations_daily_digest',
            $templateVars,
            array('context_type' => 'operations')
        );

        $subscriptionResult = $this->dispatchToSubscriptions(
            $event,
            self::CHANNEL_EMAIL_DIGEST,
            'kloperations_daily_digest',
            $templateVars,
            $subject,
            $now,
            $subscriptions
        );
        $legacyResult = $this->dispatchToLegacyRecipients(
            $event,
            'kloperations_daily_digest',
            $templateVars,
            $subject,
            $legacyRecipients
        );

        if ($subscriptionResult['sent'] > 0 || $legacyResult['sent'] > 0) {
            $this->markEventDispatched($event->id);
        } elseif ($subscriptionResult['queued'] > 0) {
            $this->touchEvent($event->id);
        }

        return (int) ($subscriptionResult['sent'] + $legacyResult['sent']);
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
        $this->processQueuedDeliveries($now);

        $subscriptions = $this->fetchSubscriptions(self::EVENT_OVERDUE_REMINDER);
        $legacyRecipients = $this->getLegacyRecipients();
        if (empty($subscriptions) && empty($legacyRecipients)) {
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

        $event = $this->createEvent(
            self::EVENT_OVERDUE_REMINDER,
            $subject,
            'kloperations_overdue_reminder',
            $templateVars,
            array('context_type' => 'operations')
        );

        $subscriptionResult = $this->dispatchToSubscriptions(
            $event,
            self::CHANNEL_EMAIL,
            'kloperations_overdue_reminder',
            $templateVars,
            $subject,
            $now,
            $subscriptions
        );
        $legacyResult = $this->dispatchToLegacyRecipients(
            $event,
            'kloperations_overdue_reminder',
            $templateVars,
            $subject,
            $legacyRecipients
        );

        if ($subscriptionResult['sent'] > 0 || $legacyResult['sent'] > 0) {
            $this->markEventDispatched($event->id);
        } elseif ($subscriptionResult['queued'] > 0) {
            $this->touchEvent($event->id);
        }

        if (($subscriptionResult['sent'] + $subscriptionResult['queued'] + $legacyResult['sent']) > 0) {
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

        return (int) ($subscriptionResult['sent'] + $legacyResult['sent']);
    }

    private function dispatchToSubscriptions(
        KlNotificationEvent $event,
        $channel,
        $template,
        array $templateVars,
        $subject,
        DateTimeImmutable $now,
        array $subscriptions
    ) {
        $result = array('sent' => 0, 'queued' => 0, 'failed' => 0);

        foreach ($subscriptions as $subscription) {
            if (!$this->isChannelEnabled($subscription, $channel)) {
                continue;
            }

            $delivery = new KlNotificationDelivery();
            $delivery->id_kl_notification_event = (int) $event->id;
            $delivery->id_kl_notification_subscription = (int) $subscription['id_kl_notification_subscription'];
            $delivery->id_employee = (int) $subscription['id_employee'];
            $delivery->id_lang = (int) $subscription['id_lang'];
            $delivery->channel = $channel;
            $delivery->recipient = $subscription['email'];
            $delivery->status = 'pending';

            $quietUntil = $this->calculateQuietUntil($subscription, $now);
            if ($quietUntil instanceof DateTimeImmutable) {
                $delivery->status = 'queued';
                $delivery->quiet_until = $quietUntil->format('Y-m-d H:i:s');
                $delivery->metadata = $this->appendMetadata($delivery->metadata, array(
                    'quiet_hours' => array(
                        'start' => $subscription['quiet_hours_start'],
                        'end' => $subscription['quiet_hours_end'],
                        'timezone' => $subscription['timezone'] ? $subscription['timezone'] : $now->getTimezone()->getName(),
                    ),
                ));
                $delivery->add();
                $result['queued']++;
                continue;
            }

            $recipientName = $this->formatRecipientName($subscription);
            $langId = (int) $subscription['id_lang'] ?: $this->langId;
            if ($this->sendMail($template, $subject, $templateVars, $subscription['email'], $recipientName, $langId)) {
                $delivery->status = 'sent';
                $delivery->sent_at = date('Y-m-d H:i:s');
                $result['sent']++;
            } else {
                $delivery->status = 'failed';
                $result['failed']++;
            }

            $delivery->add();
        }

        return $result;
    }

    private function dispatchToLegacyRecipients(
        KlNotificationEvent $event,
        $template,
        array $templateVars,
        $subject,
        array $recipients
    ) {
        $result = array('sent' => 0, 'queued' => 0, 'failed' => 0);

        foreach ($recipients as $email) {
            $delivery = new KlNotificationDelivery();
            $delivery->id_kl_notification_event = (int) $event->id;
            $delivery->channel = self::CHANNEL_LEGACY_EMAIL;
            $delivery->recipient = $email;
            $delivery->status = 'pending';

            if ($this->sendMail($template, $subject, $templateVars, $email)) {
                $delivery->status = 'sent';
                $delivery->sent_at = date('Y-m-d H:i:s');
                $result['sent']++;
            } else {
                $delivery->status = 'failed';
                $result['failed']++;
            }

            $delivery->add();
        }

        return $result;
    }

    private function processQueuedDeliveries(DateTimeImmutable $now)
    {
        $query = new DbQuery();
        $query->select('d.*, e.`subject`, e.`payload`, s.`channel_email`, s.`channel_digest`, s.`channel_calendar`, s.`quiet_hours_start`, s.`quiet_hours_end`, s.`timezone`, emp.`email` AS `employee_email`, emp.`firstname`, emp.`lastname`, emp.`id_lang` AS `employee_lang`, emp.`active` AS `employee_active`');
        $query->from(KlNotificationDelivery::$definition['table'], 'd');
        $query->innerJoin(KlNotificationEvent::$definition['table'], 'e', 'e.`id_kl_notification_event` = d.`id_kl_notification_event`');
        $query->leftJoin(KlNotificationSubscription::$definition['table'], 's', 's.`id_kl_notification_subscription` = d.`id_kl_notification_subscription`');
        $query->leftJoin('employee', 'emp', 'emp.`id_employee` = d.`id_employee`');
        $query->where('d.`status` = "queued"');
        $query->where('d.`quiet_until` IS NOT NULL');
        $query->where('d.`quiet_until` <= "' . pSQL($now->format('Y-m-d H:i:s')) . '"');

        $rows = Db::getInstance()->executeS($query) ?: array();
        if (!$rows) {
            return 0;
        }

        $sent = 0;
        foreach ($rows as $row) {
            $delivery = new KlNotificationDelivery((int) $row['id_kl_notification_delivery']);
            if (!Validate::isLoadedObject($delivery)) {
                continue;
            }

            if ((int) $row['id_kl_notification_subscription']) {
                if (!(int) $row['employee_active']) {
                    $delivery->status = 'cancelled';
                    $delivery->metadata = $this->appendMetadata($delivery->metadata, array('reason' => 'employee_inactive'));
                    $delivery->quiet_until = null;
                    $delivery->date_upd = date('Y-m-d H:i:s');
                    $delivery->update();
                    continue;
                }

                if (!$this->isChannelEnabled($row, $row['channel'])) {
                    $delivery->status = 'cancelled';
                    $delivery->metadata = $this->appendMetadata($delivery->metadata, array('reason' => 'channel_opt_out'));
                    $delivery->quiet_until = null;
                    $delivery->date_upd = date('Y-m-d H:i:s');
                    $delivery->update();
                    continue;
                }
            }

            $payload = $this->decodeEventPayload($row['payload']);
            if (empty($payload['template']) || !isset($payload['vars']) || !is_array($payload['vars'])) {
                $delivery->status = 'failed';
                $delivery->metadata = $this->appendMetadata($delivery->metadata, array('reason' => 'invalid_payload'));
                $delivery->quiet_until = null;
                $delivery->date_upd = date('Y-m-d H:i:s');
                $delivery->update();
                continue;
            }

            $recipientEmail = $row['employee_email'] ? $row['employee_email'] : $delivery->recipient;
            $recipientName = trim(($row['firstname'] ? $row['firstname'] : '') . ' ' . ($row['lastname'] ? $row['lastname'] : ''));
            $langId = (int) $row['employee_lang'];
            if (!$langId) {
                $langId = (int) $delivery->id_lang ?: $this->langId;
            }

            if ($this->sendMail($payload['template'], $row['subject'], $payload['vars'], $recipientEmail, $recipientName ?: null, $langId)) {
                $delivery->status = 'sent';
                $delivery->sent_at = date('Y-m-d H:i:s');
                $delivery->recipient = $recipientEmail;
                $delivery->quiet_until = null;
                $delivery->date_upd = date('Y-m-d H:i:s');
                $delivery->update();
                $this->markEventDispatched($delivery->id_kl_notification_event);
                $sent++;
            } else {
                $delivery->status = 'failed';
                $delivery->recipient = $recipientEmail;
                $delivery->quiet_until = null;
                $delivery->date_upd = date('Y-m-d H:i:s');
                $delivery->update();
            }
        }

        return $sent;
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

    private function sendMail($template, $subject, array $templateVars, $recipient, $recipientName = null, $langId = null)
    {
        $langId = $langId ? (int) $langId : $this->langId;

        return Mail::Send(
            $langId,
            $template,
            $subject,
            $templateVars,
            $recipient,
            $recipientName,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'kloperations/mails/',
            false,
            (int) $this->module->id
        );
    }

    private function getLegacyRecipients()
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

    private function fetchSubscriptions($eventType)
    {
        $query = new DbQuery();
        $query->select('s.*, e.`email`, e.`firstname`, e.`lastname`, e.`id_lang`');
        $query->from(KlNotificationSubscription::$definition['table'], 's');
        $query->innerJoin('employee', 'e', 'e.`id_employee` = s.`id_employee`');
        $query->where('s.`event_type` = "' . pSQL($eventType) . '"');
        $query->where('e.`active` = 1');

        $rows = Db::getInstance()->executeS($query) ?: array();

        $filtered = array();
        foreach ($rows as $row) {
            if (!Validate::isEmail($row['email'])) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    private function isChannelEnabled(array $subscription, $channel)
    {
        $column = $this->channelColumn($channel);
        if (!$column) {
            return false;
        }

        return !empty($subscription[$column]);
    }

    private function channelColumn($channel)
    {
        switch ($channel) {
            case self::CHANNEL_EMAIL:
                return 'channel_email';
            case self::CHANNEL_EMAIL_DIGEST:
                return 'channel_digest';
            default:
                return null;
        }
    }

    private function calculateQuietUntil(array $subscription, DateTimeImmutable $now)
    {
        $start = isset($subscription['quiet_hours_start']) ? trim($subscription['quiet_hours_start']) : '';
        $end = isset($subscription['quiet_hours_end']) ? trim($subscription['quiet_hours_end']) : '';
        if ($start === '' || $end === '') {
            return null;
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
            return null;
        }

        if ($start === $end) {
            return null;
        }

        $timezone = $this->resolveTimezone(isset($subscription['timezone']) ? $subscription['timezone'] : '');
        $localNow = $now->setTimezone($timezone);

        list($startHour, $startMinute) = array_map('intval', explode(':', $start));
        list($endHour, $endMinute) = array_map('intval', explode(':', $end));

        $currentMinutes = (int) $localNow->format('H') * 60 + (int) $localNow->format('i');
        $startMinutes = ($startHour * 60) + $startMinute;
        $endMinutes = ($endHour * 60) + $endMinute;

        $inQuiet = false;
        if ($startMinutes < $endMinutes) {
            $inQuiet = ($currentMinutes >= $startMinutes && $currentMinutes < $endMinutes);
        } else {
            $inQuiet = ($currentMinutes >= $startMinutes || $currentMinutes < $endMinutes);
        }

        if (!$inQuiet) {
            return null;
        }

        $quietEnd = $localNow->setTime($endHour, $endMinute, 0);
        if ($startMinutes > $endMinutes) {
            if ($currentMinutes >= $startMinutes) {
                $quietEnd = $quietEnd->modify('+1 day');
            }
        } elseif ($currentMinutes >= $endMinutes) {
            $quietEnd = $quietEnd->modify('+1 day');
        }

        return $quietEnd->setTimezone($now->getTimezone());
    }

    private function createEvent($eventType, $subject, $template, array $templateVars, array $context = array())
    {
        $event = new KlNotificationEvent();
        $event->event_type = $eventType;
        $event->subject = $subject;
        $event->payload = $this->encodePayload(array(
            'template' => $template,
            'vars' => $templateVars,
        ));
        $event->context_type = isset($context['context_type']) ? $context['context_type'] : null;
        $event->context_id = isset($context['context_id']) ? (int) $context['context_id'] : null;
        $timestamp = date('Y-m-d H:i:s');
        $event->scheduled_for = $timestamp;
        $event->dispatched_at = null;
        $event->date_add = $timestamp;
        $event->date_upd = $timestamp;
        $event->add();

        return $event;
    }

    private function markEventDispatched($eventId)
    {
        $timestamp = date('Y-m-d H:i:s');
        Db::getInstance()->update(
            KlNotificationEvent::$definition['table'],
            array(
                'dispatched_at' => pSQL($timestamp),
                'date_upd' => pSQL($timestamp),
            ),
            '`id_kl_notification_event` = ' . (int) $eventId
        );
    }

    private function touchEvent($eventId)
    {
        Db::getInstance()->update(
            KlNotificationEvent::$definition['table'],
            array('date_upd' => pSQL(date('Y-m-d H:i:s'))),
            '`id_kl_notification_event` = ' . (int) $eventId
        );
    }

    private function encodePayload(array $payload)
    {
        $encoded = Tools::jsonEncode($payload);

        return $encoded !== false ? $encoded : '{}';
    }

    private function decodeEventPayload($payload)
    {
        if (!$payload) {
            return array();
        }

        $decoded = Tools::jsonDecode($payload, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }

    private function appendMetadata($existing, array $payload)
    {
        $current = array();
        if ($existing) {
            $decoded = Tools::jsonDecode($existing, true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        $merged = array_merge($current, $payload);

        return $this->encodePayload($merged);
    }

    private function formatRecipientName(array $subscription)
    {
        $parts = array();
        if (!empty($subscription['firstname'])) {
            $parts[] = trim($subscription['firstname']);
        }
        if (!empty($subscription['lastname'])) {
            $parts[] = trim($subscription['lastname']);
        }

        $name = trim(implode(' ', $parts));

        return $name !== '' ? $name : null;
    }

    private function resolveTimezone($timezoneName)
    {
        if (!$timezoneName) {
            $timezoneName = @date_default_timezone_get();
        }

        try {
            return new DateTimeZone($timezoneName);
        } catch (Exception $exception) {
            return new DateTimeZone(@date_default_timezone_get());
        }
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

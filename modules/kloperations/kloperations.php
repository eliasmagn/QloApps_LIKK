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
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to a newer
 * versions in the future. If you wish to customize this module for your needs
 * please refer to https://store.webkul.com/customisation-guidelines for more information.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/KlOperationRun.php';
require_once __DIR__ . '/classes/KlOperationTask.php';
require_once __DIR__ . '/classes/KlOperationTaskAssignment.php';
require_once __DIR__ . '/classes/KlOperationTaskNote.php';
require_once __DIR__ . '/classes/KlNotificationSubscription.php';
require_once __DIR__ . '/classes/KlNotificationEvent.php';
require_once __DIR__ . '/classes/KlNotificationDelivery.php';
require_once __DIR__ . '/services/KlOperationTaskGenerator.php';
require_once __DIR__ . '/services/KlOperationNotificationService.php';
require_once __DIR__ . '/services/KlOperationExportService.php';
require_once __DIR__ . '/services/KlOperationTimelineSummaryService.php';
require_once _PS_MODULE_DIR_ . 'hotelreservationsystem/classes/HotelBookingDetail.php';
require_once _PS_MODULE_DIR_ . 'hotelreservationsystem/classes/KLStoryAvailabilityCache.php';

class Kloperations extends Module
{
    const ADMIN_TAB_CLASS = 'AdminKlOperationTasks';
    const ADMIN_NOTIFICATION_TAB_CLASS = 'AdminKlNotificationSubscriptions';

    public function __construct()
    {
        $this->name = 'kloperations';
        $this->tab = 'administration';
        $this->version = '1.3.0';
        $this->author = 'Kunstort Lehnin';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Operations Automation');
        $this->description = $this->l('Generates housekeeping and maintenance tasks from bookings and inquiries.');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->registerHooks()) {
            return false;
        }

        if (!$this->installTab(self::ADMIN_TAB_CLASS, 'Operations Tasks', 'AdminHotelReservationSystemManagement')) {
            return false;
        }

        if (!$this->installTab(self::ADMIN_NOTIFICATION_TAB_CLASS, 'Notification Preferences', self::ADMIN_TAB_CLASS)) {
            return false;
        }

        if (!$this->installDatabase()) {
            return false;
        }

        Configuration::updateValue('KLOPERATIONS_DIGEST_RECIPIENTS', '');
        Configuration::updateValue('KLOPERATIONS_TEAMS', '');

        return true;
    }

    public function uninstall()
    {
        if (!$this->uninstallTab(self::ADMIN_NOTIFICATION_TAB_CLASS)) {
            return false;
        }

        if (!$this->uninstallTab(self::ADMIN_TAB_CLASS)) {
            return false;
        }

        Configuration::deleteByName('KLOPERATIONS_DIGEST_RECIPIENTS');
        Configuration::deleteByName('KLOPERATIONS_TEAMS');

        return parent::uninstall();
    }

    private function registerHooks()
    {
        $hooks = array(
            'actionCronJob',
            'actionObjectHotelBookingDetailAddAfter',
            'actionObjectHotelBookingDetailUpdateAfter',
            'displayAdminRoomsBookingCalendarAfter',
        );

        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    public function installDatabase()
    {
        $sql = array(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'kl_operation_run` (
                `id_kl_operation_run` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `run_type` VARCHAR(64) NOT NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT "started",
                `started_at` DATETIME NOT NULL,
                `completed_at` DATETIME DEFAULT NULL,
                `timezone` VARCHAR(64) NOT NULL,
                `metadata` LONGTEXT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_operation_run`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'kl_operation_task` (
                `id_kl_operation_task` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_kl_operation_run` INT UNSIGNED DEFAULT NULL,
                `reference` VARCHAR(64) NOT NULL,
                `task_type` VARCHAR(64) NOT NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT "pending",
                `resource_type` VARCHAR(32) DEFAULT NULL,
                `id_resource` INT UNSIGNED DEFAULT NULL,
                `context_type` VARCHAR(32) DEFAULT NULL,
                `context_id` INT UNSIGNED DEFAULT NULL,
                `scheduled_for` DATETIME NOT NULL,
                `due_end` DATETIME DEFAULT NULL,
                `timezone` VARCHAR(64) NOT NULL,
                `payload` LONGTEXT NULL,
                `unique_key` VARCHAR(128) NOT NULL,
                `priority` TINYINT UNSIGNED NOT NULL DEFAULT 3,
                `created_by` INT UNSIGNED DEFAULT NULL,
                `completed_by` INT UNSIGNED DEFAULT NULL,
                `completed_at` DATETIME DEFAULT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_operation_task`),
                UNIQUE KEY `uniq_kl_operation_task_key` (`unique_key`),
                KEY `idx_kl_operation_task_run` (`id_kl_operation_run`),
                KEY `idx_kl_operation_task_context` (`context_type`, `context_id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'kl_operation_task_assignment` (
                `id_kl_operation_task_assignment` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_kl_operation_task` INT UNSIGNED NOT NULL,
                `id_employee` INT UNSIGNED DEFAULT NULL,
                `assignee_type` VARCHAR(32) NOT NULL DEFAULT "employee",
                `assignee_reference` VARCHAR(64) DEFAULT NULL,
                `assignee_label` VARCHAR(128) DEFAULT NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT "pending",
                `acknowledged_at` DATETIME DEFAULT NULL,
                `completed_at` DATETIME DEFAULT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_operation_task_assignment`),
                KEY `idx_operation_assignment_task` (`id_kl_operation_task`),
                KEY `idx_operation_assignment_employee` (`id_employee`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'kl_operation_task_note` (
                `id_kl_operation_task_note` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_kl_operation_task` INT UNSIGNED NOT NULL,
                `id_employee` INT UNSIGNED DEFAULT NULL,
                `note_type` VARCHAR(32) NOT NULL DEFAULT "comment",
                `content` LONGTEXT NOT NULL,
                `attachments` LONGTEXT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_operation_task_note`),
                KEY `idx_operation_note_task` (`id_kl_operation_task`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'kl_notification_subscription` (
                `id_kl_notification_subscription` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_employee` INT UNSIGNED NOT NULL,
                `event_type` VARCHAR(128) NOT NULL,
                `channel_email` TINYINT(1) NOT NULL DEFAULT 1,
                `channel_digest` TINYINT(1) NOT NULL DEFAULT 1,
                `channel_calendar` TINYINT(1) NOT NULL DEFAULT 0,
                `quiet_hours_start` CHAR(5) DEFAULT NULL,
                `quiet_hours_end` CHAR(5) DEFAULT NULL,
                `timezone` VARCHAR(64) DEFAULT NULL,
                `metadata` LONGTEXT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_notification_subscription`),
                UNIQUE KEY `uniq_notification_subscription` (`id_employee`, `event_type`),
                KEY `idx_notification_subscription_event` (`event_type`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'kl_notification_event` (
                `id_kl_notification_event` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `event_type` VARCHAR(128) NOT NULL,
                `subject` VARCHAR(255) NOT NULL,
                `payload` LONGTEXT NULL,
                `context_type` VARCHAR(64) DEFAULT NULL,
                `context_id` INT UNSIGNED DEFAULT NULL,
                `scheduled_for` DATETIME DEFAULT NULL,
                `dispatched_at` DATETIME DEFAULT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_notification_event`),
                KEY `idx_notification_event_type` (`event_type`),
                KEY `idx_notification_event_context` (`context_type`, `context_id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'kl_notification_delivery` (
                `id_kl_notification_delivery` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_kl_notification_event` INT UNSIGNED NOT NULL,
                `id_kl_notification_subscription` INT UNSIGNED DEFAULT NULL,
                `id_employee` INT UNSIGNED DEFAULT NULL,
                `id_lang` INT UNSIGNED DEFAULT NULL,
                `channel` VARCHAR(64) NOT NULL,
                `recipient` VARCHAR(255) DEFAULT NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT "pending",
                `quiet_until` DATETIME DEFAULT NULL,
                `metadata` LONGTEXT NULL,
                `sent_at` DATETIME DEFAULT NULL,
                `acknowledged_at` DATETIME DEFAULT NULL,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_notification_delivery`),
                KEY `idx_notification_delivery_event` (`id_kl_notification_event`),
                KEY `idx_notification_delivery_subscription` (`id_kl_notification_subscription`),
                KEY `idx_notification_delivery_employee` (`id_employee`),
                KEY `idx_notification_delivery_status` (`status`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        foreach ($sql as $statement) {
            if (!Db::getInstance()->execute($statement)) {
                return false;
            }
        }

        $this->addIndexIfMissing('kl_notification_delivery', 'idx_notification_delivery_event', '`id_kl_notification_event`');
        $this->addIndexIfMissing('kl_notification_delivery', 'idx_notification_delivery_subscription', '`id_kl_notification_subscription`');
        $this->addIndexIfMissing('kl_notification_delivery', 'idx_notification_delivery_employee', '`id_employee`');
        $this->addIndexIfMissing('kl_notification_delivery', 'idx_notification_delivery_status', '`status`');
        $this->addIndexIfMissing('kl_notification_subscription', 'idx_notification_subscription_event', '`event_type`');

        $this->addColumnIfMissing('kl_operation_task', 'last_reminded_at', '`last_reminded_at` DATETIME DEFAULT NULL AFTER `completed_at`');
        $this->addColumnIfMissing('kl_operation_task_assignment', 'assignee_reference', '`assignee_reference` VARCHAR(64) DEFAULT NULL AFTER `assignee_type`');
        $this->addColumnIfMissing('kl_operation_task_assignment', 'assignee_label', '`assignee_label` VARCHAR(128) DEFAULT NULL AFTER `assignee_reference`');
        $this->addColumnIfMissing('kl_operation_task_assignment', 'acknowledged_at', '`acknowledged_at` DATETIME DEFAULT NULL AFTER `status`');
        $this->addColumnIfMissing('kl_operation_task_assignment', 'completed_at', '`completed_at` DATETIME DEFAULT NULL AFTER `acknowledged_at`');
        $this->addIndexIfMissing('kl_operation_task_assignment', 'idx_operation_assignment_employee', '`id_employee`');

        return true;
    }

    public function hookActionCronJob($params)
    {
        $generator = $this->getTaskGenerator();
        $notificationService = $this->getNotificationService();
        $timezoneName = (string) Configuration::get('PS_TIMEZONE');
        if (empty($timezoneName)) {
            $timezoneName = @date_default_timezone_get();
        }
        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (Exception $exception) {
            $timezone = new DateTimeZone(date_default_timezone_get());
        }

        $now = new DateTimeImmutable('now', $timezone);
        $today = $now->setTime(0, 0, 0);
        $runMetadata = $generator->runDaily($today);
        $notificationService->sendDailyDigest($today, $runMetadata);
        $notificationService->sendOverdueReminders($now);
    }

    public function hookActionObjectHotelBookingDetailAddAfter($params)
    {
        $this->handleBookingLifecycleChange($params);
    }

    public function hookActionObjectHotelBookingDetailUpdateAfter($params)
    {
        $this->handleBookingLifecycleChange($params);
    }

    private function handleBookingLifecycleChange($params)
    {
        if (!isset($params['object']) || !$params['object'] instanceof HotelBookingDetail) {
            return;
        }

        /** @var HotelBookingDetail $booking */
        $booking = $params['object'];
        if (!$booking->id) {
            return;
        }

        $this->getTaskGenerator()->syncBookingTasks($booking);
        KLStoryAvailabilityCache::invalidateAll();
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::ADMIN_TAB_CLASS));

        return '';
    }

    private function getTaskGenerator()
    {
        return new KlOperationTaskGenerator($this);
    }

    private function getNotificationService()
    {
        return new KlOperationNotificationService($this);
    }

    public function getExportService()
    {
        return new KlOperationExportService($this);
    }

    public function getTimelineSummaryService()
    {
        return new KlOperationTimelineSummaryService($this);
    }

    public function hookDisplayAdminRoomsBookingCalendarAfter($params)
    {
        $summary = $this->getTimelineSummaryService()->buildSummary();
        $resources = array();
        foreach ($summary['resources'] as $resource) {
            $resource['link_all'] = $this->buildConsoleLink($this->buildResourceFilterParams($resource['key']));
            $resource['links'] = array(
                'overdue' => $this->buildConsoleLink($this->buildResourceFilterParams($resource['key'], 'overdue')),
                'today' => $this->buildConsoleLink($this->buildResourceFilterParams($resource['key'], 'today')),
                'tomorrow' => $this->buildConsoleLink($this->buildResourceFilterParams($resource['key'], 'tomorrow')),
            );
            $resources[] = $resource;
        }

        $summary['resources'] = $resources;

        $this->context->smarty->assign(array(
            'kloperations_timeline_widget' => array(
                'summary' => $summary,
                'console_url' => $this->buildConsoleLink(),
                'buckets' => array('overdue', 'today', 'tomorrow'),
            ),
        ));

        return $this->display(__FILE__, 'views/templates/hook/admin_timeline_summary.tpl');
    }

    private function installTab($className, $name, $parentClassName = 'AdminParentModules')
    {
        $idParent = 0;
        if ($parentClassName) {
            $idParent = (int) Tab::getIdFromClassName($parentClassName);
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->id_parent = $idParent;
        $tab->module = $this->name;
        $tab->icon = 'task';

        foreach (Language::getLanguages(true) as $language) {
            $tab->name[$language['id_lang']] = $this->l($name);
        }

        if (!$tab->add()) {
            return false;
        }

        return true;
    }

    private function addColumnIfMissing($table, $column, $definition)
    {
        $tableName = _DB_PREFIX_ . pSQL($table);
        $columnName = pSQL($column);
        $exists = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . $tableName . '` LIKE "' . $columnName . '"');
        if (!$exists) {
            Db::getInstance()->execute('ALTER TABLE `' . $tableName . '` ADD ' . $definition);
        }
    }

    private function addIndexIfMissing($table, $indexName, $definition)
    {
        $tableName = _DB_PREFIX_ . pSQL($table);
        $indexName = pSQL($indexName);
        $exists = Db::getInstance()->executeS('SHOW INDEX FROM `' . $tableName . '` WHERE Key_name = "' . $indexName . '"');
        if (!$exists) {
            Db::getInstance()->execute('ALTER TABLE `' . $tableName . '` ADD INDEX `' . $indexName . '` (' . $definition . ')');
        }
    }

    private function buildConsoleLink(array $params = array())
    {
        $link = $this->context && $this->context->link ? $this->context->link->getAdminLink(self::ADMIN_TAB_CLASS) : 'index.php?controller=' . self::ADMIN_TAB_CLASS;
        if (empty($params)) {
            return $link;
        }

        $separator = strpos($link, '?') === false ? '?' : '&';

        return $link . $separator . http_build_query($params);
    }

    private function buildResourceFilterParams($resourceKey, $bucket = null)
    {
        $params = array('submitFilter' . KlOperationTask::$definition['table'] => 1);
        if ($resourceKey !== 'general') {
            $params[KlOperationTask::$definition['table'] . 'Filter_resource_type'] = $resourceKey;
        }

        if ($bucket) {
            $params['kloperations_widget_bucket'] = $bucket;
        }

        return $params;
    }

    private function uninstallTab($className)
    {
        $idTab = (int) Tab::getIdFromClassName($className);
        if (!$idTab) {
            return true;
        }

        $tab = new Tab($idTab);
        if (Validate::isLoadedObject($tab)) {
            return (bool) $tab->delete();
        }

        return true;
    }
}

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
require_once __DIR__ . '/services/KlOperationTaskGenerator.php';
require_once __DIR__ . '/services/KlOperationNotificationService.php';
require_once __DIR__ . '/services/KlOperationExportService.php';
require_once _PS_MODULE_DIR_ . 'hotelreservationsystem/classes/HotelBookingDetail.php';

class Kloperations extends Module
{
    const ADMIN_TAB_CLASS = 'AdminKlOperationTasks';

    public function __construct()
    {
        $this->name = 'kloperations';
        $this->tab = 'administration';
        $this->version = '1.1.0';
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

        if (!$this->installDatabase()) {
            return false;
        }

        Configuration::updateValue('KLOPERATIONS_DIGEST_RECIPIENTS', '');

        return true;
    }

    public function uninstall()
    {
        if (!$this->uninstallTab(self::ADMIN_TAB_CLASS)) {
            return false;
        }

        Configuration::deleteByName('KLOPERATIONS_DIGEST_RECIPIENTS');

        return parent::uninstall();
    }

    private function registerHooks()
    {
        $hooks = array(
            'actionCronJob',
            'actionObjectHotelBookingDetailAddAfter',
            'actionObjectHotelBookingDetailUpdateAfter',
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
                `status` VARCHAR(32) NOT NULL DEFAULT "pending",
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_kl_operation_task_assignment`),
                KEY `idx_operation_assignment_task` (`id_kl_operation_task`)
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
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        foreach ($sql as $statement) {
            if (!Db::getInstance()->execute($statement)) {
                return false;
            }
        }

        $this->addColumnIfMissing('kl_operation_task', 'last_reminded_at', '`last_reminded_at` DATETIME DEFAULT NULL AFTER `completed_at`');

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

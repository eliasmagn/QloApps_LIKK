#!/usr/bin/env php
<?php
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This utility must be executed from the command line.\n");
    exit(1);
}

$rootDir = dirname(__DIR__, 3);
require $rootDir.'/config/config.inc.php';

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', $rootDir.'/admin');
}

require_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';

$arguments = $argv;
array_shift($arguments);

$scriptName = basename(__FILE__);
if (in_array('--help', $arguments, true) || in_array('-h', $arguments, true)) {
    echo "Usage: php modules/hotelreservationsystem/tools/seed_resource_profiles.php [--employee-id=ID]\n";
    echo "\n";
    echo "Seed resource taxonomy profiles for any room types that do not yet have them.\n";
    echo "Pass an optional --employee-id so the created_by/updated_by columns record who ran the import.\n";
    exit(0);
}

$employeeId = null;
foreach ($arguments as $argument) {
    if (strpos($argument, '--employee-id=') === 0) {
        $employeeId = (int) substr($argument, strlen('--employee-id='));
    }
}

$context = Context::getContext();

$idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
if ($idShop) {
    $context->shop = new Shop($idShop);
}

$idLang = (int) Configuration::get('PS_LANG_DEFAULT');
if ($idLang) {
    $context->language = new Language($idLang);
}

if ($employeeId) {
    $context->employee = new Employee($employeeId);
}

try {
    $summary = KLResourceProfileSeeder::seedFromRoomTypes($employeeId ?: null);
} catch (Exception $exception) {
    fwrite(STDERR, 'Seeding failed: '.$exception->getMessage()."\n");
    exit(1);
}

echo "Resource profile seeding complete.\n";
printf("  - created profiles: %d\n", (int) $summary['created_profiles']);
printf("  - created capacities: %d\n", (int) $summary['created_capacities']);
printf("  - patched capacities: %d\n", (int) $summary['patched_capacities']);
printf("  - existing profiles skipped: %d\n", (int) $summary['skipped_profiles']);

echo "\nRe-run the script at any time; it is idempotent and only adds missing rows.\n";

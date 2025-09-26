<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    if (!($module instanceof Kloperations)) {
        return false;
    }

    return (bool) $module->installDatabase();
}

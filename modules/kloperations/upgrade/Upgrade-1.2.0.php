<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0(Kloperations $module)
{
    if (!$module->installDatabase()) {
        return false;
    }

    if (!Configuration::hasKey('KLOPERATIONS_TEAMS')) {
        Configuration::updateValue('KLOPERATIONS_TEAMS', '');
    }

    return true;
}

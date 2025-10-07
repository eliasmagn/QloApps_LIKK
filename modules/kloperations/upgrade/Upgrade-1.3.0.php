<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($module)
{
    if (!($module instanceof Kloperations)) {
        return false;
    }

    if (!$module->installDatabase()) {
        return false;
    }

    $tabClass = Kloperations::ADMIN_NOTIFICATION_TAB_CLASS;
    if (!Tab::getIdFromClassName($tabClass)) {
        $idParent = (int) Tab::getIdFromClassName(Kloperations::ADMIN_TAB_CLASS);
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $tabClass;
        $tab->id_parent = $idParent;
        $tab->module = $module->name;
        $tab->icon = 'notifications';

        foreach (Language::getLanguages(true) as $language) {
            $tab->name[$language['id_lang']] = $module->l('Notification Preferences');
        }

        if (!$tab->add()) {
            return false;
        }
    }

    return true;
}

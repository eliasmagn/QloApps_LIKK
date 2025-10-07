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

require_once dirname(__DIR__, 2) . '/classes/KlNotificationSubscription.php';
require_once dirname(__DIR__, 2) . '/services/KlOperationNotificationService.php';

class AdminKlNotificationSubscriptionsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = KlNotificationSubscription::$definition['table'];
        $this->className = 'KlNotificationSubscription';
        $this->identifier = 'id_kl_notification_subscription';
        $this->bootstrap = true;
        $this->lang = false;

        parent::__construct();

        $this->_select = 'CONCAT(e.`firstname`, " ", e.`lastname`) AS employee_fullname, e.`email` AS employee_email';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON (e.`id_employee` = a.`id_employee`)';

        $this->fields_list = array(
            'id_kl_notification_subscription' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ),
            'employee_fullname' => array(
                'title' => $this->l('Employee'),
                'callback' => 'renderEmployeeColumn',
                'orderby' => false,
            ),
            'event_type' => array(
                'title' => $this->l('Event type'),
                'callback' => 'renderEventTypeColumn',
            ),
            'channel_email' => array(
                'title' => $this->l('Email'),
                'type' => 'bool',
                'active' => false,
                'orderby' => false,
            ),
            'channel_digest' => array(
                'title' => $this->l('Digest'),
                'type' => 'bool',
                'active' => false,
                'orderby' => false,
            ),
            'channel_calendar' => array(
                'title' => $this->l('Calendar'),
                'type' => 'bool',
                'active' => false,
                'orderby' => false,
            ),
            'quiet_hours_start' => array(
                'title' => $this->l('Quiet hours'),
                'callback' => 'renderQuietHoursColumn',
                'orderby' => false,
            ),
            'timezone' => array(
                'title' => $this->l('Timezone'),
            ),
            'date_upd' => array(
                'title' => $this->l('Updated'),
                'type' => 'datetime',
            ),
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function renderEmployeeColumn($value, $row)
    {
        $name = trim($value);
        $email = isset($row['employee_email']) ? trim($row['employee_email']) : '';

        if ($name === '' && $email === '') {
            return '-';
        }

        if ($name === '') {
            return Tools::safeOutput($email);
        }

        if ($email === '') {
            return Tools::safeOutput($name);
        }

        return sprintf('%s<br /><small>%s</small>', Tools::safeOutput($name), Tools::safeOutput($email));
    }

    public function renderEventTypeColumn($value)
    {
        $map = $this->getEventTypeLabels();
        if (isset($map[$value])) {
            return Tools::safeOutput($map[$value]);
        }

        return Tools::safeOutput($value);
    }

    public function renderQuietHoursColumn($start, $row)
    {
        $start = trim((string) $start);
        $end = isset($row['quiet_hours_end']) ? trim((string) $row['quiet_hours_end']) : '';
        if ($start === '' && $end === '') {
            return $this->l('—');
        }

        $label = sprintf('%s → %s', Tools::safeOutput($start), Tools::safeOutput($end));
        if (!empty($row['timezone'])) {
            $label .= '<br /><small>' . Tools::safeOutput($row['timezone']) . '</small>';
        }

        return $label;
    }

    public function renderForm()
    {
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Notification subscription'),
                'icon' => 'icon-bell',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Employee'),
                    'name' => 'id_employee',
                    'required' => true,
                    'options' => array(
                        'query' => $this->getEmployeeOptions(),
                        'id' => 'id',
                        'name' => 'label',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Event type'),
                    'name' => 'event_type',
                    'required' => true,
                    'options' => array(
                        'query' => $this->getEventTypeOptions(),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Email notifications'),
                    'name' => 'channel_email',
                    'values' => $this->getSwitchValues(),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Digest emails'),
                    'name' => 'channel_digest',
                    'values' => $this->getSwitchValues(),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Calendar feed'),
                    'name' => 'channel_calendar',
                    'values' => $this->getSwitchValues(),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Quiet hours start'),
                    'name' => 'quiet_hours_start',
                    'hint' => $this->l('Format: HH:MM (24-hour). Leave blank to disable quiet hours.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Quiet hours end'),
                    'name' => 'quiet_hours_end',
                    'hint' => $this->l('Format: HH:MM (24-hour). Leave blank to disable quiet hours.'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Quiet hours timezone'),
                    'name' => 'timezone',
                    'options' => array(
                        'query' => $this->getTimezoneOptions(),
                        'id' => 'id',
                        'name' => 'label',
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        if (!$this->object->id && empty($this->fields_value)) {
            $this->fields_value['timezone'] = Configuration::get('PS_TIMEZONE');
        }

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $start = trim((string) Tools::getValue('quiet_hours_start'));
            $end = trim((string) Tools::getValue('quiet_hours_end'));

            if ($start !== '' && !$this->isValidQuietHour($start)) {
                $this->errors[] = $this->l('Quiet hours start must use HH:MM (24-hour) format.');
            }
            if ($end !== '' && !$this->isValidQuietHour($end)) {
                $this->errors[] = $this->l('Quiet hours end must use HH:MM (24-hour) format.');
            }
            if (($start === '' && $end !== '') || ($start !== '' && $end === '')) {
                $this->errors[] = $this->l('Provide both quiet hours start and end or leave both blank.');
            }

            $channelsEnabled = (int) Tools::getValue('channel_email') + (int) Tools::getValue('channel_digest') + (int) Tools::getValue('channel_calendar');
            if ($channelsEnabled === 0) {
                $this->errors[] = $this->l('Enable at least one delivery channel.');
            }

            $idEmployee = (int) Tools::getValue('id_employee');
            $eventType = (string) Tools::getValue('event_type');
            $currentId = (int) Tools::getValue($this->identifier);
            if ($idEmployee && $eventType) {
                if ($this->subscriptionExists($idEmployee, $eventType, $currentId)) {
                    $this->errors[] = $this->l('This employee already has preferences for the selected event.');
                }
            }
        }

        return parent::postProcess();
    }

    private function getSwitchValues()
    {
        return array(
            array(
                'id' => 'active_on',
                'value' => 1,
                'label' => $this->l('Enabled'),
            ),
            array(
                'id' => 'active_off',
                'value' => 0,
                'label' => $this->l('Disabled'),
            ),
        );
    }

    private function getEmployeeOptions()
    {
        $query = new DbQuery();
        $query->select('`id_employee`, `firstname`, `lastname`, `email`');
        $query->from('employee');
        $query->where('`active` = 1');
        $query->orderBy('`lastname` ASC, `firstname` ASC');

        $rows = Db::getInstance()->executeS($query) ?: array();
        $options = array();
        foreach ($rows as $row) {
            $labelParts = array();
            if (!empty($row['firstname']) || !empty($row['lastname'])) {
                $labelParts[] = trim($row['firstname'] . ' ' . $row['lastname']);
            }
            if (!empty($row['email'])) {
                $labelParts[] = '<' . $row['email'] . '>';
            }
            $label = trim(implode(' ', $labelParts));
            if ($label === '') {
                $label = $this->l('Employee #') . (int) $row['id_employee'];
            }
            $options[] = array(
                'id' => (int) $row['id_employee'],
                'label' => $label,
            );
        }

        return $options;
    }

    private function getEventTypeOptions()
    {
        $labels = $this->getEventTypeLabels();
        $options = array();
        foreach ($labels as $key => $label) {
            $options[] = array(
                'id' => $key,
                'name' => $label,
            );
        }

        return $options;
    }

    private function getEventTypeLabels()
    {
        return array(
            KlOperationNotificationService::EVENT_DAILY_DIGEST => $this->l('Daily operations digest'),
            KlOperationNotificationService::EVENT_OVERDUE_REMINDER => $this->l('Overdue operations reminder'),
        );
    }

    private function getTimezoneOptions()
    {
        $identifiers = DateTimeZone::listIdentifiers();
        $options = array(array('id' => '', 'label' => $this->l('Shop default')));
        foreach ($identifiers as $identifier) {
            $options[] = array(
                'id' => $identifier,
                'label' => $identifier,
            );
        }

        return $options;
    }

    private function isValidQuietHour($value)
    {
        return (bool) preg_match('/^\d{2}:\d{2}$/', $value);
    }

    private function subscriptionExists($idEmployee, $eventType, $excludeId = null)
    {
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from(KlNotificationSubscription::$definition['table']);
        $query->where('`id_employee` = ' . (int) $idEmployee);
        $query->where('`event_type` = "' . pSQL($eventType) . '"');
        if ($excludeId) {
            $query->where('`id_kl_notification_subscription` != ' . (int) $excludeId);
        }

        return (int) Db::getInstance()->getValue($query) > 0;
    }
}

<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License version 3.0
 * that is bundled with this package in the file LICENSE.md
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
 *
 * @author Webkul IN
 * @copyright Since 2010 Webkul
 * @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
 */

class AdminHotelRatePlanSeasonsController extends ModuleAdminController
{
    /** @var KLRatePlan|null */
    private $ratePlan;

    /** @var int */
    private $idRatePlan;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'kl_rate_plan_season';
        $this->className = 'KLRatePlanSeason';
        $this->identifier = 'id_kl_rate_plan_season';
        $this->_defaultOrderBy = 'date_from';
        $this->_defaultOrderWay = 'ASC';

        $this->idRatePlan = (int) Tools::getValue('id_kl_rate_plan');
        if ($this->idRatePlan > 0 && strpos(self::$currentIndex, '&id_kl_rate_plan=') === false) {
            self::$currentIndex = self::$currentIndex.'&id_kl_rate_plan='.(int) $this->idRatePlan;
        }

        parent::__construct();

        $this->ratePlan = null;
        if ($this->idRatePlan > 0) {
            $ratePlan = new KLRatePlan($this->idRatePlan, $this->context->language->id);
            if (Validate::isLoadedObject($ratePlan)) {
                $this->ratePlan = $ratePlan;
                $this->_where = ' AND a.`id_kl_rate_plan` = '.(int) $this->idRatePlan;
            }
        }

        $this->fields_list = array(
            'id_kl_rate_plan_season' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'internal_label' => array(
                'title' => $this->l('Internal label'),
            ),
            'date_from' => array(
                'title' => $this->l('Start date'),
                'type' => 'date',
            ),
            'date_to' => array(
                'title' => $this->l('End date'),
                'type' => 'date',
            ),
            'adjustment_method' => array(
                'title' => $this->l('Adjustment'),
                'callback' => 'renderAdjustmentColumn',
                'callback_object' => $this,
            ),
            'min_stay_nights' => array(
                'title' => $this->l('Min nights'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ),
            'max_stay_nights' => array(
                'title' => $this->l('Max nights'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ),
            'min_lead_days' => array(
                'title' => $this->l('Min lead days'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ),
            'max_lead_days' => array(
                'title' => $this->l('Max lead days'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ),
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ),
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function initProcess()
    {
        if (!$this->ratePlan || !$this->ratePlan->id) {
            $this->errors[] = $this->l('The requested rate plan could not be found.');
            $this->redirect_after = $this->context->link->getAdminLink('AdminHotelRatePlans');
            $this->redirect();
        }

        parent::initProcess();
    }

    public function initToolbar()
    {
        $this->toolbar_btn = array();
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_btn = array(
            'back_to_rate_plans' => array(
                'href' => $this->context->link->getAdminLink('AdminHotelRatePlans'),
                'desc' => $this->l('Back to rate plans', null, null, false),
                'icon' => 'process-icon-back',
            ),
        );

        if (!$this->display || $this->display === 'list') {
            $this->page_header_toolbar_btn['new_season'] = array(
                'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                'desc' => $this->l('Add season', null, null, false),
                'icon' => 'process-icon-new',
            );
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @return string
     */
    protected function getRatePlanDisplayName()
    {
        if (!$this->ratePlan) {
            return '';
        }

        $name = $this->ratePlan->name;
        if (is_array($name)) {
            $idLang = (int) $this->context->language->id;
            if (isset($name[$idLang])) {
                $name = $name[$idLang];
            } else {
                $name = reset($name);
            }
        }

        if (!$name) {
            $name = $this->ratePlan->plan_code;
        }

        return $name;
    }

    public function renderList()
    {
        $this->toolbar_title = array(
            $this->l('Rate plans'),
            sprintf($this->l('Seasons for %s'), Tools::safeOutput($this->getRatePlanDisplayName()))
        );

        $this->tpl_list_vars['rate_plan'] = $this->ratePlan;

        return parent::renderList();
    }

    /**
     * @param KLRatePlanSeason $season
     *
     * @return array<string, mixed>
     */
    public function getFieldsValue($season)
    {
        $fields = parent::getFieldsValue($season);
        $fields['id_kl_rate_plan'] = $this->ratePlan->id;

        return $fields;
    }

    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        $translator = $this->context->getTranslator();

        $this->fields_form = array(
            'legend' => array(
                'title' => sprintf($this->l('Season for %s'), Tools::safeOutput($this->getRatePlanDisplayName())),
                'icon' => 'icon-calendar',
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_kl_rate_plan',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Internal label'),
                    'name' => 'internal_label',
                    'hint' => $translator->trans('Optional reference shown to staff (e.g. Winter residency).', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'date',
                    'label' => $this->l('Start date'),
                    'name' => 'date_from',
                    'required' => true,
                ),
                array(
                    'type' => 'date',
                    'label' => $this->l('End date'),
                    'name' => 'date_to',
                    'required' => true,
                    'hint' => $translator->trans('Inclusive end date for the adjustment window.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Adjustment method'),
                    'name' => 'adjustment_method',
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array('id' => 'fixed', 'name' => $this->l('Fixed amount (minor units)')),
                            array('id' => 'percent', 'name' => $this->l('Percentage (basis points)')),
                        ),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Adjustment amount (minor units)'),
                    'name' => 'adjustment_amount_minor',
                    'hint' => $translator->trans('Positive or negative amount added to the base price when using the fixed adjustment.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Adjustment percent (basis points)'),
                    'name' => 'adjustment_percent_basis_points',
                    'hint' => $translator->trans('Percentage expressed in basis points (e.g. 150 = 1.5%) when using the percent adjustment.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Minimum stay nights'),
                    'name' => 'min_stay_nights',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum stay nights'),
                    'name' => 'max_stay_nights',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Minimum occupancy'),
                    'name' => 'min_occupancy',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum occupancy'),
                    'name' => 'max_occupancy',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Minimum lead time (days)'),
                    'name' => 'min_lead_days',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Maximum lead time (days)'),
                    'name' => 'max_lead_days',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        return parent::renderForm();
    }

    /**
     * @param string $method
     * @param array<string, mixed> $row
     *
     * @return string
     */
    public function renderAdjustmentColumn($method, $row)
    {
        if ($method === 'fixed') {
            $amount = isset($row['adjustment_amount_minor']) ? (int) $row['adjustment_amount_minor'] : 0;
            $currencyId = Currency::getIdByIsoCode($this->ratePlan->currency_iso_code, (int) $this->context->shop->id);
            if (!$currencyId) {
                $currencyId = (int) Configuration::get('PS_CURRENCY_DEFAULT');
            }
            $currency = Currency::getCurrencyInstance($currencyId);

            return Tools::displayPrice($amount / 100, $currency);
        }

        if ($method === 'percent') {
            $percent = isset($row['adjustment_percent_basis_points']) ? (int) $row['adjustment_percent_basis_points'] : 0;
            return sprintf('%s %%', (float) $percent / 100);
        }

        return $method;
    }

    public function processSave()
    {
        $_POST['id_kl_rate_plan'] = $this->ratePlan->id;

        // Normalise empty numeric fields to null so validation passes.
        $numericFields = array(
            'adjustment_amount_minor',
            'adjustment_percent_basis_points',
            'min_stay_nights',
            'max_stay_nights',
            'min_occupancy',
            'max_occupancy',
            'min_lead_days',
            'max_lead_days',
        );
        foreach ($numericFields as $field) {
            if (Tools::getValue($field) === '') {
                $_POST[$field] = null;
            }
        }

        return parent::processSave();
    }
}

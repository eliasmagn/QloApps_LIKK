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

class AdminHotelRatePlansController extends ModuleAdminController
{
    /** @var array<int, array<string, string>> */
    private $pricingMethods;

    /**
     * @var array<int, array<string, string>>
     */
    private $resourceKindOptions;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'kl_rate_plan';
        $this->className = 'KLRatePlan';
        $this->identifier = 'id_kl_rate_plan';
        $this->_defaultOrderBy = 'plan_code';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $translator = $this->context->getTranslator();
        $this->pricingMethods = array(
            array(
                'value' => 'nightly',
                'label' => $translator->trans('Nightly (per room night)', array(), 'Modules.Hotelreservationsystem.Admin')
            ),
            array(
                'value' => 'weekly',
                'label' => $translator->trans('Weekly (per 7-night stay)', array(), 'Modules.Hotelreservationsystem.Admin')
            ),
            array(
                'value' => 'package',
                'label' => $translator->trans('Package (flat stay amount)', array(), 'Modules.Hotelreservationsystem.Admin')
            ),
        );

        $this->resourceKindOptions = array(
            array(
                'value' => KLResourceProfile::RESOURCE_KIND_ROOM,
                'label' => $translator->trans('Residences', array(), 'Modules.Hotelreservationsystem.Admin')
            ),
            array(
                'value' => KLResourceProfile::RESOURCE_KIND_ATELIER,
                'label' => $translator->trans('Studios & ateliers', array(), 'Modules.Hotelreservationsystem.Admin')
            ),
            array(
                'value' => KLResourceProfile::RESOURCE_KIND_SEMINAR,
                'label' => $translator->trans('Programme & seminar spaces', array(), 'Modules.Hotelreservationsystem.Admin')
            ),
            array(
                'value' => KLResourceProfile::RESOURCE_KIND_GASTRONOMY,
                'label' => $translator->trans('Dining & gastronomy', array(), 'Modules.Hotelreservationsystem.Admin')
            ),
        );

        $this->fields_list = array(
            'id_kl_rate_plan' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'plan_code' => array(
                'title' => $this->l('Code'),
                'class' => 'fixed-width-lg',
            ),
            'name' => array(
                'title' => $this->l('Name'),
            ),
            'pricing_method' => array(
                'title' => $this->l('Method'),
                'callback' => 'renderPricingMethodColumn',
                'callback_object' => $this,
                'orderby' => false,
            ),
            'currency_iso_code' => array(
                'title' => $this->l('Currency'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ),
            'is_active' => array(
                'title' => $this->l('Active'),
                'type' => 'bool',
                'align' => 'center',
                'active' => 'toggleStatus',
                'orderby' => true,
                'class' => 'fixed-width-sm',
            ),
            'date_upd' => array(
                'title' => $this->l('Updated on'),
                'type' => 'datetime',
            ),
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash',
            ),
            'enableSelection' => array(
                'text' => $this->l('Enable selection'),
                'icon' => 'icon-check-circle',
            ),
            'disableSelection' => array(
                'text' => $this->l('Disable selection'),
                'icon' => 'icon-ban',
            ),
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowAction('seasons');
    }

    /**
     * @param string $pricingMethod
     *
     * @return string
     */
    public function renderPricingMethodColumn($pricingMethod)
    {
        foreach ($this->pricingMethods as $method) {
            if ($method['value'] === $pricingMethod) {
                return $method['label'];
            }
        }

        return $pricingMethod;
    }

    public function initPageHeaderToolbar()
    {
        if (!$this->display || $this->display === 'list') {
            $this->page_header_toolbar_btn['new_rate_plan'] = array(
                'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                'desc' => $this->l('Add new rate plan', null, null, false),
                'icon' => 'process-icon-new',
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initToolbar()
    {
        $this->toolbar_btn = array();
    }

    /**
     * @param string|null $token
     * @param int $id
     *
     * @return string
     */
    public function displaySeasonsLink($token, $id, $name = null)
    {
        $href = $this->context->link->getAdminLink(
            'AdminHotelRatePlanSeasons',
            true,
            array(),
            array('id_kl_rate_plan' => (int) $id)
        );

        return sprintf(
            '<a class="btn btn-default" href="%s"><i class="icon-calendar"></i> %s</a>',
            htmlspecialchars($href),
            $this->l('Seasons')
        );
    }

    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        $translator = $this->context->getTranslator();
        $currencies = Currency::getCurrencies(false, true, true);
        $currencyOptions = array();
        foreach ($currencies as $currency) {
            $currencyOptions[] = array(
                'value' => $currency['iso_code'],
                'name' => $currency['name'].' ('.$currency['iso_code'].')',
            );
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Rate plan'),
                'icon' => 'icon-tags',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Plan code'),
                    'name' => 'plan_code',
                    'required' => true,
                    'hint' => $translator->trans('Unique identifier used in integrations and imports (e.g. BAR-RESIDENCE).', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Display name'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                    'hint' => $translator->trans('Front-office and inquiry board label for the plan.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Tagline'),
                    'name' => 'tagline',
                    'lang' => true,
                    'hint' => $translator->trans('Short teaser shown alongside the plan name.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'autoload_rte' => true,
                    'lang' => true,
                    'hint' => $translator->trans('Detailed notes for staff and storytelling blocks.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Pricing method'),
                    'name' => 'pricing_method',
                    'required' => true,
                    'options' => array(
                        'query' => $this->pricingMethods,
                        'id' => 'value',
                        'name' => 'label',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Currency'),
                    'name' => 'currency_iso_code',
                    'required' => true,
                    'options' => array(
                        'query' => $currencyOptions,
                        'id' => 'value',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Resource kinds'),
                    'name' => 'resource_kind_scope[]',
                    'multiple' => true,
                    'options' => array(
                        'query' => $this->resourceKindOptions,
                        'id' => 'value',
                        'name' => 'label',
                    ),
                    'hint' => $translator->trans('Limit the plan to specific resource kinds. Leave empty to allow all.', array(), 'Modules.Hotelreservationsystem.Admin'),
                    'class' => 'chosen',
                ),
                array(
                    'type' => 'tags',
                    'label' => $this->l('Audience segments'),
                    'name' => 'audience_segment_scope',
                    'hint' => $translator->trans('Optional comma-separated list of segments (e.g. RESIDENCY, CORPORATE, PARTNER).', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Cancellation policy notes'),
                    'name' => 'cancellation_policy_notes',
                    'hint' => $translator->trans('Internal summary of the cancellation policy linked to this plan.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Advance required (minor units)'),
                    'name' => 'advance_required_minor',
                    'hint' => $translator->trans('Deposit or prepayment amount required in the plan currency minor units (e.g. cents).', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Approval required'),
                    'name' => 'approval_required',
                    'is_bool' => true,
                    'values' => array(
                        array('id' => 'approval_required_on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'approval_required_off', 'value' => 0, 'label' => $this->l('No')),
                    ),
                    'hint' => $translator->trans('Mark plans that require manager approval before confirming quotes.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'is_active',
                    'is_bool' => true,
                    'values' => array(
                        array('id' => 'is_active_on', 'value' => 1, 'label' => $this->l('Enabled')),
                        array('id' => 'is_active_off', 'value' => 0, 'label' => $this->l('Disabled')),
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        return parent::renderForm();
    }

    /**
     * @param KLRatePlan $object
     *
     * @return array<string, mixed>
     */
    public function getFieldsValue($object)
    {
        $fields = parent::getFieldsValue($object);

        if ($object && Validate::isLoadedObject($object)) {
            $fields['resource_kind_scope[]'] = $object->getResourceKindScope();

            $segments = $object->getAudienceSegmentScope();
            if (!empty($segments)) {
                $fields['audience_segment_scope'] = implode(', ', $segments);
            } else {
                $fields['audience_segment_scope'] = '';
            }
        }

        return $fields;
    }

    public function processSave()
    {
        if (Tools::isSubmit('plan_code')) {
            $_POST['plan_code'] = Tools::strtoupper(trim(Tools::getValue('plan_code')));
        }

        $resourceKinds = Tools::getValue('resource_kind_scope', array());
        if (!is_array($resourceKinds)) {
            $resourceKinds = array($resourceKinds);
        }
        $resourceKinds = array_values(array_unique(array_filter(array_map('trim', $resourceKinds))));
        $_POST['resource_kind_scope'] = $resourceKinds ? json_encode($resourceKinds) : '';

        $segmentsRaw = Tools::getValue('audience_segment_scope', '');
        if (!is_array($segmentsRaw)) {
            $segmentsRaw = explode(',', (string) $segmentsRaw);
        }
        $segments = array();
        foreach ($segmentsRaw as $segment) {
            $segment = Tools::strtoupper(trim($segment));
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }
        $segments = array_values(array_unique($segments));
        $_POST['audience_segment_scope'] = $segments ? json_encode($segments) : '';

        return parent::processSave();
    }
}

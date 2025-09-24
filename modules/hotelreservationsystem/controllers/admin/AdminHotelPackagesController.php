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

class AdminHotelPackagesController extends ModuleAdminController
{
    /**
     * @var array<int, array<string, string>>
     */
    private $resourceKindOptions;

    /**
     * @var array<int, array<string, string>>
     */
    private $durationModes;

    /**
     * @var array<string, string>
     */
    private $componentTypeMap;

    /**
     * @var array<int, array<string, mixed>>
     */
    private $parsedComponents = array();

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'kl_package';
        $this->className = 'KLPackage';
        $this->identifier = 'id_kl_package';
        $this->lang = true;
        $this->_defaultOrderBy = 'package_code';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $translator = $this->context->getTranslator();
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

        $this->durationModes = array(
            array(
                'value' => 'nights',
                'label' => $translator->trans('Nights', array(), 'Modules.Hotelreservationsystem.Admin'),
            ),
            array(
                'value' => 'days',
                'label' => $translator->trans('Days', array(), 'Modules.Hotelreservationsystem.Admin'),
            ),
            array(
                'value' => 'weeks',
                'label' => $translator->trans('Weeks', array(), 'Modules.Hotelreservationsystem.Admin'),
            ),
            array(
                'value' => 'custom',
                'label' => $translator->trans('Custom', array(), 'Modules.Hotelreservationsystem.Admin'),
            ),
        );

        $this->componentTypeMap = array(
            'lodging' => $translator->trans('Residency lodging', array(), 'Modules.Hotelreservationsystem.Admin'),
            'atelier' => $translator->trans('Atelier / studio session', array(), 'Modules.Hotelreservationsystem.Admin'),
            'meal' => $translator->trans('Meal or catering service', array(), 'Modules.Hotelreservationsystem.Admin'),
            'experience' => $translator->trans('Experience or excursion', array(), 'Modules.Hotelreservationsystem.Admin'),
            'custom' => $translator->trans('Custom line item', array(), 'Modules.Hotelreservationsystem.Admin'),
        );

        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'kl_package_lang` pl ON (pl.`id_kl_package` = a.`id_kl_package`'
            .' AND pl.`id_lang` = '.(int) $this->context->language->id.')'
            .' LEFT JOIN `'._DB_PREFIX_.'kl_rate_plan` rp ON (rp.`id_kl_rate_plan` = a.`id_kl_rate_plan`)';
        $this->_select = 'pl.`name` AS `name`, rp.`plan_code` AS `plan_code`';

        $this->fields_list = array(
            'id_kl_package' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'package_code' => array(
                'title' => $this->l('Code'),
                'class' => 'fixed-width-lg',
            ),
            'name' => array(
                'title' => $this->l('Name'),
            ),
            'plan_code' => array(
                'title' => $this->l('Linked plan'),
                'align' => 'center',
                'class' => 'fixed-width-lg',
            ),
            'is_active' => array(
                'title' => $this->l('Active'),
                'type' => 'bool',
                'align' => 'center',
                'active' => 'toggleStatus',
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
    }

    public function initPageHeaderToolbar()
    {
        if (!$this->display || $this->display === 'list') {
            $this->page_header_toolbar_btn['new_package'] = array(
                'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                'desc' => $this->l('Add new package', null, null, false),
                'icon' => 'process-icon-new',
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initToolbar()
    {
        $this->toolbar_btn = array();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        if ($this->display === 'add' || $this->display === 'edit') {
            $this->addJS($this->module->getPathUri().'views/js/admin/package_builder.js');
            $this->addCSS($this->module->getPathUri().'views/css/admin/package_builder.css');
        }
    }

    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        $translator = $this->context->getTranslator();
        $ratePlanOptions = $this->getRatePlanOptions();

        $durationHint = $translator->trans(
            'Optional duration metadata so storytelling blocks can describe how long the stay or experience lasts.',
            array(),
            'Modules.Hotelreservationsystem.Admin'
        );

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Package'),
                'icon' => 'icon-briefcase',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Package code'),
                    'name' => 'package_code',
                    'required' => true,
                    'hint' => $translator->trans(
                        'Unique identifier referenced in integrations and imports (e.g. RESIDENCY-WEEKLY).',
                        array(),
                        'Modules.Hotelreservationsystem.Admin'
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Display name'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                    'hint' => $translator->trans(
                        'Front-office and inquiry board label for the package.',
                        array(),
                        'Modules.Hotelreservationsystem.Admin'
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Tagline'),
                    'name' => 'tagline',
                    'lang' => true,
                    'hint' => $translator->trans(
                        'Short teaser shown alongside the package name.',
                        array(),
                        'Modules.Hotelreservationsystem.Admin'
                    ),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Description'),
                    'name' => 'description',
                    'autoload_rte' => true,
                    'lang' => true,
                    'hint' => $translator->trans(
                        'Long-form description for storytelling blocks and staff context.',
                        array(),
                        'Modules.Hotelreservationsystem.Admin'
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Linked rate plan'),
                    'name' => 'id_kl_rate_plan',
                    'options' => array(
                        'query' => $ratePlanOptions,
                        'id' => 'value',
                        'name' => 'label',
                    ),
                    'hint' => $translator->trans(
                        'Select the default rate plan used when quoting this package. Leave blank to attach later.',
                        array(),
                        'Modules.Hotelreservationsystem.Admin'
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
                    'hint' => $translator->trans(
                        'Limit the package to specific resource kinds. Leave empty to allow all.',
                        array(),
                        'Modules.Hotelreservationsystem.Admin'
                    ),
                    'class' => 'chosen',
                ),
                array(
                    'type' => 'tags',
                    'label' => $this->l('Audience segments'),
                    'name' => 'audience_segment_scope',
                    'hint' => $translator->trans(
                        'Optional comma-separated segments that should see this package (e.g. RESIDENCY, PARTNER, PUBLIC).',
                        array(),
                        'Modules.Hotelreservationsystem.Admin'
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Duration mode'),
                    'name' => 'duration_mode',
                    'options' => array(
                        'query' => $this->durationModes,
                        'id' => 'value',
                        'name' => 'label',
                    ),
                    'hint' => $durationHint,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Duration value'),
                    'name' => 'duration_value',
                    'hint' => $durationHint,
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Featured'),
                    'name' => 'is_featured',
                    'is_bool' => true,
                    'values' => array(
                        array('id' => 'is_featured_on', 'value' => 1, 'label' => $this->l('Yes')),
                        array('id' => 'is_featured_off', 'value' => 0, 'label' => $this->l('No')),
                    ),
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
                array(
                    'type' => 'free',
                    'label' => $this->l('Package components'),
                    'name' => 'components_builder',
                    'form_group_class' => 'kl-package-components-group',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        return parent::renderForm();
    }

    /**
     * @param KLPackage $object
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

            $components = $this->formatComponentsForBuilder($object->getComponents());
        } else {
            $components = array();
        }

        $fields['components_builder'] = $this->renderComponentsBuilder($components);

        return $fields;
    }

    public function processSave()
    {
        if (Tools::isSubmit('package_code')) {
            $_POST['package_code'] = Tools::strtoupper(trim(Tools::getValue('package_code')));
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

        $componentsPayload = Tools::getValue('components_payload', '[]');
        $this->parsedComponents = $this->parseComponentsPayload($componentsPayload);

        $result = parent::processSave();

        if ($result && $this->object && Validate::isLoadedObject($this->object)) {
            if (!$this->persistComponents($this->object->id, $this->parsedComponents)) {
                $this->errors[] = $this->l('The package was saved but components could not be updated. Please review the payload.');
                return false;
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getRatePlanOptions()
    {
        $idLang = (int) $this->context->language->id;

        $query = new DbQuery();
        $query->select('rp.`id_kl_rate_plan`, rp.`plan_code`, rpl.`name`');
        $query->from('kl_rate_plan', 'rp');
        $query->leftJoin('kl_rate_plan_lang', 'rpl', 'rp.`id_kl_rate_plan` = rpl.`id_kl_rate_plan` AND rpl.`id_lang` = '.(int) $idLang);
        $query->orderBy('rp.`plan_code` ASC');

        $rows = Db::getInstance()->executeS($query);
        $options = array(
            array(
                'value' => 0,
                'label' => $this->l('— None —'),
            ),
        );

        if ($rows) {
            foreach ($rows as $row) {
                $labelParts = array();
                if (!empty($row['plan_code'])) {
                    $labelParts[] = $row['plan_code'];
                }
                if (!empty($row['name'])) {
                    $labelParts[] = $row['name'];
                }
                $options[] = array(
                    'value' => (int) $row['id_kl_rate_plan'],
                    'label' => implode(' — ', $labelParts),
                );
            }
        }

        return $options;
    }

    /**
     * @param array<int, KLPackageComponent> $components
     *
     * @return array<int, array<string, mixed>>
     */
    private function formatComponentsForBuilder(array $components)
    {
        $formatted = array();
        foreach ($components as $component) {
            if (!$component instanceof KLPackageComponent) {
                continue;
            }

            $formatted[] = array(
                'component_type' => (string) $component->component_type,
                'reference_code' => (string) $component->reference_code,
                'quantity' => (float) $component->quantity,
                'unit' => (string) $component->unit,
                'is_optional' => (bool) $component->is_optional,
                'price_minor' => (int) $component->price_minor,
                'id_kl_rate_plan' => (int) $component->id_kl_rate_plan,
            );
        }

        return $formatted;
    }

    /**
     * @param array<int, array<string, mixed>> $components
     *
     * @return string
     */
    private function renderComponentsBuilder(array $components)
    {
        $componentTypes = array();
        foreach ($this->componentTypeMap as $value => $label) {
            $componentTypes[] = array('value' => $value, 'label' => $label);
        }

        $ratePlans = $this->getRatePlanOptions();
        $i18n = array(
            'add_component' => $this->l('Add component'),
            'edit_component' => $this->l('Edit component'),
            'save_component' => $this->l('Save component'),
            'cancel' => $this->l('Cancel'),
            'no_components' => $this->l('No components have been added yet.'),
            'component_type' => $this->l('Type'),
            'reference_code' => $this->l('Reference code'),
            'quantity' => $this->l('Quantity'),
            'unit' => $this->l('Unit'),
            'price_minor' => $this->l('Price (minor units)'),
            'optional' => $this->l('Optional?'),
            'linked_plan' => $this->l('Linked plan'),
            'actions' => $this->l('Actions'),
            'move_up' => $this->l('Move up'),
            'move_down' => $this->l('Move down'),
            'edit' => $this->l('Edit'),
            'delete' => $this->l('Delete'),
            'confirm_delete' => $this->l('Remove this component?'),
            'included' => $this->l('Included'),
            'optional_label' => $this->l('Optional'),
            'use_package_plan' => $this->l('Use package plan'),
        );

        $this->context->smarty->assign(array(
            'kl_package_components' => array(
                'component_types' => $componentTypes,
                'rate_plans' => $ratePlans,
                'components' => $components,
                'i18n' => $i18n,
            ),
        ));

        return $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/hotel_packages/helpers/form/components_builder.tpl'
        );
    }

    /**
     * @param string $payload
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseComponentsPayload($payload)
    {
        $decoded = json_decode((string) $payload, true);
        if (!is_array($decoded)) {
            return array();
        }

        $allowedTypes = array_keys($this->componentTypeMap);
        $parsed = array();

        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $type = isset($entry['component_type']) ? (string) $entry['component_type'] : '';
            if (!in_array($type, $allowedTypes)) {
                continue;
            }

            $reference = isset($entry['reference_code']) ? Tools::substr((string) $entry['reference_code'], 0, 64) : '';
            $unit = isset($entry['unit']) ? Tools::substr((string) $entry['unit'], 0, 16) : '';
            $quantity = isset($entry['quantity']) ? (float) $entry['quantity'] : 0.0;
            $priceMinor = isset($entry['price_minor']) ? (int) $entry['price_minor'] : 0;
            $linkedPlan = isset($entry['id_kl_rate_plan']) ? (int) $entry['id_kl_rate_plan'] : 0;
            $isOptional = !empty($entry['is_optional']);

            $parsed[] = array(
                'component_type' => $type,
                'reference_code' => $reference,
                'quantity' => $quantity,
                'unit' => $unit,
                'is_optional' => $isOptional,
                'price_minor' => $priceMinor,
                'id_kl_rate_plan' => $linkedPlan > 0 ? $linkedPlan : 0,
            );
        }

        return $parsed;
    }

    /**
     * @param int $idPackage
     * @param array<int, array<string, mixed>> $components
     *
     * @return bool
     */
    private function persistComponents($idPackage, array $components)
    {
        $idPackage = (int) $idPackage;
        if ($idPackage <= 0) {
            return false;
        }

        $db = Db::getInstance();
        if (!$db->delete('kl_package_component', '`id_kl_package` = '.(int) $idPackage)) {
            return false;
        }

        $position = 1;
        foreach ($components as $componentData) {
            if (empty($componentData['component_type'])) {
                continue;
            }

            $component = new KLPackageComponent();
            $component->id_kl_package = $idPackage;
            $component->component_type = (string) $componentData['component_type'];
            $component->reference_code = (string) $componentData['reference_code'];
            $component->quantity = (float) $componentData['quantity'];
            $component->unit = (string) $componentData['unit'];
            $component->is_optional = !empty($componentData['is_optional']);
            $component->price_minor = (int) $componentData['price_minor'];
            $component->id_kl_rate_plan = (int) $componentData['id_kl_rate_plan'];
            $component->sort_order = $position++;

            if (!$component->add()) {
                return false;
            }
        }

        return true;
    }
}

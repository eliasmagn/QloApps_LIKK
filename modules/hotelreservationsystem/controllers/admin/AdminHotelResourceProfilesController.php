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

class AdminHotelResourceProfilesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'kl_resource_profile';
        $this->className = 'KLResourceProfile';
        $this->identifier = 'id_kl_resource_profile';
        $this->_defaultOrderBy = 'resource_kind';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $this->fields_list = array(
            'id_kl_resource_profile' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'resource_code' => array(
                'title' => $this->l('Code'),
            ),
            'resource_kind' => array(
                'title' => $this->l('Kind'),
                'type' => 'select',
                'list' => $this->getResourceKindFilterOptions(),
                'filter_key' => 'a!resource_kind',
            ),
            'room_type_name' => array(
                'title' => $this->l('Linked room type'),
                'filter_key' => 'pl!name',
            ),
            'is_bookable' => array(
                'title' => $this->l('Bookable'),
                'align' => 'center',
                'type' => 'bool',
                'orderby' => true,
            ),
            'is_published' => array(
                'title' => $this->l('Published'),
                'align' => 'center',
                'type' => 'bool',
                'orderby' => true,
            ),
            'display_order' => array(
                'title' => $this->l('Order'),
                'class' => 'fixed-width-sm',
                'align' => 'center',
            ),
        );

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $this->_select = 'pl.`name` AS room_type_name';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'htl_room_type` hrt ON (hrt.`id` = a.`id_room_type`)
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = hrt.`id_product`
                AND pl.`id_lang` = '.(int) $idLang.' AND pl.`id_shop` = '.(int) $idShop.')';

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            ),
        );
    }

    public function initPageHeaderToolbar()
    {
        if (!$this->display || $this->display == 'list') {
            $this->page_header_toolbar_btn['new_resource'] = array(
                'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                'desc' => $this->l('Add new profile', null, null, false),
                'icon' => 'process-icon-new',
            );
        }

        parent::initPageHeaderToolbar();
    }

    public function initToolbar()
    {
        $this->toolbar_btn = array();
    }

    public function renderForm()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        $translator = $this->context->getTranslator();
        $resourceKinds = array();
        foreach (KLResourceProfile::getSupportedResourceKinds() as $kind) {
            $resourceKinds[] = array(
                'id' => $kind,
                'name' => $translator->trans(ucfirst($kind), array(), 'Modules.Hotelreservationsystem.Admin'),
            );
        }

        $roomTypeOptions = $this->getRoomTypeOptions();

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Resource profile'),
                'icon' => 'icon-archive',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Resource code'),
                    'name' => 'resource_code',
                    'required' => true,
                    'hint' => $this->l('Short identifier used internally and across exports (e.g. ZIMMER-01).'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Resource kind'),
                    'name' => 'resource_kind',
                    'required' => true,
                    'options' => array(
                        'query' => $resourceKinds,
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Linked room type'),
                    'name' => 'id_room_type',
                    'options' => array(
                        'query' => $roomTypeOptions,
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'hint' => $this->l('Optional link to an existing room type so availability keeps using the same product. Leave empty for ateliers or gastronomy areas.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('External reference'),
                    'name' => 'external_reference',
                    'hint' => $this->l('Map the profile to upstream inventory or accounting identifiers when needed.'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Bookable'),
                    'name' => 'is_bookable',
                    'is_bool' => true,
                    'values' => array(
                        array('id' => 'is_bookable_on', 'value' => 1, 'label' => $this->l('Enabled')),
                        array('id' => 'is_bookable_off', 'value' => 0, 'label' => $this->l('Disabled')),
                    ),
                    'hint' => $this->l('Toggle whether the resource can be scheduled directly on the timeline.'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Published'),
                    'name' => 'is_published',
                    'is_bool' => true,
                    'values' => array(
                        array('id' => 'is_published_on', 'value' => 1, 'label' => $this->l('Visible')),
                        array('id' => 'is_published_off', 'value' => 0, 'label' => $this->l('Hidden')),
                    ),
                    'hint' => $this->l('Control whether the profile appears on front-office storytelling components.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Timezone'),
                    'name' => 'timezone',
                    'hint' => $this->l('IANA timezone identifier if the resource deviates from the hotel default.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Display order'),
                    'name' => 'display_order',
                    'hint' => $this->l('Controls the sort order when rendering grouped resources in the admin timeline.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Adults capacity'),
                    'name' => 'capacity_adults',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Children capacity'),
                    'name' => 'capacity_children',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Total overnight capacity'),
                    'name' => 'capacity_total',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Seated capacity'),
                    'name' => 'capacity_seated',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Standing capacity'),
                    'name' => 'capacity_standing',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Floor area (sqm)'),
                    'name' => 'floor_area_sqm',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Ceiling height (m)'),
                    'name' => 'ceiling_height_m',
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Capacity notes'),
                    'name' => 'capacity_notes',
                    'autoload_rte' => false,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
            'buttons' => array(
                'save-and-stay' => array(
                    'title' => $this->l('Save and stay'),
                    'name' => 'submitAdd'.$this->table.'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                ),
            ),
        );

        $defaultBookable = $this->object->id ? (int) $this->object->is_bookable : 1;
        $defaultPublished = $this->object->id ? (int) $this->object->is_published : 1;
        $this->fields_value = array(
            'is_bookable' => Tools::getValue('is_bookable', $defaultBookable),
            'is_published' => Tools::getValue('is_published', $defaultPublished),
        );

        if (!$this->object->display_order && $this->object->resource_kind) {
            $this->fields_value['display_order'] = KLResourceProfile::getNextDisplayOrder($this->object->resource_kind);
        }

        $capacity = KLResourceCapacity::loadByProfileId($this->object->id);
        $capacityDefaults = array(
            'capacity_adults' => $capacity ? $capacity->capacity_adults : '',
            'capacity_children' => $capacity ? $capacity->capacity_children : '',
            'capacity_total' => $capacity ? $capacity->capacity_total : '',
            'capacity_seated' => $capacity ? $capacity->capacity_seated : '',
            'capacity_standing' => $capacity ? $capacity->capacity_standing : '',
            'floor_area_sqm' => $capacity ? $capacity->floor_area_sqm : '',
            'ceiling_height_m' => $capacity ? $capacity->ceiling_height_m : '',
        );
        foreach ($capacityDefaults as $field => $defaultValue) {
            $this->fields_value[$field] = Tools::getValue($field, $defaultValue);
        }

        $this->fields_value['capacity_notes'] = Tools::getValue(
            'capacity_notes',
            $capacity ? $capacity->notes : ''
        );

        return parent::renderForm();
    }

    public function processSave()
    {
        if (!$this->loadObject(true)) {
            return;
        }

        $resourceKind = Tools::getValue('resource_kind');
        if (!$resourceKind || !in_array($resourceKind, KLResourceProfile::getSupportedResourceKinds())) {
            $this->errors[] = $this->l('Select a valid resource kind.');
        }

        if (!Tools::getValue('resource_code')) {
            $this->errors[] = $this->l('A resource code is required.');
        }

        if (!$this->errors && !(int) Tools::getValue('display_order') && $resourceKind) {
            $_POST['display_order'] = KLResourceProfile::getNextDisplayOrder($resourceKind);
        }

        $result = parent::processSave();

        if ($result && empty($this->errors) && Validate::isLoadedObject($this->object)) {
            $capacity = KLResourceCapacity::loadByProfileId($this->object->id);
            if (!$capacity) {
                $capacity = new KLResourceCapacity();
                $capacity->id_kl_resource_profile = (int) $this->object->id;
            }

            $capacity->capacity_adults = (int) Tools::getValue('capacity_adults');
            $capacity->capacity_children = (int) Tools::getValue('capacity_children');
            $capacity->capacity_total = (int) Tools::getValue('capacity_total');
            $capacity->capacity_seated = (int) Tools::getValue('capacity_seated');
            $capacity->capacity_standing = (int) Tools::getValue('capacity_standing');
            $capacity->floor_area_sqm = (float) Tools::getValue('floor_area_sqm');
            $capacity->ceiling_height_m = (float) Tools::getValue('ceiling_height_m');
            $capacity->notes = Tools::getValue('capacity_notes', '', true);
            $capacity->save();
        }

        return $result;
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    protected function getResourceKindFilterOptions()
    {
        $translator = $this->context->getTranslator();
        $options = array();
        foreach (KLResourceProfile::getSupportedResourceKinds() as $kind) {
            $options[] = array(
                'id' => $kind,
                'name' => $translator->trans(ucfirst($kind), array(), 'Modules.Hotelreservationsystem.Admin'),
            );
        }

        return $options;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    protected function getRoomTypeOptions()
    {
        $options = array(array('id' => 0, 'name' => $this->l('— None —')));

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $query = new DbQuery();
        $query->select('hrt.`id` AS id');
        $query->select('pl.`name` AS name');
        $query->from('htl_room_type', 'hrt');
        $query->innerJoin('product', 'p', 'p.`id_product` = hrt.`id_product`');
        $query->innerJoin(
            'product_lang',
            'pl',
            'pl.`id_product` = hrt.`id_product` AND pl.`id_lang` = '.(int) $idLang.' AND pl.`id_shop` = '.(int) $idShop
        );
        $query->orderBy('pl.`name` ASC');

        $rows = Db::getInstance()->executeS($query);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $options[] = array(
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                );
            }
        }

        return $options;
    }
}

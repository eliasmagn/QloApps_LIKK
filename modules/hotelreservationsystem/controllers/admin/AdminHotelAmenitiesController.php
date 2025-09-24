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

class AdminHotelAmenitiesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'kl_resource_amenity';
        $this->className = 'KLAmenity';
        $this->identifier = 'id_kl_resource_amenity';
        $this->_defaultOrderBy = 'category_code';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $this->fields_list = array(
            'id_kl_resource_amenity' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'amenity_code' => array(
                'title' => $this->l('Code'),
                'class' => 'fixed-width-lg',
            ),
            'category_code' => array(
                'title' => $this->l('Category'),
                'class' => 'fixed-width-lg',
            ),
            'translation_domain' => array(
                'title' => $this->l('Translation domain'),
            ),
            'icon' => array(
                'title' => $this->l('Icon reference'),
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
                'orderby' => true,
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

        $this->actions = array('edit', 'delete');
    }

    public function initPageHeaderToolbar()
    {
        if (!$this->display || $this->display == 'list') {
            $this->page_header_toolbar_btn['new_amenity'] = array(
                'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
                'desc' => $this->l('Add new amenity', null, null, false),
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

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Amenity'),
                'icon' => 'icon-tags',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Amenity code'),
                    'name' => 'amenity_code',
                    'required' => true,
                    'hint' => $translator->trans('Unique identifier used by APIs and imports (e.g. WATER-CANOE).', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Category code'),
                    'name' => 'category_code',
                    'required' => true,
                    'hint' => $translator->trans('Group amenities by internal category codes (e.g. WATER, EQUIPMENT).', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Translation domain'),
                    'name' => 'translation_domain',
                    'hint' => $translator->trans('Optional Symfony translation domain for copy rendered on the front office.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Icon reference'),
                    'name' => 'icon',
                    'hint' => $translator->trans('Store icon filenames or CSS class names to render amenity visuals.', array(), 'Modules.Hotelreservationsystem.Admin'),
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
                    'hint' => $translator->trans('Inactive amenities stay in history but disappear from selection lists.', array(), 'Modules.Hotelreservationsystem.Admin'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        return parent::renderForm();
    }

    public function processSave()
    {
        if (Tools::isSubmit('amenity_code')) {
            $_POST['amenity_code'] = Tools::strtoupper(trim(Tools::getValue('amenity_code')));
        }
        if (Tools::isSubmit('category_code')) {
            $_POST['category_code'] = Tools::strtoupper(trim(Tools::getValue('category_code')));
        }

        return parent::processSave();
    }
}

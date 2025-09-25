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
    /**
     * Cached amenity options for the current request.
     *
     * @var array<int, array<string, mixed>>|null
     */
    protected $amenityOptions = null;

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
        $amenityOptions = $this->loadAmenityOptions();
        $amenityDesc = $this->buildAmenityDescription($amenityOptions);

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
                    'hint' => $this->l('Maximum number of adults accommodated simultaneously. Existing bookings prevent lowering this below recorded stays.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Children capacity'),
                    'name' => 'capacity_children',
                    'hint' => $this->l('Maximum number of children accommodated simultaneously. Existing bookings prevent lowering this below recorded stays.'),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Total overnight capacity'),
                    'name' => 'capacity_total',
                    'desc' => $this->l('Leave blank to keep unlimited. When set, total capacity must be equal or greater than the sum of adults and children.'),
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
                    'hint' => $this->l('Add nuance for atypical configurations (e.g. “Studio adds two daybeds for residencies”).'),
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Amenities'),
                    'name' => 'resource_amenities',
                    'values' => array(
                        'query' => $amenityOptions,
                        'id' => 'id',
                        'name' => 'label',
                    ),
                    'hint' => $this->l('Tick the amenities that apply to this resource. Manage the catalogue under Catalog → Amenities.'),
                    'desc' => $amenityDesc,
                ),
                array(
                    'type' => 'free',
                    'label' => $this->l('Front-office preview'),
                    'name' => 'front_preview',
                    'form_group_class' => 'kl-resource-preview-group',
                ),
                array(
                    'type' => 'free',
                    'label' => $this->l('Change history'),
                    'name' => 'history_preview',
                    'form_group_class' => 'kl-resource-history-group',
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

        $selectedAmenityIds = array();
        if ($this->object->id) {
            $selectedAmenityIds = KLAmenityLink::getAmenityIdsByProfile($this->object->id);
        }

        foreach ($amenityOptions as $amenity) {
            $this->fields_value['resource_amenities_'.$amenity['id']] = in_array((int) $amenity['id'], $selectedAmenityIds, true) ? 1 : 0;
        }

        $this->fields_value['front_preview'] = $this->renderPreviewBlock($this->object, $capacity, $selectedAmenityIds);
        $this->fields_value['history_preview'] = $this->renderHistoryBlock($this->object->id);

        return parent::renderForm();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadAmenityOptions()
    {
        if ($this->amenityOptions !== null) {
            return $this->amenityOptions;
        }

        $translator = $this->context->getTranslator();
        $rows = Db::getInstance()->executeS(
            'SELECT `id_kl_resource_amenity`, `amenity_code`, `category_code`, `translation_domain`
            FROM `'._DB_PREFIX_.'kl_resource_amenity`
            WHERE `is_active` = 1
            ORDER BY `category_code` ASC, `amenity_code` ASC'
        );

        $options = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $category = trim($row['category_code']);
                $code = trim($row['amenity_code']);
                $labelParts = array();
                if ($category !== '') {
                    $labelParts[] = $category;
                }
                if ($code !== '') {
                    $labelParts[] = $code;
                }

                $displayLabel = implode(' — ', $labelParts);
                $missingTranslation = false;
                if (!empty($row['translation_domain'])) {
                    $translated = $translator->trans(
                        $code,
                        array(),
                        $row['translation_domain']
                    );
                    if ($translated && $translated !== $code) {
                        $displayLabel .= ' · '.$translated;
                    } else {
                        $missingTranslation = true;
                    }
                }

                $options[] = array(
                    'id' => (int) $row['id_kl_resource_amenity'],
                    'label' => $displayLabel,
                    'amenity_code' => $code,
                    'category_code' => $category,
                    'missing_translation' => $missingTranslation,
                );
            }
        }

        $this->amenityOptions = $options;

        return $this->amenityOptions;
    }

    /**
     * @param array<int, array<string, mixed>> $amenityOptions
     *
     * @return string
     */
    protected function buildAmenityDescription(array $amenityOptions)
    {
        if (empty($amenityOptions)) {
            return $this->l('No amenities are defined yet. Visit Catalog → Amenities to seed the catalogue before assigning them here.');
        }

        $missing = array();
        foreach ($amenityOptions as $option) {
            if (!empty($option['missing_translation'])) {
                $missing[] = $option['amenity_code'];
            }
        }

        if (!empty($missing)) {
            return sprintf(
                $this->l('Translations missing for: %s.'),
                implode(', ', $missing)
            );
        }

        return '';
    }

    /**
     * @param KLResourceProfile $profile
     * @param KLResourceCapacity|null $capacity
     * @param array<int> $selectedAmenityIds
     *
     * @return string
     */
    protected function renderPreviewBlock($profile, $capacity, array $selectedAmenityIds)
    {
        if (!$profile || !Validate::isLoadedObject($profile)) {
            return '<p class="form-control-static text-muted">'.$this->l('Save the profile to preview how metadata will surface on the residency showcase.').'</p>';
        }

        $translator = $this->context->getTranslator();
        $capacitySummary = array();
        if ($capacity && $capacity->capacity_total) {
            $capacitySummary[] = $translator->trans(
                'Overnight capacity: %count% guests',
                array('%count%' => (int) $capacity->capacity_total),
                'Modules.Hotelreservationsystem.Admin'
            );
        }
        if ($capacity && $capacity->capacity_adults) {
            $capacitySummary[] = $translator->trans(
                'Adults: %count%',
                array('%count%' => (int) $capacity->capacity_adults),
                'Modules.Hotelreservationsystem.Admin'
            );
        }
        if ($capacity && $capacity->capacity_children) {
            $capacitySummary[] = $translator->trans(
                'Children: %count%',
                array('%count%' => (int) $capacity->capacity_children),
                'Modules.Hotelreservationsystem.Admin'
            );
        }
        if ($capacity && $capacity->capacity_seated) {
            $capacitySummary[] = $translator->trans(
                'Seated: %count%',
                array('%count%' => (int) $capacity->capacity_seated),
                'Modules.Hotelreservationsystem.Admin'
            );
        }
        if ($capacity && $capacity->capacity_standing) {
            $capacitySummary[] = $translator->trans(
                'Standing: %count%',
                array('%count%' => (int) $capacity->capacity_standing),
                'Modules.Hotelreservationsystem.Admin'
            );
        }

        $amenityLabels = $this->mapAmenityLabels($selectedAmenityIds);

        if (empty($capacitySummary) && empty($amenityLabels)) {
            return '<p class="form-control-static text-muted">'.$this->l('Capacity metrics and assigned amenities will be summarised here once saved.').'</p>';
        }

        $html = '<div class="well well-sm">';
        if (!empty($capacitySummary)) {
            $html .= '<strong>'.$this->l('Capacity cues').'</strong>';
            $html .= '<ul class="list-unstyled">';
            foreach ($capacitySummary as $line) {
                $html .= '<li>'.Tools::safeOutput($line).'</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($amenityLabels)) {
            $html .= '<strong>'.$this->l('Amenities').'</strong>';
            $html .= '<ul class="list-unstyled">';
            foreach ($amenityLabels as $label) {
                $html .= '<li>'.Tools::safeOutput($label).'</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p class="text-muted">'.$this->l('No amenities assigned yet. Tick the amenities that should appear on the showcase.').'</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<int> $selectedAmenityIds
     *
     * @return array<int, string>
     */
    protected function mapAmenityLabels(array $selectedAmenityIds)
    {
        if (empty($selectedAmenityIds)) {
            return array();
        }

        $labels = array();
        $options = $this->loadAmenityOptions();
        foreach ($options as $option) {
            if (in_array((int) $option['id'], $selectedAmenityIds, true)) {
                $labels[] = $option['label'];
            }
        }

        return $labels;
    }

    /**
     * @param int $idProfile
     *
     * @return string
     */
    protected function renderHistoryBlock($idProfile)
    {
        $idProfile = (int) $idProfile;
        if ($idProfile <= 0) {
            return '<p class="form-control-static text-muted">'.$this->l('History appears after the first save.').'</p>';
        }

        $latest = KLResourceHistory::getLatestForProfile($idProfile);
        if (!$latest) {
            return '<p class="form-control-static text-muted">'.$this->l('No change history captured yet. Save the profile to log a snapshot.').'</p>';
        }

        $author = $this->l('System');
        if (!empty($latest['id_employee'])) {
            $employee = new Employee((int) $latest['id_employee']);
            if (Validate::isLoadedObject($employee)) {
                $author = trim($employee->firstname.' '.$employee->lastname);
            }
        }

        $date = Tools::displayDate($latest['date_add'], null, true);
        $snapshot = $latest['snapshot'];
        $capacity = isset($snapshot['capacity']) && is_array($snapshot['capacity']) ? $snapshot['capacity'] : array();
        $amenities = isset($snapshot['amenities']) && is_array($snapshot['amenities']) ? $snapshot['amenities'] : array();

        $html = '<div class="alert alert-info">';
        $html .= '<p><strong>'.Tools::safeOutput($author).'</strong> — '.Tools::safeOutput($date).'</p>';
        $html .= '<ul class="list-unstyled">';
        if (!empty($capacity)) {
            $html .= '<li>'.sprintf(
                $this->l('Capacity snapshot: adults %1$s, children %2$s, total %3$s.'),
                isset($capacity['adults']) ? (int) $capacity['adults'] : '—',
                isset($capacity['children']) ? (int) $capacity['children'] : '—',
                isset($capacity['total']) ? (int) $capacity['total'] : '—'
            ).'</li>';
        }
        $html .= '<li>'.sprintf(
            $this->l('Amenities assigned: %s'),
            $amenities ? implode(', ', array_map('Tools::safeOutput', $amenities)) : $this->l('none')
        ).'</li>';
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
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

        $capacityCandidate = array(
            'capacity_adults' => Tools::getValue('capacity_adults'),
            'capacity_children' => Tools::getValue('capacity_children'),
            'capacity_total' => Tools::getValue('capacity_total'),
        );

        $capacityAdults = $capacityCandidate['capacity_adults'] !== '' ? (int) $capacityCandidate['capacity_adults'] : null;
        $capacityChildren = $capacityCandidate['capacity_children'] !== '' ? (int) $capacityCandidate['capacity_children'] : null;
        $capacityTotal = $capacityCandidate['capacity_total'] !== '' ? (int) $capacityCandidate['capacity_total'] : null;

        if ($capacityTotal !== null && $capacityAdults !== null && $capacityTotal < $capacityAdults) {
            $this->errors[] = $this->l('Total overnight capacity cannot be lower than adult capacity.');
        }
        if ($capacityTotal !== null && $capacityAdults !== null && $capacityChildren !== null
            && $capacityTotal < ($capacityAdults + $capacityChildren)
        ) {
            $this->errors[] = $this->l('Total overnight capacity cannot be lower than the sum of adult and child capacity.');
        }

        if (!$this->errors && !(int) Tools::getValue('display_order') && $resourceKind) {
            $_POST['display_order'] = KLResourceProfile::getNextDisplayOrder($resourceKind);
        }

        if (empty($this->errors)) {
            $guard = new KLResourceCapacityGuard();
            $idRoomType = (int) Tools::getValue('id_room_type', $this->object->id_room_type);
            $conflicts = $guard->findBlockingBookings((int) $this->object->id, $idRoomType, $capacityCandidate);
            if (!empty($conflicts)) {
                foreach ($conflicts as $conflict) {
                    $this->errors[] = sprintf(
                        $this->l('Booking #%1$d (%2$s → %3$s) currently requires %4$d %5$s which exceeds the proposed limit of %6$d.'),
                        (int) $conflict['id_booking'],
                        Tools::displayDate($conflict['date_from'], null, true),
                        Tools::displayDate($conflict['date_to'], null, true),
                        (int) $conflict['required'],
                        $this->getDimensionLabel($conflict['dimension']),
                        (int) $conflict['limit']
                    );
                }
            }
        }

        if (!empty($this->errors)) {
            return false;
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

            $selectedAmenityIds = $this->collectSelectedAmenityIds();
            if (!$this->syncAmenityLinks((int) $this->object->id, $selectedAmenityIds)) {
                $this->errors[] = $this->l('The profile was saved but amenities could not be updated. Please retry.');
                return false;
            }

            $this->logProfileHistory($capacity, $selectedAmenityIds);

            $this->confirmations[] = $this->l('Resource profile saved and amenities synchronised.');
        }

        return $result;
    }

    /**
     * @return array<int>
     */
    protected function collectSelectedAmenityIds()
    {
        $selected = array();
        $options = $this->loadAmenityOptions();
        foreach ($options as $option) {
            $fieldName = 'resource_amenities_'.$option['id'];
            if (Tools::getValue($fieldName)) {
                $selected[] = (int) $option['id'];
            }
        }

        return array_values(array_unique($selected));
    }

    /**
     * @param int       $idProfile
     * @param array<int> $selectedAmenityIds
     *
     * @return bool
     */
    protected function syncAmenityLinks($idProfile, array $selectedAmenityIds)
    {
        $idProfile = (int) $idProfile;
        if ($idProfile <= 0) {
            return true;
        }

        $selectedAmenityIds = array_values(array_unique(array_map('intval', $selectedAmenityIds)));
        $existing = KLAmenityLink::getAmenityIdsByProfile($idProfile);

        $toDelete = array_diff($existing, $selectedAmenityIds);
        if (!empty($toDelete)) {
            $where = 'id_kl_resource_profile = '.(int) $idProfile
                .' AND id_kl_resource_amenity IN ('.implode(',', array_map('intval', $toDelete)).')';
            if (!Db::getInstance()->delete('kl_resource_amenity_link', $where)) {
                return false;
            }
        }

        $toInsert = array_diff($selectedAmenityIds, $existing);
        foreach ($toInsert as $idAmenity) {
            $link = new KLAmenityLink();
            $link->id_kl_resource_profile = (int) $idProfile;
            $link->id_kl_resource_amenity = (int) $idAmenity;
            $link->is_required = 0;
            $link->note = null;

            if (!$link->add()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param KLResourceCapacity $capacity
     * @param array<int> $selectedAmenityIds
     */
    protected function logProfileHistory(KLResourceCapacity $capacity, array $selectedAmenityIds)
    {
        if (!$this->object || !Validate::isLoadedObject($this->object)) {
            return;
        }

        $amenityCodes = array();
        $options = $this->loadAmenityOptions();
        foreach ($options as $option) {
            if (in_array((int) $option['id'], $selectedAmenityIds, true)) {
                $amenityCodes[] = $option['amenity_code'] ?: (string) $option['id'];
            }
        }

        $payload = array(
            'resource' => array(
                'resource_code' => $this->object->resource_code,
                'resource_kind' => $this->object->resource_kind,
                'id_room_type' => (int) $this->object->id_room_type,
                'is_bookable' => (bool) $this->object->is_bookable,
                'is_published' => (bool) $this->object->is_published,
                'display_order' => (int) $this->object->display_order,
                'timezone' => $this->object->timezone,
            ),
            'capacity' => array(
                'adults' => $capacity->capacity_adults !== null ? (int) $capacity->capacity_adults : null,
                'children' => $capacity->capacity_children !== null ? (int) $capacity->capacity_children : null,
                'total' => $capacity->capacity_total !== null ? (int) $capacity->capacity_total : null,
                'seated' => $capacity->capacity_seated !== null ? (int) $capacity->capacity_seated : null,
                'standing' => $capacity->capacity_standing !== null ? (int) $capacity->capacity_standing : null,
                'floor_area_sqm' => $capacity->floor_area_sqm !== null ? (float) $capacity->floor_area_sqm : null,
                'ceiling_height_m' => $capacity->ceiling_height_m !== null ? (float) $capacity->ceiling_height_m : null,
            ),
            'amenities' => $amenityCodes,
        );

        $idEmployee = $this->context && $this->context->employee ? (int) $this->context->employee->id : null;
        KLResourceHistory::logChange((int) $this->object->id, 'admin-form', $payload, $idEmployee);
    }

    /**
     * @param string $dimension
     *
     * @return string
     */
    protected function getDimensionLabel($dimension)
    {
        switch ($dimension) {
            case 'adults':
                return $this->l('adults');
            case 'children':
                return $this->l('children');
            default:
                return $this->l('guests in total');
        }
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

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

class OurPropertiesControllerCore extends FrontController
{
    public $php_self = 'our-properties';

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;

        parent::initContent();
        $ourProperties = array(
            'generated_at' => date(DATE_ATOM),
            'intro' => Configuration::get('WK_HTL_SHORT_DESC', $this->context->language->id),
            'sections' => array(),
            'storytelling_enabled' => false,
            'module_active' => false,
        );

        $isModuleActive = Module::isInstalled('hotelreservationsystem') && Module::isEnabled('hotelreservationsystem');
        if ($isModuleActive) {
            include_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';

            $presenter = new HotelReservationSystemStorytellingPresenter($this->context);
            $storytellingEnabled = $presenter->isEnabled();
            $ourProperties['storytelling_enabled'] = $storytellingEnabled;
            $ourProperties['module_active'] = true;

            $idLang = (int) $this->context->language->id;
            $idShop = (int) $this->context->shop->id;

            $definitions = array(
                'residencies' => array(
                    'method' => 'presentResidenciesLanding',
                    'resource_kind' => KLResourceProfile::RESOURCE_KIND_ROOM,
                    'controller' => 'residencies',
                ),
                'ateliers' => array(
                    'method' => 'presentAteliersLanding',
                    'resource_kind' => KLResourceProfile::RESOURCE_KIND_ATELIER,
                    'controller' => 'ateliers',
                ),
                'gastronomy' => array(
                    'method' => 'presentGastronomyLanding',
                    'resource_kind' => KLResourceProfile::RESOURCE_KIND_GASTRONOMY,
                    'controller' => 'gastronomy',
                ),
            );

            foreach ($definitions as $sectionKey => $definition) {
                if (!isset($definition['method']) || !method_exists($presenter, $definition['method'])) {
                    continue;
                }

                $landing = $presenter->{$definition['method']}($idLang, $idShop);
                if (!is_array($landing)) {
                    continue;
                }

                $resourceKind = $definition['resource_kind'];
                $metadata = isset($landing['section_metadata'][$resourceKind]) ? $landing['section_metadata'][$resourceKind] : array();
                $sectionAnchor = isset($metadata['key']) ? $metadata['key'] : $sectionKey;

                $profiles = array();
                if (isset($landing['sections'][$sectionAnchor]['profiles']) && is_array($landing['sections'][$sectionAnchor]['profiles'])) {
                    $profiles = $landing['sections'][$sectionAnchor]['profiles'];
                }

                $displayProfiles = array_slice($profiles, 0, 3);
                $availability = array();
                if (isset($landing['availability']) && is_array($landing['availability'])) {
                    $availability['message'] = isset($landing['availability']['message']) ? $landing['availability']['message'] : null;

                    if (isset($landing['availability']['slots']) && is_array($landing['availability']['slots'])) {
                        $availability['slots'] = array_slice($landing['availability']['slots'], 0, 2);
                    } else {
                        $availability['slots'] = array();
                    }
                } else {
                    $availability = array('message' => null, 'slots' => array());
                }

                $section = array(
                    'key' => $sectionAnchor,
                    'resource_kind' => $resourceKind,
                    'title' => isset($metadata['title']) ? $metadata['title'] : Tools::ucfirst($sectionKey),
                    'intro' => isset($metadata['intro']) ? $metadata['intro'] : '',
                    'profiles' => $displayProfiles,
                    'total_profiles' => count($profiles),
                    'additional_profiles' => max(0, count($profiles) - count($displayProfiles)),
                    'availability' => $availability,
                    'inquiry_url' => isset($landing['inquiry_url']) ? $landing['inquiry_url'] : null,
                    'landing_url' => ($storytellingEnabled && isset($definition['controller']))
                        ? $this->context->link->getPageLink($definition['controller'])
                        : null,
                );

                $ourProperties['sections'][] = $section;
            }
        }

        $this->context->smarty->assign(
            array(
                'our_properties' => $ourProperties,
            )
        );

        $this->setTemplate(_PS_THEME_DIR_.'our-properties.tpl');
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS(_THEME_CSS_DIR_.'our-properties.css');
        if (Module::isInstalled('hotelreservationsystem') && Module::isEnabled('hotelreservationsystem')) {
            $this->addCSS(_THEME_CSS_DIR_.'storytelling.css');
        }
    }
}

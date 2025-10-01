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
 */

class HotelReservationSystemStorytellingPresenter
{
    const CMS_SLOT_KEYS = array(
        'residencies' => array(
            'hero' => 'KL_STORY_RESIDENCIES_HERO',
            'availability' => 'KL_STORY_RESIDENCIES_AVAILABILITY',
            'practical' => 'KL_STORY_RESIDENCIES_PRACTICAL',
            'faq' => 'KL_STORY_RESIDENCIES_FAQ',
            'testimonials' => 'KL_STORY_RESIDENCIES_TESTIMONIALS',
        ),
    );

    /**
     * @var Context
     */
    private $context;

    /**
     * @var TranslatorInterface|null
     */
    private $translator;

    /**
     * @param Context|null $context
     */
    public function __construct(?Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
        $this->translator = $this->context && method_exists($this->context, 'getTranslator')
            ? $this->context->getTranslator()
            : null;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return defined('_KUNSTORT_STORYTELLING_LAUNCH_') && _KUNSTORT_STORYTELLING_LAUNCH_;
    }

    /**
     * Builds the data payload required by the residencies storytelling template.
     *
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array<string, mixed>
     */
    public function presentResidenciesLanding($idLang = null, $idShop = null)
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        return array(
            'generated_at' => date(DATE_ATOM),
            'sections' => $this->groupProfilesByKind($idLang, $idShop),
            'availability' => $this->buildAvailabilitySnapshot($idLang, $idShop),
            'cms' => $this->resolveCmsSlots('residencies', $idLang, $idShop),
            'packages' => $this->getFeaturedPackages($idLang),
            'inquiry_url' => $context && $context->link
                ? $context->link->getPageLink('inquiry', true, null, array(
                    'utm_source' => 'story_residencies',
                ))
                : null,
        );
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupProfilesByKind($idLang, $idShop)
    {
        $profiles = KLResourceProfile::getPublishedProfilesWithDetails($idLang, $idShop);
        if (!$profiles) {
            return array();
        }

        $translator = $this->getTranslator();
        $sections = array(
            KLResourceProfile::RESOURCE_KIND_ROOM => array(
                'key' => 'residences',
                'anchor' => 'residences',
                'title' => $translator ? $translator->trans('Residency houses', array(), 'Modules.Hotelreservationsystem.Front') : 'Residency houses',
                'intro' => $translator ? $translator->trans('Private rooms and shared apartments hosting artists in residence.', array(), 'Modules.Hotelreservationsystem.Front') : 'Private rooms and shared apartments hosting artists in residence.',
                'profiles' => array(),
            ),
            KLResourceProfile::RESOURCE_KIND_ATELIER => array(
                'key' => 'ateliers',
                'anchor' => 'ateliers',
                'title' => $translator ? $translator->trans('Studios & ateliers', array(), 'Modules.Hotelreservationsystem.Front') : 'Studios & ateliers',
                'intro' => $translator ? $translator->trans('Workspaces prepared for production, rehearsal and collaboration.', array(), 'Modules.Hotelreservationsystem.Front') : 'Workspaces prepared for production, rehearsal and collaboration.',
                'profiles' => array(),
            ),
            KLResourceProfile::RESOURCE_KIND_GASTRONOMY => array(
                'key' => 'gastronomy',
                'anchor' => 'gastronomy',
                'title' => $translator ? $translator->trans('Gastronomy & communal dining', array(), 'Modules.Hotelreservationsystem.Front') : 'Gastronomy & communal dining',
                'intro' => $translator ? $translator->trans('Kitchens and dining rooms that support communal meals and catering.', array(), 'Modules.Hotelreservationsystem.Front') : 'Kitchens and dining rooms that support communal meals and catering.',
                'profiles' => array(),
            ),
            KLResourceProfile::RESOURCE_KIND_SEMINAR => array(
                'key' => 'programme',
                'anchor' => 'programme',
                'title' => $translator ? $translator->trans('Programme & gathering spaces', array(), 'Modules.Hotelreservationsystem.Front') : 'Programme & gathering spaces',
                'intro' => $translator ? $translator->trans('Halls for talks, workshops, rehearsals and performances.', array(), 'Modules.Hotelreservationsystem.Front') : 'Halls for talks, workshops, rehearsals and performances.',
                'profiles' => array(),
            ),
        );

        foreach ($profiles as $profile) {
            $resourceKind = $profile['resource_kind'];
            if (!isset($sections[$resourceKind])) {
                $sections[$resourceKind] = array(
                    'key' => Tools::strtolower($resourceKind),
                    'anchor' => Tools::strtolower($resourceKind),
                    'title' => Tools::ucfirst($resourceKind),
                    'intro' => '',
                    'profiles' => array(),
                );
            }

            $sections[$resourceKind]['profiles'][] = $this->formatProfileForSection($profile);
        }

        $ordered = array();
        foreach ($sections as $section) {
            $ordered[$section['key']] = $section;
        }

        return $ordered;
    }

    /**
     * @param array<string, mixed> $profile
     *
     * @return array<string, mixed>
     */
    protected function formatProfileForSection(array $profile)
    {
        $displayName = $profile['resource_code'];
        if (!empty($profile['story']['headline'])) {
            $displayName = trim($profile['story']['headline']);
        } elseif (!empty($profile['room_type_name'])) {
            $displayName = $profile['room_type_name'];
        }

        $excerptSource = '';
        if (!empty($profile['story']['excerpt'])) {
            $excerptSource = $profile['story']['excerpt'];
        } elseif (!empty($profile['story']['body'])) {
            $excerptSource = $profile['story']['body'];
        }
        $excerptText = trim(strip_tags($excerptSource));
        if ($excerptText !== '') {
            $excerptText = Tools::truncate($excerptText, 220, '…');
        }

        $capacitySummary = $this->summariseCapacity($profile);

        return array(
            'id_kl_resource_profile' => (int) $profile['id_kl_resource_profile'],
            'resource_code' => $profile['resource_code'],
            'display_name' => $displayName,
            'excerpt' => $excerptText,
            'capacity_summary' => $capacitySummary,
            'is_bookable' => (bool) $profile['is_bookable'],
            'timezone' => $profile['timezone'],
        );
    }

    /**
     * @param array<string, mixed> $profile
     *
     * @return array<int, string>
     */
    protected function summariseCapacity(array $profile)
    {
        $translator = $this->getTranslator();
        $capacity = isset($profile['capacity']) ? $profile['capacity'] : array();
        $summary = array();

        if (!empty($capacity['total'])) {
            if (!empty($capacity['adults']) && !empty($capacity['children'])) {
                $summary[] = $translator
                    ? $translator->trans(
                        'Sleeps up to %total% guests (%adults% adults + %children% children).',
                        array(
                            '%total%' => $capacity['total'],
                            '%adults%' => $capacity['adults'],
                            '%children%' => $capacity['children'],
                        ),
                        'Modules.Hotelreservationsystem.Front'
                    )
                    : sprintf(
                        'Sleeps up to %d guests (%d adults + %d children).',
                        $capacity['total'],
                        $capacity['adults'],
                        $capacity['children']
                    );
            } else {
                $summary[] = $translator
                    ? $translator->trans(
                        'Sleeps up to %total% guests.',
                        array('%total%' => $capacity['total']),
                        'Modules.Hotelreservationsystem.Front'
                    )
                    : sprintf('Sleeps up to %d guests.', $capacity['total']);
            }
        } else {
            if (!empty($capacity['adults'])) {
                $summary[] = $translator
                    ? $translator->trans(
                        'Adults capacity: %count%',
                        array('%count%' => $capacity['adults']),
                        'Modules.Hotelreservationsystem.Front'
                    )
                    : sprintf('Adults capacity: %d', $capacity['adults']);
            }
            if (!empty($capacity['children'])) {
                $summary[] = $translator
                    ? $translator->trans(
                        'Children capacity: %count%',
                        array('%count%' => $capacity['children']),
                        'Modules.Hotelreservationsystem.Front'
                    )
                    : sprintf('Children capacity: %d', $capacity['children']);
            }
        }

        if (!empty($capacity['seated'])) {
            $summary[] = $translator
                ? $translator->trans(
                    'Seated capacity: %count%',
                    array('%count%' => $capacity['seated']),
                    'Modules.Hotelreservationsystem.Front'
                )
                : sprintf('Seated capacity: %d', $capacity['seated']);
        }
        if (!empty($capacity['standing'])) {
            $summary[] = $translator
                ? $translator->trans(
                    'Standing capacity: %count%',
                    array('%count%' => $capacity['standing']),
                    'Modules.Hotelreservationsystem.Front'
                )
                : sprintf('Standing capacity: %d', $capacity['standing']);
        }
        if (!empty($capacity['floor_area_sqm'])) {
            $summary[] = $translator
                ? $translator->trans(
                    'Floor area: %sqm% m²',
                    array('%sqm%' => Tools::ps_round($capacity['floor_area_sqm'], 2)),
                    'Modules.Hotelreservationsystem.Front'
                )
                : sprintf('Floor area: %s m²', Tools::ps_round($capacity['floor_area_sqm'], 2));
        }
        if (!empty($capacity['ceiling_height_m'])) {
            $summary[] = $translator
                ? $translator->trans(
                    'Ceiling height: %height% m',
                    array('%height%' => Tools::ps_round($capacity['ceiling_height_m'], 2)),
                    'Modules.Hotelreservationsystem.Front'
                )
                : sprintf('Ceiling height: %s m', Tools::ps_round($capacity['ceiling_height_m'], 2));
        }
        if (!empty($profile['capacity_notes'])) {
            $summary[] = Tools::truncate(trim(strip_tags($profile['capacity_notes'])), 180, '…');
        }

        if (!empty($profile['is_bookable'])) {
            $summary[] = $translator
                ? $translator->trans('Bookable directly on the occupancy timeline.', array(), 'Modules.Hotelreservationsystem.Front')
                : 'Bookable directly on the occupancy timeline.';
        } else {
            $summary[] = $translator
                ? $translator->trans('Available on request via inquiry.', array(), 'Modules.Hotelreservationsystem.Front')
                : 'Available on request via inquiry.';
        }

        return $summary;
    }

    /**
     * @param string $group
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, array<string, mixed>|null>
     */
    protected function resolveCmsSlots($group, $idLang, $idShop)
    {
        if (!isset(self::CMS_SLOT_KEYS[$group])) {
            return array();
        }

        $slots = array();
        foreach (self::CMS_SLOT_KEYS[$group] as $slot => $configurationKey) {
            $slots[$slot] = $this->loadCmsSlot($configurationKey, $idLang, $idShop);
        }

        return $slots;
    }

    /**
     * @param string $configurationKey
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, mixed>|null
     */
    protected function loadCmsSlot($configurationKey, $idLang, $idShop)
    {
        $idCms = (int) Configuration::get($configurationKey, null, null, $idShop);
        if (!$idCms) {
            return null;
        }

        $cms = new CMS($idCms, $idLang, $idShop);
        if (!Validate::isLoadedObject($cms) || !$cms->active) {
            return null;
        }

        return array(
            'id_cms' => (int) $cms->id,
            'title' => $cms->meta_title,
            'content' => $cms->content,
            'link' => $this->context && $this->context->link ? $this->context->link->getCMSLink($cms, $cms->link_rewrite) : null,
        );
    }

    /**
     * @param int $idLang
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getFeaturedPackages($idLang)
    {
        $query = new DbQuery();
        $query->select('p.`id_kl_package`, p.`package_code`, p.`resource_kind_scope`');
        $query->select('pl.`name`, pl.`tagline`, pl.`description`');
        $query->from('kl_package', 'p');
        $query->innerJoin('kl_package_lang', 'pl', 'p.`id_kl_package` = pl.`id_kl_package` AND pl.`id_lang` = '.(int) $idLang);
        $query->where('p.`is_active` = 1');
        $query->where('p.`is_featured` = 1');
        $query->orderBy('pl.`name` ASC');

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (!$rows) {
            return array();
        }

        $packages = array();
        foreach ($rows as $row) {
            $packages[] = array(
                'id_kl_package' => (int) $row['id_kl_package'],
                'package_code' => $row['package_code'],
                'name' => $row['name'],
                'tagline' => $row['tagline'],
                'description' => $row['description'],
                'resource_kind_scope' => $this->decodeJsonArray($row['resource_kind_scope']),
            );
        }

        return $packages;
    }

    /**
     * Builds a placeholder availability payload until the booking bridge is connected.
     *
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, mixed>
     */
    protected function buildAvailabilitySnapshot($idLang, $idShop)
    {
        $translator = $this->getTranslator();

        return array(
            'status' => 'pending',
            'message' => $translator
                ? $translator->trans('Availability insights are being wired in and will surface here soon.', array(), 'Shop.Theme.Kunstort')
                : 'Availability insights are being wired in and will surface here soon.',
            'slots' => array(),
        );
    }

    /**
     * @param string|null $json
     *
     * @return array<int, mixed>
     */
    protected function decodeJsonArray($json)
    {
        if (!$json) {
            return array();
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return array();
        }

        return $decoded;
    }

    /**
     * @return TranslatorInterface|null
     */
    protected function getTranslator()
    {
        return $this->translator;
    }
}

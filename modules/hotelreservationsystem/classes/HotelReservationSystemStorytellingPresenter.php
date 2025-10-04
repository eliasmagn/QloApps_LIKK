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
    const AVAILABILITY_CACHE_TTL = 900;

    const CMS_SLOT_KEYS = array(
        'residencies' => array(
            'hero' => 'KL_STORY_RESIDENCIES_HERO',
            'availability' => 'KL_STORY_RESIDENCIES_AVAILABILITY',
            'practical' => 'KL_STORY_RESIDENCIES_PRACTICAL',
            'faq' => 'KL_STORY_RESIDENCIES_FAQ',
            'testimonials' => 'KL_STORY_RESIDENCIES_TESTIMONIALS',
        ),
        'ateliers' => array(
            'hero' => 'KL_STORY_ATELIERS_HERO',
            'availability' => 'KL_STORY_ATELIERS_AVAILABILITY',
            'practical' => 'KL_STORY_ATELIERS_PRACTICAL',
            'faq' => 'KL_STORY_ATELIERS_FAQ',
            'testimonials' => 'KL_STORY_ATELIERS_TESTIMONIALS',
        ),
        'gastronomy' => array(
            'hero' => 'KL_STORY_GASTRONOMY_HERO',
            'availability' => 'KL_STORY_GASTRONOMY_AVAILABILITY',
            'practical' => 'KL_STORY_GASTRONOMY_PRACTICAL',
            'faq' => 'KL_STORY_GASTRONOMY_FAQ',
            'testimonials' => 'KL_STORY_GASTRONOMY_TESTIMONIALS',
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
     * @var array<string, array<int, array<string, mixed>>> cache of published profiles per lang/shop
     */
    private $profileCache = array();

    /**
     * @var array<int, int> cache of active room counts per product id
     */
    private $roomCountCache = array();

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
     * Builds the data payload required by the ateliers storytelling template.
     *
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array<string, mixed>
     */
    public function presentAteliersLanding($idLang = null, $idShop = null)
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        $resourceKinds = array(KLResourceProfile::RESOURCE_KIND_ATELIER);

        return array(
            'generated_at' => date(DATE_ATOM),
            'sections' => $this->groupProfilesByKind($idLang, $idShop, $resourceKinds),
            'availability' => $this->buildAvailabilitySnapshot($idLang, $idShop, $resourceKinds),
            'cms' => $this->resolveCmsSlots('ateliers', $idLang, $idShop),
            'packages' => $this->filterPackagesByResourceKinds(
                $this->getFeaturedPackages($idLang),
                $resourceKinds
            ),
            'inquiry_url' => $context && $context->link
                ? $context->link->getPageLink('inquiry', true, null, array(
                    'utm_source' => 'story_ateliers',
                ))
                : null,
        );
    }

    /**
     * Builds the data payload required by the gastronomy storytelling template.
     *
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array<string, mixed>
     */
    public function presentGastronomyLanding($idLang = null, $idShop = null)
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        $resourceKinds = array(KLResourceProfile::RESOURCE_KIND_GASTRONOMY);

        return array(
            'generated_at' => date(DATE_ATOM),
            'sections' => $this->groupProfilesByKind($idLang, $idShop, $resourceKinds),
            'availability' => $this->buildAvailabilitySnapshot($idLang, $idShop, $resourceKinds),
            'cms' => $this->resolveCmsSlots('gastronomy', $idLang, $idShop),
            'packages' => $this->filterPackagesByResourceKinds(
                $this->getFeaturedPackages($idLang),
                $resourceKinds
            ),
            'inquiry_url' => $context && $context->link
                ? $context->link->getPageLink('inquiry', true, null, array(
                    'utm_source' => 'story_gastronomy',
                ))
                : null,
        );
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @param array<int, string> $allowedResourceKinds
     *
     * @return array<string, array<string, mixed>>
     */
    protected function groupProfilesByKind($idLang, $idShop, array $allowedResourceKinds = array())
    {
        $profiles = $this->getPublishedProfiles($idLang, $idShop);
        if (!$profiles) {
            return array();
        }

        $translator = $this->getTranslator();
        $allowed = array();
        if ($allowedResourceKinds) {
            foreach ($allowedResourceKinds as $kind) {
                $allowed[$kind] = true;
            }
        }

        $defaults = array(
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

        $sections = array();
        foreach ($defaults as $resourceKind => $section) {
            if ($allowed && !isset($allowed[$resourceKind])) {
                continue;
            }
            $sections[$resourceKind] = $section;
        }
        if (!$sections) {
            $sections = array();
        }

        foreach ($profiles as $profile) {
            $resourceKind = $profile['resource_kind'];
            if ($allowed && !isset($allowed[$resourceKind])) {
                continue;
            }
            if (!isset($sections[$resourceKind])) {
                if ($allowed && !isset($allowed[$resourceKind])) {
                    continue;
                }
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
        foreach ($sections as $resourceKind => $section) {
            if ($allowed && !isset($allowed[$resourceKind])) {
                continue;
            }
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
        $amenitySummary = $this->summariseAmenities($profile);
        $amenityDetails = isset($profile['amenities']) && is_array($profile['amenities']) ? $profile['amenities'] : array();

        return array(
            'id_kl_resource_profile' => (int) $profile['id_kl_resource_profile'],
            'resource_code' => $profile['resource_code'],
            'display_name' => $displayName,
            'excerpt' => $excerptText,
            'capacity_summary' => $capacitySummary,
            'is_bookable' => (bool) $profile['is_bookable'],
            'timezone' => $profile['timezone'],
            'amenities' => $amenitySummary,
            'amenity_details' => $amenityDetails,
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
     * @param array<string, mixed> $profile
     *
     * @return array<int, string>
     */
    protected function summariseAmenities(array $profile)
    {
        $amenities = isset($profile['amenities']) && is_array($profile['amenities']) ? $profile['amenities'] : array();
        if (!$amenities) {
            return array();
        }

        $translator = $this->getTranslator();
        $summary = array();

        foreach ($amenities as $amenity) {
            $label = '';
            if (!empty($amenity['label'])) {
                $label = trim($amenity['label']);
            } elseif (!empty($amenity['amenity_code'])) {
                $label = trim($amenity['amenity_code']);
            }

            if ($label === '') {
                continue;
            }

            $note = isset($amenity['note']) ? trim($amenity['note']) : '';
            if ($note !== '') {
                $label .= ' — '.$note;
            } elseif (!empty($amenity['is_required'])) {
                $requiredLabel = $translator
                    ? $translator->trans('Required', array(), 'Shop.Theme.Kunstort')
                    : 'Required';
                $label .= ' — '.$requiredLabel;
            }

            $summary[] = $label;
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
     * @param int $idShop
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getPublishedProfiles($idLang, $idShop)
    {
        $profiles = KLResourceProfile::getPublishedProfilesWithDetails($idLang, $idShop);
        if (!$profiles) {
            return array();
        }

        $profileIds = array();
        foreach ($profiles as &$profile) {
            $idProfile = isset($profile['id_kl_resource_profile']) ? (int) $profile['id_kl_resource_profile'] : 0;
            if ($idProfile > 0) {
                $profileIds[] = $idProfile;
            }
            if (!isset($profile['amenities']) || !is_array($profile['amenities'])) {
                $profile['amenities'] = array();
            }
        }
        unset($profile);

        $amenitiesByProfile = $this->loadAmenitiesForProfiles($profileIds);
        if ($amenitiesByProfile) {
            foreach ($profiles as &$profile) {
                $idProfile = isset($profile['id_kl_resource_profile']) ? (int) $profile['id_kl_resource_profile'] : 0;
                if ($idProfile > 0 && isset($amenitiesByProfile[$idProfile])) {
                    $profile['amenities'] = $amenitiesByProfile[$idProfile];
                }
            }
            unset($profile);
        }

        return $profiles;
    }

    /**
     * @param array<int, int> $profileIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    protected function loadAmenitiesForProfiles(array $profileIds)
    {
        if (empty($profileIds)) {
            return array();
        }

        $uniqueIds = array();
        foreach ($profileIds as $idProfile) {
            $idProfile = (int) $idProfile;
            if ($idProfile > 0) {
                $uniqueIds[$idProfile] = true;
            }
        }
        if (empty($uniqueIds)) {
            return array();
        }

        $idList = implode(',', array_keys($uniqueIds));

        $query = new DbQuery();
        $query->select('link.`id_kl_resource_profile`, link.`note`, link.`is_required`');
        $query->select('amenity.`id_kl_resource_amenity`, amenity.`amenity_code`, amenity.`category_code`, amenity.`translation_domain`');
        $query->from('kl_resource_amenity_link', 'link');
        $query->innerJoin('kl_resource_amenity', 'amenity', 'amenity.`id_kl_resource_amenity` = link.`id_kl_resource_amenity`');
        $query->where('link.`id_kl_resource_profile` IN ('.$idList.')');
        $query->where('amenity.`is_active` = 1');
        $query->orderBy('amenity.`category_code` ASC, amenity.`amenity_code` ASC');

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if (!$rows) {
            return array();
        }

        $amenitiesByProfile = array();
        foreach ($rows as $row) {
            $idProfile = isset($row['id_kl_resource_profile']) ? (int) $row['id_kl_resource_profile'] : 0;
            if ($idProfile <= 0) {
                continue;
            }

            $amenity = array(
                'id_kl_resource_amenity' => isset($row['id_kl_resource_amenity']) ? (int) $row['id_kl_resource_amenity'] : 0,
                'amenity_code' => isset($row['amenity_code']) ? trim($row['amenity_code']) : '',
                'category_code' => isset($row['category_code']) ? trim($row['category_code']) : '',
                'label' => $this->buildAmenityLabel($row),
                'note' => isset($row['note']) && $row['note'] !== null ? trim(strip_tags($row['note'])) : '',
                'is_required' => !empty($row['is_required']),
            );

            if (!isset($amenitiesByProfile[$idProfile])) {
                $amenitiesByProfile[$idProfile] = array();
            }
            $amenitiesByProfile[$idProfile][] = $amenity;
        }

        return $amenitiesByProfile;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return string
     */
    protected function buildAmenityLabel(array $row)
    {
        $category = isset($row['category_code']) ? trim($row['category_code']) : '';
        $code = isset($row['amenity_code']) ? trim($row['amenity_code']) : '';

        $parts = array();
        if ($category !== '') {
            $parts[] = $category;
        }
        if ($code !== '') {
            $parts[] = $code;
        }

        $label = implode(' — ', $parts);
        if ($label === '' && $code !== '') {
            $label = $code;
        }

        $translator = $this->getTranslator();
        if ($translator && $code !== '' && !empty($row['translation_domain'])) {
            $translated = $translator->trans($code, array(), $row['translation_domain']);
            if ($translated && $translated !== $code) {
                $label = $label !== '' ? $label.' · '.$translated : $translated;
            }
        }

        return $label;
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return string
     */
    protected function getAvailabilityCacheKey($idLang, $idShop, array $resourceKinds = array())
    {
        $suffix = '';
        if ($resourceKinds) {
            $sorted = $resourceKinds;
            sort($sorted);
            $suffix = '_'.md5(implode('-', $sorted));
        }

        return 'KL_STORY_AVAILABILITY_'.(int) $idShop.'_'.$idLang.$suffix;
    }

    /**
     * @param string $cacheKey
     *
     * @return array<string, mixed>|null
     */
    protected function retrieveAvailabilityCache($cacheKey)
    {
        if (!Cache::isStored($cacheKey)) {
            return null;
        }

        $cached = Cache::retrieve($cacheKey);
        if (!is_array($cached) || !isset($cached['expires_at']) || $cached['expires_at'] < time() || !isset($cached['payload'])) {
            return null;
        }

        return $cached['payload'];
    }

    /**
     * @param string $cacheKey
     * @param array<string, mixed> $payload
     *
     * @return void
     */
    protected function storeAvailabilityCache($cacheKey, array $payload)
    {
        Cache::store($cacheKey, array(
            'expires_at' => time() + self::AVAILABILITY_CACHE_TTL,
            'payload' => $payload,
        ));
    }

    /**
     * @param array<string, mixed> $profile
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $horizon
     *
     * @return array<string, mixed>|null
     */
    protected function findNextAvailabilityForProfile(array $profile, DateTimeImmutable $start, DateTimeImmutable $horizon)
    {
        $stayLength = $this->getDefaultStayLengthForKind($profile['resource_kind']);
        if ($stayLength <= 0) {
            $stayLength = 1;
        }

        $cursor = $start;
        $step = new DateInterval('P1D');

        while ($cursor < $horizon) {
            $end = $cursor->add(new DateInterval('P'.$stayLength.'D'));
            $availability = $this->calculateAvailableRooms($profile, $cursor, $end);
            if ($availability['available'] > 0) {
                return array(
                    'resource_kind' => $profile['resource_kind'],
                    'profile' => $profile,
                    'start' => $cursor,
                    'end' => $end,
                    'nights' => $stayLength,
                    'available_rooms' => $availability['available'],
                    'total_rooms' => $availability['total'],
                );
            }

            $cursor = $cursor->add($step);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $profile
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $end
     *
     * @return array<string, int>
     */
    protected function calculateAvailableRooms(array $profile, DateTimeImmutable $start, DateTimeImmutable $end)
    {
        $idProduct = (int) $profile['id_product'];
        $totalRooms = $this->getTotalActiveRooms($idProduct);
        if ($totalRooms <= 0) {
            return array('available' => 0, 'total' => 0);
        }

        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');

        $occupied = array();

        $bookingQuery = new DbQuery();
        $bookingQuery->select('DISTINCT `id_room`');
        $bookingQuery->from('htl_booking_detail');
        $bookingQuery->where('`id_product` = '.(int) $idProduct);
        $bookingQuery->where('`is_cancelled` = 0');
        $bookingQuery->where('`is_refunded` = 0');
        $bookingQuery->where("`date_from` < '".pSQL($endDate)."'");
        $bookingQuery->where("`date_to` > '".pSQL($startDate)."'");
        $bookedRooms = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($bookingQuery);
        if ($bookedRooms) {
            foreach ($bookedRooms as $row) {
                $occupied[(int) $row['id_room']] = true;
            }
        }

        if (!empty($profile['id_room_type'])) {
            $disableQuery = new DbQuery();
            $disableQuery->select('DISTINCT `id_room`');
            $disableQuery->from('htl_room_disable_dates');
            $disableQuery->where('`id_room_type` = '.(int) $profile['id_room_type']);
            $disableQuery->where("`date_from` < '".pSQL($endDate)."'");
            $disableQuery->where("`date_to` > '".pSQL($startDate)."'");
            $disabledRooms = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($disableQuery);
            if ($disabledRooms) {
                foreach ($disabledRooms as $row) {
                    $occupied[(int) $row['id_room']] = true;
                }
            }
        }

        $occupiedCount = count($occupied);
        $available = $totalRooms - $occupiedCount;
        if ($available < 0) {
            $available = 0;
        }

        return array(
            'available' => $available,
            'total' => $totalRooms,
        );
    }

    /**
     * @param array<string, mixed> $slot
     *
     * @return array<string, mixed>
     */
    protected function normaliseSlotForTemplate(array $slot)
    {
        /** @var DateTimeImmutable $start */
        $start = $slot['start'];
        /** @var DateTimeImmutable $end */
        $end = $slot['end'];
        $profile = $slot['profile'];

        $startLabel = Tools::displayDate($start->format('Y-m-d'));
        $endLabel = Tools::displayDate($end->format('Y-m-d'));
        $profileName = $this->getProfileDisplayName($profile);
        $durationLabel = $this->getDurationLabel($slot['nights'], $slot['resource_kind']);
        $availabilityNote = $this->formatAvailabilityNote($slot['available_rooms'], $slot['total_rooms']);

        $windowParts = array($profileName, sprintf('%s – %s', $startLabel, $endLabel));
        if ($durationLabel !== '') {
            $windowParts[] = $durationLabel;
        }
        if ($availabilityNote !== '') {
            $windowParts[] = $availabilityNote;
        }

        return array(
            'resource_kind' => $slot['resource_kind'],
            'label' => $this->getResourceKindLabel($slot['resource_kind']),
            'window' => implode(' · ', $windowParts),
            'start' => $start->format(DATE_ATOM),
            'end' => $end->format(DATE_ATOM),
            'available_rooms' => $slot['available_rooms'],
            'total_rooms' => $slot['total_rooms'],
            'profile_code' => $profile['resource_code'],
            'profile_display' => $profileName,
        );
    }

    /**
     * @param int $available
     * @param int $total
     *
     * @return string
     */
    protected function formatAvailabilityNote($available, $total)
    {
        if ($total <= 0 || $available <= 0) {
            return '';
        }

        $translator = $this->getTranslator();
        if ($available >= $total) {
            return $translator
                ? $translator->trans('All spaces available', array(), 'Shop.Theme.Kunstort')
                : 'All spaces available';
        }

        if ($available === 1) {
            return $translator
                ? $translator->trans('1 space available', array(), 'Shop.Theme.Kunstort')
                : '1 space available';
        }

        return $translator
            ? $translator->trans('%count% spaces available', array('%count%' => $available), 'Shop.Theme.Kunstort')
            : sprintf('%d spaces available', $available);
    }

    /**
     * @param string $resourceKind
     *
     * @return string
     */
    protected function getResourceKindLabel($resourceKind)
    {
        $translator = $this->getTranslator();

        switch ($resourceKind) {
            case KLResourceProfile::RESOURCE_KIND_ATELIER:
                return $translator
                    ? $translator->trans('Studios & ateliers', array(), 'Modules.Hotelreservationsystem.Front')
                    : 'Studios & ateliers';
            case KLResourceProfile::RESOURCE_KIND_GASTRONOMY:
                return $translator
                    ? $translator->trans('Gastronomy & communal dining', array(), 'Modules.Hotelreservationsystem.Front')
                    : 'Gastronomy & communal dining';
            case KLResourceProfile::RESOURCE_KIND_SEMINAR:
                return $translator
                    ? $translator->trans('Programme & gathering spaces', array(), 'Modules.Hotelreservationsystem.Front')
                    : 'Programme & gathering spaces';
            case KLResourceProfile::RESOURCE_KIND_ROOM:
            default:
                return $translator
                    ? $translator->trans('Residency houses', array(), 'Modules.Hotelreservationsystem.Front')
                    : 'Residency houses';
        }
    }

    /**
     * @param int $nights
     * @param string $resourceKind
     *
     * @return string
     */
    protected function getDurationLabel($nights, $resourceKind)
    {
        if ($nights <= 0) {
            return '';
        }

        $translator = $this->getTranslator();

        if ($resourceKind === KLResourceProfile::RESOURCE_KIND_ROOM) {
            if ($nights === 1) {
                return $translator
                    ? $translator->trans('1-night stay', array(), 'Shop.Theme.Kunstort')
                    : '1-night stay';
            }

            return $translator
                ? $translator->trans('%count%-night stay', array('%count%' => $nights), 'Shop.Theme.Kunstort')
                : sprintf('%d-night stay', $nights);
        }

        if ($nights === 1) {
            return $translator
                ? $translator->trans('1-day slot', array(), 'Shop.Theme.Kunstort')
                : '1-day slot';
        }

        return $translator
            ? $translator->trans('%count%-day slot', array('%count%' => $nights), 'Shop.Theme.Kunstort')
            : sprintf('%d-day slot', $nights);
    }

    /**
     * @param array<string, mixed> $profile
     *
     * @return string
     */
    protected function getProfileDisplayName(array $profile)
    {
        if (!empty($profile['story']['headline'])) {
            return trim($profile['story']['headline']);
        }

        if (!empty($profile['room_type_name'])) {
            return $profile['room_type_name'];
        }

        if (!empty($profile['resource_code'])) {
            return $profile['resource_code'];
        }

        $translator = $this->getTranslator();

        return $translator
            ? $translator->trans('Untitled space', array(), 'Shop.Theme.Kunstort')
            : 'Untitled space';
    }

    /**
     * @param int $idProduct
     *
     * @return int
     */
    protected function getTotalActiveRooms($idProduct)
    {
        if ($idProduct <= 0) {
            return 0;
        }

        if (isset($this->roomCountCache[$idProduct])) {
            return $this->roomCountCache[$idProduct];
        }

        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('htl_room_information');
        $query->where('`id_product` = '.(int) $idProduct);
        $query->where('`id_status` != '.(int) HotelRoomInformation::STATUS_INACTIVE);

        $count = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        $this->roomCountCache[$idProduct] = $count;

        return $count;
    }

    /**
     * @param string $resourceKind
     *
     * @return int
     */
    protected function getDefaultStayLengthForKind($resourceKind)
    {
        switch ($resourceKind) {
            case KLResourceProfile::RESOURCE_KIND_ATELIER:
                return 5;
            case KLResourceProfile::RESOURCE_KIND_GASTRONOMY:
                return 2;
            case KLResourceProfile::RESOURCE_KIND_SEMINAR:
                return 2;
            case KLResourceProfile::RESOURCE_KIND_ROOM:
            default:
                return 7;
        }
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
     * Builds the availability payload consumed by the residencies template.
     * Aggregates live booking/maintenance windows, caches results briefly and
     * returns per resource-kind highlights plus a status/message tuple.
     *
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, mixed>
     */
    protected function buildAvailabilitySnapshot($idLang, $idShop, array $allowedResourceKinds = array())
    {
        $cacheKey = $this->getAvailabilityCacheKey($idLang, $idShop, $allowedResourceKinds);
        if ($cached = $this->retrieveAvailabilityCache($cacheKey)) {
            return $cached;
        }

        $translator = $this->getTranslator();
        $profiles = $this->getPublishedProfiles($idLang, $idShop);
        $allowed = array();
        if ($allowedResourceKinds) {
            foreach ($allowedResourceKinds as $kind) {
                $allowed[$kind] = true;
            }
        }
        if ($allowed && $profiles) {
            $filteredProfiles = array();
            foreach ($profiles as $profile) {
                if (isset($allowed[$profile['resource_kind']])) {
                    $filteredProfiles[] = $profile;
                }
            }
            $profiles = $filteredProfiles;
        }
        if (!$profiles) {
            $payload = array(
                'status' => 'empty',
                'message' => $translator
                    ? $translator->trans('Availability insights will appear once resource profiles are published.', array(), 'Shop.Theme.Kunstort')
                    : 'Availability insights will appear once resource profiles are published.',
                'slots' => array(),
            );
            $this->storeAvailabilityCache($cacheKey, $payload);

            return $payload;
        }

        $start = new DateTimeImmutable('today');
        $horizon = $start->add(new DateInterval('P90D'));
        $slotsByKind = array();

        foreach ($profiles as $profile) {
            if ($allowed && !isset($allowed[$profile['resource_kind']])) {
                continue;
            }
            if (empty($profile['is_bookable']) || empty($profile['id_product']) || empty($profile['id_room_type'])) {
                continue;
            }

            $slot = $this->findNextAvailabilityForProfile($profile, $start, $horizon);
            if (!$slot) {
                continue;
            }

            $kind = $slot['resource_kind'];
            if (!isset($slotsByKind[$kind]) || $slot['start'] < $slotsByKind[$kind]['start']) {
                $slotsByKind[$kind] = $slot;
            }
        }

        $slots = array();
        foreach ($slotsByKind as $slot) {
            $slots[] = $this->normaliseSlotForTemplate($slot);
        }

        if ($slots) {
            usort($slots, function ($a, $b) {
                return strcmp($a['start'], $b['start']);
            });
        }

        if (!$slots) {
            $payload = array(
                'status' => 'pending',
                'message' => $translator
                    ? $translator->trans('We are crunching live bookings to surface the next open windows. Please check back shortly.', array(), 'Shop.Theme.Kunstort')
                    : 'We are crunching live bookings to surface the next open windows. Please check back shortly.',
                'slots' => array(),
            );
            $this->storeAvailabilityCache($cacheKey, $payload);

            return $payload;
        }

        $payload = array(
            'status' => 'ready',
            'message' => $translator
                ? $translator->trans('Availability refreshes every 15 minutes based on current bookings and maintenance blocks.', array(), 'Shop.Theme.Kunstort')
                : 'Availability refreshes every 15 minutes based on current bookings and maintenance blocks.',
            'slots' => $slots,
        );

        $this->storeAvailabilityCache($cacheKey, $payload);

        return $payload;
    }

    /**
     * @param array<int, array<string, mixed>> $packages
     * @param array<int, string> $resourceKinds
     *
     * @return array<int, array<string, mixed>>
     */
    protected function filterPackagesByResourceKinds(array $packages, array $resourceKinds)
    {
        if (!$resourceKinds) {
            return $packages;
        }

        $allowed = array();
        foreach ($resourceKinds as $kind) {
            $allowed[$kind] = true;
        }

        $filtered = array();
        foreach ($packages as $package) {
            $scope = isset($package['resource_kind_scope']) ? $package['resource_kind_scope'] : array();
            if (!is_array($scope)) {
                $scope = array();
            }
            if (!$scope) {
                $filtered[] = $package;
                continue;
            }

            foreach ($scope as $kind) {
                if (isset($allowed[$kind])) {
                    $filtered[] = $package;
                    break;
                }
            }
        }

        return $filtered;
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

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

    const PACKAGE_HIGHLIGHT_CACHE_TTL = 1800;

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
        'programme' => array(
            'hero' => 'KL_STORY_PROGRAMME_HERO',
            'highlights' => 'KL_STORY_PROGRAMME_HIGHLIGHTS',
            'availability' => 'KL_STORY_PROGRAMME_AVAILABILITY',
            'schedule' => 'KL_STORY_PROGRAMME_SCHEDULE',
            'inquiry' => 'KL_STORY_PROGRAMME_INQUIRY',
            'faq' => 'KL_STORY_PROGRAMME_FAQ',
        ),
        'home' => array(
            'hero' => 'KL_STORY_HOME_HERO',
            'residencies' => 'KL_STORY_HOME_RESIDENCIES',
            'ateliers' => 'KL_STORY_HOME_ATELIERS',
            'gastronomy' => 'KL_STORY_HOME_GASTRONOMY',
            'programme' => 'KL_STORY_HOME_PROGRAMME',
        ),
    );

    const MEDIA_BREAKPOINTS = array(480, 768, 1200);

    const MEDIA_SIZES_ATTRIBUTE = '(min-width: 75rem) 520px, (min-width: 48rem) 420px, 100vw';

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
     * @var array<string, array<string, mixed>> cache of package highlight payloads per lang/shop/package
     */
    private $packageHighlightCache = array();

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

        $availability = $this->buildAvailabilitySnapshot($idLang, $idShop);
        $availability = $this->applyInquiryUrls($availability, array(
            'utm_source' => 'story_residencies',
        ));

        return array(
            'generated_at' => date(DATE_ATOM),
            'sections' => $this->groupProfilesByKind($idLang, $idShop),
            'section_metadata' => $this->getSectionMetadata($idLang, $idShop),
            'availability' => $availability,
            'cms' => $this->resolveCmsSlots('residencies', $idLang, $idShop),
            'packages' => $this->buildPackageGroups(
                $idLang,
                $idShop,
                array(KLResourceProfile::RESOURCE_KIND_ROOM),
                array('utm_source' => 'story_residencies_package')
            ),
            'inquiry_url' => $context && $context->link
                ? $context->link->getPageLink('inquiry', true, null, array(
                    'utm_source' => 'story_residencies',
                ))
                : null,
            'resource_key' => 'residencies',
            'cms_endpoints' => $this->getCmsEndpointsForGroup('residencies'),
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

        $availability = $this->buildAvailabilitySnapshot($idLang, $idShop, $resourceKinds);
        $availability = $this->applyInquiryUrls($availability, array(
            'utm_source' => 'story_ateliers',
        ));

        return array(
            'generated_at' => date(DATE_ATOM),
            'sections' => $this->groupProfilesByKind($idLang, $idShop, $resourceKinds),
            'section_metadata' => $this->getSectionMetadata($idLang, $idShop, $resourceKinds),
            'availability' => $availability,
            'cms' => $this->resolveCmsSlots('ateliers', $idLang, $idShop),
            'packages' => $this->buildPackageGroups(
                $idLang,
                $idShop,
                $resourceKinds,
                array('utm_source' => 'story_ateliers_package')
            ),
            'inquiry_url' => $context && $context->link
                ? $context->link->getPageLink('inquiry', true, null, array(
                    'utm_source' => 'story_ateliers',
                ))
                : null,
            'resource_key' => 'ateliers',
            'cms_endpoints' => $this->getCmsEndpointsForGroup('ateliers'),
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

        $availability = $this->buildAvailabilitySnapshot($idLang, $idShop, $resourceKinds);
        $availability = $this->applyInquiryUrls($availability, array(
            'utm_source' => 'story_gastronomy',
        ));

        return array(
            'generated_at' => date(DATE_ATOM),
            'sections' => $this->groupProfilesByKind($idLang, $idShop, $resourceKinds),
            'section_metadata' => $this->getSectionMetadata($idLang, $idShop, $resourceKinds),
            'availability' => $availability,
            'cms' => $this->resolveCmsSlots('gastronomy', $idLang, $idShop),
            'packages' => $this->buildPackageGroups(
                $idLang,
                $idShop,
                $resourceKinds,
                array('utm_source' => 'story_gastronomy_package')
            ),
            'inquiry_url' => $context && $context->link
                ? $context->link->getPageLink('inquiry', true, null, array(
                    'utm_source' => 'story_gastronomy',
                ))
                : null,
            'resource_key' => 'gastronomy',
            'cms_endpoints' => $this->getCmsEndpointsForGroup('gastronomy'),
        );
    }

    /**
     * Builds the data payload required by the programme storytelling template.
     *
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array<string, mixed>
     */
    public function presentProgrammeLanding($idLang = null, $idShop = null)
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        $resourceKinds = array(KLResourceProfile::RESOURCE_KIND_SEMINAR);

        $availability = $this->buildAvailabilitySnapshot($idLang, $idShop, $resourceKinds);
        $availability = $this->applyInquiryUrls($availability, array(
            'utm_source' => 'story_programme',
        ));

        return array(
            'generated_at' => date(DATE_ATOM),
            'sections' => $this->groupProfilesByKind($idLang, $idShop, $resourceKinds),
            'section_metadata' => $this->getSectionMetadata($idLang, $idShop, $resourceKinds),
            'availability' => $availability,
            'cms' => $this->resolveCmsSlots('programme', $idLang, $idShop),
            'packages' => $this->buildPackageGroups(
                $idLang,
                $idShop,
                $resourceKinds,
                array('utm_source' => 'story_programme_package')
            ),
            'inquiry_url' => $context && $context->link
                ? $context->link->getPageLink('inquiry', true, null, array(
                    'utm_source' => 'story_programme',
                ))
                : null,
            'resource_key' => 'programme',
            'cms_endpoints' => $this->getCmsEndpointsForGroup('programme'),
        );
    }

    /**
     * Builds the data payload used by the home storytelling landing.
     *
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array<string, mixed>
     */
    public function presentHomeLanding($idLang = null, $idShop = null)
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        $cms = $this->resolveCmsSlots('home', $idLang, $idShop);

        $definitions = array(
            'residencies' => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_ROOM,
                'landing' => $this->presentResidenciesLanding($idLang, $idShop),
                'controller' => 'residencies',
                'utm_source' => 'story_home_residencies',
            ),
            'ateliers' => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_ATELIER,
                'landing' => $this->presentAteliersLanding($idLang, $idShop),
                'controller' => 'ateliers',
                'utm_source' => 'story_home_ateliers',
            ),
            'gastronomy' => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_GASTRONOMY,
                'landing' => $this->presentGastronomyLanding($idLang, $idShop),
                'controller' => 'gastronomy',
                'utm_source' => 'story_home_gastronomy',
            ),
            'programme' => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_SEMINAR,
                'landing' => $this->presentProgrammeLanding($idLang, $idShop),
                'controller' => 'programme',
                'utm_source' => 'story_home_programme',
            ),
        );

        $link = $context && $context->link ? $context->link : null;
        $sections = array();

        foreach ($definitions as $resourceKey => $definition) {
            $landing = isset($definition['landing']) && is_array($definition['landing']) ? $definition['landing'] : array();
            $sections[] = $this->buildHomeSectionPayload(
                $resourceKey,
                $definition['resource_kind'],
                $landing,
                array(
                    'cms' => isset($cms[$resourceKey]) ? $cms[$resourceKey] : null,
                    'landing_url' => $link ? $link->getPageLink($definition['controller']) : null,
                    'utm_source' => $definition['utm_source'],
                )
            );
        }

        return array(
            'generated_at' => date(DATE_ATOM),
            'resource_key' => 'home',
            'hero' => $this->buildHomeHeroPayload(isset($cms['hero']) ? $cms['hero'] : null),
            'sections' => array_values(array_filter($sections)),
            'cms' => $cms,
        );
    }

    /**
     * Returns published resource profile payloads for internal API consumers.
     *
     * @param int|null $idLang
     * @param int|null $idShop
     * @param array<int, string> $allowedResourceKinds
     * @param bool $includeAvailability
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProfilesForApi($idLang = null, $idShop = null, array $allowedResourceKinds = array(), $includeAvailability = false)
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        $profiles = $this->getPublishedProfiles($idLang, $idShop);
        if (!$profiles) {
            return array();
        }

        $allowed = array();
        if ($allowedResourceKinds) {
            foreach ($allowedResourceKinds as $kind) {
                $allowed[$kind] = true;
            }
        }

        if ($allowed) {
            $filtered = array();
            foreach ($profiles as $profile) {
                if (isset($profile['resource_kind']) && isset($allowed[$profile['resource_kind']])) {
                    $filtered[] = $profile;
                }
            }
            $profiles = $filtered;
        }

        if (!$profiles) {
            return array();
        }

        if ($includeAvailability) {
            $metadata = $this->getSectionMetadata($idLang, $idShop, $allowedResourceKinds);
            $start = new DateTimeImmutable('today');
            $horizon = $start->add(new DateInterval('P90D'));

            foreach ($profiles as &$profile) {
                if (!isset($profile['resource_kind'])) {
                    $profile['next_availability'] = null;
                    continue;
                }

                if (empty($profile['is_bookable']) || empty($profile['id_product']) || empty($profile['id_room_type'])) {
                    $profile['next_availability'] = null;
                    continue;
                }

                $slot = $this->findNextAvailabilityForProfile($profile, $start, $horizon);
                if (!$slot) {
                    $profile['next_availability'] = null;
                    continue;
                }

                $resourceKind = $profile['resource_kind'];
                $sectionMeta = isset($metadata[$resourceKind]) ? $metadata[$resourceKind] : array(
                    'resource_kind' => $resourceKind,
                    'key' => Tools::strtolower($resourceKind),
                    'anchor' => Tools::strtolower($resourceKind),
                    'title' => $this->getResourceKindLabel($resourceKind),
                    'intro' => '',
                );

                $profile['next_availability'] = $this->normaliseSlotForTemplate($slot, $sectionMeta);
            }
            unset($profile);
        }

        return array_values($profiles);
    }

    /**
     * Returns the aggregated availability snapshot used by storytelling surfaces.
     *
     * @param int|null $idLang
     * @param int|null $idShop
     * @param array<int, string> $allowedResourceKinds
     *
     * @return array<string, mixed>
     */
    public function getAvailabilitySnapshotForApi($idLang = null, $idShop = null, array $allowedResourceKinds = array())
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        return $this->buildAvailabilitySnapshot($idLang, $idShop, $allowedResourceKinds);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @param array<int, string> $allowedResourceKinds
     *
     * @return array<string, array<string, mixed>>
     */
    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @param array<int, string> $allowedResourceKinds
     *
     * @return array<string, array<string, mixed>> keyed by resource kind
     */
    protected function getSectionMetadata($idLang, $idShop, array $allowedResourceKinds = array())
    {
        $translator = $this->getTranslator();
        $metadata = array(
            KLResourceProfile::RESOURCE_KIND_ROOM => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_ROOM,
                'key' => 'residences',
                'anchor' => 'residences',
                'title' => $translator ? $translator->trans('Residency houses', array(), 'Modules.Hotelreservationsystem.Front') : 'Residency houses',
                'intro' => $translator ? $translator->trans('Private rooms and shared apartments hosting artists in residence.', array(), 'Modules.Hotelreservationsystem.Front') : 'Private rooms and shared apartments hosting artists in residence.',
            ),
            KLResourceProfile::RESOURCE_KIND_ATELIER => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_ATELIER,
                'key' => 'ateliers',
                'anchor' => 'ateliers',
                'title' => $translator ? $translator->trans('Studios & ateliers', array(), 'Modules.Hotelreservationsystem.Front') : 'Studios & ateliers',
                'intro' => $translator ? $translator->trans('Workspaces prepared for production, rehearsal and collaboration.', array(), 'Modules.Hotelreservationsystem.Front') : 'Workspaces prepared for production, rehearsal and collaboration.',
            ),
            KLResourceProfile::RESOURCE_KIND_GASTRONOMY => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_GASTRONOMY,
                'key' => 'gastronomy',
                'anchor' => 'gastronomy',
                'title' => $translator ? $translator->trans('Gastronomy & communal dining', array(), 'Modules.Hotelreservationsystem.Front') : 'Gastronomy & communal dining',
                'intro' => $translator ? $translator->trans('Kitchens and dining rooms that support communal meals and catering.', array(), 'Modules.Hotelreservationsystem.Front') : 'Kitchens and dining rooms that support communal meals and catering.',
            ),
            KLResourceProfile::RESOURCE_KIND_SEMINAR => array(
                'resource_kind' => KLResourceProfile::RESOURCE_KIND_SEMINAR,
                'key' => 'programme',
                'anchor' => 'programme',
                'title' => $translator ? $translator->trans('Programme & gathering spaces', array(), 'Modules.Hotelreservationsystem.Front') : 'Programme & gathering spaces',
                'intro' => $translator ? $translator->trans('Halls for talks, workshops, rehearsals and performances.', array(), 'Modules.Hotelreservationsystem.Front') : 'Halls for talks, workshops, rehearsals and performances.',
            ),
        );

        $allowed = array();
        if ($allowedResourceKinds) {
            foreach ($allowedResourceKinds as $kind) {
                $allowed[$kind] = true;
            }
        }

        if ($allowed) {
            foreach ($metadata as $resourceKind => $section) {
                if (!isset($allowed[$resourceKind])) {
                    unset($metadata[$resourceKind]);
                }
            }
        }

        return $metadata;
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
            $profiles = array();
        }

        $metadata = $this->getSectionMetadata($idLang, $idShop, $allowedResourceKinds);
        $allowed = array();
        if ($allowedResourceKinds) {
            foreach ($allowedResourceKinds as $kind) {
                $allowed[$kind] = true;
            }
        }

        $sections = array();
        foreach ($metadata as $resourceKind => $sectionMeta) {
            $sections[$resourceKind] = array_merge($sectionMeta, array(
                'profiles' => array(),
            ));
        }

        foreach ($profiles as $profile) {
            $resourceKind = $profile['resource_kind'];
            if ($allowed && !isset($allowed[$resourceKind])) {
                continue;
            }
            if (!isset($sections[$resourceKind])) {
                $sections[$resourceKind] = array(
                    'resource_kind' => $resourceKind,
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
        $story = isset($profile['story']) && is_array($profile['story']) ? $profile['story'] : array();

        $displayName = $profile['resource_code'];
        if (!empty($story['headline'])) {
            $displayName = trim($story['headline']);
        } elseif (!empty($profile['room_type_name'])) {
            $displayName = $profile['room_type_name'];
        }

        $excerptSource = '';
        if (!empty($story['excerpt'])) {
            $excerptSource = $story['excerpt'];
        } elseif (!empty($story['body'])) {
            $excerptSource = $story['body'];
        }
        $excerptText = trim(strip_tags($excerptSource));
        if ($excerptText !== '') {
            $excerptText = Tools::truncate($excerptText, 220, '…');
        }

        $capacitySummary = $this->summariseCapacity($profile);
        $amenitySummary = $this->summariseAmenities($profile);
        $amenityDetails = isset($profile['amenities']) && is_array($profile['amenities']) ? $profile['amenities'] : array();
        $media = $this->buildProfileMediaPayload($story, $displayName, $excerptText);

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
            'media' => $media,
        );
    }

    /**
     * @param array<string, mixed> $story
     * @param string $displayName
     * @param string $excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function buildProfileMediaPayload(array $story, $displayName, $excerpt)
    {
        if (empty($story['image_reference'])) {
            return null;
        }

        $normalizedReference = Tools::link_rewrite(trim((string) $story['image_reference']));
        if ($normalizedReference === '') {
            return null;
        }

        $absoluteBaseDir = rtrim(_PS_THEME_DIR_, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'storytelling'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR;
        $relativeBaseDir = _THEME_DIR_.'storytelling/media/';

        $formats = array(
            array('extension' => 'webp', 'type' => 'image/webp'),
            array('extension' => 'jpg', 'type' => 'image/jpeg'),
        );

        $sources = array();
        $fallback = null;

        foreach ($formats as $format) {
            $srcsetEntries = array();

            foreach (self::MEDIA_BREAKPOINTS as $width) {
                $fileName = $normalizedReference.'-'.(int) $width.'.'.$format['extension'];
                $absolutePath = $absoluteBaseDir.$fileName;
                if (!Tools::file_exists_cache($absolutePath)) {
                    continue;
                }

                $src = $this->context && $this->context->link
                    ? $this->context->link->getMediaLink($relativeBaseDir.$fileName)
                    : $relativeBaseDir.$fileName;

                $srcsetEntries[] = array(
                    'src' => $src,
                    'descriptor' => $width.'w',
                    'width' => (int) $width,
                );
            }

            if (!$srcsetEntries) {
                continue;
            }

            if ($format['extension'] === 'jpg' || $format['extension'] === 'jpeg') {
                $lastEntry = $srcsetEntries[count($srcsetEntries) - 1];
                $fallback = array(
                    'src' => $lastEntry['src'],
                    'width' => $lastEntry['width'],
                );
            }

            $sources[] = array(
                'type' => $format['type'],
                'srcset' => $this->compileSrcsetString($srcsetEntries),
                'sizes' => self::MEDIA_SIZES_ATTRIBUTE,
            );
        }

        if (!$sources) {
            return null;
        }

        if ($fallback === null) {
            $firstSource = $sources[0]['srcset'];
            $firstCandidate = trim(explode(',', $firstSource)[0]);
            $parts = preg_split('/\s+/', $firstCandidate);
            $fallback = array('src' => $parts[0]);
        }

        $altText = isset($story['alt_text']) ? trim(strip_tags($story['alt_text'])) : '';
        if ($altText === '') {
            $altText = $displayName;
        }

        $caption = null;
        $excerptText = trim((string) $excerpt);
        if ($altText !== '') {
            $caption = $altText;
        }
        if ($excerptText !== '') {
            $caption = Tools::truncate($excerptText, 200, '…');
        }

        return array(
            'reference' => $normalizedReference,
            'alt' => $altText,
            'caption' => $caption,
            'sources' => $sources,
            'fallback' => $fallback,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     *
     * @return string
     */
    protected function compileSrcsetString(array $entries)
    {
        $parts = array();
        foreach ($entries as $entry) {
            if (empty($entry['src']) || empty($entry['descriptor'])) {
                continue;
            }
            $parts[] = $entry['src'].' '.$entry['descriptor'];
        }

        return implode(', ', $parts);
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
    /**
     * @param string $group
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array<string, mixed>
     */
    public function getCmsSlotsForGroup($group, $idLang = null, $idShop = null)
    {
        $context = $this->context;
        $idLang = $idLang !== null ? (int) $idLang : ($context && $context->language ? (int) $context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = $idShop !== null ? (int) $idShop : ($context && $context->shop ? (int) $context->shop->id : 0);

        return $this->resolveCmsSlots($group, $idLang, $idShop);
    }

    /**
     * @param string $group
     *
     * @return array<string, string|null>
     */
    public function getCmsEndpointsForGroup($group)
    {
        $link = $this->context && $this->context->link ? $this->context->link : null;

        $endpoints = array(
            'testimonials' => null,
            'faq' => null,
        );

        if (!$link) {
            return $endpoints;
        }

        $params = array('resource' => $group);

        $endpoints['testimonials'] = $link->getModuleLink(
            'hotelreservationsystem',
            'inquirylookup',
            array_merge($params, array('action' => 'testimonials')),
            true
        );

        $endpoints['faq'] = $link->getModuleLink(
            'hotelreservationsystem',
            'inquirylookup',
            array_merge($params, array('action' => 'faq')),
            true
        );

        return $endpoints;
    }

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
        $idLang = (int) $idLang;
        $idShop = (int) $idShop;

        $cacheKey = $idLang.'-'.$idShop;
        if (isset($this->profileCache[$cacheKey])) {
            return $this->profileCache[$cacheKey];
        }

        $profiles = KLResourceProfile::getPublishedProfilesWithDetails($idLang, $idShop);
        if (!$profiles) {
            $this->profileCache[$cacheKey] = array();

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

        $this->profileCache[$cacheKey] = $profiles;

        return $profiles;
    }

    /**
     * @param array<string, mixed>|null $cmsHero
     *
     * @return array<string, mixed>
     */
    protected function buildHomeHeroPayload($cmsHero)
    {
        $translator = $this->getTranslator();

        return array(
            'cms' => $cmsHero,
            'headline' => $translator
                ? $translator->trans('Artist campus in Lehnin', array(), 'Modules.Hotelreservationsystem.Front')
                : 'Artist campus in Lehnin',
            'lead' => $translator
                ? $translator->trans('Residencies, studios, communal dining and programme spaces come together on a historic monastery campus. Explore the spaces and open an inquiry to plan your stay.', array(), 'Modules.Hotelreservationsystem.Front')
                : 'Residencies, studios, communal dining and programme spaces come together on a historic monastery campus. Explore the spaces and open an inquiry to plan your stay.',
            'cta_label' => $translator
                ? $translator->trans('Start an inquiry', array(), 'Modules.Hotelreservationsystem.Front')
                : 'Start an inquiry',
            'cta_url' => $this->buildInquiryLink('story_home_hero'),
        );
    }

    /**
     * @param string $resourceKey
     * @param string $resourceKind
     * @param array<string, mixed> $landing
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function buildHomeSectionPayload($resourceKey, $resourceKind, array $landing, array $options = array())
    {
        $translator = $this->getTranslator();
        $resourceLabel = $this->getResourceKindLabel($resourceKind);

        $cms = isset($options['cms']) ? $options['cms'] : null;
        $landingUrl = isset($options['landing_url']) ? $options['landing_url'] : null;
        $utmSource = isset($options['utm_source']) ? $options['utm_source'] : 'story_home_'.$resourceKey;

        $metadata = isset($landing['section_metadata']) && is_array($landing['section_metadata']) ? $landing['section_metadata'] : array();
        $sectionMeta = array();
        if ($metadata) {
            $first = reset($metadata);
            if (is_array($first)) {
                $sectionMeta = $first;
            }
        }
        if (!$sectionMeta) {
            $sectionMeta = array(
                'key' => $resourceKey,
                'anchor' => $resourceKey,
                'title' => $resourceLabel,
                'intro' => '',
            );
        }

        $sectionKey = isset($sectionMeta['key']) ? $sectionMeta['key'] : $resourceKey;
        $anchor = 'home-'.$sectionKey;

        $availability = array(
            'slot' => null,
            'message' => '',
            'status' => null,
        );
        if (isset($landing['availability']) && is_array($landing['availability'])) {
            $availabilityPayload = $this->applyInquiryUrls($landing['availability'], array(
                'utm_source' => $utmSource,
            ));
            if (isset($availabilityPayload['groups'][$sectionKey]['slot'])) {
                $availability['slot'] = $availabilityPayload['groups'][$sectionKey]['slot'];
            } elseif (isset($availabilityPayload['slots']) && is_array($availabilityPayload['slots']) && $availabilityPayload['slots']) {
                $availability['slot'] = $availabilityPayload['slots'][0];
            }
            if (isset($availabilityPayload['message'])) {
                $availability['message'] = $availabilityPayload['message'];
            }
            if (isset($availabilityPayload['status'])) {
                $availability['status'] = $availabilityPayload['status'];
            }
        }

        $sections = isset($landing['sections']) && is_array($landing['sections']) ? $landing['sections'] : array();
        $profiles = array();
        if (isset($sections[$sectionKey]['profiles']) && is_array($sections[$sectionKey]['profiles'])) {
            $profiles = array_slice($sections[$sectionKey]['profiles'], 0, 2);
        }

        $packageHighlight = null;
        if (isset($landing['packages']) && is_array($landing['packages'])) {
            foreach ($landing['packages'] as $group) {
                if (!isset($group['packages']) || !is_array($group['packages'])) {
                    continue;
                }
                foreach ($group['packages'] as $package) {
                    $packageHighlight = array(
                        'name' => isset($package['name']) ? $package['name'] : '',
                        'tagline' => isset($package['tagline']) ? $package['tagline'] : '',
                        'headline' => isset($package['highlight']['headline']) ? $package['highlight']['headline'] : '',
                        'message' => isset($package['highlight']['message']) ? $package['highlight']['message'] : '',
                        'cta_label' => isset($package['cta_label']) ? $package['cta_label'] : null,
                        'inquiry_url' => isset($package['inquiry_url']) ? $package['inquiry_url'] : null,
                    );
                    break 2;
                }
            }
        }

        $summaryHtml = null;
        if ($cms && isset($cms['content']) && trim((string) $cms['content']) !== '') {
            $summaryHtml = $cms['content'];
        }

        $inquiryParameters = array('resource_interests[]' => $resourceKind);
        $inquiryUrl = $this->buildInquiryLink($utmSource, $inquiryParameters);

        return array(
            'resource' => $resourceKey,
            'resource_kind' => $resourceKind,
            'key' => $sectionKey,
            'anchor' => $anchor,
            'title' => isset($sectionMeta['title']) ? $sectionMeta['title'] : $resourceLabel,
            'intro' => isset($sectionMeta['intro']) ? $sectionMeta['intro'] : '',
            'summary_html' => $summaryHtml,
            'nav_label' => $resourceLabel,
            'availability' => $availability,
            'profiles' => $profiles,
            'package' => $packageHighlight,
            'landing_url' => $landingUrl,
            'landing_label' => $translator
                ? $translator->trans('Explore %resource%', array('%resource%' => Tools::strtolower($resourceLabel)), 'Modules.Hotelreservationsystem.Front')
                : 'Explore '.Tools::strtolower($resourceLabel),
            'inquiry_url' => $inquiryUrl,
            'inquiry_label' => $translator
                ? $translator->trans('Start a %resource% inquiry', array('%resource%' => Tools::strtolower($resourceLabel)), 'Modules.Hotelreservationsystem.Front')
                : 'Start a '.Tools::strtolower($resourceLabel).' inquiry',
        );
    }

    /**
     * @param string $utmSource
     * @param array<string, mixed> $parameters
     *
     * @return string|null
     */
    protected function buildInquiryLink($utmSource, array $parameters = array())
    {
        $link = $this->context && $this->context->link ? $this->context->link : null;
        if (!$link) {
            return null;
        }

        $query = array_merge(array('utm_source' => $utmSource), $parameters);

        return $link->getPageLink('inquiry', true, null, $query);
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
    protected function normaliseSlotForTemplate(array $slot, array $sectionMetadata = array())
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

        $sectionKey = isset($sectionMetadata['key']) ? $sectionMetadata['key'] : Tools::strtolower($slot['resource_kind']);
        $sectionAnchor = isset($sectionMetadata['anchor']) ? $sectionMetadata['anchor'] : $sectionKey;
        $sectionLabel = isset($sectionMetadata['title']) && $sectionMetadata['title'] !== ''
            ? $sectionMetadata['title']
            : $this->getResourceKindLabel($slot['resource_kind']);
        $sectionIntro = isset($sectionMetadata['intro']) ? $sectionMetadata['intro'] : '';

        $arrivalDate = $start->format('Y-m-d');
        $departureDate = $end->format('Y-m-d');
        $inquiryParams = array(
            'arrival_date' => $arrivalDate,
            'departure_date' => $departureDate,
        );
        if (!empty($slot['resource_kind'])) {
            $inquiryParams['resource_kind'] = $slot['resource_kind'];
        }
        if (!empty($profile['resource_code'])) {
            $inquiryParams['resource_code'] = $profile['resource_code'];
        }

        $inquiryQuery = http_build_query($inquiryParams, '', '&', PHP_QUERY_RFC3986);

        return array(
            'resource_kind' => $slot['resource_kind'],
            'label' => $sectionLabel,
            'window' => implode(' · ', $windowParts),
            'start' => $start->format(DATE_ATOM),
            'end' => $end->format(DATE_ATOM),
            'start_date' => $arrivalDate,
            'end_date' => $departureDate,
            'available_rooms' => $slot['available_rooms'],
            'total_rooms' => $slot['total_rooms'],
            'profile_code' => $profile['resource_code'],
            'profile_display' => $profileName,
            'section_key' => $sectionKey,
            'section_anchor' => $sectionAnchor,
            'section_intro' => $sectionIntro,
            'inquiry_params' => $inquiryParams,
            'inquiry_query' => $inquiryQuery,
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
        $query->select('p.`id_kl_rate_plan`, p.`duration_mode`, p.`duration_value`');
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
                'id_kl_rate_plan' => isset($row['id_kl_rate_plan']) ? (int) $row['id_kl_rate_plan'] : 0,
                'duration_mode' => isset($row['duration_mode']) ? $row['duration_mode'] : null,
                'duration_value' => isset($row['duration_value']) ? (int) $row['duration_value'] : null,
            );
        }

        return $packages;
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @param array<int, string> $allowedResourceKinds
     * @param array<string, string> $baseParameters
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildPackageGroups($idLang, $idShop, array $allowedResourceKinds = array(), array $baseParameters = array())
    {
        $packages = $this->getFeaturedPackages($idLang);
        if (!$packages) {
            return array();
        }

        return $this->groupPackagesByScope($packages, $idLang, $idShop, $allowedResourceKinds, $baseParameters);
    }

    /**
     * @param array<int, array<string, mixed>> $packages
     * @param int $idLang
     * @param int $idShop
     *
     * @param array<int, string> $allowedResourceKinds
     * @param array<string, string> $baseParameters
     *
     * @return array<int, array<string, mixed>>
     */
    protected function groupPackagesByScope(array $packages, $idLang, $idShop, array $allowedResourceKinds, array $baseParameters)
    {
        $metadata = $this->getSectionMetadata($idLang, $idShop, $allowedResourceKinds);
        $translator = $this->getTranslator();
        $link = $this->context && $this->context->link ? $this->context->link : null;

        $allowed = array();
        foreach ($allowedResourceKinds as $kind) {
            $allowed[$kind] = true;
        }

        $groups = array();
        foreach ($metadata as $resourceKind => $section) {
            if ($allowed && !isset($allowed[$resourceKind])) {
                continue;
            }

            $groups[$section['key']] = array(
                'key' => $section['key'],
                'resource_kind' => $resourceKind,
                'label' => $section['title'],
                'anchor' => $section['anchor'],
                'intro' => $section['intro'],
                'packages' => array(),
            );
        }

        $generalKey = 'campus_highlights';
        $generalAnchor = 'campus-highlights';

        foreach ($packages as $package) {
            $scopes = isset($package['resource_kind_scope']) && is_array($package['resource_kind_scope'])
                ? array_values(array_unique(array_filter(array_map('strval', $package['resource_kind_scope']))))
                : array();

            $scopesToApply = $scopes;
            if (!$scopesToApply) {
                $scopesToApply = array(null);
            }

            foreach ($scopesToApply as $scope) {
                if ($scope !== null && $allowed && !isset($allowed[$scope])) {
                    continue;
                }

                if ($scope === null && $allowed && $scopes) {
                    continue;
                }

                $groupKey = null;

                if ($scope !== null && isset($metadata[$scope])) {
                    $groupKey = $metadata[$scope]['key'];
                } elseif ($scope !== null) {
                    $groupKey = Tools::link_rewrite($scope);
                    if ($groupKey === '') {
                        $groupKey = 'scope_'.md5($scope);
                    }
                    if (!isset($groups[$groupKey])) {
                        $groups[$groupKey] = array(
                            'key' => $groupKey,
                            'resource_kind' => $scope,
                            'label' => $this->buildFallbackScopeLabel($scope),
                            'anchor' => $groupKey,
                            'intro' => '',
                            'packages' => array(),
                        );
                    }
                } else {
                    if (!isset($groups[$generalKey])) {
                        $groups[$generalKey] = array(
                            'key' => $generalKey,
                            'resource_kind' => null,
                            'label' => $translator
                                ? $translator->trans('Campus-wide highlights', array(), 'Shop.Theme.Kunstort')
                                : 'Campus-wide highlights',
                            'anchor' => $generalAnchor,
                            'intro' => $translator
                                ? $translator->trans('These packages span multiple space types—start an inquiry and we will tailor the details together.', array(), 'Shop.Theme.Kunstort')
                                : 'These packages span multiple space types—start an inquiry and we will tailor the details together.',
                            'packages' => array(),
                        );
                    }
                    $groupKey = $generalKey;
                }

                if ($groupKey === null || !isset($groups[$groupKey])) {
                    continue;
                }

                $groups[$groupKey]['packages'][] = $this->buildPackageEntryForStorytelling(
                    $package,
                    $scope,
                    $baseParameters,
                    $link,
                    $idLang,
                    $idShop
                );
            }
        }

        foreach ($groups as &$group) {
            if (!$group['packages']) {
                continue;
            }

            usort($group['packages'], function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
        }
        unset($group);

        $groups = array_filter($groups, function ($group) {
            return !empty($group['packages']);
        });

        return array_values($groups);
    }

    /**
     * @param array<string, mixed> $package
     * @param string|null $scope
     * @param array<string, string> $baseParameters
     * @param Link|null $link
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, mixed>
     */
    protected function buildPackageEntryForStorytelling(array $package, $scope, array $baseParameters, $link, $idLang, $idShop)
    {
        $translator = $this->getTranslator();

        $entry = array(
            'id_kl_package' => isset($package['id_kl_package']) ? (int) $package['id_kl_package'] : 0,
            'package_code' => isset($package['package_code']) ? $package['package_code'] : '',
            'name' => isset($package['name']) ? $package['name'] : '',
            'tagline' => isset($package['tagline']) ? $package['tagline'] : '',
            'description' => isset($package['description']) ? $package['description'] : '',
            'resource_kind_scope' => isset($package['resource_kind_scope']) && is_array($package['resource_kind_scope'])
                ? $package['resource_kind_scope']
                : array(),
            'primary_scope' => $scope,
        );

        $parameters = $baseParameters;
        if ($scope !== null && $scope !== '') {
            $parameters['resource_kind'] = $scope;
        }
        if (!empty($entry['package_code'])) {
            $parameters['package_code'] = $entry['package_code'];
        }

        $entry['inquiry_params'] = $parameters;
        $entry['inquiry_query'] = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        if ($link) {
            $entry['inquiry_url'] = $link->getPageLink('inquiry', true, null, $parameters);
        } else {
            $entry['inquiry_url'] = 'index.php?controller=inquiry';
            if ($entry['inquiry_query'] !== '') {
                $entry['inquiry_url'] .= '&'.$entry['inquiry_query'];
            }
        }

        $entry['cta_label'] = $translator
            ? $translator->trans('Request this package', array(), 'Shop.Theme.Kunstort')
            : 'Request this package';

        $entry['highlight'] = $this->resolvePackageHighlight($package, $idLang, $idShop);

        return $entry;
    }

    /**
     * @param array<string, mixed> $package
     * @param int $idLang
     * @param int $idShop
     *
     * @return array<string, mixed>
     */
    protected function resolvePackageHighlight(array $package, $idLang, $idShop)
    {
        $translator = $this->getTranslator();

        $message = $translator
            ? $translator->trans('Pricing preview coming soon.', array(), 'Shop.Theme.Kunstort')
            : 'Pricing preview coming soon.';

        $baseHighlight = array(
            'status' => 'pending',
            'message' => $message,
            'warnings' => array(),
            'generated_at' => date(DATE_ATOM),
        );

        $idPackage = isset($package['id_kl_package']) ? (int) $package['id_kl_package'] : 0;
        if ($idPackage <= 0) {
            return $baseHighlight;
        }

        $cacheKey = $this->getPackageHighlightCacheKey($idLang, $idShop, $idPackage);
        if ($cacheKey !== '' && isset($this->packageHighlightCache[$cacheKey])) {
            return $this->packageHighlightCache[$cacheKey];
        }

        if ($cacheKey !== '') {
            $cached = $this->retrievePackageHighlightCache($cacheKey);
            if ($cached !== null) {
                $this->packageHighlightCache[$cacheKey] = $cached;

                return $cached;
            }
        }

        $highlight = $this->buildPackageHighlightPayload($package, $idLang, $idShop, $baseHighlight);

        if ($cacheKey !== '') {
            $this->packageHighlightCache[$cacheKey] = $highlight;
            $this->storePackageHighlightCache($cacheKey, $highlight);
        }

        return $highlight;
    }

    /**
     * @param array<string, mixed> $package
     * @param int $idLang
     * @param int $idShop
     * @param array<string, mixed> $fallback
     *
     * @return array<string, mixed>
     */
    protected function buildPackageHighlightPayload(array $package, $idLang, $idShop, array $fallback)
    {
        $translator = $this->getTranslator();

        $idRatePlan = isset($package['id_kl_rate_plan']) ? (int) $package['id_kl_rate_plan'] : 0;
        if ($idRatePlan <= 0) {
            return $fallback;
        }

        $plan = new KLRatePlan($idRatePlan, $idLang);
        if (!Validate::isLoadedObject($plan) || !$plan->is_active) {
            return $fallback;
        }

        $idProfile = $this->findHighlightResourceProfileId($package, $plan, $idLang, $idShop);
        if ($idProfile <= 0) {
            $fallback['message'] = $translator
                ? $translator->trans('Pricing preview activates once sample resources are published.', array(), 'Shop.Theme.Kunstort')
                : 'Pricing preview activates once sample resources are published.';

            return $fallback;
        }

        $stay = $this->buildCanonicalStayWindow($package, $plan);

        try {
            $quote = KLQuotePricingEngine::generateQuote(array(
                'id_kl_rate_plan' => (int) $plan->id,
                'id_kl_package' => isset($package['id_kl_package']) ? (int) $package['id_kl_package'] : 0,
                'id_kl_resource_profile' => $idProfile,
                'check_in' => $stay['check_in'],
                'check_out' => $stay['check_out'],
                'id_lang' => (int) $idLang,
            ));
        } catch (Exception $exception) {
            $fallback['status'] = 'error';
            $fallback['message'] = $translator
                ? $translator->trans('Pricing preview is temporarily unavailable.', array(), 'Shop.Theme.Kunstort')
                : 'Pricing preview is temporarily unavailable.';

            return $fallback;
        }

        $price = $this->formatMinorCurrency(
            isset($quote['gross_total_minor']) ? (int) $quote['gross_total_minor'] : 0,
            isset($quote['currency_iso_code']) ? (string) $quote['currency_iso_code'] : '',
            $idShop
        );

        $headline = $translator
            ? $translator->trans('Starting from %price%', array('%price%' => $price['formatted']), 'Shop.Theme.Kunstort')
            : 'Starting from '.$price['formatted'];

        $nights = isset($quote['nights']) ? (int) $quote['nights'] : 0;
        if ($nights <= 0) {
            $nights = isset($stay['nights']) ? (int) $stay['nights'] : 1;
        }
        if ($nights <= 0) {
            $nights = 1;
        }

        $planLabel = '';
        if (isset($quote['plan']) && is_array($quote['plan'])) {
            if (!empty($quote['plan']['name'])) {
                $planLabel = (string) $quote['plan']['name'];
            } elseif (!empty($quote['plan']['plan_code'])) {
                $planLabel = (string) $quote['plan']['plan_code'];
            }
        }
        $planLabel = trim($planLabel);

        if ($planLabel !== '') {
            $sampleLabel = $translator
                ? $translator->trans(
                    'Sample stay: %nights%-night stay on the %plan% plan.',
                    array(
                        '%nights%' => $nights,
                        '%plan%' => $planLabel,
                    ),
                    'Shop.Theme.Kunstort'
                )
                : sprintf('Sample stay: %d-night stay on the %s plan.', $nights, $planLabel);
        } else {
            $sampleLabel = $translator
                ? $translator->trans(
                    'Sample stay: %nights%-night stay.',
                    array('%nights%' => $nights),
                    'Shop.Theme.Kunstort'
                )
                : sprintf('Sample stay: %d-night stay.', $nights);
        }

        $inclusions = $this->summariseHighlightInclusions($quote);
        $inclusionsLabel = '';
        if (!empty($inclusions)) {
            $inclusionsLabel = $translator
                ? $translator->trans(
                    'Includes: %list%',
                    array('%list%' => implode(', ', $inclusions)),
                    'Shop.Theme.Kunstort'
                )
                : 'Includes: '.implode(', ', $inclusions);
        }

        $warningLabel = '';
        if (!empty($quote['warnings'])) {
            $warningLabel = $translator
                ? $translator->trans('Additional conditions apply—our team will confirm the final quote.', array(), 'Shop.Theme.Kunstort')
                : 'Additional conditions apply—our team will confirm the final quote.';
        }

        return array(
            'status' => 'ready',
            'message' => '',
            'headline' => $headline,
            'price' => $price,
            'sample_label' => $sampleLabel,
            'inclusions' => $inclusions,
            'inclusions_label' => $inclusionsLabel,
            'warning_label' => $warningLabel,
            'warnings' => isset($quote['warnings']) && is_array($quote['warnings']) ? $quote['warnings'] : array(),
            'generated_at' => date(DATE_ATOM),
        );
    }

    /**
     * @param array<string, mixed> $package
     * @param KLRatePlan $plan
     * @param int $idLang
     * @param int $idShop
     *
     * @return int
     */
    protected function findHighlightResourceProfileId(array $package, KLRatePlan $plan, $idLang, $idShop)
    {
        $profiles = $this->getPublishedProfiles($idLang, $idShop);
        if (empty($profiles)) {
            return 0;
        }

        $scopes = array();
        if (!empty($package['resource_kind_scope']) && is_array($package['resource_kind_scope'])) {
            foreach ($package['resource_kind_scope'] as $scope) {
                $scope = trim((string) $scope);
                if ($scope !== '') {
                    $scopes[] = $scope;
                }
            }
        }

        if (!$scopes) {
            $planScopes = $plan->getResourceKindScope();
            if (!empty($planScopes)) {
                foreach ($planScopes as $scope) {
                    $scope = trim((string) $scope);
                    if ($scope !== '') {
                        $scopes[] = $scope;
                    }
                }
            }
        }

        if (!$scopes) {
            $scopes = KLResourceProfile::getSupportedResourceKinds();
        }

        foreach ($scopes as $scope) {
            $idProfile = $this->pickProfileForHighlight($profiles, (string) $scope);
            if ($idProfile > 0) {
                return $idProfile;
            }
        }

        return $this->pickProfileForHighlight($profiles, null);
    }

    /**
     * @param array<int, array<string, mixed>> $profiles
     * @param string|null $resourceKind
     *
     * @return int
     */
    protected function pickProfileForHighlight(array $profiles, $resourceKind)
    {
        foreach ($profiles as $profile) {
            $profileKind = isset($profile['resource_kind']) ? (string) $profile['resource_kind'] : '';
            if ($resourceKind !== null && $profileKind !== (string) $resourceKind) {
                continue;
            }
            if (empty($profile['is_bookable'])) {
                continue;
            }
            if (empty($profile['id_kl_resource_profile'])) {
                continue;
            }
            if (empty($profile['id_product'])) {
                continue;
            }

            return (int) $profile['id_kl_resource_profile'];
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $package
     * @param KLRatePlan $plan
     *
     * @return array<string, mixed>
     */
    protected function buildCanonicalStayWindow(array $package, KLRatePlan $plan)
    {
        $durationMode = isset($package['duration_mode']) ? (string) $package['duration_mode'] : '';
        $durationValue = isset($package['duration_value']) ? (int) $package['duration_value'] : 0;

        $nights = $this->resolveCanonicalNights($durationMode, $durationValue, $plan);

        try {
            $start = new DateTimeImmutable('first day of next month');
        } catch (Exception $exception) {
            $start = new DateTimeImmutable('now');
        }

        $checkIn = $start->format('Y-m-d');
        $checkOut = $start->add(new DateInterval('P'.$nights.'D'))->format('Y-m-d');

        return array(
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => $nights,
        );
    }

    /**
     * @param string $durationMode
     * @param int $durationValue
     * @param KLRatePlan $plan
     *
     * @return int
     */
    protected function resolveCanonicalNights($durationMode, $durationValue, KLRatePlan $plan)
    {
        $value = (int) $durationValue;

        switch ($durationMode) {
            case 'nights':
                $nights = max(1, $value);
                break;
            case 'days':
                $nights = max(1, $value > 0 ? $value - 1 : 0);
                break;
            case 'weeks':
                $nights = max(1, $value * 7);
                break;
            default:
                if ($plan->pricing_method === 'weekly') {
                    $nights = 7;
                } elseif ($plan->pricing_method === 'nightly') {
                    $nights = max(1, $value);
                } else {
                    $nights = max(1, $value);
                }
        }

        return $nights;
    }

    /**
     * @param array<string, mixed> $quote
     *
     * @return array<int, string>
     */
    protected function summariseHighlightInclusions(array $quote)
    {
        if (empty($quote['component_applications']) || !is_array($quote['component_applications'])) {
            return array();
        }

        $labels = $this->getComponentTypeLabels();
        $inclusions = array();

        foreach ($quote['component_applications'] as $component) {
            if (!is_array($component)) {
                continue;
            }

            $isOptional = !empty($component['is_optional']);
            $selected = isset($component['selected']) ? (bool) $component['selected'] : true;
            if ($isOptional && !$selected) {
                continue;
            }

            $type = '';
            if (isset($component['metadata']) && is_array($component['metadata']) && !empty($component['metadata']['component_type'])) {
                $type = Tools::strtolower((string) $component['metadata']['component_type']);
            } elseif (!empty($component['label'])) {
                $type = Tools::strtolower((string) $component['label']);
            }

            $label = '';
            if ($type !== '' && isset($labels[$type])) {
                $label = $labels[$type];
            } elseif ($type !== '') {
                $label = Tools::ucfirst(str_replace('_', ' ', $type));
            }

            if ($label === '' && !empty($component['label'])) {
                $label = (string) $component['label'];
            }

            $label = trim($label);
            if ($label === '') {
                continue;
            }

            $inclusions[$label] = true;
        }

        return array_keys($inclusions);
    }

    /**
     * @return array<string, string>
     */
    protected function getComponentTypeLabels()
    {
        $translator = $this->getTranslator();

        return array(
            'lodging' => $translator
                ? $translator->trans('Residency lodging', array(), 'Shop.Theme.Kunstort')
                : 'Residency lodging',
            'atelier' => $translator
                ? $translator->trans('Atelier or studio session', array(), 'Shop.Theme.Kunstort')
                : 'Atelier or studio session',
            'meal' => $translator
                ? $translator->trans('Meal or catering service', array(), 'Shop.Theme.Kunstort')
                : 'Meal or catering service',
            'experience' => $translator
                ? $translator->trans('Experience or excursion', array(), 'Shop.Theme.Kunstort')
                : 'Experience or excursion',
            'custom' => $translator
                ? $translator->trans('Custom component', array(), 'Shop.Theme.Kunstort')
                : 'Custom component',
        );
    }

    /**
     * @param int $amountMinor
     * @param string $currencyIso
     * @param int $idShop
     *
     * @return array<string, mixed>
     */
    protected function formatMinorCurrency($amountMinor, $currencyIso, $idShop)
    {
        $amountMinor = (int) $amountMinor;
        $currencyIso = (string) $currencyIso;
        $idShop = (int) $idShop;

        $idCurrency = 0;
        if ($currencyIso !== '') {
            $idCurrency = (int) Currency::getIdByIsoCode($currencyIso, $idShop > 0 ? $idShop : null);
            if (!$idCurrency) {
                $idCurrency = (int) Currency::getIdByIsoCode($currencyIso);
            }
        }

        if (!$idCurrency) {
            $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        $currency = Currency::getCurrencyInstance($idCurrency);
        $amount = $amountMinor / 100;

        return array(
            'amount_minor' => $amountMinor,
            'amount' => $amount,
            'currency_iso_code' => $currency ? (string) $currency->iso_code : $currencyIso,
            'formatted' => Tools::displayPrice($amount, $currency),
        );
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param int $idPackage
     *
     * @return string
     */
    protected function getPackageHighlightCacheKey($idLang, $idShop, $idPackage)
    {
        $idPackage = (int) $idPackage;
        if ($idPackage <= 0) {
            return '';
        }

        return 'KL_STORY_PACKAGE_HIGHLIGHT_'.(int) $idLang.'_'.(int) $idShop.'_'.$idPackage;
    }

    /**
     * @param string $cacheKey
     *
     * @return array<string, mixed>|null
     */
    protected function retrievePackageHighlightCache($cacheKey)
    {
        if ($cacheKey === '' || !Cache::isStored($cacheKey)) {
            return null;
        }

        $cached = Cache::retrieve($cacheKey);
        if (!is_array($cached) || !isset($cached['expires_at']) || !isset($cached['payload'])) {
            return null;
        }
        if ($cached['expires_at'] < time()) {
            return null;
        }

        return is_array($cached['payload']) ? $cached['payload'] : null;
    }

    /**
     * @param string $cacheKey
     * @param array<string, mixed> $payload
     *
     * @return void
     */
    protected function storePackageHighlightCache($cacheKey, array $payload)
    {
        if ($cacheKey === '') {
            return;
        }

        Cache::store($cacheKey, array(
            'expires_at' => time() + self::PACKAGE_HIGHLIGHT_CACHE_TTL,
            'payload' => $payload,
        ));
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    protected function buildFallbackScopeLabel($scope)
    {
        $normalized = Tools::strtolower(trim((string) $scope));
        $normalized = str_replace(array('_', '-'), ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized !== '' ? Tools::ucfirst($normalized) : '';
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
        $sectionMetadata = $this->getSectionMetadata($idLang, $idShop, $allowedResourceKinds);
        $groups = array();
        foreach ($sectionMetadata as $resourceKind => $metadata) {
            if ($allowed && !isset($allowed[$resourceKind])) {
                continue;
            }

            $groups[$metadata['key']] = array(
                'resource_kind' => $resourceKind,
                'label' => $metadata['title'],
                'anchor' => $metadata['anchor'],
                'intro' => $metadata['intro'],
                'slot' => null,
                'slots' => array(),
            );
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
                'groups' => $groups,
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
        foreach ($slotsByKind as $resourceKind => $slot) {
            if (!isset($sectionMetadata[$resourceKind])) {
                $sectionMetadata[$resourceKind] = array(
                    'resource_kind' => $resourceKind,
                    'key' => Tools::strtolower($resourceKind),
                    'anchor' => Tools::strtolower($resourceKind),
                    'title' => $this->getResourceKindLabel($resourceKind),
                    'intro' => '',
                );
            }

            $groupKey = $sectionMetadata[$resourceKind]['key'];
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = array(
                    'resource_kind' => $resourceKind,
                    'label' => $sectionMetadata[$resourceKind]['title'],
                    'anchor' => $sectionMetadata[$resourceKind]['anchor'],
                    'intro' => $sectionMetadata[$resourceKind]['intro'],
                    'slot' => null,
                    'slots' => array(),
                );
            }

            $normalisedSlot = $this->normaliseSlotForTemplate($slot, $sectionMetadata[$resourceKind]);
            $slots[] = $normalisedSlot;
            $groups[$groupKey]['slot'] = $normalisedSlot;
            $groups[$groupKey]['slots'] = array($normalisedSlot);
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
                'groups' => $groups,
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
            'groups' => $groups,
        );

        $this->storeAvailabilityCache($cacheKey, $payload);

        return $payload;
    }

    /**
     * @param array<string, mixed> $availability
     * @param array<string, string> $baseParameters
     *
     * @return array<string, mixed>
     */
    protected function applyInquiryUrls(array $availability, array $baseParameters = array())
    {
        if (!$availability) {
            return $availability;
        }

        $link = $this->context && $this->context->link ? $this->context->link : null;
        if (!$link) {
            return $availability;
        }

        if (isset($availability['slots']) && is_array($availability['slots'])) {
            $availability['slots'] = $this->decorateSlotsWithInquiryUrl($availability['slots'], $baseParameters, $link);
        }

        if (isset($availability['groups']) && is_array($availability['groups'])) {
            foreach ($availability['groups'] as $groupKey => $group) {
                if (isset($group['slot']) && is_array($group['slot'])) {
                    $group['slot'] = $this->decorateSlotWithInquiryUrl($group['slot'], $baseParameters, $link);
                }
                if (isset($group['slots']) && is_array($group['slots'])) {
                    $group['slots'] = $this->decorateSlotsWithInquiryUrl($group['slots'], $baseParameters, $link);
                }
                $availability['groups'][$groupKey] = $group;
            }
        }

        return $availability;
    }

    /**
     * @param array<int, array<string, mixed>> $slots
     * @param array<string, string> $baseParameters
     * @param Link $link
     *
     * @return array<int, array<string, mixed>>
     */
    protected function decorateSlotsWithInquiryUrl(array $slots, array $baseParameters, Link $link)
    {
        foreach ($slots as $index => $slot) {
            if (!is_array($slot)) {
                continue;
            }

            $slots[$index] = $this->decorateSlotWithInquiryUrl($slot, $baseParameters, $link);
        }

        return $slots;
    }

    /**
     * @param array<string, mixed> $slot
     * @param array<string, string> $baseParameters
     * @param Link $link
     *
     * @return array<string, mixed>
     */
    protected function decorateSlotWithInquiryUrl(array $slot, array $baseParameters, Link $link)
    {
        if (!isset($slot['inquiry_params']) || !is_array($slot['inquiry_params'])) {
            return $slot;
        }

        $parameters = array_merge($baseParameters, $slot['inquiry_params']);
        $slot['inquiry_url'] = $link->getPageLink('inquiry', true, null, $parameters);
        $slot['inquiry_query'] = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        return $slot;
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

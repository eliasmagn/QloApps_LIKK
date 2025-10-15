<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if ($path === '/') {
    header('Location: /story/residencies', true, 302);
    return;
}

if ($path !== '/story/residencies') {
    http_response_code(404);
    echo 'Not Found';
    return;
}

$root = realpath(__DIR__.'/../../..');
if ($root === false) {
    http_response_code(500);
    echo 'Project root unavailable.';
    return;
}

require_once $root.'/tools/smarty/Smarty.class.php';

$cacheBase = sys_get_temp_dir().'/kl_storytelling_router';
$compileDir = $cacheBase.'/compile';
$cacheDir = $cacheBase.'/cache';
if (!is_dir($compileDir) && !mkdir($compileDir, 0777, true) && !is_dir($compileDir)) {
    http_response_code(500);
    echo 'Unable to prepare Smarty compile directory.';
    return;
}
if (!is_dir($cacheDir) && !mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
    http_response_code(500);
    echo 'Unable to prepare Smarty cache directory.';
    return;
}

$smarty = new Smarty();
$smarty->setCompileDir($compileDir);
$smarty->setCacheDir($cacheDir);
$smarty->setConfigDir($root.'/config');
$smarty->setTemplateDir($root.'/themes/hotel-reservation-theme');
$smarty->registerPlugin('function', 'l', static function (array $params): string {
    return isset($params['s']) ? (string) $params['s'] : '';
});

$themeDir = $root.'/themes/hotel-reservation-theme';
$smarty->assign(array(
    'tpl_dir' => $themeDir.'/',
));

$storytelling = array(
    'resource_key' => 'residencies',
    'cms_endpoints' => array(
        'testimonials' => '/api/storytelling/residencies/testimonials',
        'faq' => '/api/storytelling/residencies/faq',
    ),
    'inquiry_url' => '/index.php?controller=inquiry',
    'cms' => array(
        'hero' => array(
            'content' => '<h1 class="h1">Residencies at Kunstort Lehnin</h1><p class="lead text-muted">Stay on campus for uninterrupted work time surrounded by forest and lakes.</p>',
        ),
        'availability' => array(
            'content' => '<p>Current cohorts update every Friday. Reach out to confirm bespoke timelines.</p>',
        ),
        'practical' => array(
            'content' => '<p>Studios include private bedrooms, stocked kitchens and rehearsal hall access within a short walk.</p>',
        ),
        'testimonials' => array(
            'content' => '<article class="kl-storytelling__testimonial"><blockquote>“The winter residency gave our ensemble the headspace to plan our next tour.”</blockquote><cite>— Ensemble Grünewald</cite></article>',
        ),
        'faq' => array(
            'content' => '<div class="panel panel-default"><div class="panel-heading" role="tab"><h3 class="panel-title">When do applications open?</h3></div><div class="panel-collapse collapse in" role="tabpanel"><div class="panel-body">Rolling admissions stay open year-round. We typically confirm placements within five working days.</div></div></div>',
        ),
    ),
    'availability' => array(
        'message' => 'Contact our residency team to co-design dates that suit your collective.',
        'slots' => array(
            array(
                'label' => 'Winter cohort',
                'window' => '1 Feb – 14 Feb 2025',
                'inquiry_url' => '/index.php?controller=inquiry&resource_kind=room&start=2025-02-01',
            ),
            array(
                'label' => 'Spring makers sprint',
                'window' => '7 Apr – 21 Apr 2025',
                'inquiry_url' => '/index.php?controller=inquiry&resource_kind=room&start=2025-04-07',
            ),
        ),
    ),
    'sections' => array(
        array(
            'anchor' => 'residency-studios',
            'title' => 'Residency studios',
            'intro' => 'Light-filled studios with blackout options, acoustic treatment and private terraces.',
            'profiles' => array(
                array(
                    'display_name' => 'North Studio Loft',
                    'excerpt' => 'Split-level workspace with kiln access, mezzanine sleeping quarters and forest views.',
                    'capacity_summary' => array(
                        'Sleeps up to 4 guests (2 bedrooms).',
                        '120 m² heated workspace with sprung floor.',
                        'Dedicated rehearsal audio suite and lighting grid.',
                    ),
                    'media' => array(
                        'alt' => 'Artists collaborating inside the North Studio Loft',
                        'fallback' => array(
                            'src' => 'https://cdn.example.test/storytelling/north-studio.jpg',
                            'width' => 1024,
                        ),
                        'sources' => array(
                            array(
                                'type' => 'image/webp',
                                'srcset' => 'https://cdn.example.test/storytelling/north-studio@1x.webp 1x, https://cdn.example.test/storytelling/north-studio@2x.webp 2x',
                            ),
                        ),
                        'caption' => 'Morning light across the main workspace.',
                    ),
                ),
                array(
                    'display_name' => 'Garden Atelier',
                    'excerpt' => 'Ground-floor atelier with double doors opening into landscaped courtyards and clay facilities.',
                    'capacity_summary' => array(
                        'Hosts collaborative cohorts of up to 6 artists.',
                        'Includes kiln, printmaking press and blackout textiles.',
                    ),
                    'media' => array(
                        'alt' => 'Garden Atelier exterior with open studio doors',
                        'fallback' => array(
                            'src' => 'https://cdn.example.test/storytelling/garden-atelier.jpg',
                            'width' => 960,
                        ),
                        'sources' => array(),
                        'caption' => 'Courtyard access keeps large-format works ventilated.',
                    ),
                ),
            ),
        ),
    ),
    'packages' => array(
        array(
            'anchor' => 'seasonal-highlights',
            'label' => 'Seasonal highlights',
            'intro' => 'Curated residencies surfaced when editors flag limited programmes for promotion.',
            'packages' => array(
                array(
                    'name' => 'Winter Retreat',
                    'tagline' => 'Three nights with catering and rehearsal hall access.',
                    'cta_label' => 'Request this package',
                    'inquiry_url' => '/index.php?controller=inquiry&utm_source=story_residencies_package&package_code=WINTER-RETREAT',
                    'description' => '<p>Includes private studio time, facilitated critique sessions and daily meals prepared by our campus chef.</p>',
                    'highlight' => array(
                        'status' => 'ready',
                        'headline' => '€480 per artist',
                        'sample_label' => 'Based on a 3-night stay for ensembles of 4.',
                        'inclusions_label' => 'Studio, accommodation, meals and rehearsal hall.',
                        'warning_label' => 'Limited to two cohorts per season.',
                    ),
                ),
            ),
        ),
    ),
);

$smarty->assign(array(
    'storytelling' => $storytelling,
));

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8" /><title>Kunstort Residencies Storytelling</title><meta name="viewport" content="width=device-width, initial-scale=1" /></head><body>';
echo $smarty->fetch('storytelling/residencies.tpl');
echo '<script>if (performance && performance.mark) { performance.mark("storytelling-ready"); }</script>';
echo '</body></html>';

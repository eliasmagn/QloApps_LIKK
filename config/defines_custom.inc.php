<?php
/**
 * Custom runtime flags for the Kunstort Lehnin distribution.
 */
if (!defined('_KUNSTORT_CORE_MODE_')) {
    define('_KUNSTORT_CORE_MODE_', 'inquiry');
}

if (!defined('_QLOAPP_DISABLE_MARKETPLACE_')) {
    define('_QLOAPP_DISABLE_MARKETPLACE_', true);
}

// Kept for backward compatibility: installs should toggle storytelling via
// WK_STORYTELLING_ENABLED in Hotel General Settings, but legacy overrides can
// still define the constant to seed the configuration during upgrades.
if (!defined('_KUNSTORT_STORYTELLING_LAUNCH_')) {
    define('_KUNSTORT_STORYTELLING_LAUNCH_', false);
}

if (!defined('_KUNSTORT_RESOURCE_API_TOKEN_')) {
    define('_KUNSTORT_RESOURCE_API_TOKEN_', '');
}

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

class ResourceProfileApiControllerCore extends FrontController
{
    /** @var string */
    public $php_self = 'resourceprofileapi';

    /** @var bool */
    public $display_column_left = false;

    /** @var bool */
    public $display_column_right = false;

    public function init()
    {
        parent::init();

        $this->display_header = false;
        $this->display_footer = false;
        $this->ajax = true;
    }

    public function initContent()
    {
        parent::initContent();

        $this->sendNoCacheHeaders();

        if (!$this->ensureModuleReady()) {
            return;
        }

        if (!$this->authenticateRequest()) {
            return;
        }

        include_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';

        $idLang = (int) Tools::getValue('id_lang', $this->context && $this->context->language ? (int) $this->context->language->id : (int) Configuration::get('PS_LANG_DEFAULT'));
        $idShop = (int) Tools::getValue('id_shop', $this->context && $this->context->shop ? (int) $this->context->shop->id : 0);
        $resourceKinds = $this->parseResourceKinds();
        $action = Tools::strtolower((string) Tools::getValue('action', 'list'));

        switch ($action) {
            case 'detail':
                $this->handleDetailAction($idLang, $idShop);
                break;
            case 'list':
            default:
                $this->handleListAction($idLang, $idShop, $resourceKinds);
                break;
        }
    }

    /**
     * @return bool
     */
    protected function ensureModuleReady()
    {
        if (!Module::isInstalled('hotelreservationsystem') || !Module::isEnabled('hotelreservationsystem')) {
            $this->renderError('The hotel reservation system module is not available.', 503);

            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function authenticateRequest()
    {
        $expectedToken = defined('_KUNSTORT_RESOURCE_API_TOKEN_') ? trim((string) _KUNSTORT_RESOURCE_API_TOKEN_) : '';
        if ($expectedToken === '') {
            $this->renderError('Resource profile API token is not configured.', 503);

            return false;
        }

        $providedToken = $this->extractBearerToken();
        if ($providedToken === '') {
            $this->renderError('Missing authentication token.', 401);

            return false;
        }

        if (!hash_equals($expectedToken, $providedToken)) {
            $this->renderError('Invalid authentication token.', 403);

            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function extractBearerToken()
    {
        $header = $this->getAuthorizationHeader();
        if ($header !== '') {
            if (stripos($header, 'Bearer ') === 0) {
                return trim(substr($header, 7));
            }

            return trim($header);
        }

        $queryToken = Tools::getValue('token');
        if ($queryToken) {
            return trim((string) $queryToken);
        }

        return '';
    }

    /**
     * @return string
     */
    protected function getAuthorizationHeader()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim((string) $_SERVER['HTTP_AUTHORIZATION']);
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim((string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return trim((string) $headers['Authorization']);
            }
            if (isset($headers['authorization'])) {
                return trim((string) $headers['authorization']);
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    protected function parseResourceKinds()
    {
        $kinds = Tools::getValue('resource_kinds');
        if (!$kinds) {
            $single = Tools::getValue('resource_kind');
            if ($single) {
                $kinds = $single;
            }
        }

        if (!$kinds) {
            return array();
        }

        if (is_array($kinds)) {
            $values = $kinds;
        } else {
            $values = explode(',', (string) $kinds);
        }

        $normalised = array();
        foreach ($values as $value) {
            $value = trim(Tools::strtolower((string) $value));
            if ($value !== '') {
                $normalised[] = $value;
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * @param int $idLang
     * @param int $idShop
     * @param array<int, string> $resourceKinds
     *
     * @return void
     */
    protected function handleListAction($idLang, $idShop, array $resourceKinds)
    {
        $presenter = new HotelReservationSystemStorytellingPresenter($this->context);

        $profiles = $presenter->getProfilesForApi($idLang, $idShop, $resourceKinds, true);
        $availability = $presenter->getAvailabilitySnapshotForApi($idLang, $idShop, $resourceKinds);

        $response = array(
            'generated_at' => date(DATE_ATOM),
            'resource_kinds' => $resourceKinds,
            'profiles' => $profiles,
            'availability' => $availability,
        );

        $this->sendJsonResponse($response);
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return void
     */
    protected function handleDetailAction($idLang, $idShop)
    {
        $resourceCode = trim((string) Tools::getValue('resource_code'));
        $profileId = (int) Tools::getValue('id_kl_resource_profile');

        if ($resourceCode === '' && $profileId <= 0) {
            $this->renderError('A resource_code or id_kl_resource_profile parameter is required.', 400);

            return;
        }

        $presenter = new HotelReservationSystemStorytellingPresenter($this->context);
        $profiles = $presenter->getProfilesForApi($idLang, $idShop, array(), true);

        $match = null;
        foreach ($profiles as $profile) {
            if ($resourceCode !== '' && Tools::strtolower($profile['resource_code']) === Tools::strtolower($resourceCode)) {
                $match = $profile;
                break;
            }
            if ($profileId > 0 && (int) $profile['id_kl_resource_profile'] === $profileId) {
                $match = $profile;
                break;
            }
        }

        if ($match === null) {
            $this->renderError('Resource profile not found.', 404);

            return;
        }

        $availability = $presenter->getAvailabilitySnapshotForApi($idLang, $idShop, array($match['resource_kind']));

        $response = array(
            'generated_at' => date(DATE_ATOM),
            'profile' => $match,
            'availability' => $availability,
        );

        $this->sendJsonResponse($response);
    }

    /**
     * @param array<string, mixed> $payload
     * @param int $statusCode
     *
     * @return void
     */
    protected function sendJsonResponse($payload, $statusCode = 200)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('X-Content-Type-Options: nosniff');
            http_response_code($statusCode);
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param string $message
     * @param int $statusCode
     *
     * @return void
     */
    protected function renderError($message, $statusCode)
    {
        $this->sendJsonResponse(
            array(
                'error' => array(
                    'code' => $statusCode,
                    'message' => $message,
                ),
            ),
            $statusCode
        );
    }

    /**
     * @return void
     */
    protected function sendNoCacheHeaders()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('X-Content-Type-Options: nosniff');
        }
    }
}

<?php

require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLInquirySubmission.php';
require_once _PS_MODULE_DIR_.'hotelreservationsystem/classes/KLResourceProfile.php';

class InquiryControllerCore extends FrontController
{
    /** @var array<string, mixed> */
    protected $formValues = array();

    /** @var bool */
    protected $submissionSuccess = false;

    /** @var HotelInquiry|null */
    protected $submittedInquiry = null;

    public function init()
    {
        parent::init();

        $this->php_self = 'inquiry';
        $this->ssl = Configuration::get('PS_SSL_ENABLED');
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitInquiryForm')) {
            $this->handleSubmission();
        }
    }

    public function initContent()
    {
        parent::initContent();

        $this->addCSS(_THEME_CSS_DIR_.'inquiry-form.css');
        $this->addJS(_THEME_JS_DIR_.'inquiry-form.js');

        $resourceKindOptions = $this->getResourceKindOptions();
        $this->bootstrapFormValues($resourceKindOptions);

        $this->context->smarty->assign(array(
            'form_values' => $this->formValues,
            'resource_kind_options' => $resourceKindOptions,
            'submission_success' => $this->submissionSuccess,
            'submitted_inquiry' => $this->submittedInquiry,
            'inquiry_lookup_endpoint' => $this->context->link->getModuleLink('hotelreservationsystem', 'inquirylookup'),
            'inquiry_form_token' => Tools::getToken(false),
            'data_usage_statement' => $this->trans('I consent to Kunstort Lehnin using my submitted data to process this residency inquiry.', array(), 'Shop.Theme.Kunstort'),
            'newsletter_opt_in_label' => $this->trans('Keep me posted about future programmes and residencies (optional).', array(), 'Shop.Theme.Kunstort'),
        ));

        $this->setTemplate('inquiry.tpl');
    }

    protected function handleSubmission()
    {
        $this->formValues = $this->collectFormValues();
        $errors = $this->validateFormValues($this->formValues);

        if (!empty($errors)) {
            $this->errors = array_merge($this->errors, $errors);
            return;
        }

        try {
            $result = KLInquirySubmission::createFromFront($this->formValues, $this->context);
            $this->submittedInquiry = $result['inquiry'];
            $this->submissionSuccess = true;
            $this->formValues = array();
            $this->confirmations[] = $this->trans('Thank you! Your inquiry has been received and the residency team will be in touch shortly.', array(), 'Shop.Theme.Kunstort');
        } catch (PrestaShopException $exception) {
            PrestaShopLogger::addLog('Inquiry submission failed: '.$exception->getMessage(), 3, null, 'InquiryController', null, true);
            $this->errors[] = $this->trans('We could not save your request right now. Please try again later or contact us directly.', array(), 'Shop.Theme.Kunstort');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectFormValues()
    {
        $resourceInterests = Tools::getValue('resource_interests');
        if (!is_array($resourceInterests)) {
            $resourceInterests = array();
        }

        $packageSelections = Tools::getValue('package_preferences');
        if (!is_array($packageSelections)) {
            $packageSelections = array();
        }

        $partyAdults = (int) Tools::getValue('party_size_adults');
        $partyChildren = (int) Tools::getValue('party_size_children');

        if ($partyAdults <= 0) {
            $partyAdults = 1;
        }
        if ($partyChildren < 0) {
            $partyChildren = 0;
        }

        $values = array(
            'guest_name' => trim(Tools::getValue('guest_name')),
            'guest_email' => trim(Tools::getValue('guest_email')),
            'guest_phone' => trim(Tools::getValue('guest_phone')),
            'programme_focus' => trim(Tools::getValue('programme_focus')),
            'arrival_date' => Tools::getValue('arrival_date'),
            'departure_date' => Tools::getValue('departure_date'),
            'date_flexibility' => Tools::getValue('date_flexibility') ? true : false,
            'party_size_adults' => $partyAdults,
            'party_size_children' => $partyChildren,
            'resource_interests' => array_values(array_unique(array_map('strval', $resourceInterests))),
            'resource_notes' => trim(Tools::getValue('resource_notes')),
            'package_preferences' => array_values(array_unique(array_map('strval', $packageSelections))),
            'additional_notes' => trim(Tools::getValue('additional_notes')),
            'consent_data_usage' => Tools::getValue('consent_data_usage') ? true : false,
            'consent_newsletter' => Tools::getValue('consent_newsletter') ? true : false,
        );

        return $values;
    }

    /**
     * @param array<string, string> $resourceKindOptions
     *
     * @return void
     */
    protected function bootstrapFormValues(array $resourceKindOptions)
    {
        $defaults = array(
            'guest_name' => '',
            'guest_email' => '',
            'guest_phone' => '',
            'programme_focus' => '',
            'arrival_date' => '',
            'departure_date' => '',
            'date_flexibility' => false,
            'party_size_adults' => 1,
            'party_size_children' => 0,
            'resource_interests' => array(),
            'resource_notes' => '',
            'package_preferences' => array(),
            'additional_notes' => '',
            'consent_data_usage' => false,
            'consent_newsletter' => false,
        );

        $prefill = $this->getQueryPrefillValues($resourceKindOptions);

        $this->formValues = array_merge($defaults, $prefill, $this->formValues);
    }

    /**
     * @param array<string, string> $resourceKindOptions
     *
     * @return array<string, mixed>
     */
    protected function getQueryPrefillValues(array $resourceKindOptions)
    {
        $values = array();

        $arrival = Tools::getValue('arrival_date');
        if ($arrival && Validate::isDateFormat($arrival)) {
            $values['arrival_date'] = $arrival;
        }

        $departure = Tools::getValue('departure_date');
        if ($departure && Validate::isDateFormat($departure)) {
            $values['departure_date'] = $departure;
        }

        $resourceKind = Tools::getValue('resource_kind');
        if ($resourceKind && is_string($resourceKind) && isset($resourceKindOptions[$resourceKind])) {
            $values['resource_interests'] = array($resourceKind);
        }

        $resourceCode = trim((string) Tools::getValue('resource_code'));
        if ($resourceCode !== '') {
            $values['resource_notes'] = $resourceCode;
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<int, string>
     */
    protected function validateFormValues(array $values)
    {
        $errors = array();

        if (!$values['guest_name']) {
            $errors[] = $this->trans('Please tell us who is enquiring.', array(), 'Shop.Theme.Kunstort');
        } elseif (!Validate::isGenericName($values['guest_name'])) {
            $errors[] = $this->trans('The guest name contains invalid characters.', array(), 'Shop.Theme.Kunstort');
        }

        if (!$values['guest_email']) {
            $errors[] = $this->trans('We need an email address so we can respond.', array(), 'Shop.Theme.Kunstort');
        } elseif (!Validate::isEmail($values['guest_email'])) {
            $errors[] = $this->trans('The email address looks invalid.', array(), 'Shop.Theme.Kunstort');
        }

        if ($values['guest_phone'] && !Validate::isPhoneNumber($values['guest_phone'])) {
            $errors[] = $this->trans('The phone number is not valid.', array(), 'Shop.Theme.Kunstort');
        }

        if (!$values['arrival_date'] || !Validate::isDateFormat($values['arrival_date'])) {
            $errors[] = $this->trans('Please select your desired arrival date.', array(), 'Shop.Theme.Kunstort');
        }

        if (!$values['departure_date'] || !Validate::isDateFormat($values['departure_date'])) {
            $errors[] = $this->trans('Please select your desired departure date.', array(), 'Shop.Theme.Kunstort');
        }

        if ($values['arrival_date'] && $values['departure_date'] && Validate::isDateFormat($values['arrival_date']) && Validate::isDateFormat($values['departure_date'])) {
            if (strtotime($values['departure_date']) <= strtotime($values['arrival_date'])) {
                $errors[] = $this->trans('Departure must be after arrival. If your dates are flexible tick the flexibility option.', array(), 'Shop.Theme.Kunstort');
            }
        }

        if ($values['party_size_adults'] < 0 || $values['party_size_children'] < 0) {
            $errors[] = $this->trans('Party size cannot be negative.', array(), 'Shop.Theme.Kunstort');
        }

        if (!$values['consent_data_usage']) {
            $errors[] = $this->trans('Please accept the data usage statement so we can process your request.', array(), 'Shop.Theme.Kunstort');
        }

        $token = Tools::getValue('token');
        if (!$token || $token !== Tools::getToken(false)) {
            $errors[] = $this->trans('Your session expired. Reload the page and try again.', array(), 'Shop.Theme.Kunstort');
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    protected function getResourceKindOptions()
    {
        $module = Module::getInstanceByName('hotelreservationsystem');

        return array(
            KLResourceProfile::RESOURCE_KIND_ROOM => $module->l('Residency rooms', 'InquiryController'),
            KLResourceProfile::RESOURCE_KIND_ATELIER => $module->l('Studios & ateliers', 'InquiryController'),
            KLResourceProfile::RESOURCE_KIND_SEMINAR => $module->l('Seminar & programme spaces', 'InquiryController'),
            KLResourceProfile::RESOURCE_KIND_GASTRONOMY => $module->l('Gastronomy & catering', 'InquiryController'),
        );
    }
}

<?php

class ParentOrderControllerCore extends FrontController
{
    public function init()
    {
        parent::init();
    }

    public function initContent()
    {
        parent::initContent();

        if (!$this->isInquiryMode()) {
            Tools::redirect($this->context->link->getPageLink('index', true));
            return;
        }

        $this->context->smarty->assign(array(
            'inquiry_link' => $this->getInquiryLink(),
        ));

        $this->setTemplate('checkout-inquiry.tpl');
    }

    protected function getInquiryLink()
    {
        return $this->context->link->getPageLink('inquiry', true);
    }

    protected function isInquiryMode()
    {
        return defined('_KUNSTORT_CORE_MODE_') && _KUNSTORT_CORE_MODE_ === 'inquiry';
    }
}

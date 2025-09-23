<?php

class InquiryControllerCore extends FrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign(array(
            'contact_link' => $this->context->link->getPageLink('contact', true),
        ));

        $this->setTemplate('inquiry.tpl');
    }
}

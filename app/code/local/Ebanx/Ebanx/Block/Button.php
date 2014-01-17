<?php

/**
 * Adds a button to the EBANX module settings page which points to our custom
 * controller. The target action updates the available payment methods in direct
 * mode.
 */
class Ebanx_Ebanx_Block_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('ebanx/payment/updatePayments');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
                     ->setType('button')
                     ->setClass('scalable')
                     ->setLabel('Update payment methods')
                     ->setOnClick("window.open('$url')")
                     ->toHtml();

        return $html;
    }
}
?>
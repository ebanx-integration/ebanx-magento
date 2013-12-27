<?php

class Ebanx_Ebanx_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Set up the form options
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebanx/ebanx/form.phtml');
    }

    /**
     * Prepare form contents before rendering it
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->_prepareForm();
        return parent::_beforeToHtml();
    }

    /**
     * Get the order final value (with shipping, tax, etc)
     * @return float
     */
    public function getFinalValue()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        $totals = $quote->getTotals();
        return $totals['grand_total']->_data['value'];
    }

    /**
     * Build the checkout form
     * @return void
     */
    protected function _prepareForm()
    {
        $ebanxConfig = Mage::getStoreConfig('payment/ebanx');

        $installmentsActive = $ebanxConfig['active_installments'];
        $maxInstallments    = $ebanxConfig['maximum_installments'];

        $installmentCards = array('Visa', 'Mastercard');

        $currencySymbol = Mage::app()->getLocale()
                                     ->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
                                     ->getSymbol();

        // Get the final value with interest
        $priceInterest = ($this->getFinalValue() * (100 + floatval($ebanxConfig['interest_installments']))) / 100.0;

        $this->addData(array(
           'installments_active' => $installmentsActive
         , 'max_installments'    => $maxInstallments
         , 'installment_cards'   => $installmentCards
         , 'price_upfront'       => $this->getFinalValue()
         , 'price_interest'      => $priceInterest
         , 'currency_symbol'     => $currencySymbol
        ));
    }

}
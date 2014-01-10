<?php

/**
 * Copyright (c) 2014, EBANX Tecnologia da Informação Ltda.
 *  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * Neither the name of EBANX nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

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
        $ebanxConfig = Mage::getStoreConfig('payment/ebanx');

        // Check if we're going to render the checkout or the direct form
        if (intval($ebanxConfig['direct']) == 1)
        {
            $this->setTemplate('ebanx/ebanx/direct.phtml');
        }
        else
        {
            $this->setTemplate('ebanx/ebanx/checkout.phtml');
        }

        $this->_prepareForm();

        return parent::_beforeToHtml();
    }

    /**
     * Get the order final value (with shipping, tax, etc)
     * @return float
     */
    public function getFinalValue()
    {
        return Mage::getSingleton('checkout/cart')
                ->getQuote()
                ->collectTotals()
                ->getGrandTotal();
    }

    /**
     * Build the checkout form
     * @return void
     */
    protected function _prepareForm()
    {
        $ebanxConfig = Mage::getStoreConfig('payment/ebanx');

        $installmentsActive = $ebanxConfig['active_installments'];
        $maxInstallments    = intval($ebanxConfig['maximum_installments']);

        // Get the final value with interest
        $priceInterest = ($this->getFinalValue() * (100 + floatval($ebanxConfig['interest_installments']))) / 100.0;

        // Enforces minimum installment value (R$20)
        $currencyCode =  strtoupper(Mage::app()->getStore()->getCurrentCurrencyCode());

        // Convert the total to BRL (approximation)
        $total = $this->getFinalValue();
        switch ($currencyCode)
        {
          case 'USD':
            $totalReal = $total * 2.5;
            break;
          case 'EUR':
            $totalReal = $total * 3.4;
            break;
          case 'BRL':
          default:
            $totalReal = $total;
            break;
        }

        if (($totalReal / 20) < $maxInstallments)
        {
          $maxInstallments = floor($totalReal / 20);
        }

        $installmentCards = array('Visa', 'Mastercard');

        $currencySymbol = Mage::app()->getLocale()
                                     ->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
                                     ->getSymbol();

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
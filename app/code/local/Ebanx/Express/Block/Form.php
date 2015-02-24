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

class Ebanx_Express_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Set up the form options
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebanx/express/form.phtml');
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
        $ebanxConfig = Mage::getStoreConfig('payment/ebanx_express');

        $installmentsActive = (bool) $ebanxConfig['active_installments'];

        $currencyCode =  strtoupper(Mage::app()->getStore()->getCurrentCurrencyCode());

        // Enforces minimum installment value (R$20)
        $maxInstallments  = intval($ebanxConfig['maximum_installments']);
        $interestRate     = $ebanxConfig['interest_installments'];
        $interestMode     = $ebanxConfig['installments_mode'];
        $total            = $this->getFinalValue();
        $installmentsOptions = array();

        // Setup installment options
        for ($i = 2; $i <= $maxInstallments; $i++)
        {
            // Minimum amount per installment is R$20
            if (($total / $i) >= 20)
            {
                $installmentsOptions[$i] = Ebanx_Express_Utils::calculateTotalWithInterest(
                                                  $interestMode
                                                , $interestRate
                                                , $total
                                                , $i
                                            );
            }
        }

        $installmentCards = array('Visa', 'Mastercard');

        $currencySymbol = Mage::app()->getLocale()
                                     ->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
                                     ->getSymbol();

        $customer  = Mage::getSingleton('customer/session')->getCustomer();
        $cpf       = $customer->getEbanxCpf();

        $birthDate = $customer->getEbanxBirthdate();
        $birthDay   = 0;
        $birthMonth = 0;
        $birthYear  = 0;

        if (isset($birthDate))
        {
            $birthDate = explode('/', $birthDate);
            $birthDay   = $birthDate[0];
            $birthMonth = $birthDate[1];
            $birthYear  = $birthDate[2];
        }

        $this->addData(array(
           'installments_active' => $installmentsActive
         , 'max_installments'    => $maxInstallments
         , 'installment_cards'   => $installmentCards
         , 'installments'        => $installmentsOptions
         , 'price_upfront'       => $this->getFinalValue()
         , 'currency_symbol'     => $currencySymbol
         , 'cpf'                 => $cpf
         , 'birth_day'           => $birthDay
         , 'birth_month'         => $birthMonth
         , 'birth_year'          => $birthYear
        ));
    }
}
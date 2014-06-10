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

/**
 * Observer class that saves order data to the session,
 * so it can be retrieved during the checkout
 */
class Ebanx_Ebanx_Model_Observer
{
    public function saveOrderQuoteToSession($observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $quote = $event->getQuote();

        $session = Mage::getSingleton('checkout/session');
        $quoteId = $quote->getId();
        $orderId = $order->getId();
        $incrId = $order->getIncrementId();

        Mage::log("Saving quote [$quoteId] and order [$incrId] to checkout/session");

        $session->setData('apiOrderId', $orderId);
        $session->setData('apiOrderIncrementId', $incrId);

        $ebanx = Mage::app()->getRequest()->getParam('ebanx');

        $session->setData('ebanxBaseGrandTotal', $order->getBaseGrandTotal());

        // Setup installments and update the total with the interest rate
        $installmentsNumber = 1;
        $installmentsCard   = null;
        if (isset($ebanx) && count($ebanx) >= 1)
        {
            $installmentsNumber = $ebanx['installments'];
            $installmentsCard   = (isset($ebanx['installments_card'])) ? $ebanx['installments_card'] : $ebanx['cc_type'];

            // Update grand total with interest values
            // @todo: add interest field and show it to the client => like a new tax
            if (intval($installmentsNumber) > 1)
            {
                $interestRate = Mage::getStoreConfig('payment/ebanx/interest_installments');
                $grandTotal = ($order->getBaseGrandTotal() * (100 + floatval($interestRate))) / 100.0;
                $session->setData('ebanxBaseGrandTotal', $grandTotal);
            }
        }

        $session->setData('ebanxQuoteId', $quoteId);
        $session->setData('ebanxInstallmentsNumber', $installmentsNumber);
        $session->setData('ebanxInstallmentsCard', $installmentsCard);

        $birthDate = str_pad($ebanx['birth_day'],   2, '0', STR_PAD_LEFT) . '/'
                   . str_pad($ebanx['birth_month'], 2, '0', STR_PAD_LEFT) . '/'
                   . $ebanx['birth_year'];

        $session->setData('ebanxCpf',    $ebanx['cpf']);
        $session->setData('ebanxBirth',  $birthDate);
        $session->setData('ebanxMethod', $ebanx['method']);

        if ($ebanx['method'] == 'creditcard')
        {
            $ccExpiration = str_pad($ebanx['cc_expiration_month'], 2, '0', STR_PAD_LEFT) . '/'
                          . $ebanx['cc_expiration_year'];

            $session->setData('ebanxCCName',       $ebanx['cc_name']);
            $session->setData('ebanxCCType',       $ebanx['cc_type']);
            $session->setData('ebanxCCNumber',     $ebanx['cc_number']);
            $session->setData('ebanxCCCVV',        $ebanx['cc_cvv']);
            $session->setData('ebanxCCExpiration', $ccExpiration);
        }

        // If method == TEF, get the bank value
        if ($ebanx['method'] == 'tef')
        {
            $session->setData('ebanxMethodTef', true);
            $session->setData('ebanxMethod', $ebanx['tef_bank']);
        }

        // Save CPF and birthdate
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customer->setEbanxCpf($ebanx['cpf']);
        $customer->setEbanxBirthdate($birthDate);
        $customer->save();

        return $this;
    }
}
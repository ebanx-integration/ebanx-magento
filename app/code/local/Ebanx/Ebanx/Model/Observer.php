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

        // Setup installments and update the total with the interest rate
        $installmentsNumber = 1;
        $installmentsCard   = null;
        if (isset($ebanx) && count($ebanx) >= 1)
        {
            $installmentsNumber = $ebanx['installments'];
            $installmentsCard   = $ebanx['installments_card'];

            // Update grand total with interest values
            // @todo: add interest field and show it to the client => like a new tax
            if (intval($installmentsNumber) > 1)
            {
                $interestRate = Mage::getStoreConfig('payment/ebanx/interest_installments');
                $grandTotal = ($order->getGrandTotal() * (100 + floatval($interestRate))) / 100.0;
                $order->setGrandTotal($grandTotal)
                      ->save();
            }
        }

        $session->setData('ebanxQuoteId', $quoteId);
        $session->setData('ebanxInstallmentsNumber', $installmentsNumber);
        $session->setData('ebanxInstallmentsCard', $installmentsCard);

        return $this;
    }
}
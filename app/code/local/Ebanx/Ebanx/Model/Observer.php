<?php

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
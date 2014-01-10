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

require_once dirname(dirname(__FILE__)) . '/etc/bootstrap.php';

class Ebanx_Ebanx_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Create a new checkout request and redirects to EBANX
     * @return void
     */
    public function checkoutAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $order   = Mage::getModel('sales/order')->load($session['apiOrderId']);

        $params = array(
            'name'              => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
          , 'email'             => $order->getCustomerEmail()
          , 'currency_code'     => $order->getBaseCurrencyCode()
          , 'amount'            => $session['ebanxBaseGrandTotal']
          , 'payment_type_code' => '_all'
          , 'merchant_payment_code' => $session['apiOrderIncrementId']
          , 'zipcode'           => $order->getBillingAddress()->getPostcode()
          , 'address'           => $order->getBillingAddress()->getStreet()
          , 'city'              => $order->getBillingAddress()->getCity()
          , 'state'             => $order->getBillingAddress()->getRegion()
        );

        // Add installments to order
        if (intval($session['ebanxInstallmentsNumber']) > 1)
        {
            $params['instalments'] = $session['ebanxInstallmentsNumber'];
            $params['payment_type_code'] = $session['ebanxInstallmentsCard'];
        }

        $response = \Ebanx\Ebanx::doRequest($params);

        if (!empty($response) && $response->status == 'SUCCESS')
        {
            // Add the EBANX hash in the order data
            $order->getPayment()
                  ->setData('ebanx_hash', $response->payment->hash)
                  ->save();

            // Redirect to EBANX
            $this->getResponse()
                 ->setRedirect($response->redirect_url);
        }
        else
        {
            $msg = $response->status_message;
            Mage::log("BeginPayment failed with [$msg]");
            $session->addError('An unrecoverable error occured while processing your payment information. ' . $msg);
            Mage::throwException($msg);
        }
    }

    /**
     * Notification URI for the EBANX robot
     * @return void
     */
    public function notifyAction()
    {
        $hashes = explode(',', $this->getRequest()->getParam('hash_codes'));

        foreach ($hashes as $hash)
        {
            $orderPayment = Mage::getModel('sales/order_payment')
                                ->getCollection()
                                ->addFieldToFilter('ebanx_hash', $hash)
                                ->getFirstItem();

            $response = \Ebanx\Ebanx::doQuery(array('hash' => $hash));

            // Get the new status from Magento
            $orderStatus = $this->_getOrderStatus($response->payment->status);

            // Update order status
            $order = Mage::getModel('sales/order')
                        ->load($orderPayment->getParentId(), 'entity_id');
            $order->setStatus($orderStatus)
                  ->save();
        }
    }

    /**
     * Checkout return action
     * @return void
     */
    public function returnAction()
    {
        if ($this->getRequest()->isGet())
        {
            $hash = $this->getRequest()->getParam('hash');
            $merchantPaymentCode = $this->getRequest()->getParam('merchant_payment_code');

            $response = \Ebanx\Ebanx::doQuery(array('hash' => $hash));

            if ($response->status == 'SUCCESS')
            {
                // Get the new status from Magento
                $orderStatus = $this->_getOrderStatus($response->payment->status);

                // Update order status
                $order = Mage::getModel('sales/order')->loadByIncrementId($merchantPaymentCode);
                $order->setStatus($orderStatus)
                      ->save();
            }

            $this->getResponse()
                 ->setRedirect(Mage::getUrl('checkout/onepage/success'));
        }
    }

    /**
     * Get the new order status from Magento
     * @param  string $status The EBANX order status code
     * @return string The Magento order status
     */
    protected function _getOrderStatus($status)
    {
        $orderStatus = array(
            'CO' => Mage::getStoreConfig('payment/ebanx/paid_order_status')
          , 'PE' => Mage::getStoreConfig('payment/ebanx/new_order_status')
          , 'CA' => Mage::getStoreConfig('payment/ebanx/canceled_order_status')
          , 'OP' => Mage::getStoreConfig('payment/ebanx/open_order_status')
        );

        return $orderStatus[$status];
    }
}

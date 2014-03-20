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

        $ebanxConfig = Mage::getStoreConfig('payment/ebanx');

        if (intval($ebanxConfig['direct']) == 1)
        {
            $this->_doDirectCheckout($order, $session);
        }
        else
        {
            $this->_doDefaultCheckout($order, $session);
        }
    }

    protected function _doDefaultCheckout($order, $session)
    {

      $params = array(
          'name'              => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
        , 'email'             => $order->getCustomerEmail()
        , 'currency_code'     => $order->getBaseCurrencyCode()
        , 'amount'            => $session['ebanxBaseGrandTotal']
        , 'payment_type_code' => '_all'
        , 'merchant_payment_code' => $session['apiOrderIncrementId']
        , 'zipcode'           => $order->getBillingAddress()->getData('postcode')
        , 'address'           => $order->getBillingAddress()->getData('street')
        , 'city'              => $order->getBillingAddress()->getData('city')
        , 'state'             => $order->getBillingAddress()->getRegionCode()
      );

      // Add installments to order
      if (intval($session['ebanxInstallmentsNumber']) > 1)
      {
          $params['instalments']       = $session['ebanxInstallmentsNumber'];
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

    protected function _doDirectCheckout($order, $session)
    {
        $params = array(
            'mode'      => 'full'
          , 'operation' => 'request'
          , 'payment'   => array(
                'name'              => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
              , 'document'          => $session['ebanxCpf']
              , 'birth_date'        => $session['ebanxBirth']
              , 'email'             => $order->getCustomerEmail()
              , 'phone_number'      => $order->getBillingAddress()->getTelephone()
              , 'currency_code'     => $order->getBaseCurrencyCode()
              , 'amount_total'      => $order->getBaseGrandTotal()
              , 'payment_type_code' => $session['ebanxMethod']
              , 'merchant_payment_code' => $session['apiOrderIncrementId']
              , 'zipcode'           => $order->getBillingAddress()->getData('postcode')
              , 'address'           => $order->getBillingAddress()->getData('street')
              , 'street_number'     => preg_replace('/[\D]/', '', $order->getBillingAddress()->getData('street'))
              , 'city'              => $order->getBillingAddress()->getData('city')
              , 'state'             => $order->getBillingAddress()->getRegionCode()
              , 'country'           => 'br'
          )
        );

        // Add credit card fields if the method is credit card
        if ($session['ebanxMethod'] == 'creditcard')
        {
            $params['payment']['payment_type_code'] = $session['ebanxCCType'];
            $params['payment']['creditcard'] = array(
                'card_name'     => $session['ebanxCCName']
              , 'card_number'   => $session['ebanxCCNumber']
              , 'card_cvv'      => $session['ebanxCCCVV']
              , 'card_due_date' => $session['ebanxCCExpiration']
            );
        }

        // Add installments to order - except to Discover payments
        if (intval($session['ebanxInstallmentsNumber']) > 1 && $session['ebanxInstallmentsCard'] != 'discover')
        {
            $params['payment']['instalments']  = $session['ebanxInstallmentsNumber'];
            $params['payment']['amount_total'] = $session['ebanxBaseGrandTotal'];
        }

        $response = \Ebanx\Ebanx::doRequest($params);

        if (!empty($response) && $response->status == 'SUCCESS')
        {
            $hash = $response->payment->hash;

            // Add the EBANX hash in the order data
            $order->getPayment()
                  ->setData('ebanx_hash', $hash)
                  ->save();

            // Redirect to EBANX
            $this->getResponse()
                 ->setRedirect(Mage::getUrl('ebanx/payment/success') . '?hash=' . $hash);
        }
        else
        {
            $msg = $response->status_message;
            Mage::log("BeginPayment failed with [$msg]");
            $session->addError('An unrecoverable error occured while processing your payment information. ' . $msg);

            // Recover the cart
            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
            $quote->setIsActive(true)
                  ->save();

            // Cancel the order
            $order->setStatus($this->_getOrderStatus('CA'))
                  ->save();

            // Redirect back to cart
            $this->getResponse()
                 ->setRedirect(Mage::getUrl('checkout/cart'));
        }
    }

    /**
     * Notification URI for the EBANX robot
     * @return void
     */
    public function notifyAction()
    {
        $hashes = $this->getRequest()->getParam('hash_codes');

        if (isset($hashes) && $hashes != null)
        {
            $hashes = explode(',', $hashes);

            foreach ($hashes as $hash)
            {
                $orderPayment = Mage::getModel('sales/order_payment')
                                    ->getCollection()
                                    ->addFieldToFilter('ebanx_hash', $hash)
                                    ->getFirstItem();

                $response = \Ebanx\Ebanx::doQuery(array('hash' => $hash));

                if ($response->status == 'SUCCESS')
                {
                    // Get the new status from Magento
                    $orderStatus = $this->_getOrderStatus($response->payment->status);

                    // Update order status
                    $order = Mage::getModel('sales/order')
                                ->load($orderPayment->getParentId(), 'entity_id');

                    if ($order->getRealOrderId())
                    {
                        $order->addStatusToHistory($orderStatus, '', true)
                              ->save();
                    }
                }
            }
        }

        echo 'OK';
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

            if ($hash == null)
            {
              $this->getResponse()
                 ->setRedirect(Mage::getUrl('customer/account'));
            }

            $merchantPaymentCode = $this->getRequest()->getParam('merchant_payment_code');

            $response = \Ebanx\Ebanx::doQuery(array('hash' => $hash));

            if ($response->status == 'SUCCESS')
            {
                // Get the new status from Magento
                $orderStatus = $this->_getOrderStatus($response->payment->status);

                // Update order status
                $order = Mage::getModel('sales/order')->loadByIncrementId($merchantPaymentCode);

                if ($order)
                {
                  $order->setStatus($orderStatus)
                        ->save();
                }
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

    /**
     * Success action for boleto payments
     * @return void
     */
    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');

        // Shows the boleto print page
        if ($session['ebanxMethod'] == 'boleto')
        {
            $hash     = $this->getRequest()->getParam('hash');
            $response = \Ebanx\Ebanx::doQuery(array('hash' => $hash));

            // Render the success page - inherits the default success page
            $incrementId = $response->payment->merchant_payment_code;
            $order = Mage::getModel('sales/order')->load($incrementId, 'increment_id');

            $this->loadLayout();
            $this->getLayout()
                 ->getBlock('content')
                 ->setTemplate('ebanx/payment/success.phtml');

            // hacks!
            $_SESSION['boletoUrl']    = $response->payment->boleto_url;

            $session->clear();
            Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($order->getId())));
            $this->loadLayout()->renderLayout();
        }
        // If it was a credit card payment redirects to the success page
        else
        {
            $this->getResponse()
                 ->setRedirect(Mage::getUrl('checkout/onepage/success'));
        }
    }

    /**
     * Updates the allowed payment methods in direct mode
     * @return void
     */
    public function updatePaymentsAction()
    {
        if (isset($_COOKIE['adminhtml']))
        {
            // Use the fake API
            $ch = curl_init();

            $key = \Ebanx\Config::getIntegrationKey();
            $url = 'http://integration.ebanx.com/api/getPaymentMethods.php?key=' . $key;

            // Check if test mode is enabled
            if (\Ebanx\Config::getTestMode() == true)
            {
                $url .= '&test';
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($response);

            if ($response->status == 'SUCCESS')
            {
              //Reset all values
              Mage::getModel('core/config')
                  ->saveConfig('payment/ebanx/direct_cards', 0);

              if (in_array('boleto', $response->methods))
              {
                echo "Boleto is enabled.</br>";
              }

              if (in_array('credit_cards', $response->methods))
              {
                echo "Credit cards are enabled.</br>";

                Mage::getModel('core/config')
                    ->saveConfig('payment/ebanx/direct_cards', 1);
              }

              if (in_array('tef', $response->methods))
              {
                echo "Electronic Funds Transfer is enabled.</br>";
              }

              echo 'The payment methods for direct method were successfully updated.</br>';
            }
            else
            {
              if (isset($response->status) && $response->status == 'ERROR')
              {
                echo $response->message;
              }
              else
              {
                echo 'Direct mode is disabled for your merchant account.</br>';
              }
            }

            echo '<button onclick="window.close()">Close window</button>';
        }
        else
        {
            $this->_forward('defaultNoRoute');
        }
    }
}

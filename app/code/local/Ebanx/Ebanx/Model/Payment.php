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

class Ebanx_Ebanx_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'ebanx';

  protected $_isGateway          = true;
  protected $_isInitializeNeeded = false;

  /**
   * Allowed operations
   */
  protected $_canAuthorize           = true;
  protected $_canCapture             = true;
  protected $_canCapturePartial      = false;
  protected $_canRefund              = true;
  protected $_canVoid                = true;
  protected $_canCancel              = true;
  protected $_canUseInternal         = false;
  protected $_canUseCheckout         = true;
  protected $_canUseForMultishipping = false;

  /**
   * The form block
   * @var string
   */
	protected $_formBlockType = 'ebanx/form';

  /**
   * Gets the payment controller checkout URL
   * @return string
   */
	public function getOrderPlaceRedirectUrl()
	{
    Mage::log('Redirecting to ' . $_SESSION['ebxRedirectUrl']);
		return $_SESSION['ebxRedirectUrl'];
	}

    /**
     * Voids an order
     * @param  Varien_Object $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function void(Varien_Object $payment)
    {
        parent::void($payment);

        $hash = $payment->getData('ebanx_hash');
        Mage::log('Void order ' . $hash);

        $response = \Ebanx\Ebanx::doRefundOrCancel(array(
            'hash' => $hash
          , 'description' => 'The order has been cancelled.'
        ));

        return $this;
    }

    /**
     * Cancels an order
     * @param  Varien_Object $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $hash = $payment->getData('ebanx_hash');
        Mage::log('Cancel order ' . $hash);

        return $this->void($payment);
    }

    /**
     * Authorizes a transaction
     * @param  Varien_Object $payment
     * @param  float $amount
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $session = Mage::getSingleton('checkout/session');
        $order   = $payment->getOrder();
        $ebanx   = Mage::app()->getRequest()->getParam('ebanx');

        Mage::log('Authorizing order [' . $order->getApiOrderId() . ']');

        $birthDate = str_pad($ebanx['birth_day'],   2, '0', STR_PAD_LEFT) . '/'
                       . str_pad($ebanx['birth_month'], 2, '0', STR_PAD_LEFT) . '/'
                       . $ebanx['birth_year'];

        $params = array(
            'mode'      => 'full'
          , 'operation' => 'request'
          , 'payment'   => array(
                'name'              => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
              , 'document'          => $ebanx['cpf']
              , 'birth_date'        => $birthDate
              , 'email'             => $order->getCustomerEmail()
              , 'phone_number'      => $order->getBillingAddress()->getTelephone()
              , 'currency_code'     => $order->getBaseCurrencyCode()
              , 'amount_total'      => $order->getBaseGrandTotal()
              , 'payment_type_code' => $ebanx['method']
              , 'merchant_payment_code' => $order->getIncrementId()
              , 'zipcode'           => $order->getBillingAddress()->getData('postcode')
              , 'address'           => $order->getBillingAddress()->getData('street')
              , 'street_number'     => preg_replace('/[\D]/', '', $order->getBillingAddress()->getData('street'))
              , 'city'              => $order->getBillingAddress()->getData('city')
              , 'state'             => $order->getBillingAddress()->getRegionCode()
              , 'country'           => 'br'
          )
        );

        // Add credit card fields if the method is credit card
        if ($ebanx['method'] == 'creditcard')
        {
            $ccExpiration = str_pad($ebanx['cc_expiration_month'], 2, '0', STR_PAD_LEFT) . '/'
                          . $ebanx['cc_expiration_year'];

            $params['payment']['payment_type_code'] = $ebanx['cc_type'];
            $params['payment']['creditcard'] = array(
                'card_name'     => $ebanx['cc_name']
              , 'card_number'   => $ebanx['cc_number']
              , 'card_cvv'      => $ebanx['cc_cvv']
              , 'card_due_date' => $ccExpiration
            );
        }

        // For TEF and Bradesco, add redirect another parameter
        if ($ebanx['method'] == 'tef')
        {
            $params['payment']['payment_type_code'] = $ebanx['tef_bank'];

            // For Bradesco, set payment method as bank transfer
            if ($ebanx['tef_bank'] == 'bradesco')
            {
              $params['payment']['payment_type_code_option'] = 'banktransfer';
            }
        }

        try
        {
            $response = \Ebanx\Ebanx::doRequest($params);

            Mage::log('Authorizing order [' . $order->getIncrementId() . '] - calling EBANX');

            if (!empty($response) && $response->status == 'SUCCESS')
            {
                $hash = $response->payment->hash;

                // Add the EBANX hash in the order data
                $order->getPayment()
                      ->setData('ebanx_hash', $hash)
                      ->save();

                // Redirect to bank page if the client chose TEF
                if (isset($response->redirect_url))
                {
                  $_SESSION['ebxRedirectUrl'] = $response->redirect_url;
                }
                // Redirect to EBANX success page on client store
                else
                {
                  $_SESSION['ebxRedirectUrl'] = Mage::getUrl('ebanx/payment/success') . '?hash=' . $hash;
                }

                Mage::log('Authorizing order [' . $order->getIncrementId() . '] - success');
            }
            else
            {
                Mage::log('Authorizing order [' . $order->getIncrementId() . '] - error: ' . $response->status_message);
                Mage::throwException($response->status_message);
            }
        }
        catch (Exception $e)
        {
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    /**
     * Refunds a transaction
     * @param  Varien_Object $payment
     * @param  float $amount
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        parent::refund($payment, $amount);

        $hash = $payment->getData('ebanx_hash');
        Mage::log('Refund order ' . $hash);

        $response = \Ebanx\Ebanx::doRefund(array(
            'hash'      => $hash
          , 'operation' => 'request'
          , 'amount'    => $amount
          , 'description' => 'Order re'
        ));

        return $this;
    }
}
<?php

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
          , 'currency_code'     => $order->getGlobalCurrencyCode()
          , 'amount'            => $order->getGrandTotal()
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

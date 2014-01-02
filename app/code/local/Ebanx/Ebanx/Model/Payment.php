<?php

require_once dirname(dirname(__FILE__)) . '/etc/bootstrap.php';

class Ebanx_Ebanx_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'ebanx';

	protected $_isInitializeNeeded = true;
    protected $_isGateway          = true;

    protected $_canAuthorize       = false;
    protected $_canCapture         = false;
    protected $_canCapturePartial  = false;
    protected $_canRefund          = false;
    protected $_canVoid            = true;
    protected $_canUseInternal     = false;
    protected $_canUseCheckout     = true;
    protected $_canUseForMultishipping = true;

  	protected $_formBlockType = 'ebanx/form';

	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('ebanx/payment/checkout');
	}

    // /**
    //  * Refund an order
    //  * @todo: make it work. no online refund option available, no docs
    //  * @param  Varien_Object $payment [description]
    //  * @param  [type]        $amount  [description]
    //  * @return [type]                 [description]
    //  */
    // public function refund(Varien_Object $payment, $amount)
    // {
    //     $hlp = Mage::helper('googlecheckout');

    //     $reason = $this->getReason() ? $this->getReason() : $hlp->__('No Reason');
    //     $comment = $this->getComment() ? $this->getComment() : $hlp->__('No Comment');

    //     $api = Mage::getModel('googlecheckout/api')->setStoreId($payment->getOrder()->getStoreId());
    //     $api->refund($payment->getOrder()->getExtOrderId(), $amount, $reason, $comment);

    //     return $this;
    // }

    /**
     * Cancel an order
     * @param  Varien_Object $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function void(Varien_Object $payment)
    {
        die('lawl');
        $hash = $payment->getData('ebanx_hash');

        \Ebanx\Ebanx::doCancel(array(
            'hash' => $request->payment->hash
        ));

        return $this;
    }
}
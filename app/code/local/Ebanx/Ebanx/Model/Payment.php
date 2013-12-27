<?php

class Ebanx_Ebanx_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'ebanx';
	protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
  	protected $_formBlockType = 'ebanx/form';

		public function getOrderPlaceRedirectUrl()
		{
				return Mage::getUrl('ebanx/payment/checkout');
		}
}
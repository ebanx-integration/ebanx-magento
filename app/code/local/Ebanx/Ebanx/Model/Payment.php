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

	protected $_isInitializeNeeded = true;
    protected $_isGateway          = true;

    /**
     * Allowed operations
     */
    protected $_canAuthorize           = false;
    protected $_canCapture             = false;
    protected $_canCapturePartial      = false;
    protected $_canRefund              = false;
    protected $_canVoid                = true;
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
		return Mage::getUrl('ebanx/payment/checkout');
	}

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
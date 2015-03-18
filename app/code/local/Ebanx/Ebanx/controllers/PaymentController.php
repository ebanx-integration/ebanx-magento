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
                  try
                  {
                    // Get the new status from Magento
                    $orderStatus = $this->_getOrderStatus($response->payment->status);

                    // Update order status
                    $order = Mage::getModel('sales/order')
                                ->load($orderPayment->getParentId(), 'entity_id');

                    // Checks if the order exists
                    if (!$order->getRealOrderId())
                    {
                      throw new Exception('Order cannot be found.');
                    }

                    // If payment status is CA - Canceled - AND order can be cancelled
                    if ($response->payment->status == 'CA' && $order->canCancel())
                    {
                      if (!$order->canCancel())
                      {
                        throw new Exception('Order cannot be canceled, assuming already processed.');
                      }

                      // Cancel order
                      $order->cancel();

                      // Set orderStatus to Generic canceled status - nothing more to do
                      $orderStatus = 'canceled';

                        // Comment on order
                      $order->addStatusHistoryComment('Automatically CANCELED by EBANX Notification.', false);
                    }

                    // If payment status is CO - Paid - AND order can be invoiced
                    if ($response->payment->status == 'CO')
                    {
                      // If can NOT Invoice or order is not new
                      if (!$order->canInvoice())
                      {
                        throw new Exception('Order cannot be invoiced, assuming already processed.');
                      }

                      // Invoice
                      $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                      $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                      $invoice->register();
                      $invoice->getOrder()->setCustomerNoteNotify(true);
                      $invoice->getOrder()->setIsInProcess(true);

                      // Commit invoice to order
                      $transactionSave =  Mage::getModel('core/resource_transaction')
                                  ->addObject($invoice)
                                  ->addObject($invoice->getOrder());
                      $transactionSave->save();

                        // Comment on order
                      $order->addStatusHistoryComment('Automatically INVOICED by EBANX Notification.', false);
                      $order->getSendConfirmation();
                      $order->setEmailSent(true);
                      $order->sendNewOrderEmail();
                    }

                    // Set status
                    $order->addStatusToHistory($orderStatus, 'Status changed by EBANX Notification.', false)
                          ->save();

                    echo 'OK: payment ' . $hash . ' was updated<br>';
                  }
                  catch (Exception $e)
                  {
                    echo 'NOK: payment ' . $hash . ' could not update order, Exception: '  . $e->getMessage() . '<br>';
                  }
                }
                else
                {
                  echo 'NOK: payment ' . $hash . ' could not be updated.<br>';
                }
            }
        }
        else
        {
          echo 'NOK: empty request.';
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
          , 'PE' => Mage::getStoreConfig('payment/ebanx/order_status')
          , 'CA' => Mage::getStoreConfig('payment/ebanx/canceled_order_status')
          , 'OP' => Mage::getStoreConfig('payment/ebanx/order_status')
        );

        return $orderStatus[$status];
    }
}

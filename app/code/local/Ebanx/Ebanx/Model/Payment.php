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

      $country = strtolower($payment->getOrder()->getBillingAddress()->getCountry());

      if ($country == 'br')
      {
        $this->authorizeDirectApi($payment, $amount);
      }
      else
      {
        $this->authorizeCheckout($payment, $amount);
      }
  }

  /**
   * Authorizes a transaction using EBANX Direct
   * @param  Varien_Object $payment
   * @param  float $amount
   * @return Mage_Payment_Model_Method_Abstract
   */
  public function authorizeCheckout(Varien_Object $payment, $amount)
  {
    $session = Mage::getSingleton('checkout/session');
    $order   = $payment->getOrder();
    $ebanx   = Mage::app()->getRequest()->getParam('ebanx');

    Mage::log('Authorizing order [' . $order->getApiOrderId() . ']');

    // Street number workaround
    $streetNumber = preg_replace('/[\D]/', '', $order->getBillingAddress()->getData('street'));
    $streetNumber = ($streetNumber > 0) ? $streetNumber : '1';

    // Defines the order ID, if in test append time() to avoid errors
    $testMode = (intval(Mage::getStoreConfig('payment/ebanx/testing')) == 1);
    $orderId  = $order->getIncrementId() . ($testMode ? time() : '');

    // Cut order ID in test mode
    if (strlen($orderId) > 20 && $testMode)
    {
      $orderId = substr($orderId, 0, 20);
    }

    // Gets the currency code and total
    // Backend/base currency
    if (Mage::getStoreConfig('payment/ebanx/paymentcurrency') == 'base')
    {
      $amountTotal  = $order->getBaseGrandTotal();
      $currencyCode = $order->getBaseCurrencyCode();
    }
    else
    // Frontend currency
    {
      $amountTotal  = $order->getGrandTotal();
      $currencyCode = $order->getOrderCurrency()->getCurrencyCode();
    }

    // On guest checkout, get billing email address
    $email = $order->getCustomerEmail() ?: $order->getBillingAddress()->getEmail();

    $params = array(
          'name'              => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
        , 'email'             => $email
        , 'phone_number'      => $order->getBillingAddress()->getTelephone()
        , 'currency_code'     => $currencyCode
        , 'amount'            => $amountTotal
        , 'payment_type_code' => '_all'
        , 'merchant_payment_code' => $orderId
        , 'order_number'      => $order->getIncrementId()
        , 'zipcode'           => $order->getBillingAddress()->getData('postcode')
        , 'address'           => $order->getBillingAddress()->getData('street')
        , 'street_number'     => $streetNumber
        , 'city'              => $order->getBillingAddress()->getData('city')
        , 'state'             => $order->getBillingAddress()->getRegionCode()
        , 'country'           => strtolower($order->getBillingAddress()->getCountry())
    );

    try
    {
        \Ebanx\Config::setDirectMode(false);
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
            Mage::throwException($this->getEbanxErrorMessage($response->status_code));
        }
    }
    catch (Exception $e)
    {
        Mage::throwException($e->getMessage());
    }

    return $this;
  }

    /**
     * Authorizes a transaction using EBANX Direct
     * @param  Varien_Object $payment
     * @param  float $amount
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function authorizeDirectApi(Varien_Object $payment, $amount)
    {
      $session = Mage::getSingleton('checkout/session');
      $order   = $payment->getOrder();
      $ebanx   = Mage::app()->getRequest()->getParam('ebanx');

      Mage::log('Authorizing order [' . $order->getApiOrderId() . ']');

      $birthDate = str_pad($ebanx['birth_day'],   2, '0', STR_PAD_LEFT) . '/'
                     . str_pad($ebanx['birth_month'], 2, '0', STR_PAD_LEFT) . '/'
                     . $ebanx['birth_year'];

      // Street number workaround
      $streetNumber = preg_replace('/[\D]/', '', $order->getBillingAddress()->getData('street'));
      $streetNumber = ($streetNumber > 0) ? $streetNumber : '1';

      // Defines the order ID, if in test append time() to avoid errors
      $testMode = (intval(Mage::getStoreConfig('payment/ebanx/testing')) == 1);
      $orderId  = $order->getIncrementId() . ($testMode ? time() : '');

      // Cut order ID in test mode
      if (strlen($orderId) > 20 && $testMode)
      {
        $orderId = substr($orderId, 0, 20);
      }

      // Gets the currency code and total
      // Backend/base currency
      if (Mage::getStoreConfig('payment/ebanx/paymentcurrency') == 'base')
      {
        $amountTotal  = $order->getBaseGrandTotal();
        $currencyCode = $order->getBaseCurrencyCode();
      }
      else
      // Frontend currency
      {
        $amountTotal  = $order->getGrandTotal();
        $currencyCode = $order->getOrderCurrency()->getCurrencyCode();
      }

      // On guest checkout, get billing email address
      $email = $order->getCustomerEmail() ?: $order->getBillingAddress()->getEmail();

      $state = $order->getBillingAddress()->getRegionCode();
      if (strlen($state) > 2)
      {
        $state = 'PR';
      }

      $params = array(
          'mode'      => 'full'
        , 'operation' => 'request'
        , 'payment'   => array(
              'name'              => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
            , 'document'          => $ebanx['cpf']
            , 'birth_date'        => $birthDate
            , 'email'             => $email
            , 'phone_number'      => $order->getBillingAddress()->getTelephone()
            , 'currency_code'     => $currencyCode
            , 'amount_total'      => $amountTotal
            , 'payment_type_code' => $ebanx['method']
            , 'merchant_payment_code' => $orderId
            , 'order_number'      => $order->getIncrementId()
            , 'zipcode'           => $order->getBillingAddress()->getData('postcode')
            , 'address'           => $order->getBillingAddress()->getData('street')
            , 'street_number'     => $streetNumber
            , 'city'              => $order->getBillingAddress()->getData('city')
            , 'state'             => $state
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

      // For boleto, set the due date
      if ($ebanx['method'] == 'boleto')
      {
        $dueDays = intval(Mage::getStoreConfig('payment/ebanx/boleto_due_date'));
        $dueDate = date('d/m/Y', strtotime("+{$dueDays} day", time()));
        $params['payment']['due_date'] = $dueDate;
      }

      // If has installments, adjust total
      if (isset($ebanx['installments']))
      {
        if ($ebanx['method'] == 'creditcard' && intval($ebanx['installments']) > 1)
        {
          $interestRate = floatval(Mage::getStoreConfig('payment/ebanx/interest_installments'));
          $interestMode = Mage::getStoreConfig('payment/ebanx/installments_mode');

          $params['payment']['instalments']  = intval($ebanx['installments']);
          $params['payment']['amount_total'] = Ebanx_Ebanx_Utils::calculateTotalWithInterest(
                                                    $interestMode
                                                  , $interestRate
                                                  , $amountTotal
                                                  , intval($ebanx['installments'])
                                                );
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
              Mage::throwException($this->getEbanxErrorMessage($response->status_code));
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
      $hash = $payment->getData('ebanx_hash');
      Mage::log('Refund order ' . $hash);

      $response = \Ebanx\Ebanx::doRefund(array(
          'hash'      => $hash
        , 'operation' => 'request'
        , 'amount'    => $amount
        , 'description' => 'Order refunded'
      ));

      return parent::refund($payment, $amount);
  }

    /**
   * Returns user friendly error messages
   * @param  string $errorCode The error code
   * @return string
   */
  protected function getEbanxErrorMessage($errorCode)
  {
      $errors = array(
          "BP-DR-1"  => "O modo deve ser full ou iframe"
        , "BP-DR-2"  => "É necessário selecionar um método de pagamento"
        , "BP-DR-3"  => "É necessário selecionar uma moeda"
        , "BP-DR-4"  => "A moeda não é suportada pelo EBANX"
        , "BP-DR-5"  => "É necessário informar o total do pagamento"
        , "BP-DR-6"  => "O valor do pagamento deve ser maior do que X"
        , "BP-DR-7"  => "O valor do pagamento deve ser menor do que"
        , "BP-DR-8"  => "O valor total somado ao valor de envio deve ser igual ao valor total"
        , "BP-DR-13" => "É necessário informar um nome"
        , "BP-DR-14" => "O nome não pode conter mais de 100 caracteres"
        , "BP-DR-15" => "É necessário informar um email"
        , "BP-DR-16" => "O email não pode conter mais de 100 caracteres"
        , "BP-DR-17" => "O email informado é inválido"
        , "BP-DR-18" => "O cliente está suspenso no EBANX"
        , "BP-DR-19" => "É necessário informar a data de nascimento"
        , "BP-DR-20" => "A data de nascimento deve estar no formato dd/mm/aaaa"
        , "BP-DR-21" => "É preciso ser maior de 16 anos"
        , "BP-DR-22" => "É necessário informar um CPF ou CNPJ"
        , "BP-DR-23" => "O CPF informado não é válido"
        , "BP-DR-24" => "É necessário informar um CEP"
        , "BP-DR-25" => "É necessário informar o endereço"
        , "BP-DR-26" => "É necessário informar o número do endereço"
        , "BP-DR-27" => "É necessário informar a cidade"
        , "BP-DR-28" => "É necessário informar o estado"
        , "BP-DR-29" => "O estado informado é inválido. Deve se informar a sigla do estado (ex.: SP)"
        , "BP-DR-30" => "O código do país deve ser 'br'"
        , "BP-DR-31" => "É necessário informar um telefone"
        , "BP-DR-32" => "O telefone informado é inválido"
        , "BP-DR-33" => "Número de parcelas inválido"
        , "BP-DR-34" => "Número de parcelas inválido"
        , "BP-DR-35" => "Método de pagamento inválido: X"
        , "BP-DR-36" => "O método de pagamento não está ativo"
        , "BP-DR-39" => "CPF, nome e data de nascimento não combinam"
        , "BP-DR-40" => "Cliente atingiu o limite de pagamentos para o período"
        , "BP-DR-41" => "Deve-se escolher um tipo de pessoa - física ou jurídica."
        , "BP-DR-42" => "É necessário informar os dados do responsável pelo pagamento"
        , "BP-DR-43" => "É necessário informar o nome do responsável pelo pagamento"
        , "BP-DR-44" => "É necessário informar o CPF do responsável pelo pagamento"
        , "BP-DR-45" => "É necessário informar a data de bascunebti do responsável pelo pagamento"
        , "BP-DR-46" => "CPF, nome e data de nascimento do responsável não combinam"
        , "BP-DR-47" => "A conta bancário deve conter no máximo 10 caracteres"
        , "BP-DR-48" => "É necessário informar os dados do cartão de crédito"
        , "BP-DR-49" => "É necessário informar o número do cartão de crédito"
        , "BP-DR-51" => "É necessário informar o nome do titular do cartão de crédito"
        , "BP-DR-52" => "O nome do titular do cartão deve conter no máximo 50 caracteres"
        , "BP-DR-54" => "É necessário informar o CVV do cartão de crédito"
        , "BP-DR-55" => "O CVV deve conter no máximo 4 caracteres"
        , "BP-DR-56" => "É necessário informar a data de venciomento do cartão de crédito"
        , "BP-DR-57" => "A data de vencimento do cartão de crédito deve estar no formato dd/mm/aaaa"
        , "BP-DR-58" => "A data de vencimento do boleto é inválida"
        , "BP-DR-59" => "A data de vencimento do boleto é menor do que o permitido"
        , "BP-DR-61" => "Não foi possível criar um token para este cartão de crédito"
        , "BP-DR-62" => "Pagamentos recorrentes não estão habilitados para este merchant"
        , "BP-DR-63" => "Token não encontrado para este adquirente"
        , "BP-DR-64" => "Token não encontrado"
        , "BP-DR-65" => "O token informado já está sendo utilizado"
        , "BP-DR-66" => "Token inválido. O token deve ter entre 32 e 128 caracteres"
        , "BP-DR-67" => "A data de venciomento do cartão de crédito é inválida"
        , "BP-DR-68" => "É necessário informar o número da conta bancária"
        , "BP-DR-69" => "A conta bancária não pode conter mais de 10 caracteres"
        , "BP-DR-70" => "É necessário informar a agência bancária"
        , "BP-DR-71" => "O código do banco não pode ter mais de 5 caracteres"
        , "BP-DR-72" => "É necessário informar o código do banco"
        , "BP-DR-73" => "É necessário informar os dados da conta para débito em conta"
        , "BP-R-1" => "É necessário informar a moeda"
        , "BP-R-2" => "É necessário informar o valor do pagamento"
        , "BP-R-3" => "É necessário informar o código do pedido"
        , "BP-R-4" => "É necessário informar o nome"
        , "BP-R-5" => "É necessário informar o email"
        , "BP-R-6" => "É necessário selecionar o método de pagamento"
        , "BP-R-7" => "O método de pagamento não está ativo"
        , "BP-R-8" => "O método de pagamento é inválido"
        , "BP-R-9" => "O valor do pagamento deve ser positivo: X"
        , "BP-R-10" => "O valor do pagamento deve ser maior do que X"
        , "BP-R-11" => "O método de pagamento não suporta parcelamento"
        , "BP-R-12" => "O número máximo de parcelas é X. O valor informado foi de X parcelas."
        , "BP-R-13" => "O valor mínimo das parcelas é de R$ X."
        , "BP-R-17" => "O pagamento não está aberto"
        , "BP-R-18" => "O típo de pessoa é inválido"
        , "BP-R-19" => "O checkout com CNPJ não está habilitado"
        , "BP-R-20" => "A data de vencimento deve estar no formato dd/mm/aaaa"
        , "BP-R-21" => "A data de vencimento é inválida"
        , "BP-R-22" => "A data de vencimento é inválida"
        , "BP-R-23" => "A moeda não está ativa no sistema"
        , "BP-ZIP-1" => "O CEP não foi informado"
        , "BP-ZIP-2" => "O CEP não é válido"
        , "BP-ZIP-3" => "O endereço não pode ser encontrado"
      );

      if (array_key_exists($errorCode, $errors))
      {
          return $errors[$errorCode];
      }

      return 'Ocorreu um erro desconhecido. Por favor contacte o administrador.';
  }
}
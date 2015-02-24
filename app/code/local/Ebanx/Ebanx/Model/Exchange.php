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

/**
 * Adds EBANX Exchange to Magento currencies
 */
class Ebanx_Ebanx_Model_Exchange extends Mage_Directory_Model_Currency_Import_Abstract
{
    protected $_messages = array();

    protected function _convert($currencyFrom, $currencyTo, $retry = 0)
    {
        try
        {
            $response = \Ebanx\Ebanx::doExchange(array(
                'currency_code' => $currencyFrom
              , 'currency_base_code' => $currencyTo
            ));

            return (float) $response->currency_rate->rate * 1.0;
        }
        catch (Exception $e)
        {
            $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from EBANX.');
            return null;
        }

        $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from EBANX: unsupported currencies.');
        return null;
    }
}
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

/**
 * Helper methods
 */
class Ebanx_Ebanx_Utils
{
    public static function calculateTotalWithInterest($interestMode, $interestRate, $orderTotal, $installments)
    {
        switch ($interestMode) {
          case 'compound':
            $total = self::calculateTotalCompoundInterest($interestRate, $orderTotal, $installments);
            break;
          case 'simple':
            $total = self::calculateTotalSimpleInterest($interestRate, $orderTotal, $installments);
            break;
          default:
            throw new Exception("Interest mode {$interestMode} is unsupported.");
            break;
        }

        return $total;
    }

    protected static function calculateTotalSimpleInterest($interestRate, $orderTotal, $installments)
    {
        return (floatval($interestRate / 100) * floatval($orderTotal) * intval($installments)) + floatval($orderTotal);
    }

    protected static function calculateTotalCompoundInterest($interestRate, $orderTotal, $installments)
    {
        return $orderTotal * pow((1.0 + floatval($interestRate / 100)), $installments);
    }
}
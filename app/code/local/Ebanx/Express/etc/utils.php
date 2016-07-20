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
class Ebanx_Express_Utils
{
    public static function calculateTotalWithInterest($orderTotal, $installments)
    {
          switch ($installments) {
            case '1':
            $interest_rate = 1;
            break;
          case '2':
            $interest_rate = 2.30;
            break;
          case '3':
            $interest_rate = 3.40;
            break;
          case '4':
            $interest_rate = 4.50;
            break;
          case '5':
            $interest_rate = 5.60;
            break;
          case '6':
            $interest_rate = 6.70;
            break;
          case '7':
            $interest_rate = 7.80;
            break;
          case '8':
            $interest_rate = 8.90;
            break;
          case '9':
            $interest_rate = 9.10;
            break;
          case '10':
            $interest_rate = 10.11;
            break;
          case '11':
            $interest_rate = 11.22;
            break;
          case '12':
            $interest_rate = 12.33;
            break;
          default:
            # code...
            break;
        }
         $total = (floatval($interest_rate / 100) * floatval($orderTotal) + floatval($orderTotal));

        return $total; //number_format($total, 2, ",", " ");
     }
}
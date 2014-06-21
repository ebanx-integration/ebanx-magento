# EBANX Magento Payment Gateway Extension

This plugin allows you to integrate your Magento store with the EBANX payment gateway.
It includes support to installments and custom interest rates.

## Requirements

* PHP >= 5.3
* cURL
* Magento >= 1.7

## Installation
### Source
1. Clone the git repo to your Magento root folder
```
git clone --recursive https://github.com/ebanx/ebanx-magento.git
```
2. If you have cache enabled, you'll need to flush it at **System > Cache Management**.
3. Visit your Magento payment settings at **System > Configuration > Payment Methods > EBANX**.
4. Add the integration key you were given by the EBANX integration team. You will need to use different keys in test and production modes.
5. Change the other settings if needed.
6. Go to the EBANX Merchant Area, then to **Integration > Merchant Options**.
  1. Change the _Status Change Notification URL_ to:
```
{YOUR_SITE}/ebanx/payment/notify
```
  2. Change the _Response URL_ to:
```
{YOUR_SITE}/ebanx/payment/return
```
7. That's all!

## Changelog
* 1.4.0: removed unused code, refactored notifications processing.
* 1.3.1: added brazilian states list, country filter.
* 1.3.0: added authorize method.
* 1.2.1: fixed direct checkout bank selector, updated EBANX library
* 1.2.0: added TEF to direct checkout
* 1.1.6: fixed multiple adresses issue (it was sending an array instead of string)
* 1.1.5: fixed notification errors (missing hashes and/or orders)
* 1.1.4: changed boleto page layout (one column), fixed direct checkout broken HTML
* 1.1.3: fixed missing order in notify action
* 1.1.2: add validation to return and notify actions
* 1.1.1: updated the UpdatePaymentMethods action to use the fake API, removed installments from checkout mode
* 1.1.0: implemented direct checkout (full mode)
* 1.0.7: fixed wrong installment values when using multiple currencies
* 1.0.6: fixed wrong totals when using many shipping methods
* 1.0.5: fixed wrong checkout total and currency code
* 1.0.4: included EUR conversion in minimum installment value
* 1.0.3: show installments in checkout when EBANX is selected by default.
* 1.0.2: enforced minimum installment value (R$20,00).
* 1.0.1: added Ebanx_Ebanx_Helper_Data because Magento 1.6 requires it.
* 1.0.0: first release.

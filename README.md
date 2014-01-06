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
2. Visit your Magento payment settings at **System > Configuration > Payment Methods > EBANX**.
3. Click the _Install_ link, and wait for the extension installation to complete.
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

### Magento Connect
Coming soon!

## Changelog
_1.0.0_: first release
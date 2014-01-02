<?php

require_once Mage::getBaseDir('lib') . '/Ebanx/src/autoload.php';

$ebanxConfig = Mage::getStoreConfig('payment/ebanx');
\Ebanx\Config::set(array(
    'integrationKey' => $ebanxConfig['integration_key']
  , 'testMode'       => (intval($ebanxConfig['testing']) == 1)
));
<?php

$this->startSetup();

$attribute = array(
    'type'            => 'text'
  , 'backend_type'    => 'text'
  , 'frontend_input'  => 'text'
  , 'is_user_defined' => true
  , 'label'           => 'EBANX hash'
  , 'visible'         => true
  , 'required'        => false
  , 'user_defined'    => false
  , 'searchable'      => true
  , 'filterable'      => false
  , 'comparable'      => false
  , 'default'         => ''
);

$this->addAttribute("order_payment", "ebanx_hash", $attribute);
$this->endSetup();

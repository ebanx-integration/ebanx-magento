<?php

$this->startSetup();

$this->addAttribute('order_payment', 'ebanx_hash', array(
    'type'            => 'text'
  , 'backend_type'    => 'text'
  , 'frontend_input'  => 'text'
  , 'is_user_defined' => 0
  , 'label'           => 'EBANX hash'
  , 'visible'         => 1
  , 'required'        => 0
  , 'user_defined'    => 0
  , 'searchable'      => 1
  , 'filterable'      => 0
  , 'comparable'      => 0
  , 'default'         => ''
));

$this->addAttribute('customer', 'ebanx_cpf', array(
    'type'             => 'text'
  , 'input'            => 'text'
  , 'label'            => 'EBANX CPF'
  , 'visible'          => 1
  , 'is_user_defined'  => 0
  , 'required'         => 0
  , 'visible_on_front' => 0
  , 'global'           => 1
  , 'default'          => ''
  , 'adminhtml_only'   => 1
  , 'source'           => NULL
));

$this->addAttribute('customer', 'ebanx_birthdate', array(
    'type'             => 'text'
  , 'input'            => 'text'
  , 'label'            => 'EBANX birthdate'
  , 'visible'          => 1
  , 'is_user_defined'  => 0
  , 'required'         => 0
  , 'visible_on_front' => 0
  , 'global'           => 1
  , 'default'          => ''
  , 'adminhtml_only'   => 1
  , 'source'           => NULL
));

$this->endSetup();

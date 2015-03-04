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

try
{
  $this->run("
    UPDATE sales_flat_order_payment
    SET method = 'ebanx_standard'
    WHERE method = 'ebanx';
  ");
}
catch(Exception $e)
{
  Mage::logException($e);
}

try
{
  $this->run("
    INSERT INTO `{$this->getTable('directory/country_region')}`
    (`country_id`,`default_name`,`code`) VALUES
    ('BR','Acre','AC'),
    ('BR','Alagoas','AL'),
    ('BR','Amapá','AP'),
    ('BR','Amazonas','AM'),
    ('BR','Bahia','BA'),
    ('BR','Ceará','CE'),
    ('BR','Distrito Federal','DF'),
    ('BR','Espírito Santo','ES'),
    ('BR','Goiás','GO'),
    ('BR','Maranhão','MA'),
    ('BR','Mato Grosso','MT'),
    ('BR','Mato Grosso do Sul','MS'),
    ('BR','Minas Gerais','MG'),
    ('BR','Pará','PA'),
    ('BR','Paraíba','PB'),
    ('BR','Paraná','PR'),
    ('BR','Pernambuco','PE'),
    ('BR','Piauí','PI'),
    ('BR','Rio de Janeiro','RJ'),
    ('BR','Rio Grande do Norte','RN'),
    ('BR','Rio Grande do Sul','RS'),
    ('BR','Rondônia','RO'),
    ('BR','Roraima','RR'),
    ('BR','Santa Catarina','SC'),
    ('BR','São Paulo','SP'),
    ('BR','Sergipe','SE'),
    ('BR','Tocantins','TO');

    INSERT INTO `{$this->getTable('directory/country_region_name')}`
    (`locale`,`region_id`,`name`)
    SELECT
      'en_US',
      `region_id`,
      `default_name`
    FROM
      `{$this->getTable('directory/country_region')}`
    WHERE
      `country_id` = 'BR';
  ");
}
catch(Exception $e)
{
  Mage::logException($e);
}

$this->endSetup();
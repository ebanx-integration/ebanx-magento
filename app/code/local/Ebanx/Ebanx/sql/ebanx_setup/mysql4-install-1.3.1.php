<?php

$this->startSetup();

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
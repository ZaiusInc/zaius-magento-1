<?php
 
$installer = $this;
 
$installer->startSetup();
$secret = uniqid('', true);
$this->setConfigData('zaius_engage/settings/cart_abandon_secret_key', $secret);
$installer->endSetup();

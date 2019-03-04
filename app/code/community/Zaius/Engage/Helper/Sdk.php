<?php


class Zaius_Engage_Helper_Sdk extends Mage_Core_Helper_Abstract
{
    private $helper;

    private $sdk;


    public function __construct()
    {
        $this->helper = Mage::helper('zaius_engage');
    }

    public function isComposerInstalled()
    {
        $json = 'composer.json';
        if (file_exists($json)) {
            //composer exists
            return true;
        }
        return false;
    }

    public function isSdkInstalled()
    {
        $composer = $this->isComposerInstalled();
        return $composer && file_exists($this->getSdkPath());
    }

    public function getSdkPath()
    {
        $base_path = Mage::getBaseDir('base');
        return $base_path . DS . 'vendor/zaius/zaius-php-sdk';
    }

    public function getSdkClient()
    {
        $apiKey = $this->helper->getZaiusApiKey();
        $zaiusClient = new \ZaiusSDK\ZaiusClient($apiKey);

        $zaiusClient->setQueueDatabaseCredentials([
            'driver' => 'mysql',
            'host' => Mage::getConfig()->getNode('global/resources/default_setup/connection/host'),
            'db_name' => Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname'),
            'user' => Mage::getConfig()->getNode('global/resources/default_setup/connection/username'),
            'password' => Mage::getConfig()->getNode('global/resources/default_setup/connection/password'),
            'port' => Mage::getConfig()->getNode('global/resources/default_setup/connection/port')
        ], Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname').'.zaius_job');

        return $zaiusClient;
    }


}

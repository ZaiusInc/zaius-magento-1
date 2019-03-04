<?php

class Zaius_Engage_Model_Observer_Autoload {
    const AUTOLOADER_FILE = '/vendor/autoload.php';

    protected static $added = false;

    public function addAutoloader()
    {
        if(!self::$added) {
            $autoloadFile = Mage::getBaseDir('base').self::AUTOLOADER_FILE;
            if(file_exists($autoloadFile)) {
                self::$added = true;
                require_once($autoloadFile);
            }
        }

        return $this;
    }
}
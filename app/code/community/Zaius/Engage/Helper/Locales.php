<?php

class Zaius_Engage_Helper_Locales extends Mage_Core_Helper_Abstract
{

    public function getWebsiteCode($id)
    {
        $_storeCode = Mage::app()->getWebsite($id)->getCode();

        return ($this->isWebsiteCodeDefault($id)) ? 'admin' : $_storeCode;

    }

    public function getStoreCode($id)
    {
        $_storeCode = Mage::app()->getStore($id)->getCode();

        return ($this->isStoreCodeDefault($id)) ? 'default' : $_storeCode;

    }

    public function getLangCode($storeId)
    {
        $_langCode = Mage::getStoreConfig('general/locale/code', $storeId);
        return $_langCode;
    }

    public function isWebsiteCodeDefault($storeId)
    {
        $_storeCode = Mage::app()->getWebsite($storeId)->getCode();
        //Mage::log("isStoreCodeDefault - ".$_storeCode,7);

        if ($_storeCode === "admin" || $_storeCode === "default") {
            return true;
        } else {
            return false;
        }
    }

    public function isStoreCodeDefault($storeId)
    {
        $_storeCode = Mage::app()->getStore($storeId)->getCode();
        //Mage::log("isStoreCodeDefault - ".$_storeCode,7);

        if ($_storeCode === "admin" || $_storeCode === "default") {
            return true;
        } else {
            return false;
        }
    }

    public function getLocales()
    {
        /*
         *    $allStores = Mage::app()->getStores();
         *    $allLanguages = Mage::app()->getLocale()->getOptionLocales();
         *    $build = array();
         *
         *    foreach ($allStores as $_eachStoreId => $val)
         *    {
         *      $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
         *      $_storeName = Mage::app()->getStore($_eachStoreId)->getName();
         *      $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
         *
         *      $_langCode = Mage::getStoreConfig('general/locale/code',$_storeId);
         *      $_languageLabel = '';
         *      foreach ($allLanguages as $language) {
         *        if ($language['value'] == $_langCode) {
         *          $_languageLabel = $language['label'];
         *          break;
         *        }
         *      }
         *
         *      $_currencyCode = Mage::app()->getStore($_storeId)->getCurrentCurrencyCode();
         *      $_currencySymbol = Mage::app()->getLocale()->currency( $_currencyCode )->getSymbol();
         *      $_currencyName = Mage::app()->getLocale()->currency( $_currencyCode)->getName();
         *      $_localeBaseUrl = Mage::app()->getStore($_storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
         *
         *      $build[$_storeCode] = ['label'=> $_languageLabel,
         *        'code' => $_langCode,
         *        'currency_symbold' => $_currencySymbol,
         *        'currency_code' => $_currencyCode,
         *        'base_url' => $_localeBaseUrl,
         *      ];
         */

        //Mage::log($_storeId);
        //      //Mage::log($_storeCode);
        //            //Mage::log($_storeName);
        //                  //Mage::log($_languageLabel);
        //                        //Mage::log($_currencyCode);
        //                              //Mage::log($_currencyName);
        //                                    //Mage::log($_currencySymbol);
        //                                          //Mage::log($build);
        //                                              }
    }
}

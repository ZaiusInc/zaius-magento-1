<?php

class Zaius_Engage_Model_Observer_Customer extends Zaius_Engage_Model_Observer
{

    public function entity($observer)
    {
        Mage::log(__METHOD__, 7,$this->logFile,true);
        if ($this->helper->isEnabled()) {
            $this->postCustomerEntity($observer->getCustomer());
        }
    }

    public function entityFromAddress($observer)
    {
        Mage::log(__METHOD__, 7,$this->logFile,true);
        if ($this->helper->isEnabled()) {
            $this->postCustomerEntity($observer->getCustomerAddress()->getCustomer());
        }
    }

    private function postCustomerEntity($customer)
    {
        $customerData = $customer->getData();
        $customerStoreId = $customerData['website_id'];
        $storeCode = $this->localesHelper->getWebsiteCode($customerStoreId);
        $langCode = $this->localesHelper->getLangCode($customerStoreId);
        $entity = array(
            'email' => $customerData['email'],
            'first_name' => $customerData['firstname'],
            'last_name' => $customerData['lastname']
        );
        $entity['store_view_code'] = $storeCode;
        $entity['store_view'] = $langCode;
        $this->helper->addCustomerId($customer->getId(), $entity);
        $addresses = $customer->getAddresses();
        $addressData = null;
        if (isset($customerData['default_billing']) && isset($addresses[$customerData['default_billing']])) {
            $addressData = $addresses[$customerData['default_billing']]->getData();
        } else if (isset($customerData['default_shipping']) && isset($addresses[$customerData['default_shipping']])) {
            $addressData = $addresses[$customerData['default_shipping']]->getData();
        }
        if ($addressData) {
            $streetParts = mb_split(PHP_EOL, (isset($addressData['street']) ? $addressData['street'] : ''));
            $entity['street1'] = $streetParts[0];
            $entity['street2'] = count($streetParts) > 1 ? $streetParts[1] : '';
            $entity['city'] = $addressData['city'];
            $entity['state'] = isset($addressData['region']) ? $addressData['region'] : '';
            $entity['zip'] = $addressData['postcode'];
            $entity['country'] = $addressData['country_id'];
            $entity['phone'] = $addressData['telephone'];
        }
        $this->postEntity('customer', $entity);
        Mage::log(__METHOD__, 7,$this->logFile,true);
        Mage::log(json_encode($entity), 7,$this->logFile,true);
    }

    public function register($observer)
    {
        if (Mage::helper('zaius_engage')->isEnabled()) {
            $customerStoreId = $observer->getCustomer()->getWebsiteId();
            $storeCode = $this->localesHelper->getWebsiteCode($customerStoreId);
            $langCode = $this->localesHelper->getLangCode($customerStoreId);

            $eventData = array();
            $eventData['action'] = 'register';
            $eventData['customer_id'] = $observer->getCustomer()->getId();
            $eventData['store_view_code'] = $storeCode;
            $eventData['store_view'] = $langCode;
            $this->postEvent('customer', $eventData);

            Mage::log(__METHOD__, 7,$this->logFile,true);
            Mage::log(json_encode($eventData),7,$this->logFile,true);
        }
    }

    public function login($observer)
    {
        if (Mage::helper('zaius_engage')->isEnabled()) {
            $customerStoreId = $observer->getCustomer()->getWebsiteId();
            $storeCode = $this->localesHelper->getWebsiteCode($customerStoreId);
            $langCode = $this->localesHelper->getLangCode($customerStoreId);

            $eventData = array();
            $eventData['action'] = 'login';
            $eventData['customer_id'] = $observer->getCustomer()->getId();
            $eventData['store_view_code'] = $storeCode;
            $eventData['store_view'] = $langCode;
            $this->postEvent('customer', $eventData);
        }
        Mage::log(__METHOD__, 7,$this->logFile,true);
        Mage::log(json_encode($eventData),7,$this->logFile,true);
    }

    public function logout($observer)
    {
        $localesHelper = Mage::helper('zaius_engage/locales');
        $langCode = $localesHelper->getLangCode($observer->getCustomer()->getStoreId());

        $customerStoreId = $observer->getCustomer()->getWebsiteId();
        $storeCode = $this->localesHelper->getWebsiteCode($customerStoreId);

        if (Mage::helper('zaius_engage')->isEnabled()) {
            $eventData = array();
            $eventData['action'] = 'logout';
            $eventData['customer_id'] = $observer->getCustomer()->getId();
            $eventData['store_view_code'] = $storeCode;
            $eventData['store_view'] = $langCode;
            $this->postEvent('customer', $eventData);
            $this->addEvent('anonymize');
        }
        Mage::log(__METHOD__, 7,$this->logFile,true);
        Mage::log(json_encode($eventData),7,$this->logFile,true);
    }

}

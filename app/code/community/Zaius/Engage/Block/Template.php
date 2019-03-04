<?php

class Zaius_Engage_Block_Template extends Mage_Core_Block_Template
{

    public function getCacheKeyInfo()
    {
        $session = Mage::getSingleton('core/session');
        $SID = $session->getEncryptedSessionId();

        $info = parent::getCacheKeyInfo();
        $info[] = 'ZAIUS_ENGAGE_CACHEBUSTER_' .
            $SID . '_' .
            md5($this->getEventDataJson);

        return $info;
    }

    private function getSession()
    {
        return Mage::getSingleton('zaius_engage/session');
    }

    public function getEvents()
    {
        $event = Mage::helper('zaius_engage/event');
        $events = $this->getSession()->getEvents();
        if (!$events) {
            $events = array();
        }
        return $events;
    }

    public function clearEvents()
    {
        $this->getSession()->clearEvents();
    }

    public function getEventDataJson($event)
    {
        $eventData = $event->eventParams;
        $store = Mage::app()->getStore();
        if ($store) {
            $eventData['magento_website'] = $store->getWebsite()->getName();
            $eventData['magento_store'] = $store->getGroup()->getName();
            $eventData['magento_store_view'] = $store->getName();
        }
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            Mage::helper('zaius_engage')->addCustomerIdOrEmail($customer->getId(), $eventData);
        }
        return Mage::helper('core')->jsonEncode($eventData);
    }
}

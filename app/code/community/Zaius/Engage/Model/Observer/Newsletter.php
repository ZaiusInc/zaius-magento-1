<?php

class Zaius_Engage_Model_Observer_Newsletter extends Zaius_Engage_Model_Observer
{

    public function subscriptionChange($observer)
    {
        if ($this->helper->isEnabled()) {
            $data = $observer->getDataObject();
            $status = $data->getSubscriberStatus();
            $subscriberData = $data->getData();
            $customerStoreId = $subscriberData['store_id'];
            $storeCode = $this->localesHelper->getStoreCode($customerStoreId);
            $langCode = $this->localesHelper->getLangCode($customerStoreId);

            $createdColumnName = 'zaius_created_at';
            $updatedColumnName = 'zaius_updated_at';

            if ($status == 1) {
                $event = array(
                    'action' => 'subscribe',
                    'email' => $subscriberData['subscriber_email'],
                    'list_id' => $this->helper->getNewsletterListID(),
                    'subscribed' => true,
                    'ts' => now()
                );
                $event['store_view_code'] = $storeCode;
                $this->postEvent('list', $event);
            } else if ($status == 2) {
                /* Status Not Active */
                if ($this->helper->isSubmitNotActiveStatus()) {
                    $event = array(
                        'action' => 'email_submitted',
                        'email' => $subscriberData['subscriber_email'],
                        'list_id' => $this->helper->getNewsletterListID(),
                        'ts' => $subscriberData[$updatedColumnName]
                    );
                    $event['store_view_code'] = $storeCode;
                    $event['store_view'] = $langCode;
                    $this->postEvent('newsletter', $event);
                }
            } else if ($status == 3) {
                $event = array(
                    'action' => 'unsubscribe',
                    'email' => $subscriberData['subscriber_email'],
                    'list_id' => $this->helper->getNewsletterListID(),
                    'subscribed' => false,
                    'ts' => strtotime($subscriberData[$updatedColumnName])
                );
                $event['store_view_code'] = $storeCode;
                $event['store_view'] = $langCode;
                $this->postEvent('list', $event);
            }
        }
        Mage::log(__METHOD__, 7, $this->logFile, true);
    }

}

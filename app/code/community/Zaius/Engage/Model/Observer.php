<?php

class Zaius_Engage_Model_Observer
{

    private $flag = 'zaiusEngageCron';

    protected $helper;
    protected $localesHelper;
    protected $sdk;

    protected $logFile = 'zaius.log';

    const XML_CONFIG_BATCH_NAME = 'zaius_engage/delayed_updates/status';

    public function __construct()
    {
        $this->helper = Mage::helper('zaius_engage');
        $this->localesHelper = Mage::helper('zaius_engage/locales');
        $this->sdk = Mage::helper('zaius_engage/sdk');
    }

    //TODO: Function may no longer be needed as we are no longer needing an entry point from config save.
    public function adminSystemConfigChangedSection()
    {

        $is_active = $this->helper->isCronActive();

        if (!$is_active) {
            //TODO: Stop cron if it is present
            // for now just do nothing
        } else {
            Mage::log('cron feature enabled!', 1);
            $this->doBatchUpdate();
        }
        Mage::log("admin_system_config_changed_section_zaius_engage", 7);

    }

    public function runDelayedUpdatesCron()
    {
        Mage::helper('zaius_engage/sdk')->getSdkClient();
        $worker = new \ZaiusSDK\Zaius\Worker();
        $worker->processAll();
    }

    public function runCron()
    {
        Mage::log('run_cron entry point', 7);
        $is_active = $this->helper->isCronActive();

        if ($is_active) {
            $this->doBatchUpdate();
        }
    }

    public function getSession()
    {
        return Mage::getSingleton('zaius_engage/session');
    }

    public function addEvent($eventType, $eventParams = array())
    {
        $this->getSession()->addEvent($eventType, $eventParams);
    }

    public function clearEvents()
    {
        $this->getSession()->clearEvents();
    }

    protected function doBatchUpdate()
    {
        try {

            //Default opts
            $optsTmp = [
                'limit' => 0,
                'updated_at' => $this->helper->getFlagValue($this->flag),
                'context' => Zaius_Engage_Model_Api::CONTEXT_BATCH,
                'refund' => false,
                'cancel' => false
            ];

            $is_locales_toggled = $this->helper->isLocalesToggled();

            //Add locales flag to opts
            if ($is_locales_toggled) {
                $optsTmp['append_store_view_code'] = true;
                $optsTmp['synthetic_upsert_default'] = true;
                $locales = $this->isStoreViewValid();
                Mage::log('locales toggled', 7);

                foreach ($locales as $locale) {
                    Mage::log($locale, 7);
                    $optsTmp['store_view_code'] = $locale;
                    $opts = json_encode($optsTmp);
                    Mage::log($opts, 7);
                    $locale_products_pull = Mage::getModel('zaius_engage/api')->products($opts);

                    foreach (json_decode($locale_products_pull) as $product) {
                        //Mage::log($product,7);
                        //Mage::log($product->data->name,7);
                        //Mage::log($product->type,7);
                        $this->postEntity($product->type, $product->data);
                    }

                }

                unset($optsTmp['store_view_code']);
                unset($optsTmp['append_store_view_code']);
                unset($optsTmp['synthetic_upsert_default']);
            }

            //Mage::log('out locales toggle',7);
            //Mage::log($optsTmp,7);
            $opts = json_encode($optsTmp);

            $products_pull = Mage::getModel('zaius_engage/api')->products($opts);
            $customers_pull = Mage::getModel('zaius_engage/api')->customers($opts);
            $orders_pull = Mage::getModel('zaius_engage/api')->orders($opts);

            foreach (json_decode($products_pull, true) as $product) {
                //Mage::log($product,7);
                //Mage::log($product->data->name,7);
                //Mage::log($product->type,7);
                $this->postEntity($product['type'], $product['data']);
            }

            foreach (json_decode($customers_pull, true) as $customer) {
                //Mage::log($customer,7);
                //Mage::log($customer->data->email,7);
                //Mage::log($customer->type,7);
                $this->postEntity($customer['type'], $customer['data']);
            }

            foreach (json_decode($orders_pull, true) as $order) {
                //Mage::log($order,7);
                //Mage::log($order->data->action,7);
                //Mage::log($order->type,7);
                $this->postEvent($order['type'], $order['data']);
            }

        } catch (\Exception $e) {
            Mage::log($e, 7);
        }

        $doBatchUpdate = $this->helper->getUpdateTime();
        Mage::log("Batch Update Timestamp: $doBatchUpdate", null, "zaius.log");
        $this->helper->setFlagValue($this->flag, $doBatchUpdate);
    }
    //TODO: once localesHelper is merged this function should go in there and taken out of @line:549 of Api.php aswell
    //also maybe rename to validStoreViews()
    private function isStoreViewValid()
    {
        $stores = Mage::app()->getStores();
        $validStores = [];

        foreach ($stores as $store) {
            $store = Mage::app()->getStore($store);
            $storeCode = $store->getCode();
            $defaultLocale = Mage::getStoreConfig('general/locale/code');
            $storeViewLocale = Mage::getStoreConfig('general/locale/code', $store);
            if ($storeViewLocale != $defaultLocale) {
                $validStores[] = $storeCode;
            }
        }
        return $validStores;
    }

    protected function getFullActionName($observer)
    {
        $name = NULL;
        $action = $observer->getAction();
        if ($action) {
            $name = $action->getFullActionName();
        }
        return $name;
    }

    protected function getParams($observer)
    {
        $params = NULL;
        $action = $observer->getAction();
        if ($action) {
            $request = $action->getRequest();
            if ($request) {
                $params = $request->getParams();
            }
        }
        return $params;
    }

    protected function postEntity($type, $data)
    {
        $sdk = Mage::helper('zaius_engage/sdk');
        $zaiusClient = $sdk->getSdkClient();
        switch ($type) {
            case 'customer':
                if ($this->helper->isAmazonEnabled()) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->helper->getTrackerId(),
                        $this->helper->getAmazonS3Key(),
                        $this->helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadCustomers($data);
                }
                $zaiusClient->postCustomer($data, $this->isBatchModeEnabled());
                break;
            case 'product':
                if ($this->helper->isAmazonEnabled()) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->helper->getTrackerId(),
                        $this->helper->getAmazonS3Key(),
                        $this->helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadProducts($data);
                }
                $zaiusClient->postProduct($data, $this->isBatchModeEnabled());
                break;
            default:
                $this->post('https://api.zaius.com/v2/entities', $type, $data);
        }
    }

    public function isBatchModeEnabled()
    {
        return (bool)Mage::getStoreConfig(self::XML_CONFIG_BATCH_NAME);
    }

    protected function postEvent($type, $data)
    {
        $sdk = Mage::helper('zaius_engage/sdk');
        $zaiusClient = $sdk->getSdkClient();
        switch ($type) {
            case 'customer':
                if ($this->helper->isAmazonEnabled()) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->helper->getTrackerId(),
                        $this->helper->getAmazonS3Key(),
                        $this->helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadEvents($data);
                }
                $zaiusClient->postCustomer($data, $this->isBatchModeEnabled());
                break;
            case 'list':
            case 'newsletter':
                if ($this->helper->isAmazonEnabled()) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->helper->getTrackerId(),
                        $this->helper->getAmazonS3Key(),
                        $this->helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadEvents($data);
                }
                $zaiusClient->updateSubscription($data, $this->isBatchModeEnabled());
                break;
            case 'order':
            case 'product':
                if ($this->helper->isAmazonEnabled()) {
                    $s3Client = $zaiusClient->getS3Client(
                        $this->helper->getTrackerId(),
                        $this->helper->getAmazonS3Key(),
                        $this->helper->getAmazonS3Secret()
                    );
                    $s3Client->uploadEvents($data);
                }
                $zaiusClient->postEvent($data, $this->isBatchModeEnabled());
                break;
            default:
                $this->post('https://api.zaius.com/v2/events', $type, $data);
        }
    }

    protected function post($url, $type, $data)
    {
        if (!isset($data['type'])) {
            $data['type'] = 'type';
        }
        $sdk = Mage::helper('zaius_engage/sdk');
        $zaiusClient = $sdk->getSdkClient();

        return $zaiusClient->call($data, 'POST', $url, $this->isBatchModeEnabled());
    }
}
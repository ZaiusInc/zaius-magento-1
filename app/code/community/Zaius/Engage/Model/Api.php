<?php

class Zaius_Engage_Model_Api extends Mage_Api_Model_Resource_Abstract
{

    const DEFAULT_COUPON_QTY = 1;
    const DEFAULT_COUPON_FORMAT = 'alphanum';
    const CONTEXT_DEFAULT = 'default';
    const CONTEXT_BATCH = 'batch';
    const ZAIUS_LOG_FILE = 'zaius.log';

    private $categoriesById = null;

    public function locales()
    {

        $allStores = $this->isStoreViewValid();
        Mage::log(json_encode($allStores), null, 'zaius.log');
        $allLanguages = Mage::app()->getLocale()->getOptionLocales();
        $build = array();

        foreach ($allStores as $_eachStoreId) {
            $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
            Mage::log($_storeCode, null, 'zaius.log');
            $_storeName = Mage::app()->getStore($_eachStoreId)->getName();
            $_storeId = Mage::app()->getStore($_eachStoreId)->getId();

            $_langCode = Mage::getStoreConfig('general/locale/code', $_storeId);
            $_languageLabel = '';
            foreach ($allLanguages as $language) {
                if ($language['value'] == $_langCode) {
                    $_languageLabel = $language['label'];
                    break;
                }
            }

            $_currencyCode = Mage::app()->getStore($_storeId)->getCurrentCurrencyCode();
            $_currencySymbol = Mage::app()->getLocale()->currency($_currencyCode)->getSymbol();
            $_currencyName = Mage::app()->getLocale()->currency($_currencyCode)->getName();
            $_localeBaseUrl = Mage::app()->getStore($_storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

            $build[$_storeCode] = [
                'label' => $_languageLabel,
                'store_code' => $_storeCode,
                'locale' => $_langCode,
                'currency_symbol' => $_currencySymbol,
                'currency_code' => $_currencyCode,
                'base_url' => $_localeBaseUrl,
            ];

            //Mage::log($_storeId);
            //Mage::log($_storeCode);
            //Mage::log($_storeName);
            //Mage::log($_languageLabel);
            //Mage::log($_currencyCode);
            //Mage::log($_currencyName);
            //Mage::log($_currencySymbol);
            //Mage::log($build);
        }
        return json_encode(['locales' => $build]);
    }

    public function customers($jsonOpts)
    {
        $version = Mage::getConfig()->getNode('modules/Zaius_Engage/version');
        $helper = Mage::helper('zaius_engage');
        list($limit, $offset) = $this->parseOpts($jsonOpts);

        $opts = json_decode($jsonOpts, true);
        $updatedAt = isset($opts['updated_at']) ? $opts['updated_at'] : null;
        $createdAt = isset($opts['created_at']) ? $opts['created_at'] : null;
        $context = isset($opts['context']) ? $opts['context'] : self::CONTEXT_DEFAULT;
        $filterId = isset($opts['id']) ? $opts['id'] : null;
        $filterMail = isset($opts['email']) ? $opts['email'] : null;

        $customerCollection =
            Mage::getModel('customer/customer')->getCollection()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('firstname')
                ->addAttributeToSelect('lastname')
                ->joinAttribute('billing_street', 'customer_address/street', 'default_billing', null, 'left')
                ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
                ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
                ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->joinAttribute('shipping_street', 'customer_address/street', 'default_shipping', null, 'left')
                ->joinAttribute('shipping_city', 'customer_address/city', 'default_shipping', null, 'left')
                ->joinAttribute('shipping_region', 'customer_address/region', 'default_shipping', null, 'left')
                ->joinAttribute('shipping_postcode', 'customer_address/postcode', 'default_shipping', null, 'left')
                ->joinAttribute('shipping_country_id', 'customer_address/country_id', 'default_shipping', null, 'left')
                ->joinAttribute('shipping_telephone', 'customer_address/telephone', 'default_shipping', null, 'left')
                ->addAttributeToSort('entity_id');
        $customerCollection->getSelect()->limit($limit, $offset);

        if ($updatedAt || $createdAt) {
            list($updateFilter, $createFilter) = $this->parseTime($jsonOpts);
            if ($updateFilter) {
                $customerCollection->addAttributeToFilter('updated_at', array('from' => $updateFilter));
            }
            if ($createFilter) {
                $customerCollection->addAttributeToFilter('created_at', array('from' => $createFilter));
            }
        }

        if ($filterId) {
            $customerCollection->addAttributeToFilter('entity_id', $filterId);
        }

        if ($filterMail) {
            $customerCollection->addAttributeToFilter('email', $filterMail);
        }

        $customers = array();
        foreach ($customerCollection as $mageCustomer) {
            $customerData = $mageCustomer->getData();
            $email = $customerData['email'];

            $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
            $isSubscribed = false;
            if ($subscriber->getId()) {
                $isSubscribed = $subscriber->getData('subscriber_status') == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED;
            }

            $customer = array(
                'email' => $email,
                'first_name' => $customerData['firstname'],
                'last_name' => $customerData['lastname'],
                'subscribed' => $isSubscribed
            );

            $customerId = $helper->getCustomerID($mageCustomer->getId());
            if (!empty($customerId)) {
                $customer['customer_id'] = $customerId;
            }

            $addressType = null;
            if (isset($customerData['default_billing']) && $customerData['default_billing'] != null) {
                $addressType = 'billing';
            } else if (isset($customerData['default_shipping']) && $customerData['default_shipping'] != null) {
                $addressType = 'shipping';
            }
            if ($addressType != null) {
                $streetParts = mb_split(PHP_EOL, (isset($customerData["${addressType}_street"]) ? $customerData["${addressType}_street"] : ''));
                $customer['street1'] = $streetParts[0];
                $customer['street2'] = count($streetParts) > 1 ? $streetParts[1] : '';
                $customer['city'] = $customerData["${addressType}_city"];
                $customer['state'] = $customerData["${addressType}_region"];
                $customer['zip'] = $customerData["${addressType}_postcode"];
                $customer['country'] = $customerData["${addressType}_country_id"];
                $customer['phone'] = $customerData["${addressType}_telephone"];
            }
            if ($updateFilter) {
                $customer['updated_at'] = $mageCustomer->getData('updated_at');
            }
            if ($createFilter) {
                $customer['created_at'] = $mageCustomer->getData('created_at');
            }
            $customer['zaius_engage_version'] = $version;
            $suppressions = 0;
            if (is_null($customer['customer_id']) && is_null($customer['email'])) {
                $emptyFields = array();
                $suppressions++;
                $emptyFields[] = is_null($customer['email']) ? 'email' : false;
                $emptyFields[] = is_null($customer['customer_id']) ? 'customer_id' : false;

                Mage::log("Customer information cannot be null.", 4, self::ZAIUS_LOG_FILE);
                // requested operation, time of API call
                Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 4, self::ZAIUS_LOG_FILE);
                // missing field
                Mage::log("Null field(s): ", 4, self::ZAIUS_LOG_FILE);
                Mage::log(print_r($emptyFields, true), 4, self::ZAIUS_LOG_FILE);
                Mage::log("End Null field(s).", 4, self::ZAIUS_LOG_FILE);
            } else {
                $customers[] = array(
                    'type' => 'customer',
                    'data' => $customer
                );
            }
        }

        Mage::log("Customer information fully assembled.", 6, self::ZAIUS_LOG_FILE);
        // requested operation, time of API call
        Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 6, self::ZAIUS_LOG_FILE);
        // length of response
        Mage::log("Response Length: " . count($customers) . ".", 6, self::ZAIUS_LOG_FILE);
        // supressed fields
        Mage::log("# Field suppression: " . $suppressions . ".", 6, self::ZAIUS_LOG_FILE);

        return json_encode($customers);
    }

    public function products($jsonOpts)
    {
        $version = Mage::getConfig()->getNode('modules/Zaius_Engage/version');
        list($limit, $offset) = $this->parseOpts($jsonOpts);

        $opts = json_decode($jsonOpts, true);
        $typeId = isset($opts['type_id']) ? $opts['type_id'] : null;
        $updatedAt = isset($opts['updated_at']) ? $opts['updated_at'] : null;
        $createdAt = isset($opts['created_at']) ? $opts['created_at'] : null;
        $localesFlag = isset($opts['locales']) ? $opts['locales'] : null;

        isset($opts['store_view_code']) ?
            (list($storeView, $showStore) = array($opts['store_view_code'], true)) :
            (list($storeView, $showStore) = array($this->getDefaultStoreView(), false));

        $appendCode = isset($opts['append_store_view_code']) ? $opts['append_store_view_code'] : false;
        $upsertCode = isset($opts['synthetic_upsert_default']) ? $opts['synthetic_upsert_default'] : false;

        $productId = isset($opts['product_id']) ? $opts['product_id'] : null;
        $productSku = isset($opts['sku']) ? $opts['sku'] : null;

        $d = '$LOCALE$';

        $isStoreViewValid = $this->isStoreViewValid();
        if (isset($opts['store_view_code'])) {
            if (!in_array($opts['store_view_code'], $isStoreViewValid)) {
                $optsError = array(
                    'ERROR' => 'Please set store_view_code to a valid locale store view code. Available options: ' . implode(',', $isStoreViewValid)
                );
                return json_encode($optsError);
            }
        }

        if ($upsertCode && !$appendCode) {
            $optsError = array(
                'ERROR' => 'Please set append_store_view_code to true.'
            );
            return json_encode($optsError);
        }

        $storeCode = $storeView;
        $storeId = $this->getStoreId($storeView);

        $currencyCode = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
        $currencySymb = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();

        if ($storeView !== $this->getDefaultStoreView()) {
            // verify we have a valid store_view_code
            if (in_array($storeView, $isStoreViewValid, true)) {
                Mage::log('Valid Store View Code.', null, self::ZAIUS_LOG_FILE);
            } else {
                $storeView = $this->getDefaultStoreView();
                Mage::log('Invalid Store View Code.', null, self::ZAIUS_LOG_FILE);
            }
        }
        $context = isset($opts['context']) ? $opts['context'] : self::CONTEXT_DEFAULT;
        Mage::log("Products running in $context context", null, self::ZAIUS_LOG_FILE);

        $productCollection = null;
        if (Mage::helper('zaius_engage')->isCollectAllProductAttributes()) {
            $productCollection =
                Mage::getModel('catalog/product')->getCollection()
                    ->setStoreId($storeId)
                    ->addAttributeToSelect('id')
                    ->addAttributeToSort('entity_id');
        } else {
            $productCollection =
                Mage::getModel('catalog/product')->getCollection()
                    ->setStoreId($storeId)
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('sku')
                    ->addAttributeToSelect('price')
                    ->addAttributeToSelect('special_price')
                    ->addAttributeToSelect('special_from_date')
                    ->addAttributeToSelect('special_to_date')
                    ->addAttributeToSelect('short_description')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('manufacturer')
                    ->addAttributeToSort('entity_id');
        }
        $productCollection->getSelect()->limit($limit, $offset);

        if ($typeId == "configurable" || $typeId == "simple") {
            $productCollection->addAttributeToFilter('type_id', $typeId);
        }

        if ($updatedAt || $createdAt) {
            list($updateFilter, $createFilter) = $this->parseTime($jsonOpts);
            if ($updateFilter) {
                $productCollection->addAttributeToFilter('updated_at', array('from' => $updateFilter));
            }
            if ($createFilter) {
                $productCollection->addAttributeToFilter('created_at', array('from' => $createFilter));
            }
        }

        if ($productId) {
            $productCollection->addAttributeToFilter('entity_id', $productId);
        }

        if ($productSku) {
            $productCollection->addAttributeToFilter('sku', $productSku);
        }

        $categoryCollection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('name');
        $this->categoriesById = array();
        foreach ($categoryCollection as $mageCategory) {
            $this->categoriesById[$mageCategory->getId()] = $mageCategory;
        }

        $mediaConfigHelper = Mage::getModel('catalog/product_media_config');
        $products = array();
        foreach ($productCollection as $mageProductForId) {
            $mageProduct = $mageProductForId;
            $product = array();
            $helper = Mage::helper('zaius_engage');
            if ($helper->isCollectAllProductAttributes()) {
                $mageProduct = Mage::getModel('catalog/product')->setStoreId($storeId)->load($mageProductForId->getId());
                $product = Zaius_Engage_Model_ProductAttribute::getAttributes($mageProduct);
            }
            $product['product_id'] = ($upsertCode !== false || ($appendCode && !$upsertCode)) ?
                $helper->getProductID($mageProduct->getId()) . $d . $storeView :
                $helper->getProductID($mageProduct->getId());
            if ($upsertCode) {
                list($sku) = explode('$', $product['product_id']);
                $product['default_language_product_id'] = $sku;
            }
            $product['product_url'] = $mageProduct->getProductUrl();
            $product['name'] = $mageProduct->getName();
            $product['sku'] = $mageProduct->getSku();
            $product['description'] = $mageProduct->getShortDescription();
            $product['image_url'] = $mediaConfigHelper->getMediaUrl($mageProduct->getImage());
            $product['category'] = $this->getDeepestCategoryPath($mageProduct);
            if ($mageProduct->getManufacturer()) {
                $product['brand'] = $mageProduct->getAttributeText('manufacturer');
            }
            if ($mageProduct->getPrice()) {
                $product['price'] = $mageProduct->getPrice();
            }
            if ($mageProduct->getSpecialPrice()) {
                $product['special_price'] = $mageProduct->getSpecialPrice();
                if ($mageProduct->getSpecialFromDate()) {
                    $product['special_price_from_date'] = strtotime($mageProduct->getSpecialFromDate());
                }
                if ($mageProduct->getSpecialToDate()) {
                    $product['special_price_to_date'] = strtotime($mageProduct->getSpecialToDate());
                }
            }
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->loadByProduct($mageProduct);
            if ($stockItem && $stockItem->getId() && $stockItem->getManageStock()) {
                $product['qty'] = $stockItem->getQty();
                $product['is_in_stock'] = $stockItem->getIsInStock();
            }
            if ($typeId) {
                $product['type_id'] = $mageProduct->getData('type_id');
            }
            $product['parent_product_id'] = $this->getParentIds($mageProduct);
            if ($updateFilter) {
                $product['updated_at'] = $mageProduct->getData('updated_at');
            }
            if ($createFilter) {
                $product['created_at'] = $mageProduct->getData('created_at');
            }
            if ($context == self::CONTEXT_BATCH) {
                $zaiusEngageCron = strtotime($helper->getFlagValue('zaiusEngageCron'));
                $productUpdatedAt = strtotime($mageProduct->getData('updated_at'));
                $zaiusCronUpdatedAt = $this->getZaiusUpdatedAt($product['product_id'], 'product');

                if ($zaiusCronUpdatedAt > $productUpdatedAt) {
                    $mageProduct->setData('_hasDataChanges', true);
                }
                if ($zaiusEngageCron > $zaiusCronUpdatedAt && $zaiusEngageCron > $productUpdatedAt) {
                    continue;
                }
            }
            if ($showStore === true) {
                $product['currency_symbol'] = $currencySymb;
                $product['currency_code'] = $currencyCode;
            }
            //getIsSalable call getIsAvailable
            $product['availability_state'] = $mageProduct->getIsSalable();
            $product['zaius_engage_version'] = $version;
            $suppressions = 0;
            if ($upsertCode) {
                $upsert['product_id'] = $helper->getProductID($mageProduct->getId());
                $upsert[$storeView . '_product_id'] = $product['product_id'];
                $upsert['default_language_product_id'] = $upsert['product_id'];
                if (is_null($upsert['product_id'])) {
                    $suppressions++;
                    Mage::log("Product information cannot be null.", 4, self::ZAIUS_LOG_FILE);
                    // requested operation, time of API call
                    Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 4, self::ZAIUS_LOG_FILE);
                    // missing field
                    Mage::log("Null field: product_id.", 4, self::ZAIUS_LOG_FILE);
                } else {
                    $products[] = array(
                        'type' => 'product',
                        'data' => $upsert
                    );
                }
            }
            if (is_null($product['product_id'])) {
                $suppressions++;
                Mage::log("Product information cannot be null.", 4, self::ZAIUS_LOG_FILE);
                // requested operation, time of API call
                Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 4, self::ZAIUS_LOG_FILE);
                // missing field
                Mage::log("Null field: product_id.", 4, self::ZAIUS_LOG_FILE);
            } else {
                $products[] = array(
                    'type' => 'product',
                    'data' => $product
                );
            }
        }
        $productCount = count($products);
        Mage::log("Products running in $context context; product count returned: $productCount", null, self::ZAIUS_LOG_FILE);
        Mage::log(json_encode($products), 7);

        Mage::log("Product information fully assembled.", 6, self::ZAIUS_LOG_FILE);
        // requested operation, time of API call
        Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 6, self::ZAIUS_LOG_FILE);
        // length of response
        Mage::log("Response Length: " . $productCount . ".", 6, self::ZAIUS_LOG_FILE);
        // supressed fields
        Mage::log("# Field suppression: " . $suppressions . ".", 6, self::ZAIUS_LOG_FILE);

        return json_encode($products);
    }

    private function buildProduct()
    {


    }

    public function orders($jsonOpts)
    {
        $version = Mage::getConfig()->getNode('modules/Zaius_Engage/version');
        $helper = Mage::helper('zaius_engage');
        list($limit, $offset) = $this->parseOpts($jsonOpts);

        $opts = json_decode($jsonOpts, true);
        $updatedAt = isset($opts['updated_at']) ? $opts['updated_at'] : null;
        $createdAt = isset($opts['created_at']) ? $opts['created_at'] : null;
        $refundAction = isset($opts['refund']) ? $opts['refund'] : true;
        $cancelAction = isset($opts['cancel']) ? $opts['cancel'] : true;
        $context = isset($opts['context']) ? $opts['context'] : self::CONTEXT_DEFAULT;

        $orderId = isset($opts['id']) ? $opts['id'] : null;
        $entityId = isset($opts['entity_id']) ? $opts['entity_id'] : null;
        $customerId = isset($opts['customer_id']) ? $opts['customer_id'] : null;
        $customerEmail = isset($opts['email']) ? $opts['email'] : null;

        $orderCollection =
            Mage::getModel('sales/order')->getCollection()
                ->addAttributeToSort('entity_id');
        $orderCollection->getSelect()->limit($limit, $offset);

        $updateFilter = null;
        $createFilter = null;

        if ($updatedAt || $createdAt) {
            list($updateFilter, $createFilter) = $this->parseTime($jsonOpts);
            if ($updateFilter) {
                $orderCollection->addAttributeToFilter('updated_at', array('from' => $updateFilter));
            }
            if ($createFilter) {
                $orderCollection->addAttributeToFilter('created_at', array('from' => $createFilter));
            }
        }

        if ($orderId) {
            $orderCollection->addAttributeToFilter('increment_id', $orderId);
        }

        if ($entityId) {
            $orderCollection->addAttributeToFilter('entity_id', $entityId);
        }

        if ($customerId) {
            $orderCollection->addAttributeToFilter('customer_id', $customerId);
        }

        if ($customerEmail) {
            $orderCollection->addAttributeToFilter('customer_email', $customerEmail);
        }

        $orders = array();
        foreach ($orderCollection as $mageOrder) {
            $ip = '';
            if ($mageOrder->getXForwardedFor()) {
                $ip = $mageOrder->getXForwardedFor();
            } else if ($mageOrder->getRemoteIp()) {
                $ip = $mageOrder->getRemoteIp();
            }
            $event = array(
                'action' => 'purchase',
                'ts' => strtotime($mageOrder->getCreatedAt()),
                'ip' => $ip,
                'ua' => '',
                'order' => $helper->buildOrder($mageOrder)
            );

            try {
                $store = $mageOrder->getStore();
                if ($store) {
                    if ($store->getWebsite()) {
                        $event['magento_website'] = $store->getWebsite()->getName();
                    }
                    if ($store->getGroup()) {
                        $event['magento_store'] = $store->getGroup()->getName();
                    }
                    $event['magento_store_view'] = $store->getName();
                }
            } catch (Mage_Core_Model_Store_Exception $e) {
            }
            $customerId = $mageOrder->getCustomerId();
            $customerIdToUse = $helper->getCustomerID($customerId);
            if (!empty($customerIdToUse)) {
                $event['customer_id'] = $customerIdToUse;
            } elseif ($mageOrder->getCustomerEmail()) {
                $event['email'] = $mageOrder->getCustomerEmail();
            }
            if ($updateFilter !== null) {
                $event['updated_at'] = $mageOrder->getData('updated_at');
            }
            if ($createFilter !== null) {
                $event['created_at'] = $mageOrder->getData('created_at');
            }
            $event['zaius_engage_version'] = $version;
            $suppressions = 0;
            if (is_null($event['action']) || is_null($event['order']['order_id'])) {
                $suppressions++;
                $emptyAction = is_null($event['action']) ? 'action' : false;
                if (!$emptyAction) {
                    unset($event['action']);
                }
                $emptyOrderId = is_null($event['order']['order_id']) ? 'order_id' : false;
                if (!$emptyOrderId) {
                    unset($event['order']['order_id']);
                }
                $emptyBoth = ($emptyAction && $emptyOrderId) ? ' and ' : '';
                Mage::log("Order information cannot be null.", 4, self::ZAIUS_LOG_FILE);
                // requested operation, time of API call
                Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 4, self::ZAIUS_LOG_FILE);
                // missing field
                Mage::log("Null field(s): " . $emptyEmail . $emptyBoth . $emptyListId . ".", 4, self::ZAIUS_LOG_FILE);
            } else {
                $orders[] = array(
                    'type' => 'order',
                    'data' => $event
                );
            }

            if ($refundAction === true && $mageOrder->getTotalRefunded() > 0) {
                $event['action'] = 'refund';
                $event['order'] = $helper->buildOrderNegation(
                    $mageOrder, $mageOrder->getTotalRefunded() * -1);
                $event['zaius_engage_version'] = $version;
                if (is_null($event['action']) || is_null($event['order']['order_id'])) {
                    $suppressions++;
                    $emptyAction = is_null($event['action']) ? 'action' : false;
                    if (!$emptyAction) {
                        unset($event['action']);
                    }
                    $emptyOrderId = is_null($event['order']['order_id']) ? 'order_id' : false;
                    if (!$emptyOrderId) {
                        unset($event['order']['order_id']);
                    }
                    $emptyBoth = ($emptyAction && $emptyOrderId) ? ' and ' : '';
                    Mage::log("Order information cannot be null.", 4, self::ZAIUS_LOG_FILE);
                    // requested operation, time of API call
                    Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 4, self::ZAIUS_LOG_FILE);
                    // missing field
                    Mage::log("Null field(s): " . $emptyEmail . $emptyBoth . $emptyListId . ".", 4, self::ZAIUS_LOG_FILE);
                } else {
                    $orders[] = array(
                        'type' => 'order',
                        'data' => $event
                    );
                }
            } elseif ($cancelAction === true && $mageOrder->getTotalCanceled() > 0) {
                $event['action'] = 'cancel';
                $event['order'] = $helper->buildOrderNegation(
                    $mageOrder, $mageOrder->getTotalCanceled() * -1);
                $event['zaius_engage_version'] = $version;
                if (is_null($event['action']) || is_null($event['order']['order_id'])) {
                    $suppressions++;
                    $emptyAction = is_null($event['action']) ? 'action' : false;
                    if (!$emptyAction) {
                        unset($event['action']);
                    }
                    $emptyOrderId = is_null($event['order']['order_id']) ? 'order_id' : false;
                    if (!$emptyOrderId) {
                        unset($event['order']['order_id']);
                    }
                    $emptyBoth = ($emptyAction && $emptyOrderId) ? ' and ' : '';
                    Mage::log("Order information cannot be null.", 4, self::ZAIUS_LOG_FILE);
                    // requested operation, time of API call
                    Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 4, self::ZAIUS_LOG_FILE);
                    // missing field
                    Mage::log("Null field(s): " . $emptyEmail . $emptyBoth . $emptyOrderId . ".", 4, self::ZAIUS_LOG_FILE);
                } else {
                    $orders[] = array(
                        'type' => 'order',
                        'data' => $event
                    );
                }
            }
            continue;

        }

        Mage::log("Order information fully assembled.", 6, self::ZAIUS_LOG_FILE);
        // requested operation, time of API call
        Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 6, self::ZAIUS_LOG_FILE);
        // length of response
        Mage::log("Response Length: " . count($orders) . ".", 6, self::ZAIUS_LOG_FILE);
        // supressed fields
        Mage::log("# Field suppression: " . $suppressions . ".", 6, self::ZAIUS_LOG_FILE);

        return json_encode($orders);
    }

    private function parseOpts($jsonOpts)
    {
        $opts = json_decode($jsonOpts, true);
        $limit = null;
        if (!isset($opts['limit']) || is_null($opts['limit']) || !is_numeric($opts['limit'])) {
            Mage::throwException('Must specify valid limit');
        } else {
            $limit = intval($opts['limit']);
        }
        $offset = 0;
        if (isset($opts['offset'])) {
            if (!is_null($opts['offset']) && is_numeric($opts['offset'])) {
                $offset = intval($opts['offset']);
            } else {
                Mage::throwException('Invalid offset');
            }
        }
        return array($limit, $offset);
    }

    private function parseTime($jsonOpts)
    {
        $opts = json_decode($jsonOpts, true);
        $createFilter = false;
        $updateFilter = false;
        if (!isset($opts['updated_at']) || null === $opts['updated_at'] || !is_string($opts['updated_at'])) {
            $updateFilter = false;
        } else {
            $updateFilter = date("Y-m-d H:i:s", Mage::getModel("core/date")
                ->gmtDate(strtotime($opts['updated_at'])));
        }
        if (!isset($opts['created_at']) || null === $opts['created_at'] || !is_string($opts['created_at'])) {
            $createFilter = false;
        } else {
            $createFilter = date("Y-m-d H:i:s", Mage::getModel("core/date")
                ->gmtDate(strtotime($opts['created_at'])));
        }
        return array($updateFilter, $createFilter);
    }

    private function getZaiusUpdatedAt($id, $method)
    {
        $coreSource = Mage::getSingleton('core/resource');
        $createdColumnName = 'zaius_created_at';
        $updatedColumnName = 'zaius_updated_at';
        $connection = $coreSource->getConnection('core_read');

        if ($method === 'product') {
            $table = 'catalog_product_entity_media_gallery';
            $column = 'entity_id';
        } elseif ($method === 'coupon') {
            $table = 'salesrule_coupon';
            $column = 'coupon_id';
        } elseif ($method === 'subscriber') {
            $table = 'newsletter_subscriber';
            $column = 'subscriber_email';
        }

        $fileUpdate = false;

        if ($connection->tableColumnExists($table, $createdColumnName) === true && $connection->tableColumnExists($table, $updatedColumnName) === true) {
            $fileUpdate = [];
            $fileUpdate = $connection->fetchCol('SELECT ' . $updatedColumnName . ' FROM ' . $table . ' WHERE ' . $column . '= "' . $id . '"');
        } else {
            Mage::log("Columns do not exist.", 7, self::ZAIUS_LOG_FILE);
        }


        if (!$fileUpdate || $fileUpdate === 0) {
            Mage::log("fileUpdate empty.", 7, self::ZAIUS_LOG_FILE);
            return 0;
        } else if (count($fileUpdate) > 1) {
            usort($fileUpdate, array('Zaius_Engage_Model_Api', 'sortTime'));
            $fileUpdate = reset($fileUpdate);
        } else {
            $fileUpdate = reset($fileUpdate);

        }
        return strtotime($fileUpdate);
    }

    private static function sortTime($time1, $time2)
    {
        if (strtotime($time1) < strtotime($time2))
            return 1;
        else if (strtotime($time1) > strtotime($time2))
            return -1;
        else
            return 0;
    }

    private function getDefaultStoreView()
    {
        return Mage::app()->getDefaultStoreView()->getCode();
    }

    private function getStoreId($storeView)
    {
        $store = Mage::app()->getStore($storeView);
        return $store->getId();
    }

    private function isStoreViewValid()
    {
        $stores = Mage::app()->getStores();
        $validStores = [];
        foreach ($stores as $store) {
            $store = Mage::app()->getStore($store);
            $storeCode = $store->getCode();
            $defaultLocale = Mage::getStoreConfig('general/locale/code');
            $storeViewLocale = Mage::getStoreConfig('general/locale/code', $store);
            //if ($storeViewLocale != $defaultLocale) {
                $validStores[] = $storeCode;
            //}
        }
        return $validStores;
    }

    private function getParentIds($product)
    {
        $id = $product->getId();
        $type = $product->getTypeId();
        $parentIds = null;
        if ($type == 'simple') {
            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
                ->getParentIdsByChild($id);
        }

        if (count($parentIds)) {
            return array_pop($parentIds);
        } else {
            return $id;
        }
    }

    private function getDeepestCategory($product)
    {
        $maxDepth = -1;
        $deepestCategory = null;
        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoriesById[$categoryId];
                if ($category) {
                    $depth = count(explode('/', $category->getPath()));
                    if ($depth > $maxDepth) {
                        $maxDepth = $depth;
                        $deepestCategory = $category;
                    }
                }
            }
        }
        return $deepestCategory;
    }

    private function getDeepestCategoryPath($product)
    {
        $category = $this->getDeepestCategory($product);
        if ($category) {
            return $this->buildCategoryPath($category->getId());
        }
        return null;
    }

    private function buildCategoryPath($catId)
    {
        $catPath = '';
        $cat = $this->categoriesById[$catId];
        if ($cat) {
            $catIds = explode('/', $cat->getPath());
            $numCats = count($catIds) - 1;
            $i = 0;
            foreach (array_slice($catIds, 1) as $catId) {
                $cat = $this->categoriesById[$catId];
                if ($cat) {
                    $catPath .= $cat->getName();
                    if (++$i < $numCats) {
                        $catPath .= ' > ';
                    }
                }
            }
        }
        return $catPath;
    }

    public function createCoupons($jsonOpts)
    {
        /** @var Mage_SalesRule_Helper_Coupon $helper */
        $helper = Mage::helper('salesrule/coupon');
        $version = (string)Mage::getConfig()->getNode('modules/Zaius_Engage/version');
        $opts = json_decode($jsonOpts, true);
        $ruleId = isset($opts['rule_id']) ? intval($opts['rule_id']) : 0;
        $format = isset($opts['format']) ? $opts['format'] : self::DEFAULT_COUPON_FORMAT;
        $qty = isset($opts['qty']) ? intval($opts['qty']) : self::DEFAULT_COUPON_QTY;
        $length = isset($opts['length']) ? intval($opts['length']) : $helper->getDefaultLength();
        $delimiter = isset($opts['delimiter']) ? $opts['delimiter'] : $helper->getCodeSeparator();
        $dash = isset($opts['dash']) ? intval($opts['dash']) : $helper->getDefaultDashInterval();
        $prefix = isset($opts['prefix']) ? $opts['prefix'] : $helper->getDefaultPrefix();
        $suffix = isset($opts['suffix']) ? $opts['suffix'] : $helper->getDefaultSuffix();

        /** @var Mage_SalesRule_Model_Rule $rule */
        $rule = Mage::getModel('salesrule/rule')->load($ruleId);
        if (!$rule || !$rule->getId()) {
            Mage::throwException('No salesrule exists with id ' . $ruleId);
        }
        if (
            $rule->getCouponType() == Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON
            || ($rule->getCouponType() == Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
            && !$rule->getUseAutoGeneration())
        ) {
            Mage::throwException('Cannot auto-generate coupons for this rule.');
        }
        $massGenerator = $rule->getCouponMassGenerator();
        $massGenerator->setRuleId($ruleId)
            ->setFormat($format)
            ->setQty(1)
            ->setLength($length)
            ->setDelimiter($delimiter)
            ->setDash($dash)
            ->setPrefix($prefix)
            ->setSuffix($suffix);
        Mage_SalesRule_Model_Rule::setCouponCodeGenerator($massGenerator);

        $codes = array();
        for ($i = 0; $i < $qty; ++$i) {
            $coupon = $this->_acquireCoupon($rule);
            $codes[] = $coupon->getCode();
        }
        $event = array(
            'type' => 'coupon',
            'data' => array(
                'zaius_engage_version' => $version,
                'codes' => $codes
            )
        );
        return json_encode($event);
    }

    protected function _acquireCoupon($rule, $saveNewlyCreated = true, $saveAttemptCount = 10)
    {
        /** @var Mage_SalesRule_Model_Coupon $coupon */
        $coupon = Mage::getModel('salesrule/coupon');
        $coupon->setRule($rule)
            ->setIsPrimary(false)
            ->setUsageLimit($rule->getUsesPerCoupon() ? $rule->getUsesPerCoupon() : null)
            ->setUsagePerCustomer($rule->getUsesPerCustomer() ? $rule->getUsesPerCustomer() : null)
            ->setExpirationDate($rule->getToDate());

        $couponCode = Mage_SalesRule_Model_Rule::getCouponCodeGenerator()->generateCode();
        $coupon->setCode($couponCode);

        $ok = false;
        if (!$saveNewlyCreated) {
            $ok = true;
        } else if ($rule->getId()) {
            for ($attemptNum = 0; $attemptNum < $saveAttemptCount; $attemptNum++) {
                try {
                    $coupon
                        ->setType(Mage_SalesRule_Helper_Coupon::COUPON_TYPE_SPECIFIC_AUTOGENERATED)
                        ->save();
                } catch (Exception $e) {
                    if ($e instanceof Mage_Core_Exception || $coupon->getId()) {
                        throw $e;
                    }
                    $coupon->setCode(
                        $couponCode .
                        Mage_SalesRule_Model_Rule::getCouponCodeGenerator()->getDelimiter() .
                        sprintf('%04u', rand(0, 9999))
                    );
                    continue;
                }
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            Mage::throwException(Mage::helper('salesrule')->__('Can\'t acquire coupon.'));
        }
        return $coupon;
    }

    public function subscribers($jsonOpts)
    {
        $version = Mage::getConfig()->getNode('modules/Zaius_Engage/version');
        $helper = Mage::helper('zaius_engage');
        list($limit, $offset) = $this->parseOpts($jsonOpts);

        $opts = json_decode($jsonOpts, true);
        $updatedAt = isset($opts['updated_at']) ? $opts['updated_at'] : null;
        $createdAt = isset($opts['created_at']) ? $opts['created_at'] : null;
        $context = isset($opts['context']) ? $opts['context'] : self::CONTEXT_DEFAULT;
        $filterMail = isset($opts['email']) ? $opts['email'] : null;

        $createdColumnName = 'zaius_created_at';
        $updatedColumnName = 'zaius_updated_at';

        $subscriberCollection = Mage::getModel('newsletter/subscriber')->getCollection()->setOrder('subscriber_id');
        $subscriberCollection->getSelect()->limit($limit, $offset);

        if ($updatedAt || $createdAt) {
            list($updateFilter, $createFilter) = $this->parseTime($jsonOpts);
            if ($updateFilter) {
                $subscriberCollection->addFieldToFilter($updatedColumnName, array('from' => $updateFilter));
            }
            if ($createFilter) {
                $subscriberCollection->addFieldToFilter($createdColumnName, array('from' => $createFilter));
            }
        }

        if ($filterMail) {
            $subscriberCollection->addAttributeToFilter('subscriber_email', $filterMail);
        }

        $subscribers = array();
        foreach ($subscriberCollection as $subscriber) {
            $data = $subscriber->getData();
            $isSubscribed = ($data['subscriber_status'] == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
            $entry = array(
                'email' => $data['subscriber_email'],
                'list_id' => $helper->getNewsletterListID(),
                'action' => $isSubscribed ? 'subscribe' : 'unsubscribe'
            );
            if (is_null($entry['email'] || $entry['list_id'])) {
                $broken = true;
                $emptyEmail = is_null($entry['email']) ? 'email' : false;
                if (!$emptyEmail) {
                    unset($entry['email']);
                }
                $emptyListId = is_null($entry['list_id']) ? 'list_id' : false;
                if (!$emptyListId) {
                    unset($entry['list_id']);
                }
                $emptyBoth = ($emptyEmail && $emptyListId) ? ' and ' : '';
                Mage::log("Subscriber information cannot be null.", 4, self::ZAIUS_LOG_FILE);
                // requested operation, time of API call
                Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 4, self::ZAIUS_LOG_FILE);
                // missing field
                Mage::log("Null field(s): " . $emptyEmail . $emptyBoth . $emptyListId . ".", 4, self::ZAIUS_LOG_FILE);
            }
            $zaiusSubscriberUpdatedAt = strtotime($data[$updatedColumnName]);
            $zaiusSubscriberCreatedAt = strtotime($data[$createdColumnName]);
            $zaiusEngageCron = strtotime($helper->getFlagValue('zaiusEngageCron'));
            if ($context == self::CONTEXT_BATCH) {
                if ($zaiusSubscriberCreatedAt === $zaiusSubscriberUpdatedAt) {
                    // do nothing
                    // it's assumed subscriber was sent to Zaius on creation through zaius_engage/observer_newsletter::subscriptionChange
                    Mage::log("Subscriber update not needed.", 7, self::ZAIUS_LOG_FILE);
                    continue;
                }
                if ($zaiusEngageCron > $zaiusSubscriberUpdatedAt) {
                    // do nothing
                    // it's assumed subscriber was sent to Zaius through last cron run
                    Mage::log("Subscriber update not needed.", 7, self::ZAIUS_LOG_FILE);
                    continue;
                }
            }

            /**
             * /* send the subscriber.
             * /* it's assumed subscriber has been updated without observers.
             * /* however, this poses a problem. If the subscriber updates themselves (through observers) BEFORE a cron run,
             * /* $zaiusSubscriberUpdatedAt will be greater than $zaiusEngageCron.
             **/
            $past = date("Y-m-d H:i:s", Mage::getModel("core/date")->timestamp() - 86400 * 60);
            $entry['ts'] = $past;
            $entry['zaius_engage_version'] = $version;
            $subscribers[] = array(
                'type' => 'list',
                'data' => $entry
            );
        }
        if (!$broken) {
            Mage::log("Subscriber information fully assembled.", 6, self::ZAIUS_LOG_FILE);
            // requested operation, time of API call
            Mage::log("Call to " . __METHOD__ . " at " . time() . ".", 6, self::ZAIUS_LOG_FILE);
            // length of response
            Mage::log("Response Length: " . count($entry) . ".", 6, self::ZAIUS_LOG_FILE);
            // supressed fields
            Mage::log("No field suppression.", 6, self::ZAIUS_LOG_FILE);
        }
        return json_encode($subscribers);
    }

    public function configuration($jsonOpts)
    {
        $configuration = [];
        $version = (array)Mage::getConfig()->getNode('modules/Zaius_Engage/version');
        $helper = Mage::helper('zaius_engage');

        $opts = json_decode($jsonOpts, true);
        $zaiusTrackingId = isset($opts['zaius_tracking_id']) ? $opts['zaius_tracking_id'] : null;

        // Check to see if Enterprise Edition FPC is enabled:
        $cacheTypes = Mage::app()->getCacheInstance()->getTypes();
        $fpcEnabled = false;
        foreach ($cacheTypes as $cacheCode => $cacheInfo) {
            if ($cacheCode == 'full_page' && $cacheInfo['status']) {
                $fpcEnabled = true;
                break;
            }
        }
        $edition = 'unknown';
        if (method_exists(Mage, 'getEdition')) {
            $edition = Mage::getEdition();
        }
        // create array for default scope values
        $defaultConfig = Mage::getStoreConfig('zaius_engage');
        unset($defaultConfig['settings']['cart_abandon_secret_key']);
        Mage::log(json_encode($defaultConfig), null, 'zaius.log');
        Mage::log($opts, null, 'zaius.log');
        $inDefaultConfigArray = in_array($zaiusTrackingId, array_map(function ($el) {
            return $el['tracking_id'];
        }, $defaultConfig), true);

        if ($zaiusTrackingId === null || $inDefaultConfigArray) {
            $defaultConfiguration = array(
                'default' => array(
                    'wsi_enabled' => (bool)Mage::getStoreConfig('api/config/compliance_wsi'),
                    'magento_fpc_enabled' => $fpcEnabled,
                    'magento_edition' => $edition,
                    'magento_version' => Mage::getVersion(),
                    'zaius_engage_version' => $version[0],
                    'zaius_engage_enabled' => $helper->isEnabled(),
                    'config' => $defaultConfig
                )
            );
            $configuration['default'] = $defaultConfiguration['default'];
            // $defaultConfiguration;
        }

        // return valid store_views
        $validStores = $this->isStoreViewValid();
        foreach ($validStores as $store) {
            $mageStore = Mage::app()->getStore($store);
            $storeCode = $mageStore->getCode();
            $storeId = $mageStore->getId();

            $zaiusConfig = Mage::getStoreConfig('zaius_engage', $storeId);
            unset($zaiusConfig['settings']['cart_abandon_secret_key']);
            $inStoreConfigArray = Mage::getStoreConfig('zaius_engage/zaius_config/tracking_id', $storeId) == $zaiusTrackingId;

            if ($zaiusTrackingId === null || $inStoreConfigArray) {
                $configuration[$storeCode] = array(
                    'magento_fpc_enabled' => $fpcEnabled,
                    'magento_edition' => $edition,
                    'wsi_enabled' => (bool)Mage::getStoreConfig('api/config/compliance_wsi', $storeId),
                    'magento_version' => Mage::getVersion(),
                    'zaius_engage_version' => $version[0],
                    'zaius_engage_enabled' => $helper->isEnabled(),
                    'config' => $zaiusConfig
                );
            }
        }
        return json_encode($configuration);
    }
}

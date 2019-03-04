<?php

class Zaius_Engage_Helper_Data extends Mage_Core_Helper_Abstract
{

    const VUID_LENGTH = 36;

    public function isEnabled()
    {
        return Mage::helper('core/data')->isModuleEnabled('Zaius_Engage');
    }

    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Zaius_Engage->version;
    }

    public function getTrackerId()
    {
        return trim(Mage::getStoreConfig('zaius_engage/config/zaius_tracker_id'));
    }

    public function getZaiusApiKey()
    {
        return trim(Mage::getStoreConfig('zaius_engage/config/zaius_private_api'));
    }

    public function isAmazonEnabled()
    {
        return trim(Mage::getStoreConfig('zaius_engage/config/amazon_active'));
    }

    public function getAmazonS3Key()
    {
        return trim(Mage::getStoreConfig('zaius_engage/config/amazon_s3_key'));
    }

    public function getAmazonS3Secret()
    {
        return trim(Mage::getStoreConfig('zaius_engage/config/amazon_s3_secret'));
    }

    public function getGlobalIDPrefix()
    {
        return trim(Mage::getStoreConfig('zaius_engage/settings/global_id_prefix'));
    }

    public function getNewsletterListID()
    {
        $listId = trim(Mage::getStoreConfig('zaius_engage/settings/zaius_newsletter_list_id'));
        if (empty($listId)) {
            $listId = 'newsletter';
        }
        $storeName = trim(Mage::app()->getStore()->getGroup()->getName());
        $storeName = mb_strtolower($storeName, mb_detect_encoding($storeName));
        $storeName = mb_ereg_replace('\s+', '_', $storeName);
        $storeName = mb_ereg_replace('[^a-z0-9_\.\-]', '', $storeName);
        $listId = $storeName . '_' . $listId;
        return $this->applyGlobalIDPrefix($listId);
    }

    public function isUseMagentoCustomerID()
    {
        return Mage::getStoreConfigFlag('zaius_engage/settings/use_magento_customer_id');
    }

    public function isTrackOrdersOnFrontend()
    {
        return Mage::getStoreConfigFlag('zaius_engage/settings/track_orders_on_frontend');
    }

    public function isCollectAllProductAttributes()
    {
        return Mage::getStoreConfigFlag('zaius_engage/settings/collect_all_product_attributes');
    }

    public function isLocalesToggled()
    {
        return trim(Mage::getStoreConfig('zaius_engage/localizations/status'));
    }

    public function isCronActive()
    {
        return trim(Mage::getStoreConfig('zaius_engage/batch_updates/status'));
    }

    public function getCronSettings()
    {
        return trim(Mage::getStoreConfig('zaius_engage/batch_updates/schedule'));
    }

    public function getUpdateTime()
    {
        return Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s');
    }

    public function setFlagValue($flag, $value)
    {
        /** @var Mage_Core_Model_Flag $flagModel */
        $flagModel = Mage::getModel('core/flag', ['flag_code' => $flag])->loadSelf();
        $flagModel->setFlagData($value);
        $flagModel->save();
    }

    public function getFlagValue($flag)
    {
        /** @var Mage_Core_Model_Flag $flagModel */
        $flagModel = Mage::getModel('core/flag', ['flag_code' => $flag])->loadSelf();
        return $flagModel->getFlagData();
    }

    public function getReportedCurrency()
    {
        $currency = Mage::getStoreConfig('zaius_engage/config/reported_currency');
        if ($currency == Zaius_Engage_Model_System_Config_Source_Currency::USE_STORE_OPTION) {
            return null;
        }
        return $currency;
    }

    public function getVUID()
    {
        $vuid = null;
        if (!Mage::app()->getStore()->isAdmin()) {
            $vuidCookie = Mage::getModel('core/cookie')->get('vuid');
            if ($vuidCookie && strlen($vuidCookie) >= self::VUID_LENGTH) {
                $vuid = substr($vuidCookie, 0, self::VUID_LENGTH);
            }
        }
        return $vuid;
    }

    public function getVTSRC()
    {
        $vtsrc = null;
        if (!Mage::app()->getStore()->isAdmin()) {
            $vtsrcCookie = Mage::getModel('core/cookie')->get('vtsrc');
            if ($vtsrcCookie) {
                $vtsrc = $vtsrcCookie;
            }
        }
        return $this->prepareVTSRC($vtsrc);
    }

    public function prepareVTSRC($vtsrc)
    {
        $explode = explode('|', urldecode($vtsrc));
        foreach ($explode as $e)
        {
            list($k, $v) = explode('=', $e);
            $result[ $k ] = $v;
        }
        return $result;
    }

    public function addCustomerId($customerId, &$data)
    {
        $customerIdToUse = $this->getCustomerID($customerId);
        if (!empty($customerIdToUse)) {
            $data['customer_id'] = $customerIdToUse;
        }
    }

    public function addCustomerIdOrEmail($customerId, &$data)
    {
        $customerIdToUse = $this->getCustomerID($customerId);
        if (!empty($customerIdToUse)) {
            $data['customer_id'] = $customerIdToUse;
        } elseif (!empty($customerId)) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer) {
                $customerEmail = $customer->getEmail();
                if (!empty($customerEmail)) {
                    $data['email'] = $customer->getEmail();
                }
            }
        }
    }

    public function getCustomerID($customerId)
    {
        $customerIdToUse = null;
        if ($this->isUseMagentoCustomerID()) {
            $customerIdToUse = $this->applyGlobalIDPrefix($customerId);
        }
        return $customerIdToUse;
    }

    public function getProductID($productId)
    {
        return $this->applyGlobalIDPrefix($productId);
    }

    public function getOrderID($orderId)
    {
        return $this->applyGlobalIDPrefix($orderId);
    }

    public function applyGlobalIDPrefix($idToPrefix)
    {
        $prefix = $this->getGlobalIDPrefix();
        if (!empty($prefix) && !empty($idToPrefix)) {
            $idToPrefix = $prefix . $idToPrefix;
        }
        return $idToPrefix;
    }

    public function buildCategoryPath($catId)
    {
        $catPath = '';
        $cat = Mage::getModel('catalog/category')->load($catId);
        $catIds = explode('/', $cat->getPath());
        $numCats = count($catIds) - 1;
        $i = 0;
        foreach (array_slice($catIds, 1) as $catId) {
            $cat = Mage::getModel('catalog/category')->load($catId);
            $catPath .= $cat->getName();
            if (++$i < $numCats) {
                $catPath .= ' > ';
            }
        }
        return $catPath;
    }

    public function buildOrder($mageOrder)
    {
        $convertCurrency = Mage::getSingleton('directory/currency');
        $localesHelper = Mage::helper('zaius_engage/locales');
        $reportedCurrency = $this->getReportedCurrency();
        $order = array(
            'order_id' => $this->getOrderID($mageOrder->getIncrementId()),
            'total' => $convertCurrency->convert($mageOrder->getBaseGrandTotal(), $reportedCurrency),
            'subtotal' => $convertCurrency->convert($mageOrder->getBaseSubtotal(), $reportedCurrency),
            'coupon_code' => $convertCurrency->convert($mageOrder->getCouponCode(), $reportedCurrency),
            'discount' => $convertCurrency->convert($mageOrder->getBaseDiscountAmount(), $reportedCurrency) * -1,
            'tax' => $convertCurrency->convert($mageOrder->getBaseTaxAmount(), $reportedCurrency),
            'shipping' => $convertCurrency->convert($mageOrder->getBaseShippingAmount(), $reportedCurrency),
            'currency' => is_null($reportedCurrency) ? $mageOrder->getBaseCurrencyCode() : $reportedCurrency,
            'native_total' => $mageOrder->getGrandTotal(),
            'native_subtotal' => $mageOrder->getSubtotal(),
            'native_discount' => $mageOrder->getDiscountAmount() * -1,
            'native_tax' => $mageOrder->getTaxAmount(),
            'native_shipping' => $mageOrder->getShippingAmount(),
            'native_currency' => $mageOrder->getCurrencyCode()
        );
        $store = $mageOrder->getStore();
        $storeId = $store->getStoreId();
        $storeCode = $localesHelper->getStoreCode($storeId);
        $langCode = $localesHelper->getLangCode($storeId);

        if ($store) {
            if ($store->getWebsite()) {
                $order['magento_website'] = $store->getWebsite()->getName();
            }
            if ($store->getGroup()) {
                $order['magento_store'] = $store->getGroup()->getName();
            }
            $order['magento_store_view'] = $store->getName();
            $order['store_view_code'] = $storeCode;
            $order['store_view'] = $langCode;
        }
        $ip = '';
        if ($mageOrder->getXForwardedFor()) {
            $ip = $mageOrder->getXForwardedFor();
        } else if ($mageOrder->getRemoteIp()) {
            $ip = $mageOrder->getRemoteIp();
        }
        $order['ip'] = $ip;
        if ($mageOrder->getBillingAddress() != null) {
            $billAddress = $mageOrder->getBillingAddress()->getData();
            $order['bill_address'] = $this->formatAddress($billAddress);
            $order['email'] = $billAddress['email'];
            $order['phone'] = $billAddress['telephone'];
            $order['first_name'] = $billAddress['firstname'];
            $order['last_name'] = $billAddress['lastname'];
        }
        if ($mageOrder->getShippingAddress() != null) {
            $order['ship_address'] = $this->formatAddress($mageOrder->getShippingAddress()->getData());
        }
        if ($order['email'] == null && $mageOrder->getCustomerEmail() != null) {
            $order['email'] = $mageOrder->getCustomerEmail();
        }
        $order['items'] = array();
        foreach ($mageOrder->getAllVisibleItems() as $mageItem) {
            $order['items'][] = array(
                'product_id' => ($this->isLocalesToggled() === '1' && $mageOrder->getStore()->getCode() !== Mage::app()->getDefaultStoreView()->getCode()) ?
                    $this->getProductID($mageItem->getProductId()) . '$LOCALE$' . $mageOrder->getStore()->getCode() :
                    $this->getProductID($mageItem->getProductId()),
                'subtotal' => $convertCurrency->convert($mageItem->getBaseRowTotal(), $reportedCurrency),
                'sku' => $mageItem->getSku(),
                'quantity' => $mageItem->getQtyOrdered(),
                'price' => $convertCurrency->convert($mageItem->getBasePrice(), $reportedCurrency),
                'discount' => $convertCurrency->convert($mageItem->getBaseDiscountAmount(), $reportedCurrency) * -1,
                'native_subtotal' => $mageItem->getRowTotal(),
                'native_price' => $mageItem->getPrice(),
                'native_discount' => $mageItem->getDiscountAmount() * -1,
            );
        }
        return $order;
    }

    public function buildOrderCancel($mageOrder, $magePayment)
    {
        return $this->buildOrderNegation($mageOrder, $magePayment->getBaseAmountOrdered() * -1, $magePayment->getAmountOrdered() * -1);
    }

    public function buildOrderRefund($mageOrder, $mageCreditmemo)
    {
        return $this->buildOrderNegation($mageOrder, $mageCreditmemo->getBaseGrandTotal() * -1, $mageCreditmemo->getGrandTotal() * -1);
    }

    public function formatAddress($address)
    {
        $street = '';
        if (isset($address['street'])) {
            $street = mb_ereg_replace(PHP_EOL, ", ", $address['street']);
        }
        return "$street, ${address['city']}, ${address['region']}, ${address['postcode']}, ${address['country_id']}";
    }

    public function buildOrderNegation($mageOrder, $refundAmount, $nativeRefundAmount = null)
    {
        $convertCurrency = Mage::getSingleton('directory/currency');
        $localesHelper = Mage::helper('zaius_engage/locales');
        $reportedCurrency = $this->getReportedCurrency();
        $refundAmountStr = sprintf("%0.4f", $convertCurrency->convert($refundAmount, $reportedCurrency));
        $nativeRefundAmountStr = sprintf("%0.4f", $nativeRefundAmount);
        $order = array(
            'order_id' => $this->getOrderID($mageOrder->getIncrementId()),
            'total' => $refundAmountStr,
            'subtotal' => $refundAmountStr,
            'currency' => is_null($reportedCurrency) ? $mageOrder->getBaseCurrencyCode() : $reportedCurrency,
            'native_total' => $nativeRefundAmountStr,
            'native_subtotal' => $nativeRefundAmountStr,
            'native_currency' => $mageOrder->getCurrencyCode()
        );
        $store = $mageOrder->getStore();
        $storeId = $store->getStoreId();
        $storeCode = $localesHelper->getStoreCode($storeId);

        if ($store) {
            if ($store->getWebsite()) {
                $order['magento_website'] = $store->getWebsite()->getName();
            }
            if ($store->getGroup()) {
                $order['magento_store'] = $store->getGroup()->getName();
            }
            $order['magento_store_view'] = $store->getName();
            $order['store_view_code'] = $storeCode;
        }
        if ($mageOrder->getBillingAddress() != null) {
            $billAddress = $mageOrder->getBillingAddress()->getData();
            $order['email'] = $billAddress['email'];
            $order['phone'] = $billAddress['telephone'];
            $order['first_name'] = $billAddress['firstname'];
            $order['last_name'] = $billAddress['lastname'];
        }
        if ($order['email'] == null && $mageOrder->getCustomerEmail() != null) {
            $order['email'] = $mageOrder->getCustomerEmail();
        }
        return $order;
    }

    public function computeQuoteHashV3($quote)
    {
        $secret = trim(Mage::getStoreConfig('zaius_engage/settings/cart_abandon_secret_key'));
        if ($quote == null || $quote->getId() == null || $secret == '' || $quote->getStoreId() == null) {
            return null;
        } else {
            return base64_encode(md5($quote->getId() . $secret . $quote->getStoreId()));
        }
    }

    public function computeQuoteHashV2($quote)
    {
        if ($quote == null || $quote->getId() == null || $quote->getCreatedAt() == null || $quote->getStoreId() == null) {
            return null;
        } else {
            return base64_encode(md5($quote->getId() . $quote->getCreatedAt() . $quote->getStoreId()));
        }
    }

    public function computeQuoteHashV1($quote)
    {
        if ($quote == null || $quote->getId() == null || $quote->getCreatedAt() == null || $quote->getStoreId() == null) {
            return null;
        } else {
            return base64_encode(Mage::helper('core')->encrypt($quote->getId() . $quote->getCreatedAt() . $quote->getStoreId()));
        }
    }

    public function isSubmitNotActiveStatus()
    {
        return Mage::getStoreConfigFlag('zaius_engage/config/submit_status_not_active');
    }
}

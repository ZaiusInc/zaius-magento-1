<?php

class Zaius_Engage_Model_Observer_Product extends Zaius_Engage_Model_Observer
{

    public function entity($observer)
    {
        if ($this->helper->isEnabled()) {
            $convertCurrency = Mage::getSingleton('directory/currency');
            $product = $observer->getProduct();
            $productId = $this->helper->getProductID($product->getId());
            $productStoreId = $product->getStoreId();
            $delimiter = '$LOCALE$';
            $langCode = $this->localesHelper->getLangCode($productStoreId);
            $storeCode = $this->localesHelper->getStoreCode($productStoreId);
            $is_locales_toggled = $this->helper->isLocalesToggled();

            $currencyCode = Mage::app()->getStore($product->getStoreId())->getCurrentCurrencyCode();
            $currencySymb = Mage::app()->getLocale()->currency($currencyCode)->getSymbol();

            $entity = array();
            if ($this->helper->isCollectAllProductAttributes()) {
                $entity = Zaius_Engage_Model_ProductAttribute::getAttributes($product);
            }
            $entity['product_id'] = ($is_locales_toggled) ? $productId . $delimiter . $storeCode : $productId;
            if ($is_locales_toggled) {
                $entity['default_language_product_id'] = $productId;
            }
            $entity['name'] = $product->getName();
            $entity['sku'] = $product->getSku();
            $entity['description'] = $product->getShortDescription();
            $entity['category'] = $this->getDeepestCategoryPath($product);
            $entity['store_view_code'] = $storeCode;
            $entity['store_view'] = $langCode;
            if ($product->getManufacturer()) {
                $entity['brand'] = $product->getAttributeText('manufacturer');
            }
            $reportedCurrency = $this->helper->getReportedCurrency();
            if ($product->getPrice()) {
                $entity['price'] = $convertCurrency->convert($product->getPrice(), $reportedCurrency);
            }
            if ($product->getSpecialPrice()) {
                $specialPrice = $convertCurrency->convert($product->getSpecialPrice(), $reportedCurrency);
                $entity['special_price'] = $specialPrice;
                if ($product->getSpecialFromDate()) {
                    $entity['special_price_from_date'] = strtotime($product->getSpecialFromDate());
                }
                if ($product->getSpecialToDate()) {
                    $entity['special_price_to_date'] = strtotime($product->getSpecialToDate());
                }
            }
            try {
                $entity['image_url'] = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getImage());
            } catch (Exception $e) {
                Mage::log('ZAIUS: Unable to retrieve product image_url - ' . $e->getMessage(),7,$this->logFile,true);
            }
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->loadByProduct($product);
            if ($stockItem && $stockItem->getId() && $stockItem->getManageStock()) {
                $entity['qty'] = $stockItem->getQty();
                $entity['is_in_stock'] = $stockItem->getIsInStock();
            }
            $entity['parent_product_id'] = $product->getParentIds($product);
            $entity['currency_symbol'] = $currencySymb;
            $entity['currency_code'] = $currencyCode;
            $entity['availability_state'] = $product->getIsSalable();
            if ($is_locales_toggled) {
                $localize = array();
                $localize['product_id'] = $productId;
                $localize[$storeCode . '_product_id'] = $entity['product_id'];
                $localize['default_language_product_id'] = $productId;
                $this->postEntity('product', $localize);
                Mage::log(json_encode($localize),7,$this->logFile,true);
            }
            $this->postEntity('product', $entity);
            Mage::log(__METHOD__,7,$this->logFile,true);
            Mage::log(json_encode($entity),7,$this->logFile,true);
        }
    }

    public function addToCart($observer)
    {
        if ($this->helper->isEnabled()) {
            $product = $observer->getProduct();
            $productId = $this->helper->getProductID($product->getId());
            $productStoreId = $product->getStoreId();
            $delimiter = '$LOCALE$';
            $langCode = $this->localesHelper->getLangCode($productStoreId);
            $storeCode = $this->localesHelper->getStoreCode($productStoreId);
            $is_locales_toggled = $this->helper->isLocalesToggled();

            $identifiers = array(
                'vuid' => str_replace('-','',$this->helper->getVUID())
            );

            $data = array(
                'product_id' => ($is_locales_toggled) ? $productId . $delimiter . $langCode : $productId,
                'category'   => $this->getCurrentOrDeepestCategoryPath($product)
            );
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quoteHash = $this->helper->computeQuoteHashV3($quote);
            if ($quoteHash != null) {
                $data['cart_id'] = $quote->getId();
                $data['cart_hash'] = $quoteHash;
            }
            $data['store_view_code'] = $storeCode;
            $data['store_view'] = $langCode;

            //build event
            $eventData = array(
                'type'        => 'product',
                'action'      => 'add_to_cart',
                'identifiers' => $identifiers,
                'data'        => $data
            );
            $this->postEvent('product', $eventData, 'add_to_cart');
        }
        Mage::log(__METHOD__, 7,$this->logFile,true);
        Mage::log(json_encode($eventData),7,$this->logFile,true);
    }

    public function removeFromCart($observer)
    {
        if ($this->helper->isEnabled()) {
            $product = $observer->getQuoteItem()->getProduct();
            $productId = $this->helper->getProductID($product->getId());
            $productStoreId = $product->getStoreId();
            $delimiter = '$LOCALE$';
            $langCode = $this->localesHelper->getLangCode($productStoreId);
            $storeCode = $this->localesHelper->getStoreCode($productStoreId);

            $identifiers = array(
                'vuid' => str_replace('-','',$this->helper->getVUID())
            );

            $data = array(
                'product_id'      => $productId,
                'category'        => $this->getCurrentOrDeepestCategoryPath($product),
                'store_view_code' => $storeCode,
                'store_view'      => $langCode
            );

            //build event
            $eventData = array(
                'type' => 'product',
                'action' => 'remove_from_cart',
                'identifiers' => $identifiers,
                'data' => $data
            );
            $this->postEvent('product', $eventData, 'remove_from_cart');
        }
        Mage::log(__METHOD__, 7,$this->logFile,true);
        Mage::log(json_encode($eventData),7,$this->logFile,true);
    }

    public function wishlist($observer)
    {
        if ($this->helper->isEnabled()) {
            $product = $observer->getProduct();
            $productId = $this->helper->getProductID($product->getId());
            $productStoreId = $product->getStoreId();
            $delimiter = '$LOCALE$';
            $langCode = $this->localesHelper->getLangCode($productStoreId);
            $storeCode = $this->localesHelper->getStoreCode($productStoreId);
            $is_locales_toggled = $this->helper->isLocalesToggled();

            $identifiers = array(
                'vuid' => str_replace('-','',$this->helper->getVUID())
            );

            $data = array(
                'product_id' => $is_locales_toggled ? $productId . $delimiter . $langCode : $productId,
                'category'   => $this->getCurrentOrDeepestCategoryPath($product),
                'store_view_code' => $storeCode,
                'store_view'      => $langCode
            );

            //build event
            $eventData = array(
                'type'        => 'product',
                'action'      => 'add_to_wishlist',
                'identifiers' => $identifiers,
                'data'        => $data
            );
            $this->postEvent('product', $eventData, 'add_to_wishlist');
        }
        Mage::log(__METHOD__, 7,$this->logFile,true);
        Mage::log(json_encode($eventData),7,$this->logFile,true);
    }

    private function getCurrentOrDeepestCategoryPath($product)
    {
        $category = Mage::registry('current_category');
        if (!$category) {
            $category = $this->getDeepestCategory($product);
        }
        if ($category) {
            return Mage::helper('zaius_engage')->buildCategoryPath($category->getId());
        }
        return null;
    }

    private function getDeepestCategory($product)
    {
        $maxDepth = -1;
        $deepestCategory = null;
        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            foreach ($categoryIds as $categoryId) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $depth = count(explode('/', $category->getPath()));
                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                    $deepestCategory = $category;
                }
            }
        }
        return $deepestCategory;
    }

    private function getDeepestCategoryPath($product)
    {
        $category = $this->getDeepestCategory($product);
        if ($category) {
            return Mage::helper('zaius_engage')->buildCategoryPath($category->getId());
        }
        return null;
    }

}

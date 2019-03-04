<?php

class Zaius_Engage_Model_Observer_Stock_Item extends Zaius_Engage_Model_Observer
{

    public function saveAfter($observer)
    {
        if ($this->helper->isEnabled()) {
            $stockItem = $observer->getEvent()->getData('item');
            if ($stockItem->getManageStock() &&
                ($stockItem->getData('qty') != $stockItem->getOrigData('qty') ||
                    $stockItem->getData('is_in_stock') != $stockItem->getOrigData('is_in_stock'))
            ) {


                $productId = $stockItem->getData('product_id');
                $product = Mage::getModel('catalog/product')->load($productId);
                $productStoreId = $product->getStoreId();
                $delimiter = '$LOCALE$';
                $langCode = $this->localesHelper->getLangCode($productStoreId);
                $storeCode = $this->localesHelper->getStoreCode($productStoreId);

                $entity = array();
                $entity['product_id'] = ($this->localesHelper->isStoreCodeDefault($productStoreId)) ? $productId : $productId . $delimiter . $langCode;
                $entit ['qty'] = $stockItem->getQty();
                $entity['is_in_stock'] = $stockItem->getIsInStock();
                $entity['store_view_code'] = $storeCode;
                $entity['store_view'] = $langCode;

                $this->postEntity('product', $entity);
                Mage::log(__METHOD__, 7, $this->logFile, true);
                Mage::log(json_encode($entity), 7, $this->logFile, true);
            }
        }
    }
}

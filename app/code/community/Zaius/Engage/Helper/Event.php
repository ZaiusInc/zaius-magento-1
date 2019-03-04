<?php
class Zaius_Engage_Helper_Event extends Mage_Core_Helper_Abstract {

    // All events that are not unique to the user's session should be generated in here.  
    // We can 'pre-bake' these into the DOM, and serve it up using FPC if the client has this 
    // feature of EE enabled
    // nick@trellis.co
    private $helper = null;
    private $localesHelper = null;

    protected $logFile = 'zaius.log';

    function __construct(){
      $this->helper = Mage::helper('zaius_engage');
      $this->localesHelper = Mage::helper('zaius_engage/locales');
    }

    public function getPrebakedEvents() {

        if (!Mage::helper('zaius_engage')->isEnabled()) {
            return array();
        }
        
        $request = Mage::app()->getRequest();

        $events = array();

        // Handle pageview: 
        array_push($events, array(
            'eventType' => 'pageview', 
            'eventData' => array()
        )); 

        // Handle standard navigation: 
        $events = array_merge($events, $this->handleNavigationEvent($request)); 

        // Handle product views: 
        $events = array_merge($events, $this->handleProductDetailEvent($request));

        return $events;
    }

    protected function handleNavigationEvent($request) {
        $events = array();
        $eventData = array();
        $action = $this->getActionName($request); 
        $params = $request->getParams(); 
        if ($action === 'catalog_category_view') {
          $eventData['action'] = 'browse';
          $eventData['category'] = Mage::helper('zaius_engage')->buildCategoryPath($params['id']);
        } else if ($action === 'catalogsearch_result_index') {
          $eventData['action'] = 'search';
          $eventData['search_term'] = $params['q'];
        }
        if (count($eventData) > 0) {
            array_push($events, array('eventType' => 'navigation', 'eventData' => $eventData)); 
        }
        return $events;
    }

    protected function handleProductDetailEvent($request) {
        $events = array();
        $eventData = array();
        $action = $this->getActionName($request); 
        if ($action === 'catalog_product_view') {

            $product = Mage::registry('current_product');
            $productId               = $this->helper->getProductID($product->getId());
            $productStoreId          = $product->getStoreId();
            $delimiter               = '$LOCALE$';
            $langCode                = $this->localesHelper->getLangCode($productStoreId);
            $storeCode               = $this->localesHelper->getStoreCode($productStoreId);
            $is_locales_toggled     = $this->helper->isLocalesToggled();

            if ($product) {

                $eventData               = array();
                $eventData['action']     = 'detail';
                $eventData['category']   = $this->getCurrentOrDeepestCategoryPath($product);
                $eventData['product_id'] = ($is_locales_toggled) ?  $productId.$delimiter.$langCode : $productId;
                $eventData['store_view_code'] = $storeCode;
                $eventData['store_view']      = $langCode;


                array_push($events, array('eventType' => 'product', 'eventData' => $eventData));
            }
        Mage::log(__METHOD__,7, $this->logFile, true);
        Mage::log($eventData,7, $this->logFile, true);
        }
        return $events;
    }

    private function getCurrentOrDeepestCategoryPath($product) {
        $category = Mage::registry('current_category');
        if (!$category) {
          $category = $this->getDeepestCategory($product);
        }
        if ($category) {
          return Mage::helper('zaius_engage')->buildCategoryPath($category->getId());
        }
        return null;
      }
    
      private function getDeepestCategory($product) {
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
    
    private function getDeepestCategoryPath($product) {
        $category = $this->getDeepestCategory($product);
        if ($category) {
            return Mage::helper('zaius_engage')->buildCategoryPath($category->getId());
        }
        return null;
    }

    protected function getActionName($request) {
        return $request->getRequestedRouteName() . "_" . 
        $request->getRequestedControllerName() . "_" . 
        $request->getRequestedActionName();
    }
}

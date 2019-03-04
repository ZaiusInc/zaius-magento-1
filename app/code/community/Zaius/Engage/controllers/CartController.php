<?php

class Zaius_Engage_CartController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->getResponse()->setRedirect(Mage::getBaseUrl());
        $quoteId = (int)$this->getRequest()->getParam('cart_id');
        $quoteHash = $this->getRequest()->getParam('cart_hash');
        if ($quoteId != null && $quoteHash != null) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);

            if ($quote != null && ($quoteHash === Mage::helper('zaius_engage')->computeQuoteHashV3($quote) ||
                    $quoteHash === Mage::helper('zaius_engage')->computeQuoteHashV2($quote) ||
                    $quoteHash === Mage::helper('zaius_engage')->computeQuoteHashV2($quote))) {
                // retain the utm campaign params
                $url = parse_url(Mage::helper('checkout/cart')->getCartUrl());
                $query = array();
                if (isset($url['query'])) {
                    parse_str($url['query'], $query);
                };
                $query['utm_medium'] = $this->getRequest()->getParam('utm_medium');
                $query['utm_source'] = $this->getRequest()->getParam('utm_source');
                $query['utm_campaign'] = $this->getRequest()->getParam('utm_campaign');
                $query['utm_content'] = $this->getRequest()->getParam('utm_content');
                $query['utm_term'] = $this->getRequest()->getParam('utm_term');

                $checkoutSession = Mage::getSingleton('checkout/session');
                if (!$checkoutSession->getQuote() || $checkoutSession->getQuote()->getId() != $quoteId) {
                    $newQuote = Mage::getModel('checkout/cart')->getQuote();
                    $cart = Mage::getSingleton('checkout/cart');
                    $cart->truncate();
                    $newQuote->merge($quote);
                    $checkoutSession->resetCheckout();
                    Mage::getSingleton('customer/session')->logout()->renewSession();
                    $checkoutSession->setCartWasUpdated(true);
                    $newQuote->collectTotals()->save();
                    $cart->save();
                    $query['SID'] = Mage::getSingleton('core/session')->getEncryptedSessionId();
                }

                $url['query'] = http_build_query($query);
                $this->getResponse()->setRedirect($this->unparse_url($url));
            }
        }
        return;
    }

    function unparse_url($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}

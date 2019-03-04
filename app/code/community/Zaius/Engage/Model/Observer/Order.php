<?php

class Zaius_Engage_Model_Observer_Order extends Zaius_Engage_Model_Observer
{

    public function orderPlaced($observer) {
        if ($this->helper->isEnabled()) {
            if ($this->helper->isTrackOrdersOnFrontend() && !Mage::app()->getStore()->isAdmin()) {
                $mageOrder = $observer->getOrder();
                if ($mageOrder == null) {
                    Mage::log('ZAIUS: Unable to retrieve order information. Please contact your Zaius rep for support.',7, $this->logFile, true);
                } else {
                    $data = $this->helper->buildOrder($mageOrder);
                    $identifiers = array();
                    if ($mageOrder->getCustomerId()) {
                        $identifiers['customer_id'] = $mageOrder->getCustomerId();
                    }
                    if ($data['email']) {
                        $identifiers['email'] = $data['email'];
                    }

                    $event = array(
                        'type'        => 'order',
                        'action'      => 'purchase',
                        'identifiers' => $identifiers,
                        'data'        => array('order' => $data)
                    );
                    $this->postEvent('order', $event);
                    Mage::log(__METHOD__, 7, $this->logFile, true);
                    Mage::log(json_encode($event), 7, $this->logFile, true);
                }
            }
        }
    }

    public function orderSaved($observer) {
        $zaiusEngage = Mage::helper('zaius_engage');
        if ($zaiusEngage->isEnabled() && (!$zaiusEngage->isTrackOrdersOnFrontend() || Mage::app()->getStore()->isAdmin())) {
            $mageOrder = $observer->getOrder();
            if ($mageOrder == null) {
                Mage::log('ZAIUS: Unable to retrieve order information. Please contact your Zaius rep for support.',7, $this->logFile, true);
            } else {
                $goalStates = array($mageOrder::STATE_NEW, $mageOrder::STATE_PROCESSING, $mageOrder::STATE_COMPLETE);
                if (in_array($mageOrder->getState(), $goalStates) && !in_array($mageOrder->getOrigData('state'), $goalStates)) {
                    $this->postBackendOrder($mageOrder, $zaiusEngage->getVUID());
                }
            }
        }
        Mage::log(__METHOD__,7, $this->logFile, true);
    }

    public function cancel($observer) {
        if ($this->helper->isEnabled()) {
            $magePayment = $observer->getPayment();
            $mageOrder = $magePayment->getOrder();
            if ($mageOrder == null) {
                Mage::log('ZAIUS: Unable to retrieve order information. Please contact your Zaius rep for support.',7, $this->logFile, true);
            }
            $data = $this->helper->buildOrderCancel($mageOrder, $magePayment);
            $identifiers = array();
            if ($mageOrder->getCustomerId()) {
                $identifiers['customer_id'] = $mageOrder->getCustomerId();
            }
            if ($data['email']) {
                $identifiers['email'] = $data['email'];
            }
            $event = array(
                'type'        => 'orders',
                'action'      => 'cancel',
                'identifiers' => $identifiers,
                'data'        => array('order' => $data)
            );

            $this->postEvent('order', $event);
        }
        Mage::log(__METHOD__,7, $this->logFile, true);
        Mage::log(json_encode($event),7, $this->logFile, true);
    }

    public function refund($observer) {
        if ($this->helper->isEnabled()) {
            $mageCreditmemo = $observer->getCreditmemo();
            $magePayment    = $observer->getPayment();
            $mageOrder      = $magePayment->getOrder();
            if ($mageOrder == null) {
                Mage::log('ZAIUS: Unable to retrieve order information. Please contact your Zaius rep for support.',7, $this->logFile, true);
            }
            $data = $this->helper->buildOrderRefund($mageOrder, $mageCreditmemo);
            $identifiers = array();
            if ($mageOrder->getCustomerId()) {
                $identifiers['customer_id'] = $mageOrder->getCustomerId();
            }
            if ($data['email']) {
                $identifiers['email'] = $data['email'];
            }
            $event = array(
                'type'        => 'orders',
                'action'      => 'refund',
                'identifiers' => $identifiers,
                'data'        => array('order' => $data)
            );

            $this->postEvent('order', $event);
        }
        Mage::log(__METHOD__,7, $this->logFile, true);
        Mage::log(json_encode($event),7, $this->logFile, true);

    }

    private function postBackendOrder($mageOrder, $vuid = null) {
        $this->helper = Mage::helper('zaius_engage');
        $data = $this->helper->buildOrder($mageOrder);
        $identifiers = array();
        if ($vuid) {
            $identifiers['vuid'] = $vuid;
        }
        if ($mageOrder->getCustomerId()) {
            $identifiers['customer_id'] = $mageOrder->getCustomerId();
        }
        if ($data['email']) {
            $identifiers['email'] = $data['email'];
        }
        $event = array(
            'type'        => 'order',
            'action'      => 'purchase',
            'identifiers' => $identifiers,
            'data'        => array('order' => $data)
        );
        $this->postEvent('order', $event);
        Mage::log(__METHOD__,7, $this->logFile, true);
        Mage::log(json_encode($event),7, $this->logFile, true);
    }
}

<?php

class Zaius_Engage_Model_Session extends Mage_Core_Model_Session_Abstract
{

    public function __construct()
    {
        $this->init('zaius_engage');
    }

    public function clearEvents()
    {
        $this->setEvents(array());
    }

    public function addEvent($eventType, $eventParams = array())
    {
        $events = $this->getEvents();
        if (is_null($events) || !is_array($events)) {
            $events = array();
        }
        $event = new stdClass;
        $event->eventType = $eventType;
        $event->eventParams = $eventParams;
        $events[] = $event;
        $this->setEvents($events);
    }

}

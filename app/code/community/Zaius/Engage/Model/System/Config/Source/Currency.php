<?php

class Zaius_Engage_Model_System_Config_Source_Currency
    extends Mage_Adminhtml_Model_System_Config_Source_Currency
{
    const USE_STORE_OPTION = 0;

    public function toOptionArray($isMultiselect)
    {
        $options = parent::toOptionArray($isMultiselect);
        array_unshift($options, array('value' => self::USE_STORE_OPTION, 'label' => 'Use Store Currency'));
        return $options;
    }
}
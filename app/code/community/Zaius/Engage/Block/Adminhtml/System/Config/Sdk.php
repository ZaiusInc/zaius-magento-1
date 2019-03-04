<?php

class Zaius_Engage_Block_Adminhtml_System_Config_Sdk extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $helperSdk = Mage::helper('zaius_engage/sdk');
        $field = $element->getData('name');

        if (strpos($field,'composer') !== false) {
            $field = 'composer';
            if ($helperSdk->isComposerInstalled() !== false) {
                return '<strong>' . ucfirst($field) . ' Detected</strong>';
            }
        }

        if (strpos($field,'sdk') !== false) {
            $field = 'sdk';
            if ($helperSdk->isSdkInstalled() !== false) {
                return '<strong>' . strtoupper($field) . ' Detected</strong>';
            }
        }

        return '<strong>' . (($field === 'sdk') ? strtoupper($field) : ucfirst($field)) . ' Not Detected</strong>';
    }
}

<?php
class Zaius_Engage_Block_Adminhtml_System_Config_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('zaius_engage/system/config/button.phtml');
  }

  protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
  {
    return $this->_toHtml();
  }
  public function getAjaxCheckUrl()
  {
    return Mage::helper('adminhtml')->getUrl('adminhtml/schema/sync');
  }

  public function getButtonHtml()
  {
    $button = $this->getLayout()->createBlock('adminhtml/widget_button')
      ->setData(array(
        'id'        => 'zaius_engage_cron_button',
        'label'     => $this->helper('adminhtml')->__('Update Schema'),
        'onclick'   => 'javascript:check(); return false;'
      ));

    return $button->toHtml();
  }
}

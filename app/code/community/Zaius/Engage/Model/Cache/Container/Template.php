<?php 
if (class_exists('Enterprise_PageCache_Model_Container_Abstract')) 
{
    class Zaius_Engage_Model_Cache_Container_Template extends Enterprise_PageCache_Model_Container_Abstract
    {
        protected function _getCacheId()
        {
            return $this->_placeholder->getAttribute('cache_id');
        }

        public function applyWithoutApp(&$content)
        {
            return false;
        }

        protected function _renderBlock()
        {
            $block = $this->_getPlaceHolderBlock();
            $block->setNameInLayout($this->_placeholder->getAttribute('name'));
            return $block->toHtml();
        }
    }
}

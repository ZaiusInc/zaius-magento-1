<?php

class Zaius_Engage_Adminhtml_SchemaController extends Mage_Adminhtml_Controller_Action
{
    private $tables = ['catalog_product_entity_media_gallery','salesrule_coupon','newsletter_subscriber'];

    public function syncAction()
    {
		$helper = Mage::helper('zaius_engage');

        $syncAction = $helper->getUpdateTime();
        $helper->setFlagValue('zaiusEngageCron', $syncAction);
		
        $tablesGoodToGo = $this->addZaiusColumns();

        Mage::log("following tables are good to go ".implode(",",$tablesGoodToGo) ,7);

        //fake some time and send tablelist back to frontend
        sleep(3);
        $result = implode(",",$tablesGoodToGo);
        Mage::app()->getResponse()->setBody($result);
    }

    private function addZaiusColumns()
    {
        $installer = new Mage_Core_Model_Resource_Setup('zaius_engage_setup');
        $createdColumnName = 'zaius_created_at';
        $updatedColumnName = 'zaius_updated_at';
        $connection = $installer->getConnection();
        $goodToGo = [];

        Mage::log("checking tables",7);
        foreach ($this->tables as $table) {
            Mage::log("checking ".$table. " for zaius columns.",7);

            if ($connection->tableColumnExists($table, $createdColumnName) === false
                && $connection->tableColumnExists($table, $updatedColumnName) === false) {
                Mage::log("Columns do not existing",7);

                // TODO: circle back to using ORM friendly query instead of sql run. See witharray() in
                // 8f521bc1e1221490e01952eb6ae1b78e642aa711 inziaus_cron repo for earlier ORM query that was just not
                // working despite being correct.
                $installer->startSetup();
                $installer->run(<<<sql
alter table `$table`
add `$createdColumnName` timestamp not null default current_timestamp comment 'created at',
add `$updatedColumnName` timestamp not null default current_timestamp on update current_timestamp comment 'updated at';
sql
                );
                $installer->endSetup();

                Mage::log("Columns should now existing in ".$table,7);

            } else {
                Mage::log("Columns exist move on to next table check",7);
            }
            array_push($goodToGo, $table);
        }
        return $goodToGo;
    }
}


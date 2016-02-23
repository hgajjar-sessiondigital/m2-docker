<?php
namespace Session\DeliveryDate\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Db\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class InstallSchema
 * @package Session\Vendor\Setup
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * Install database table
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $installer->getConnection()
            ->addColumn(
                $installer->getTable('quote'),
                'delivery_date',
                [
                    'type' => Table::TYPE_DATETIME,
                    'nullable' => false,
                    'comment' => 'Delivery Date'
                ]
            );
        $installer->getConnection()
            ->addColumn(
                $installer->getTable('sales_order'),
                'delivery_date',
                [
                    'type' => Table::TYPE_DATETIME,
                    'nullable' => false,
                    'comment' => 'Delivery Date'
                ]
            );
        $installer->getConnection()
            ->addColumn(
                $installer->getTable('sales_order_grid'),
                'delivery_date',
                [
                    'type' => Table::TYPE_DATETIME,
                    'nullable' => false,
                    'comment' => 'Delivery Date'
                ]
            );

        $installer->endSetup();
    }
}
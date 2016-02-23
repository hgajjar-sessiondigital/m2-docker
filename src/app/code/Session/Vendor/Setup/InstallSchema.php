<?php
namespace Session\Vendor\Setup;

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

        $table = $installer->getConnection()
            ->newTable($installer->getTable('session_vendor'))
            ->addColumn(
                'vendor_id',
                Table::TYPE_SMALLINT,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Vendor ID'
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                32,
                ['nullable' => true, 'default' => null],
                'Vendor Name'
            )
            ->setComment('Vendors Table');

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
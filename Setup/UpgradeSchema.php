<?php

namespace Bananacode\GreenPay\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class InstallSchema
 * @package Bananacode\Cyberfuel\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $connection->addColumn(
                $setup->getTable('sales_order'),
                'greenpay_response',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'GreenPay WebHook Response'
                ]
            );
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            if (!$setup->tableExists('greenpay_split')) {
                $table = $setup
                    ->getConnection()
                    ->newTable($setup->getTable('greenpay_split'))
                    ->addColumn(
                        'split_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'nullable' => false,
                            'primary' => true,
                            'unsigned' => true,
                        ],
                        'Split ID'
                    )
                    ->addColumn(
                        'name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        ['nullable => false'],
                        'Name'
                    )
                    ->addColumn(
                        'secret',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        ['nullable => false'],
                        'Secret'
                    )
                    ->addColumn(
                        'public_key',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '64k',
                        [],
                        'Public Key'
                    )
                    ->addColumn(
                        'terminal_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        [],
                        'Terminal ID'
                    )
                    ->addColumn(
                        'merchant_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        [],
                        'Merchant ID'
                    )
                    ->addColumn(
                        'type',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        1,
                        [],
                        'Type (Brand - Shipping)'
                    )
                    ->addColumn(
                        'reference',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        255,
                        [],
                        'Partner ID/Code'
                    )
                    ->addColumn(
                        'created_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                        'Created At'
                    )
                    ->addColumn(
                        'updated_at',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                        'Updated At'
                    )
                    ->setComment('GreenPay Split Partners');

                $setup
                    ->getConnection()
                    ->createTable($table);

                $setup->getConnection()->addIndex(
                    $setup->getTable('greenpay_split'),
                    $setup->getIdxName(
                        $setup->getTable('greenpay_split'),
                        ['secret', 'public_key', 'terminal_id', 'merchant_id', 'reference'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                    ),
                    ['secret', 'public_key', 'terminal_id', 'merchant_id', 'reference'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                );
            }
        }

        $setup->endSetup();
    }
}

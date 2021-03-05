<?php
/**
 *    This file is part of Mobile Assistant Connector.
 *
 *   Mobile Assistant Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Mobile Assistant Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Mobile Assistant Connector.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Emagicone\Mobassistantconnector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Emagicone\Mobassistantconnector\Helper;

/**
 * Class InstallSchema
 * @package Emagicone\Mobassistantconnector\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->createTableUsers($installer);
        $this->createTableSessionKeys($installer);
        $this->createTableFailedLogin($installer);
        $this->createTableDevices($installer);
        $this->createTablePushNotifications($installer);
        $this->createTableAccounts($installer);
        $this->insertDefaultData($installer);

        $installer->endSetup();
    }

    private function createTableUsers($installer)
    {
        $table = $installer->getConnection()
            ->newTable($installer->getTable(Helper\Constants::TABLE_USERS))
            ->addColumn(
                'user_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true, 'auto_increment' => true]
            )
            ->addColumn('username', Table::TYPE_TEXT, 100, ['nullable' => false])
            ->addColumn('password', Table::TYPE_TEXT, 35, ['nullable' => false])
            ->addColumn('allowed_actions', Table::TYPE_TEXT, 1000, [])
            ->addColumn('qr_code_hash', Table::TYPE_TEXT, 70, [])
            ->addColumn('status', Table::TYPE_SMALLINT, null, [])
            ->addIndex($installer->getIdxName(Helper\Constants::TABLE_USERS, ['username']), ['username']);

        $installer->getConnection()->createTable($table);
    }

    private function createTableSessionKeys($installer)
    {
        $table = $installer->getConnection()
            ->newTable($installer->getTable(Helper\Constants::TABLE_SESSION_KEYS))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true, 'auto_increment' => true]
            )
            ->addColumn('session_key', Table::TYPE_TEXT, 100, ['nullable' => false])
            ->addColumn('user_id', Table::TYPE_INTEGER, null, [])
            ->addColumn('date_added', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addIndex($installer->getIdxName(Helper\Constants::TABLE_SESSION_KEYS, ['user_id']), ['user_id']);

        $installer->getConnection()->createTable($table);
    }

    private function createTableFailedLogin($installer)
    {
        $table = $installer->getConnection()
            ->newTable($installer->getTable(Helper\Constants::TABLE_FAILED_LOGIN))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true, 'auto_increment' => true]
            )
            ->addColumn('ip', Table::TYPE_TEXT, 20, ['nullable' => false])
            ->addColumn('date_added', Table::TYPE_DATETIME, null, ['nullable' => false]);

        $installer->getConnection()->createTable($table);
    }

    private function createTableDevices($installer)
    {
        $table = $installer->getConnection()
            ->newTable($installer->getTable(Helper\Constants::TABLE_DEVICES))
            ->addColumn(
                'device_unique_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true, 'auto_increment' => true]
            )
            ->addColumn('device_unique', Table::TYPE_TEXT, 100, [])
            ->addColumn('account_id', Table::TYPE_INTEGER, null, ['nullable' => true])
            ->addColumn('device_name', Table::TYPE_TEXT, 150, [])
            ->addColumn('last_activity', Table::TYPE_DATETIME, null, ['nullable' => false])
            ->addIndex(
                $installer->getIdxName(Helper\Constants::TABLE_DEVICES, ['device_unique', 'account_email']),
                ['device_unique', 'account_id']
            );

        $installer->getConnection()->createTable($table);
    }

    private function createTablePushNotifications($installer)
    {
        $table = $installer->getConnection()
            ->newTable($installer->getTable(Helper\Constants::TABLE_PUSH_NOTIFICATIONS))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true, 'auto_increment' => true]
            )
            ->addColumn('device_unique_id', Table::TYPE_INTEGER, null, [])
            ->addColumn('user_id', Table::TYPE_INTEGER, null, [])
            ->addColumn('device_id', Table::TYPE_TEXT, 200, [])
            ->addColumn('new_order', Table::TYPE_SMALLINT, null, [])
            ->addColumn('new_customer', Table::TYPE_SMALLINT, null, [])
            ->addColumn('order_statuses', Table::TYPE_TEXT, 1000, [])
            ->addColumn('app_connection_id', Table::TYPE_SMALLINT, null, [])
            ->addColumn('store_group_id', Table::TYPE_SMALLINT, null, ['default' => -1])
            ->addColumn('currency_code', Table::TYPE_TEXT, 25, []);

        $installer->getConnection()->createTable($table);
    }

    private function createTableAccounts($installer)
    {
        $table = $installer->getConnection()
            ->newTable($installer->getTable(Helper\Constants::TABLE_ACCOUNTS))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true, 'auto_increment' => true]
            )
            ->addColumn('account_email', Table::TYPE_TEXT, 150, [])
            ->addColumn('status', Table::TYPE_SMALLINT, null, []);

        $installer->getConnection()->createTable($table);
    }

    private function insertDefaultData($installer)
    {
        $installer->getConnection()->insert(
            Helper\Tools::getDbTablePrefix() . Helper\Constants::TABLE_USERS,
            [
                'username'        => '1',
                'password'        => md5('1'),
                'allowed_actions' => implode(';', Helper\UserPermissions::getActionsCodes()),
                'qr_code_hash'    => hash('sha256', time()),
                'status'          => 1
            ]
        );
    }
}

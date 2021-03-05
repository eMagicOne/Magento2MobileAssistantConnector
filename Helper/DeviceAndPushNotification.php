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

namespace Emagicone\Mobassistantconnector\Helper;

/**
 * Class DeviceAndPushNotification
 * @package Emagicone\Mobassistantconnector\Helper
 */
class DeviceAndPushNotification
{
    public static function deletePushSettingByRegAndCon($registration_id, $app_connection_id)
    {
        $result = false;

        $pushes = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\PushNotification')
            ->getCollection()
            ->addFieldToFilter(
                ['device_id', 'app_connection_id'],
                [['eq' => $registration_id], ['eq' => $app_connection_id]]
            );

        foreach ($pushes as $push) {
            try {
                $push->delete();
                $result = true;
            } catch (\Exception $e) {
                $result = false;
                Tools::getLogger()->err("Unable to delete push settings by reg_id and con_id ({$e->getMessage()}).");
            }
        }

        return $result;
    }

    public static function deleteEmptyDevices()
    {
        $result = false;

        $devices = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\Device')->getCollection();
        $devices->getSelect()->joinLeft(
                ['p' => Tools::getDbTablePrefix() . Constants::TABLE_PUSH_NOTIFICATIONS],
                'main_table.`device_unique_id` = p.`device_unique_id`',
                'p.device_unique_id AS dev_id'
            );
        $devices->addFieldToFilter('p.device_unique_id', ['null' => true]);

        foreach ($devices as $device) {
            try {
                $device->delete();
                $result = true;
            } catch (\Exception $e) {
                $result = false;
                Tools::getLogger()->err("Unable to delete device ({$e->getMessage()}).");
            }
        }

        return $result;
    }

    public static function deleteEmptyAccounts()
    {
        $result = false;

        $accounts = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\Account')->getCollection();
        $accounts->getSelect()->joinLeft(
            ['d' => Tools::getDbTablePrefix() . Constants::TABLE_DEVICES],
            'main_table.`id` = d.`account_id`',
            []
        );
        $accounts->addFieldToFilter('d.account_id', ['null' => true]);

        foreach ($accounts as $account) {
            try {
                $account->delete();
                $result = true;
            } catch (\Exception $e) {
                $result = false;
                Tools::getLogger()->err("Unable to delete account ({$e->getMessage()}).");
            }
        }

        return $result;
    }

    public static function addDevice($data)
    {
        $result = false;

        if (!empty($data)) {
            try {
                $device = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\Device')
                    ->loadByDeviceUniqueAndAccountId($data['device_unique'], $data['account_id'])
                    ->setData('device_name', $data['device_name'])
                    ->setData('last_activity', $data['last_activity'])
                    ->save();

                $result = $device->getId();
            } catch (\Exception $e) {
                Tools::getLogger()->err("Unable to insert device ({$e->getMessage()}).");
            }
        }

        return $result;
    }

    public static function addPushNotification($data)
    {
        $result = true;

        try {
            $push = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\PushNotification')
                ->loadByRegistrationIdAppConnectionId($data['device_id'], $data['app_connection_id'])
                ->setData('device_unique_id', $data['device_unique_id'])
                ->setData('user_id', $data['user_id'])
                ->setData('store_group_id', $data['store_group_id'])
                ->setData('currency_code', $data['currency_code']);

            if (isset($data['new_order'])) {
                $push->setData('new_order', $data['new_order']);
            }

            if (isset($data['new_customer'])) {
                $push->setData('new_customer', $data['new_customer']);
            }

            if (isset($data['order_statuses'])) {
                $push->setData('order_statuses', $data['order_statuses']);
            }

            $push->save();
        } catch (\Exception $e) {
            $result = false;
            Tools::getLogger()->err("Unable to add push notification ({$e->getMessage()}).");
        }

        return $result;
    }

    public static function updateOldPushRegId($registration_id_old, $registration_id_new)
    {
        $result = true;

        try {
            $pushes = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\PushNotification')
                ->getCollection()
                ->addFieldToFilter('device_id', ['eq' => $registration_id_old]);

            foreach ($pushes as $push) {
                $push->setData('device_id', $registration_id_new)
                    ->save();
            }
        } catch (\Exception $e) {
            $result = false;
            Tools::getLogger()->err("Unable to update push notification ({$e->getMessage()}).");
        }

        return $result;
    }
}

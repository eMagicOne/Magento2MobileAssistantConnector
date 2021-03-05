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

namespace Emagicone\Mobassistantconnector\Observer;

use Emagicone\Mobassistantconnector\Helper\Tools;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CustomerRegisterSuccess
 * @package Emagicone\Mobassistantconnector\Observer
 */
class CustomerRegisterSuccess extends ObserverCore implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $devices = $this->getActiveDevices();
        $storeUrl = $this->getBaseStoreUrl();

        foreach ($devices as $device) {
            $deviceId = $device->getDeviceId();
            $appConnectionId = (int)$device->getAppConnectionId();
            $storeGroupId = (int)$device->getStoreGroupId();

            if (
                !empty($deviceId) &&
                $appConnectionId > 0 &&
                (int)$device->getNewCustomer() === 1 &&
                ($storeGroupId === -1 || $storeGroupId === (int)$customer->getGroupId())
            ) {
                $fields = array(
                    'push_notif_type' => 'new_customer',
                    'email' => $customer->getEmail(),
                    'customer_name' => "{$customer->getFirstname()} {$customer->getLastname()}",
                    'customer_id' => $customer->getId(),
                    'store_url' => $storeUrl,
                    'group_id' => $storeGroupId,
                    'app_connection_id' => $appConnectionId
                );

                $this->sendPushMessage($fields, $deviceId);
                $this->sendFCM($fields, $deviceId);
            }
        }
    }
}

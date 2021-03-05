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
 * Class SalesOrderSaveAfter
 * @package Emagicone\Mobassistantconnector\Observer
 */
class SalesOrderSaveAfter extends ObserverCore implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getStatus();

        if ($oldStatus && $oldStatus != $newStatus) {
            $type = 'order_changed';
        } elseif (!$oldStatus) {
            $type = 'new_order';
        } else {
            return;
        }

        $orderGroupId = $order->getStore()->getGroupId();
        $statusLabel = $newStatus;
        $devices = $this->getActiveDevices();

        $statuses = Tools::getCollectionOrderStatuses()->getData();
        foreach ($statuses as $st) {
            if ($st['status'] == $newStatus) {
                $statusLabel = $st['label'];
            }
        }

        foreach ($devices as $device) {
            $deviceId = $device->getDeviceId();
            $appConnectionId = (int)$device->getAppConnectionId();
            $storeGroupId = (int)$device->getStoreGroupId();

            $deviceOrderStatuses = $device->getOrderStatuses();
            if (!empty($deviceOrderStatuses)) {
                $deviceOrderStatuses = explode('|', $deviceOrderStatuses);
            }
            if (!is_array($deviceOrderStatuses)) {
                $deviceOrderStatuses = [];
            }

            if (!empty($deviceId)
                && ($storeGroupId === -1 || $storeGroupId === $orderGroupId)
                && $appConnectionId > 0
                && (
                    ($type == 'new_order' &&  (int)$device->getNewOrder() === 1)
                    || ($type == 'order_changed'
                        && (
                            in_array('-1', $deviceOrderStatuses)
                            || in_array($newStatus, $deviceOrderStatuses)
                        )
                    )
                )
            ) {
                $this->sendRequest($device, $type, $order, $statusLabel);
            }
        }
    }

    private function sendRequest($device, $type, $order, $statusLabel)
    {
        $currencyCode = $order->getGlobalCurrencyCode();

        $deviceCurrencyCode = $device->getCurrencyCode();
        if (empty($deviceCurrencyCode) || (string)$deviceCurrencyCode == 'base_currency') {
            $deviceCurrencyCode = $currencyCode;
        }

        $total = Tools::getConvertedAndFormattedPrice(
            $order->getBaseGrandTotal(),
            $currencyCode,
            $deviceCurrencyCode
        );

        $fields = array(
            'push_notif_type' => $type,
            'email' => $order->getCustomerEmail(),
            'customer_name' => "{$order->getCustomerFirstname()} {$order->getCustomerLastname()}",
            'order_id' => $order->getId(),
            'total' => $total,
            'store_url' => $this->getBaseStoreUrl(),
            'group_id' => $order->getStore()->getGroupId(),
            'app_connection_id' => $device->getAppConnectionId()
        );

        if ($type == 'order_changed') {
            $fields['new_status'] = $statusLabel;
        }

        $this->sendPushMessage($fields, $device->getDeviceId());
        $this->sendFCM($fields, $device->getDeviceId());
    }
}

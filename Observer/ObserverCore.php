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

use Emagicone\Mobassistantconnector\Helper\Constants;
use Emagicone\Mobassistantconnector\Helper\DeviceAndPushNotification;
use Emagicone\Mobassistantconnector\Helper\Tools;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class ObserverCore
 * @package Emagicone\Mobassistantconnector\Observer
 */
class ObserverCore
{
    private $_objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    private function proceedGoogleResponse($response, $deviceRegistrationId)
    {
        $json = array();

        if ($response && strpos($response, '{') === 0) {
            try {
                $json = Tools::jsonDecode($response);
            } catch (\Exception $e) {
                Tools::getLogger()->err('Error on json decoding');
                return;
            }
        }

        if (!$json || !is_array($json) || !isset($json['results'])) {
            return;
        }

        foreach ($json['results'] as $result) {
            if ((isset($result['registration_id'], $json['canonical_ids']) && (int)$json['canonical_ids'] > 0)
                || (isset($result['error'])
                    && ($result['error'] == 'NotRegistered' || $result['error'] == 'InvalidRegistration')
                )
            ) {
                $deviceCollection = $this->_objectManager
                    ->create('Emagicone\Mobassistantconnector\Model\PushNotification')
                    ->getCollection()
                    ->addFieldToFilter('device_id', $deviceRegistrationId);

                if (isset($result['registration_id'], $json['canonical_ids']) && (int)$json['canonical_ids'] > 0) {
                    foreach ($deviceCollection as $device) {
                        $collection = $this->_objectManager
                            ->create('Emagicone\Mobassistantconnector\Model\PushNotification')
                            ->getCollection()
                            ->addFieldToFilter('device_id', $result['registration_id'])
                            ->addFieldToFilter('user_id', $device->getUserId())
                            ->addFieldToFilter('app_connection_id', $device->getAppConnectionId());

                        if ($collection->getSize() > 0) {
                            $device->delete();
                        } else {
                            $device->setDeviceId($result['registration_id']);
                            $device->save();
                        }
                    }
                } else {
                    foreach ($deviceCollection as $device) {
                        $device->delete();
                    }

                    DeviceAndPushNotification::deleteEmptyDevices();
                    DeviceAndPushNotification::deleteEmptyAccounts();
                    Tools::getLogger()->warn("Google error response: {$response}");
                }
            }
        }
    }

    protected function getActiveDevices()
    {
        $pushesCollection = $this->_objectManager->create('Emagicone\Mobassistantconnector\Model\PushNotification')
            ->getCollection();
        $pushesCollection->getSelect()
            ->joinLeft(
                ['d' => Tools::getDbTablePrefix() . Constants::TABLE_DEVICES],
                'main_table.`device_unique_id` = d.`device_unique_id`',
                []
            )
            ->joinLeft(
                ['a' => Tools::getDbTablePrefix() . Constants::TABLE_ACCOUNTS],
                'a.`id` = d.`account_id`',
                []
            )
            ->joinLeft(
                ['u' => Tools::getDbTablePrefix() . Constants::TABLE_USERS],
                'u.`user_id` = main_table.`user_id`',
                []
            )
            ->where('a.`status` = 1 AND u.`status` = 1 OR main_table.`user_id` IS NULL OR d.`account_id` IS NULL');

        return $pushesCollection;
    }

    protected function getBaseStoreUrl()
    {
        $storeUrl = $this->_objectManager->create('Magento\Store\Model\Store')->getBaseUrl();
        $storeUrl = str_replace('http://', '', $storeUrl);
        $storeUrl = str_replace('https://', '', $storeUrl);
        $storeUrl = rtrim($storeUrl, '/');

        return $storeUrl;
    }

    protected function sendPushMessage($message, $deviceRegistrationId)
    {
        $result = false;
        if (is_callable('curl_init')) {
            $apiKey = Tools::getConfigValue(Constants::CONFIG_PATH_API_KEY);
            if (!$apiKey) {
                return;
            }

            $headers = ["Authorization: key=$apiKey", 'Content-Type: application/json'];
            $data = array(
                'registration_ids'  => array($deviceRegistrationId),
                'data'              => array('message' => $message)
            );

            $ch     = curl_init();
            $url    = 'https://android.googleapis.com/gcm/send';
            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL             => $url,
                    CURLOPT_POST            => true,
                    CURLOPT_HTTPHEADER      => $headers,
                    CURLOPT_RETURNTRANSFER  => true,
                    CURLOPT_SSL_VERIFYPEER  => false,
                    CURLOPT_POSTFIELDS      => Tools::jsonEncode($data)
                )
            );

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                Tools::getLogger()->err(
                    "Push message error while sending CURL request: {$result}. Curl error is:" . curl_error($ch)
                );
            }

            curl_close($ch);
        }

        $this->proceedGoogleResponse($result, $deviceRegistrationId);
    }

    protected function sendFCM($message, $deviceRegistrationId)
    {
        $result = false;
        if (is_callable('curl_init')) {
            $apiKey = 'AAAANJlpe88:APA91bH_uxLK69tM41Wl_XSVYmPkJ_s4Wi0KVwg9F13wblEStUc235w2IlhjOHvHRqtyRU2OB4QGt5xZ0rr9' .
                'GNXz327yL9xido2UI-L5wm_64CmfSZHkIfHUu-RK6d1heRC-71H0cWbA';

            $headers = ["Authorization: key=$apiKey", 'Content-Type: application/json'];
            $data = array(
                'to'            => $deviceRegistrationId,
                // If we ever will work with notification title/body.
                // Should be fixed firstly from firebase side - https://github.com/firebase/quickstart-android/issues/4
                // 'notification'  => array(
                //     'body'          => $notificationTitle,
                //     'title'         => $notificationBody,
                //     'icon'          => 'ic_launcher',
                //     'sound'         => 'default',
                //     'badge'         => '1'
                // ),
                'data'          => array('message' => $message),
                'priority'      => 'high'
            );

            $ch     = curl_init();
            $url    = 'https://fcm.googleapis.com/fcm/send';
            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL             => $url,
                    CURLOPT_POST            => true,
                    CURLOPT_HTTPHEADER      => $headers,
                    CURLOPT_RETURNTRANSFER  => true,
                    CURLOPT_SSL_VERIFYHOST  => 0,
                    CURLOPT_SSL_VERIFYPEER  => false,
                    CURLOPT_POSTFIELDS      => Tools::jsonEncode($data)
                )
            );

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                Tools::getLogger()->err(
                    "Push message error while sending CURL request: {$result}. Curl error is:" . curl_error($ch)
                );
            }

            curl_close($ch);
        }

        $this->proceedGoogleResponse($result, $deviceRegistrationId);
    }
}

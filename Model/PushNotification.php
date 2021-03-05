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

namespace Emagicone\Mobassistantconnector\Model;

/**
 * Class PushNotification
 * @package Emagicone\Mobassistantconnector\Model
 */
class PushNotification extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Emagicone\Mobassistantconnector\Model\ResourceModel\PushNotification');
    }

    /**
     * Check if data exist in table and load them or set new data
     * @param $registration_id
     * @param $app_connection_id
     * @return $this
     */
    public function loadByRegistrationIdAppConnectionId($registration_id, $app_connection_id)
    {
        $matches = $this->getResourceCollection()
            ->addFieldToFilter('device_id', $registration_id)
            ->addFieldToFilter('app_connection_id', $app_connection_id);

        foreach ($matches as $match) {
            return $this->load($match->getId());
        }

        return $this->setData(['device_id' => $registration_id, 'app_connection_id' => $app_connection_id]);
    }

    public function deleteByUserId($userId) {
        $matches = $this->getResourceCollection()->addFieldToFilter('user_id', (int)$userId);

        foreach ($matches as $match) {
            $this->load($match->getId())->delete();
        }
    }
}

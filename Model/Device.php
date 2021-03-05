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
 * Class Device
 * @package Emagicone\Mobassistantconnector\Model
 */
class Device extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Emagicone\Mobassistantconnector\Model\ResourceModel\Device');
    }

    /**
     * Check if data exist in table and load them or set new data
     * @param $device_unique
     * @param $account_id
     * @return $this
     */
    public function loadByDeviceUniqueAndAccountId($device_unique, $account_id)
    {
        $matches = $this->getResourceCollection()
            ->addFieldToFilter('device_unique', $device_unique);

        if (!$account_id) {
            $matches->addFieldToFilter('account_id', [['null' => true], ['eq' => '']]);
        } else {
            $matches->addFieldToFilter('account_id', $account_id);
        }

        foreach ($matches as $match) {
            return $this->load($match->getId());
        }

        return $this->setData(['device_unique' => $device_unique, 'account_id' => $account_id]);
    }
}

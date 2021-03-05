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

use Emagicone\Mobassistantconnector\Api\Data\SessionKeyInterface;

/**
 * Class SessionKey
 * @package Emagicone\Mobassistantconnector\Model
 */
class SessionKey extends \Magento\Framework\Model\AbstractModel implements SessionKeyInterface
{
    public function _construct()
    {
        $this->_init('Emagicone\Mobassistantconnector\Model\ResourceModel\SessionKey');
    }

    public function getSessionKey()
    {
        return $this->getData(self::SESSION_KEY);
    }

    public function getUserId()
    {
        return $this->getData(self::USER_ID);
    }

    public function getDateAdded()
    {
        return $this->getData(self::DATE_ADDED);
    }

    public function setSessionKey($session_key)
    {
        return $this->setData(self::SESSION_KEY, $session_key);
    }

    public function setUserId($user_id)
    {
        return $this->setData(self::USER_ID, $user_id);
    }

    public function setDateAdded($date_added)
    {
        return $this->setData(self::DATE_ADDED, $date_added);
    }

    /**
     * Check if data exist in table and load them or set new data
     * @param $user_id
     * @return $this
     */
    public function loadByUserId($userId)
    {
        $matches = $this->getResourceCollection()->addFieldToFilter('user_id', (int)$userId);

        foreach ($matches as $match) {
            return $this->load($match->getId());
        }

        return $this;
    }

    public function deleteByUserId($userId) {
        $matches = $this->getResourceCollection()->addFieldToFilter('user_id', (int)$userId);

        foreach ($matches as $match) {
            $this->load($match->getId())->delete();
        }
    }
}

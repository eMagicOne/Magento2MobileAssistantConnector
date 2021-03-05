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

namespace Emagicone\Mobassistantconnector\Model\ResourceModel\User;

use Emagicone\Mobassistantconnector\Helper\UserPermissions;
use \Emagicone\Mobassistantconnector\Model\ResourceModel\AbstractCollection;

/**
 * Class Collection
 * @package Emagicone\Mobassistantconnector\Model\ResourceModel\User
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'user_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Emagicone\Mobassistantconnector\Model\User',
            'Emagicone\Mobassistantconnector\Model\ResourceModel\User'
        );
    }

    protected function _afterLoadData()
    {
        $count = count($this->_data);

        for ($i = 0; $i < $count; $i++) {
            $this->_data[$i]['allowed_actions_for_view'] = UserPermissions::getUserAllowedActionsAsString(
                explode(';', $this->_data[$i]['allowed_actions'])
            );
        }
    }
}

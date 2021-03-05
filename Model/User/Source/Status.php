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

namespace Emagicone\Mobassistantconnector\Model\User\Source;

/**
 * Class Status
 * @package Emagicone\Mobassistantconnector\Model\User\Source
 */
class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Emagicone\Mobassistantconnector\Model\User
     */
    protected $user;

    /**
     * Constructor
     *
     * @param \Emagicone\Mobassistantconnector\Model\User $user
     */
    public function __construct(\Emagicone\Mobassistantconnector\Model\User $user)
    {
        $this->user = $user;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->user->getAvailableStatuses();

        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $options;
    }
}

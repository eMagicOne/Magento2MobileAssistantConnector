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

namespace Emagicone\Mobassistantconnector\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for user search results.
 * @api
 */
interface UserSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get users list.
     *
     * @return \Emagicone\Mobassistantconnector\Api\Data\UserInterface[]
     */
    public function getItems();

    /**
     * Set users list.
     *
     * @param \Emagicone\Mobassistantconnector\Api\Data\UserInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

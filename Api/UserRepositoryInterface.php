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

namespace Emagicone\Mobassistantconnector\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * User page CRUD interface.
 * @api
 */
interface UserRepositoryInterface
{
    /**
     * Save user.
     *
     * @param \Emagicone\Mobassistantconnector\Api\Data\UserInterface $user
     * @return \Emagicone\Mobassistantconnector\Api\Data\UserInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Emagicone\Mobassistantconnector\Api\Data\UserInterface $user);

    /**
     * Retrieve user.
     *
     * @param int $userId
     * @return \Emagicone\Mobassistantconnector\Api\Data\UserInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($userId);

    /**
     * Retrieve user.
     *
     * @param string $username
     * @return \Emagicone\Mobassistantconnector\Api\Data\UserInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByUsername($username);

    /**
     * Retrieve users matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Emagicone\Mobassistantconnector\Api\Data\UserSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete user.
     *
     * @param \Emagicone\Mobassistantconnector\Api\Data\UserInterface $user
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Emagicone\Mobassistantconnector\Api\Data\UserInterface $user);

    /**
     * Delete user by ID.
     *
     * @param int $userId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($userId);
}

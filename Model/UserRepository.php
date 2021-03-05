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

use Emagicone\Mobassistantconnector\Api\Data;
use Emagicone\Mobassistantconnector\Api\UserRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Emagicone\Mobassistantconnector\Model\ResourceModel\User as ResourceUser;
use Emagicone\Mobassistantconnector\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class UserRepository
 * @package Emagicone\Mobassistantconnector\Model
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * @var ResourceUser
     */
    protected $resource;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var UserCollectionFactory
     */
    protected $userCollectionFactory;

    /**
     * @var Data\UserSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \Emagicone\Mobassistantconnector\Api\Data\UserInterfaceFactory
     */
    protected $dataPageFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceUser $resource
     * @param UserFactory $userFactory
     * @param Data\UserInterfaceFactory $dataUserFactory
     * @param UserCollectionFactory $userCollectionFactory
     * @param Data\UserSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceUser $resource,
        UserFactory $userFactory,
        Data\UserInterfaceFactory $dataUserFactory,
        UserCollectionFactory $userCollectionFactory,
        Data\UserSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->userFactory = $userFactory;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataUserFactory = $dataUserFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * Save Page data
     *
     * @param \Emagicone\Mobassistantconnector\Api\Data\UserInterface $user
     * @return User
     * @throws CouldNotSaveException
     */
    public function save(\Emagicone\Mobassistantconnector\Api\Data\UserInterface $user)
    {
        try {
            $this->resource->save($user);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $user;
    }

    /**
     * Load User data by given User Identity
     *
     * @param string $userId
     * @return User
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($userId)
    {
        $user = $this->userFactory->create();
        $user->load($userId);

        if (!$user->getId()) {
            throw new NoSuchEntityException(__('User with id "%1" does not exist.', $userId));
        }

        return $user;
    }

    /**
     * Retrieve user.
     *
     * @param string $username
     * @return \Emagicone\Mobassistantconnector\Api\Data\UserInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getByUsername($username)
    {
        return $this->userFactory->create()
            ->load($username, 'username');
    }

    /**
     * Load User data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Emagicone\Mobassistantconnector\Model\ResourceModel\User\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->userCollectionFactory->create();

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();

        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }

        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $pages = [];

        /** @var User $userModel */
        foreach ($collection as $userModel) {
            $userData = $this->dataUserFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $userData,
                $userModel->getData(),
                'Emagicone\Mobassistantconnector\Api\Data\UserInterface'
            );
            $users[] = $this->dataObjectProcessor->buildOutputDataArray(
                $userData,
                'Emagicone\Mobassistantconnector\Api\Data\UserInterface'
            );
        }

        $searchResults->setItems($users);

        return $searchResults;
    }

    /**
     * Delete User
     *
     * @param \Emagicone\Mobassistantconnector\Api\Data\UserInterface $user
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(\Emagicone\Mobassistantconnector\Api\Data\UserInterface $user)
    {
        try {
            $this->resource->delete($user);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * Delete User by given User Identity
     *
     * @param string $userId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($userId)
    {
        return $this->delete($this->getById($userId));
    }
}

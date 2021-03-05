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

namespace Emagicone\Mobassistantconnector\Controller\Adminhtml\User;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Emagicone\Mobassistantconnector\Model\ResourceModel\User\CollectionFactory;
use Emagicone\Mobassistantconnector\Api\UserRepositoryInterface;
use Emagicone\Mobassistantconnector\Model\UserFactory;
use Emagicone\Mobassistantconnector\Model\ResourceModel\User\Collection;
use Magento\Framework\Controller\ResultFactory;
use Emagicone\Mobassistantconnector\Controller\Adminhtml\AbstractUserMassAction;

/**
 * Class MassDisable
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml\User
 */
class MassDisable extends AbstractUserMassAction
{
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param UserRepositoryInterface $userRepository
     * @param UserFactory $userFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        UserRepositoryInterface $userRepository,
        UserFactory $userFactory
    ) {
        parent::__construct($context, $filter, $collectionFactory);
        $this->userRepository = $userRepository;
        $this->userFactory = $userFactory;
    }

    /**
     * User mass disable action
     *
     * @param Collection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(Collection $collection)
    {
        $usersUpdated = 0;
        foreach ($collection->getAllIds() as $userId) {
            // Verify customer exists
            $this->userRepository->getById($userId);
            $this->userFactory->create()->load($userId)->setData('status', 0)->save();
            $usersUpdated++;
        }

        if ($usersUpdated) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) were updated.', $usersUpdated));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }
}

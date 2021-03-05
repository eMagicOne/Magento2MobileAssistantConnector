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
use Emagicone\Mobassistantconnector\Api\UserRepositoryInterface as UserRepository;
use Magento\Framework\Controller\Result\JsonFactory;
use Emagicone\Mobassistantconnector\Api\Data\UserInterface;

/**
 * Class InlineEdit
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml\User
 */
class InlineEdit extends \Magento\Backend\App\Action
{
    /** @var UserRepository  */
    protected $userRepository;

    /** @var JsonFactory  */
    protected $jsonFactory;

    /**
     * @param Context $context
     * @param UserRepository $userRepository
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        UserRepository $userRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->userRepository = $userRepository;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        // Validate username
        $usernames = [];
        foreach (array_keys($postItems) as $userId) {
            $user = $this->userRepository->getByUsername($postItems[$userId]['username']);

            if ($user->getId() && $user->getId() != $userId || in_array($postItems[$userId]['username'], $usernames)) {
                return $resultJson->setData([
                    'messages' => [__("Username '{$postItems[$userId]['username']}' should be unique")],
                    'error' => true,
                ]);
            }

            $usernames[] = $postItems[$userId]['username'];
        }

        foreach (array_keys($postItems) as $userId) {
            /** @var \Emagicone\Mobassistantconnector\Model\User $user */
            $user = $this->userRepository->getById($userId);

            try {
                $userData = $postItems[$userId];
                $extendedUserData = $user->getData();
                $this->setMobassistantconnectorUserData($user, $extendedUserData, $userData);
                $this->userRepository->save($user);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $this->getErrorWithUserId($user, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithUserId($user, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithUserId(
                    $user,
                    __('Something went wrong while saving the page.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    /**
     * Add username to error message
     *
     * @param UserInterface $user
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithUserId(UserInterface $user, $errorText)
    {
        return '[User ID: ' . $user->getId() . '] ' . $errorText;
    }

    /**
     * Set user data
     *
     * @param \Emagicone\Mobassistantconnector\Model\User $user
     * @param array $extendedUserData
     * @param array $userData
     * @return $this
     */
    public function setMobassistantconnectorUserData(
        \Emagicone\Mobassistantconnector\Model\User $user,
        array $extendedUserData,
        array $userData
    ) {
        $user->setData(array_merge($user->getData(), $extendedUserData, $userData));

        return $this;
    }
}

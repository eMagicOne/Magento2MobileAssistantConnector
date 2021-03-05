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

use Emagicone\Mobassistantconnector\Helper\DeviceAndPushNotification;

/**
 * Class Delete
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml\User
 */
class Delete extends \Magento\Backend\App\Action
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Emagicone_Mobassistantconnector::user_edit');
    }

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('user_id');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            $title = '';

            try {
                // init model and delete
                $user = $this->_objectManager->create('Emagicone\Mobassistantconnector\Model\User')->load($id);
                $title = $user->getUsername();
                $user->delete();

                $this->_objectManager->create('Emagicone\Mobassistantconnector\Model\SessionKey')->deleteByUserId($id);
                $this->_objectManager->create('Emagicone\Mobassistantconnector\Model\PushNotification')
                    ->deleteByUserId($id);

                DeviceAndPushNotification::deleteEmptyDevices();
                DeviceAndPushNotification::deleteEmptyAccounts();

                // display success message
                $this->messageManager->addSuccess(__("The user '$title' has been deleted"));

                // go to grid
                $this->_eventManager->dispatch(
                    'adminhtml_mobassistantconnectoruser_on_delete',
                    ['title' => $title, 'status' => 'success']
                );

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->_eventManager->dispatch(
                    'adminhtml_mobassistantconnectoruser_on_delete',
                    ['title' => $title, 'status' => 'fail']
                );

                // display error message
                $this->messageManager->addError($e->getMessage());

                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['user_id' => $id]);
            }
        }

        // display error message
        $this->messageManager->addError(__('We can\'t find a user to delete.'));

        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}

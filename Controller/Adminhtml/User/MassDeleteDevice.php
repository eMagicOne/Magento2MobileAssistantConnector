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

use Emagicone\Mobassistantconnector\Controller\Adminhtml\PushNotification as PushNotificationController;
use Emagicone\Mobassistantconnector\Helper\DeviceAndPushNotification;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDeleteDevice
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml\User
 */
class MassDeleteDevice extends PushNotificationController
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $deviceIds = $this->getRequest()->getParam('devices');

        if (!is_array($deviceIds)) {
            $this->messageManager->addError(__('Please select device(s).'));
        } else {
            try {
                foreach ($deviceIds as $deviceId) {
                    $this->pushNotificationFactory->create()->load($deviceId)
                        ->delete();
                }

                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been deleted.', count($deviceIds))
                );

                DeviceAndPushNotification::deleteEmptyDevices();
                DeviceAndPushNotification::deleteEmptyAccounts();
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('Something went wrong while deleting these device(s).')
                );
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath(
            'mobassistantconnector/*/' . $this->getRequest()->getParam('ret', 'index')
            . '/user_id/' . (int)$this->getRequest()->getParam('user_id')
        );

        return $resultRedirect;
    }
}

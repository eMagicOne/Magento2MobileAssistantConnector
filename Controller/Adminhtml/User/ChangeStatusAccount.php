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

use Emagicone\Mobassistantconnector\Controller\Adminhtml\Account as AccountController;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class ChangeStatusAccount
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml\User
 */
class ChangeStatusAccount extends AccountController
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $accountId = (int)$this->getRequest()->getParam('id');
        $accountStatus = (int)$this->getRequest()->getParam('value');

        if ($accountId > 0) {
            try {
                $this->accountFactory->create()->load($accountId)
                    ->setData('status', $accountStatus)
                    ->save();
                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been updated.', 1)
                );
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while updating these records.'));
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath(
            'mobassistantconnector/*/' . $this->getRequest()->getParam('ret', 'index')
            . '/user_id/' . $this->getRequest()->getParam('user_id')
        );

        return $resultRedirect;
    }
}

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
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml\User
 */
class Index extends \Magento\Backend\App\Action
{
    protected $_objectManager;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_objectManager = $context->getObjectManager();
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Emagicone_Mobassistantconnector::user_view');
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $collection = $this->_objectManager->create('Emagicone\Mobassistantconnector\Model\User')->getCollection()
            ->addFieldToFilter('username', '1')
            ->addFieldToFilter('password', md5('1'));

        if ($collection->getSize() > 0) {
            $this->messageManager->addWarning(
                'Some user has default login and password are "1". Change them because of security reasons, please!'
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Emagicone_Mobassistantconnector::mobassistantconnector_user');
        $resultPage->addBreadcrumb(__('MOBASSISTANTCONNECTOR'), __('MOBASSISTANTCONNECTOR'));
        $resultPage->addBreadcrumb(__('Manage Users'), __('Manage Users'));
        $resultPage->getConfig()->getTitle()->prepend(__('Users'));

        return $resultPage;
    }
}

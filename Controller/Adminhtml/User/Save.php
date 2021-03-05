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

use Magento\Backend\App\Action;

/**
 * Class Save
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml\User
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @param Action\Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(Action\Context $context, PostDataProcessor $dataProcessor)
    {
        $this->dataProcessor = $dataProcessor;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Emagicone_Mobassistantconnector::user_view');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $data = $this->dataProcessor->filter($data);
            $model = $this->_objectManager->create('Emagicone\Mobassistantconnector\Model\User');
            $model->load($data['username'], 'username');

            if (
                $model->getUsername() == $data['username'] &&
                (!isset($data['user_id']) || $data['user_id'] != $model->getId())
            ) {
                $this->messageManager->addError("Another user exists with the same username '{$data['username']}'");

                if (!isset($data['user_id'])) {
                    return $resultRedirect->setPath('*/*/new', ['_current' => true]);
                }

                return $resultRedirect->setPath('*/*/edit', ['user_id' => $data['user_id'], '_current' => true]);
            }

            $id = $this->getRequest()->getParam('user_id');
            if ($id) {
                $model->load($id);
            } else {
                $data['qr_code_hash'] = hash('sha256', time());
            }

            if ($data['password'] != $model->getPassword()) {
                $data['password'] = md5($data['password']);
            }

            $model->setData($data);

            /*$this->_eventManager->dispatch(
                'mobassistantconnector_user_prepare_save',
                ['user' => $model, 'request' => $this->getRequest()]
            );*/

            try {
                $model->save();
                $this->messageManager->addSuccess(__("You saved the user '{$data['username']}'"));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['user_id' => $model->getId(), '_current' => true]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __("Something went wrong while saving the user '{$data['username']}'")
                );
            }

            $this->_getSession()->setFormData($data);

            return $resultRedirect->setPath('*/*/edit', ['user_id' => $this->getRequest()->getParam('user_id')]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}

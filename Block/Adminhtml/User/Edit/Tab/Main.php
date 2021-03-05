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

namespace Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\Tab;

use Emagicone\Mobassistantconnector\Helper\Tools;

/**
 * User edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /* @var $model \Emagicone\Mobassistantconnector\Model\User */
        $model = $this->_coreRegistry->registry('mobassistantconnector_user');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Emagicone_Mobassistantconnector::user_view')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('user_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('User Information')]);
        $hash = '';

        if ($model->getId()) {
            $hash = $model->getQrCodeHash();
            $fieldset->addField('user_id', 'hidden', ['name' => 'user_id']);
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'title' => __('User Status'),
                'name' => 'user_status',
                'options' => $model->getAvailableStatuses(),
                'disabled' => $isElementDisabled
            ]
        );
        if (!$model->getId()) {
            $model->setData('status', $isElementDisabled ? '0' : '1');
        }

        $fieldset->addField(
            'username',
            'text',
            [
                'name' => 'username',
                'label' => __('Username'),
                'title' => __('Username'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'password',
            'password',
            [
                'name' => 'password',
                'label' => __('Password'),
                'title' => __('Password'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        if ($model->getId()) {
            $baseUrl = $this->getBaseUrl();
            $url = $baseUrl . "mobassistantconnector/index?call_function=get_qr_code&hash=$hash";
            $fieldset->addField(
                'qr_code_data',
                'hidden',
                [
                    'name'  => 'qr_code_data',
                    'value' => Tools::getDataToQrCode($baseUrl, $model->getUsername(), $model->getPassword())
                ]
            );

            $fieldset->addField(
                'qrcode_image',
                'label',
                [
                    'label' => __('QR-code'),
                    'value' => '',
                    'note' => 'Store URL and access details (login and password) for Mobile Assistant Connector are encoded
                        in this QR code. Scan it with special option available on connection settings page of Magento
                        Mobile Assistant application to autofill access settings and connect to your Magento store.',
                    'disabled' => $isElementDisabled
                ]
            );

            $fieldset->addField(
                'qrcode_link',
                'link',
                [
                    'label' => __('QR-code link'),
                    'href' => $url,
                    'value' => $url,
                    'target' => '_blank',
                    'note' => 'QR-code can be got by this link only if status of user is active',
                    'disabled' => $isElementDisabled
                ]
            );

            $fieldset->addField('qrcode_hash', 'hidden', ['name' => 'qrcode_hash', 'value' => $hash]);
        }

        $this->_eventManager->dispatch(
            'adminhtml_mobassistantconnector_user_edit_tab_main_prepare_form', ['form' => $form]
        );

        $form->addValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('User Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('User Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    public function isAjaxLoaded() {
        return false;
    }
}

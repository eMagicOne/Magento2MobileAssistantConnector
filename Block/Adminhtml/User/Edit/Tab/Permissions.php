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

use Emagicone\Mobassistantconnector\Helper\UserPermissions;

/**
 * User edit form permissions tab
 */
class Permissions extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Permissions')]);
        $userPermissionCodes = [];

        if ($model->getId()) {
            $fieldset->addField('user_id', 'hidden', ['name' => 'user_id']);
            $userPermissionCodes = explode(';', $model->getAllowedActions());
        }

        $contentField = $fieldset->addField(
            'mass_checked',
            'hidden',
            [
                'name'     => 'mass_checked',
                'disabled' => $isElementDisabled,
            ]
        );
        $renderer = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element')
            ->setTemplate('Emagicone_Mobassistantconnector::user/edit/form/renderer/permissions.phtml');
        $contentField->setRenderer($renderer);

        $permissions = UserPermissions::getRestrictedActions();
        $count = count($permissions);

        for ($i = 0; $i < $count; $i++) {
            $countChild = count($permissions[$i]['child']);
            $values = [];
            $checked = [];

            for ($j = 0; $j < $countChild; $j++) {
                if (in_array($permissions[$i]['child'][$j]['code'], $userPermissionCodes)) {
                    $checked[] = $permissions[$i]['child'][$j]['code'];
                }

                $values[] = [
                    'label' => $permissions[$i]['child'][$j]['name'],
                    'value' => $permissions[$i]['child'][$j]['code'],
                ];
            }

            $fieldset->addField(
                "permissions_$i",
                'checkboxes',
                [
                    'name'     => 'allowed_actions[]',
                    'label'    => $permissions[$i]['group_name'],
                    'values'   => $values,
                    'disabled' => $isElementDisabled,
                    'checked'  => $checked
                ]
            );
        }

        $this->_eventManager->dispatch(
            'adminhtml_mobassistantconnector_user_edit_tab_permissions_prepare_form', ['form' => $form]
        );

        $form->setValues($model->getData());
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
        return __('Permissions');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Permissions');
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
}

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

namespace Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification;

use Emagicone\Mobassistantconnector\Helper\Constants;
use Emagicone\Mobassistantconnector\Helper\Tools;
use Emagicone\Mobassistantconnector\Model\PushNotificationFactory;
use Emagicone\Mobassistantconnector\Model\ResourceModel\PushNotification\CollectionFactory
    as PushNotificationCollectionFactory;


/**
 * Class Devices
 * @package Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification
 */
class Devices extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var string
     */
    protected $_defaultDir = 'asc';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Device collection model factory
     *
     * @var PushNotificationCollectionFactory
     */
    protected $_devicesFactory;

    /**
     * Device model factory
     *
     * @var PushNotificationFactory
     */
    protected $_deviceFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param PushNotificationFactory $deviceFactory
     * @param PushNotificationCollectionFactory $devicesFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        PushNotificationFactory $deviceFactory,
        PushNotificationCollectionFactory $devicesFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_devicesFactory = $devicesFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_deviceFactory = $deviceFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize grid
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('deviceGrid');
        $this->setDefaultSort('device_name');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Review\Block\Adminhtml\Grid
     */
    protected function _prepareCollection()
    {
        /** @var $collection PushNotificationCollectionFactory */
        $collection = $this->_devicesFactory->create();
        $userId = (int)$this->getRequest()->getParam('user_id');

        $collection->getSelect()
            ->joinLeft(
                ['d' => Tools::getDbTablePrefix() . Constants::TABLE_DEVICES],
                'd.device_unique_id = main_table.device_unique_id',
                ['device_name', 'last_activity', 'account_id']
            )
            ->joinLeft(
                ['a' => Tools::getDbTablePrefix() . Constants::TABLE_ACCOUNTS],
                'a.id = d.account_id',
                ['account_email', 'status']
            )
            ->where('main_table.user_id = ' . $userId);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return \Magento\Backend\Block\Widget\Grid
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'device_name',
            [
                'header' => __('Device Name'),
                'type' => 'text',
                'filter_index' => 'device_name',
                'index' => 'device_name',
            ]
        );

        $this->addColumn(
            'account_email',
            [
                'header' => __('Account Email'),
                'type' => 'text',
                'filter_index' => 'account_email',
                'index' => 'account_email',
            ]
        );

        $this->addColumn(
            'last_activity',
            [
                'header' => __('Last Activity'),
                'type' => 'date',
                'filter_index' => 'last_activity',
                'index' => 'last_activity',
                'renderer' => 'Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Renderer\Date'
            ]
        );

        $this->addColumn(
            'app_connection_id',
            [
                'header' => __('App Connection ID'),
                'type' => 'text',
                'filter_index' => 'app_connection_id',
                'index' => 'app_connection_id',
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'store_id',
                [
                    'header' => __('Store'),
                    'index' => 'store_group_id',
                    'filter' => false,
                    'type' => 'group',
                    'renderer' => 'Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Renderer\Store'
                ]
            );
        }

        $this->addColumn(
            'currency_code',
            [
                'header' => __('Currency Code'),
                'type' => 'text',
                'filter' => false,
                'index' => 'currency_code',
                'renderer' => 'Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Renderer\Currency'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => [1 => __('Enabled'), 0 => __('Disabled')],
                'filter' => 'Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Filter\Status',
            ]
        );

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'renderer' => 'Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Renderer\Action',
                'filter' => false,
                'sortable' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Prepare grid mass actions
     *
     * @return void
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFilter('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        $this->getMassactionBlock()->setFormFieldName('devices');

        $this->getMassactionBlock()->addItem(
            'enable',
            [
                'label' => __('Enable Accounts'),
                'url' => $this->getUrl(
                    '*/*/massChangeStatusAccount',
                    ['value' => 1, 'ret' => 'edit', 'user_id' => $this->getRequest()->getParam('user_id')]
                )
            ]
        );

        $this->getMassactionBlock()->addItem(
            'disable',
            [
                'label' => __('Disable Accounts'),
                'url' => $this->getUrl(
                    '*/*/massChangeStatusAccount',
                    ['value' => 0, 'ret' => 'edit', 'user_id' => $this->getRequest()->getParam('user_id')]
                )
            ]
        );

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete Rows'),
                'url' => $this->getUrl(
                    '*/*/massDeleteDevice',
                    ['ret' => 'edit', 'user_id' => $this->getRequest()->getParam('user_id')]
                ),
                'confirm' => __('Are you sure you want to delete selected records?')
            ]
        );
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('mobassistantconnector/*/devices', ['_current' => true]);
    }
}

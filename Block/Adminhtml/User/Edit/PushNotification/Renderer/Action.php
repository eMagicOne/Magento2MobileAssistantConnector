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

namespace Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Renderer;

/**
 * Class Action
 * @package Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Renderer
 */
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Array to store all options data
     *
     * @var array
     */
    protected $_actions = [];

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $this->_actions = [];
        $status = (int)$row->getDataByKey('status');

        $deleteAction = [
            '@' => [
                'href' => $this->getUrl(
                    'mobassistantconnector/user/deleteDevice',
                    ['id' => $row->getId(), 'ret' => 'edit', 'user_id' => $this->getRequest()->getParam('user_id')]
                ),
            ],
            '#' => __('Delete&nbsp;Row'),
        ];

        if ($row->getAccountEmail()) {
            $changeStatusAction = [
                '@' => [
                    'href' => $this->getUrl(
                        'mobassistantconnector/user/changeStatusAccount',
                        [
                            'id' => $row->getAccountId(),
                            'value' => ($status == 1 ? 0 : 1),
                            'ret' => 'edit',
                            'user_id' => $this->getRequest()->getParam('user_id')
                        ]
                    ),
                ],
                '#' => ($status == 1 ? __('Disable&nbsp;Account') : __('Enable&nbsp;Account')),
            ];

            $this->addToActions($changeStatusAction);
        }

        $this->addToActions($deleteAction);

        return $this->_actionsToHtml();
    }

    /**
     * Get escaped value
     *
     * @param string $value
     * @return string
     */
    protected function _getEscapedValue($value)
    {
        return addcslashes(htmlspecialchars($value), '\\\'');
    }

    /**
     * Render options array as a HTML string
     *
     * @param array $actions
     * @return string
     */
    protected function _actionsToHtml(array $actions = [])
    {
        $html = [];
        $attributesObject = new \Magento\Framework\DataObject();

        if (empty($actions)) {
            $actions = $this->_actions;
        }

        foreach ($actions as $action) {
            $attributesObject->setData($action['@']);
            $html[] = '<a ' . $attributesObject->serialize() . '>' . $action['#'] . '</a>';
        }

        return implode('', $html);
    }

    /**
     * Add one action array to all options data storage
     *
     * @param array $actionArray
     * @return void
     */
    public function addToActions($actionArray)
    {
        $this->_actions[] = $actionArray;
    }
}

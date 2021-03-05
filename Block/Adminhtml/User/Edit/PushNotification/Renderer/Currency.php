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

use Emagicone\Mobassistantconnector\Helper\Tools;
use \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text;

/**
 * Class Currency
 * @package Emagicone\Mobassistantconnector\Block\Adminhtml\User\Edit\PushNotification\Renderer
 */
class Currency extends Text
{
    /**
     * Render row currency
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Framework\Phrase|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($row->getCurrencyCode() == 'base_currency') {
            return Tools::getDefaultCurrency()->getShortName();
        }

        return parent::render($row);
    }
}

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

namespace Emagicone\Mobassistantconnector\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Emagicone\Mobassistantconnector\Model\AccountFactory;

/**
 * Class Account
 * @package Emagicone\Mobassistantconnector\Controller\Adminhtml
 */
abstract class Account extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * Review model factory
     *
     * @var \Emagicone\Mobassistantconnector\Model\AccountFactory
     */
    protected $accountFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Emagicone\Mobassistantconnector\Model\AccountFactory $accountFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        AccountFactory $accountFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->accountFactory = $accountFactory;
        parent::__construct($context);
    }

    /**
     * Check is allowed access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}

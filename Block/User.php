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

namespace Emagicone\Mobassistantconnector\Block;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class Page
 * @package Emagicone\Mobassistantconnector\Block
 */
class Page extends AbstractBlock implements IdentityInterface
{
    /**
     * @var \Emagicone\Mobassistantconnector\Model\User
     */
    protected $_user;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * User factory
     *
     * @var \Emagicone\Mobassistantconnector\Model\UserFactory
     */
    protected $_userFactory;

    /**
     * @var \Magento\Framework\View\Page\Config
     */
    protected $pageConfig;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Emagicone\Mobassistantconnector\Model\User $user
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Emagicone\Mobassistantconnector\Model\UserFactory $userFactory
     * @param \Magento\Framework\View\Page\Config $pageConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Emagicone\Mobassistantconnector\Model\User $user,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Emagicone\Mobassistantconnector\Model\UserFactory $userFactory,
        \Magento\Framework\View\Page\Config $pageConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_user = $user;
        $this->_storeManager = $storeManager;
        $this->_userFactory = $userFactory;
        $this->pageConfig = $pageConfig;
    }

    /**
     * Retrieve User instance
     *
     * @return \Emagicone\Mobassistantconnector\Model\User
     */
    public function getUser()
    {
        if (!$this->hasData('user')) {
            if ($this->getUserId()) {
                /** @var \Emagicone\Mobassistantconnector\Model\User $user */
                $user = $this->_userFactory->create();
            } else {
                $user = $this->_user;
            }

            $this->setData('user', $user);
        }

        return $this->getData('user');
    }

    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $user = $this->getUser();
        $this->_addBreadcrumbs($user);
        $this->pageConfig->addBodyClass('mobassistantconnector-' . $user->getIdentifier());
        return parent::_prepareLayout();
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Emagicone\Mobassistantconnector\Model\User::CACHE_TAG . '_' . $this->getUser()->getId()];
    }
}

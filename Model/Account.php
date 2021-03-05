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

namespace Emagicone\Mobassistantconnector\Model;

/**
 * Class Account
 * @package Emagicone\Mobassistantconnector\Model
 */
class Account extends \Magento\Framework\Model\AbstractModel
{
    function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_init('Emagicone\Mobassistantconnector\Model\ResourceModel\Account');
    }

    /**
     * Saves account if it is missing and return account object
     *
     * @param $accountEmail
     * @return $this
     */
    public function saveAndGetAccountByEmail($accountEmail)
    {
        $matches = $this->getResourceCollection()
            ->addFieldToFilter('account_email', $accountEmail);

        foreach ($matches as $match) {
            return $this->load($match->getId());
        }

        return $this->setData(['account_email' => $accountEmail, 'status' => 1])->save();
    }
}

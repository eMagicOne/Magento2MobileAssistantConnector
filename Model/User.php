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

use Emagicone\Mobassistantconnector\Api\Data\UserInterface;
use Magento\Framework\DataObject\IdentityInterface;

/**
 * Class User
 * @package Emagicone\Mobassistantconnector\Model
 */
class User extends \Magento\Framework\Model\AbstractModel implements UserInterface, IdentityInterface
{
    /**
     * No route user id
     */
    const NOROUTE_USER_ID = 'no-route';

    /**#@+
     * Page's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    /**#@-*/

    /**
     * User cache tag
     */
    const CACHE_TAG = 'mobassistantconnector_user';

    /**
     * @var string
     */
    protected $_cacheTag = 'mobassistantconnector_user';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'mobassistantconnector_user';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Emagicone\Mobassistantconnector\Model\ResourceModel\User');
    }

    /**
     * Load object data
     *
     * @param int|null $id
     * @param string $field
     * @return $this
     */
    public function load($id, $field = null)
    {
        if ($id === null) {
            return $this->noRouteUser();
        }

        return parent::load($id, $field);
    }

    /**
     * Load No-Route User
     *
     * @return \Emagicone\Mobassistantconnector\Model\User
     */
    public function noRouteUser()
    {
        return $this->load(self::NOROUTE_USER_ID, $this->getIdFieldName());
    }

    /**
     * Prepare user's statuses.
     * Available event mobassistantconnector_user_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getUserId()
    {
        return parent::getData(self::USER_ID);
    }

    public function getUsername()
    {
        return parent::getData(self::USERNAME);
    }

    public function getPassword()
    {
        return parent::getData(self::PASSWORD);
    }

    public function getAllowedActions()
    {
        return parent::getData(self::ALLOWED_ACTIONS);
    }

    public function getQrCodeHash()
    {
        return parent::getData(self::QR_CODE_HASH);
    }

    public function getStatus()
    {
        return parent::getData(self::STATUS);
    }

    public function setUserId($user_id)
    {
        return $this->setData(self::USER_ID, $user_id);
    }

    public function setUsername($username)
    {
        return $this->setData(self::USERNAME, $username);
    }

    public function setPassword($password)
    {
        return $this->setData(self::PASSWORD, $password);
    }

    public function setAllowedActions($allowed_actions)
    {
        return $this->setData(self::ALLOWED_ACTIONS, $allowed_actions);
    }

    public function setQrCodeHash($qr_code_hash)
    {
        return $this->setData(self::QR_CODE_HASH, $qr_code_hash);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}

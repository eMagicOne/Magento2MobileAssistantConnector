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

namespace Emagicone\Mobassistantconnector\Api\Data;

/**
 * Interface UserInterface
 * @package Emagicone\Mobassistantconnector\Api\Data
 */
interface UserInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const USER_ID         = 'user_id';
    const USERNAME        = 'username';
    const PASSWORD        = 'password';
    const ALLOWED_ACTIONS = 'allowed_actions';
    const QR_CODE_HASH    = 'qr_code_hash';
    const STATUS          = 'status';

    public function getUserId();
    public function getUsername();
    public function getPassword();
    public function getAllowedActions();
    public function getQrCodeHash();
    public function getStatus();

    public function setUserId($user_id);
    public function setUsername($username);
    public function setPassword($password);
    public function setAllowedActions($allowed_actions);
    public function setQrCodeHash($qr_code_hash);
    public function setStatus($status);
}

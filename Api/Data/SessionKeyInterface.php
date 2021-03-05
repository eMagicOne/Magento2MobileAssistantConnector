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
 * Interface SessionKeyInterface
 * @package Emagicone\Mobassistantconnector\Api\Data
 */
interface SessionKeyInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID          = 'id';
    const SESSION_KEY = 'session_key';
    const USER_ID     = 'user_id';
    const DATE_ADDED  = 'date_added';

    public function getId();
    public function getSessionKey();
    public function getUserId();
    public function getDateAdded();

    public function setId($id);
    public function setSessionKey($session_key);
    public function setUserId($user_id);
    public function setDateAdded($date_added);
}

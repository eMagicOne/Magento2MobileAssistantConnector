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

namespace Emagicone\Mobassistantconnector\Helper;

/**
 * Class Constants
 * @package Emagicone\Mobassistantconnector\Helper
 */
class Constants
{
    const HASH_ALGORITHM = 'sha256';
    const MAX_LIFETIME   = 43200; /* 12 hours */

    /* Tables */
    const TABLE_SESSION_KEYS       = 'mobassistantconnector_session_keys';
    const TABLE_FAILED_LOGIN       = 'mobassistantconnector_failed_login';
    const TABLE_USERS              = 'mobassistantconnector_users';
    const TABLE_DEVICES            = 'mobassistantconnector_devices';
    const TABLE_PUSH_NOTIFICATIONS = 'mobassistantconnector_push_notifications';
    const TABLE_ACCOUNTS           = 'mobassistantconnector_accounts';

    /* Pathes in core_config_data */
    const CONFIG_PATH_CLEAR_DATE = 'emagicone/mobassistantconnector/access/cl_date';
    const CONFIG_PATH_API_KEY    = 'emagicone/mobassistantconnector/access/api_key';
}

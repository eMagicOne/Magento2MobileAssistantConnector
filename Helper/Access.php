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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;

/**
 * Class Access
 * @package Emagicone\Mobassistantconnector\Helper
 */
class Access
{
    public static function clearOldData()
    {
        $timestamp = time();
        $date_clear_prev = Tools::getConfigValue(
            Constants::CONFIG_PATH_CLEAR_DATE
        );

        $date = date('Y-m-d H:i:s', ($timestamp - Constants::MAX_LIFETIME));
        if (!$date_clear_prev || ($timestamp - (int)$date_clear_prev) > Constants::MAX_LIFETIME) {
            // Delete old session keys
            $sessions = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\SessionKey')
                ->getCollection()
                ->addFieldToFilter('date_added', ['lt' => $date]);
            foreach ($sessions as $session) {
                $session->delete();
            }

            // Delete old failed logins
            $attempts = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\FailedLogin')
                ->getCollection()
                ->addFieldToFilter('date_added', ['lt' => $date]);
            foreach ($attempts as $attempt) {
                $attempt->delete();
            }

            // Update clearing date in core_config_data table
            Tools::saveConfigValue(
                Constants::CONFIG_PATH_CLEAR_DATE,
                $timestamp,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
    }

    public static function getSessionKey($hash, $user_id = false)
    {
        if (!$user_id) {
            $login_data = self::checkAuth($hash);

            if ($login_data) {
                $user_id = (int)$login_data['user_id'];
            }
        }

        if ($user_id) {
            return self::generateSessionKey($user_id);
        }

        self::addFailedAttempt();

        return false;
    }

    public static function checkSessionKey($key, $user_id = false)
    {
        $timestamp = time();
        $sessions = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\SessionKey')
            ->getCollection()
            ->addFieldToFilter(
                'main_table.date_added',
                ['gt' => date('Y-m-d H:i:s', ($timestamp - Constants::MAX_LIFETIME))]
            )
            ->addFieldToFilter('main_table.session_key', ['eg' => $key])
            ->addFieldToFilter('u.status', ['eg' => 1]);
        $sessions->getSelect()
            ->joinLeft(
                ['u' => Tools::getDbTablePrefix() . Constants::TABLE_USERS],
                'u.user_id = main_table.user_id',
                []
            );

        if ($user_id) {
            $sessions->addFieldToFilter('main_table.user_id', ['eg' => (int)$user_id]);
        }

        if ($sessions->getSize() > 0) {
            return true;
        }

        self::addFailedAttempt();
        return false;
    }

    private static function generateSessionKey($user_id)
    {
        $timestamp = time();
        $sessions = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\SessionKey')
            ->getCollection()
            ->addFieldToFilter('date_added', ['gt' => date('Y-m-d H:i:s', ($timestamp - Constants::MAX_LIFETIME))])
            ->addFieldToFilter('user_id', ['eg' => (int)$user_id]);

        foreach ($sessions as $session) {
            return $session->getSessionKey();
        }

        $encryptor = Tools::getObjectManager()->create('Magento\Framework\Encryption\EncryptorInterface');
        $key = hash(Constants::HASH_ALGORITHM, $encryptor->getHash((string)$timestamp, true));

        Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\SessionKey')
            ->loadByUserId($user_id)
            ->setData(['user_id' => $user_id, 'session_key' => $key, 'date_added' => date('Y-m-d H:i:s', $timestamp)])
            ->save();

        return $key;
    }

    public static function addFailedAttempt()
    {
        $timestamp = time();

        // Add data to database
        Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\FailedLogin')
            ->setData(['ip' => $_SERVER['REMOTE_ADDR'], 'date_added' => $timestamp])
            ->save();

        // Get count of failed attempts for last time and set delay
        $attempts = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\FailedLogin')
            ->getCollection()
            ->addFieldToFilter('date_added', ['gt' => ($timestamp - Constants::MAX_LIFETIME)])
            ->addFieldToFilter('ip', ['eq' => $_SERVER['REMOTE_ADDR']]);
        $count_failed_attempts = $attempts->getSize();
        self::setDelay((int)$count_failed_attempts);
    }

    public static function checkAuth($hash, $is_log = false)
    {
        $users = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\User')->getCollection()
            ->addFieldToFilter('status', ['eq' => 1]);

        foreach ($users as $user) {
            if (hash(Constants::HASH_ALGORITHM, $user->getUsername() . $user->getPassword()) == $hash) {
                return $user->toArray();
            }
        }

        if ($is_log) {
            Tools::getLogger()->warn('Hash accepted is incorrect');
        }

        return false;
    }

    public static function getAllowedActionsBySessionKey($key)
    {
        $result = [];

        $sessionKey = Tools::getObjectManager()
            ->create('Emagicone\Mobassistantconnector\Model\SessionKey')
            ->load($key, 'session_key');

        if (!$sessionKey->getId()) {
            return $result;
        }

        $user = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\User')
            ->load($sessionKey->getUserId());
        $allowedActions = $user->getAllowedActions();

        if (!empty($allowedActions)) {
            $result = explode(';', $allowedActions);
        }

        return $result;
    }

    public static function getAllowedActionsByUserId($user_id)
    {
        $result =[];
        $users = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\User')->getCollection()
            ->addFieldToFilter('user_id', ['eq' => $user_id]);

        foreach ($users as $user) {
            $result = explode(';', $user->getAllowedActions());
            break;
        }

        return $result;
    }

    public static function getUserIdBySessionKey($key)
    {
        $user_id = false;
        $users = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\User')->getCollection();
        $users->getSelect()
            ->joinLeft(
                ['k' => Tools::getDbTablePrefix() . 'mobassistantconnector_session_keys'],
                'k.user_id = main_table.user_id'
            )
            ->where("k.session_key = '$key' AND main_table.`status` = 1");

        foreach ($users as $user) {
            $user_id = $user->getUserId();
            break;
        }

        return $user_id;
    }

    private static function setDelay($count_attempts)
    {
        if ($count_attempts > 3 && $count_attempts <= 10) {
            sleep(1);
        } elseif ($count_attempts <= 20) {
            sleep(5);
        } elseif ($count_attempts <= 50) {
            sleep(10);
        } else {
            sleep(20);
        }
    }
}

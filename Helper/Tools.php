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

use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Tools
 * @package Emagicone\Mobassistantconnector\Helper
 */
class Tools
{
    private static $object_manager;
    private static $logger;
    private static $config;
    private static $json_encoder;
    private static $json_decoder;
    private static $deployment_config;

    public static function getObjectManager()
    {
        if (!self::$object_manager) {
            self::$object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        }

        return self::$object_manager;
    }

    public static function getLogger()
    {
        if (!self::$logger) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/emagicone_mobassistantconnector.log');
            self::$logger = new \Zend\Log\Logger();
            self::$logger->addWriter($writer);
        }

        return self::$logger;
    }

    public static function getConfigValue($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        self::cleanCache('config');

        return self::getObjectManager()->create('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue($path, $scope);
    }

    public static function saveConfigValue($path, $value, $scope)
    {
        if (!self::$config) {
            self::$config = self::getObjectManager()
                ->create('Magento\Framework\App\Config\ConfigResource\ConfigInterface');
        }

        return self::$config->saveConfig($path, $value, $scope, 0);
    }

    public static function jsonEncode($data)
    {
        if (!self::$json_encoder) {
            self::$json_encoder = self::getObjectManager()->create('Magento\Framework\Json\EncoderInterface');
        }

        return self::$json_encoder->encode($data);
    }

    public static function jsonDecode($data)
    {
        if (!self::$json_decoder) {
            self::$json_decoder = self::getObjectManager()->create('Magento\Framework\Json\DecoderInterface');
        }

        return self::$json_decoder->decode($data);
    }

    public static function translate($message_id)
    {
        $message = new \Magento\Framework\Phrase($message_id);
        return $message->__toString();
    }

    public static function getLocaleCurrency()
    {
        return self::getObjectManager()->create('Magento\Framework\Locale\CurrencyInterface');
    }

    public static function getModelCurrency()
    {
        return self::getObjectManager()->create('Magento\Directory\Model\Currency');
    }

    public static function getStoreManager()
    {
        return self::getObjectManager()->create('Magento\Store\Model\StoreManagerInterface');
    }

    public static function getPriceCurrency()
    {
        return self::getObjectManager()->create('Magento\Framework\Pricing\PriceCurrencyInterface');
    }

    public static function getDirectoryHelperData()
    {
        return self::getObjectManager()->create('Magento\Directory\Helper\Data');
    }

    public static function getCollectionOrderStatuses()
    {
        return self::getObjectManager()->create('Magento\Sales\Model\ResourceModel\Status\Collection');
    }

    public static function getDateTime()
    {
        return self::getObjectManager()->create('Magento\Framework\Stdlib\DateTime\DateTime');
    }

    public static function getConvertedAndFormattedPrice(
        $amount,
        $currency_code_from,
        $currency_code_to,
        $include_container = false
    ) {
        $amount = self::getConvertedPrice($amount, $currency_code_from, $currency_code_to);
        return self::getFormattedPrice($amount, $currency_code_to, $include_container);
    }

    public static function getConvertedPrice($amount, $currency_code_from, $currency_code_to)
    {
        return self::getObjectManager()->create('Magento\Directory\Model\Currency')->load($currency_code_from)
            ->convert($amount, $currency_code_to);
    }

    public static function getFormattedPrice(
        $amount,
        $currency_code = null,
        $include_container = false,
        $precision = 2,
        $scope = null
    ) {
        return self::getPriceCurrency()->format($amount, $include_container, $precision, $scope, $currency_code);
    }

    public static function getDbTablePrefix()
    {
        if (!self::$deployment_config) {
            self::$deployment_config = self::getObjectManager()->create('Magento\Framework\App\DeploymentConfig');
        }

        return self::$deployment_config->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);
    }

    public static function cleanCache($typeCode) {
        self::getObjectManager()->create('Magento\Framework\App\Cache\TypeListInterface')
            ->cleanType($typeCode);
    }

    public static function getDataToQrCode($base_url, $username, $password)
    {
        $base_url = str_replace(array('http://', 'https://'), '', $base_url);
        preg_replace('/\/*$/i', '', $base_url);

        $data = [
            'url' => $base_url,
            'login' => $username,
            'password' => $password
        ];

        return base64_encode(self::jsonEncode($data));
    }

    public static function getDefaultCurrency()
    {
        return self::getLocaleCurrency()->getCurrency(self::getLocaleCurrency()->getDefaultCurrency());
    }
}

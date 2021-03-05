<?php
namespace Emagicone\Mobassistantconnector\Controller\Index;

use Emagicone\Mobassistantconnector\Helper\Access;
use Emagicone\Mobassistantconnector\Helper\DeviceAndPushNotification;
use Emagicone\Mobassistantconnector\Helper\Tools;
use Emagicone\Mobassistantconnector\Helper\Constants;
use Emagicone\Mobassistantconnector\Helper\UserPermissions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Action;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Index extends Action
{
    private $call_function;
    private $callback;
    private $hash = false;
    private $def_currency;
    private $currency_code;
    private $device_unique_id;
    private $registration_id;
    private $registration_id_old;
    private $api_key;
    private $app_connection_id;
    private $group_id;
    private $last_order_id;
    private $push_new_order;
    private $push_new_customer;
    private $push_order_statuses;
    private $push_currency_code;
    private $account_email;
    private $device_name;
    private $order_id;
    private $sort_by;
    private $order_by;
    private $action;
    private $tracking_title;
    private $tracking_number;
    private $carrier_code;
    private $is_mail;
    private $customers_from;
    private $customers_to;
    private $products_from;
    private $products_to;
    private $search_val;
    private $cust_with_orders;
    private $page;
    private $show;
    private $user_id;
    private $params;
    private $val;
    private $statuses;
    private $product_id;
    private $search_carts;
    private $carts_from;
    private $carts_to;
    private $show_unregistered_customers;
    private $cart_id;
    private $session_key;
    private $full_image_size = 0;
    private $generate_thumbnails = true;
    private $check_permission;
    private $group_by_product_id;

    const MB_VERSION  = '7';
    const MODULE_NAME = 'Emagicone_Mobassistantconnector';

    public function execute()
    {
        $this->indexAction();
        return $this->_response;
    }

    private function indexAction()
    {
        $def_currency = $this->_get_default_currency();
        $this->def_currency = $def_currency['currency'];

        Access::clearOldData();

        if ($this->getRequest()->has('callback')) {
            $this->callback = $this->getRequest()->get('callback');
        }

        if ($this->getRequest()->has('call_function')) {
            $this->call_function = $this->getRequest()->get('call_function');
        } else {
            $this->run_self_test();
            return;
        }

        if ($this->getRequest()->has('hash_only')) {
            $this->generate_output('You should update Magento Mobile Assistant application.');
            return;
        }

        if ($this->getRequest()->has('hash')) {
            $this->hash = $this->getRequest()->get('hash');
        }

        if ($this->getRequest()->has('key')) {
            $this->session_key = $this->getRequest()->get('key');
        }

        if ($this->getRequest()->has('device_unique_id')) {
            $this->device_unique_id = $this->getRequest()->get('device_unique_id');
        }

        if ($this->getRequest()->has('registration_id')) {
            $this->registration_id = $this->getRequest()->get('registration_id');
        }

        $this->updateDeviceLastActivity();

        if ($this->getRequest()->has('call_function') && $this->getRequest()->get('call_function') == 'get_version') {
            $this->get_version();
            return;
        }

        if ($this->call_function == 'get_qr_code' && $this->hash) {
            $this->getQrCode($this->hash);
            return;
        }

        if ($this->hash) {
            $key = Access::getSessionKey($this->hash);

            if (!$key) {
                Tools::getLogger()->warn('Hash accepted is incorrect');
                $this->generate_output('auth_error');
                return;
            }

            $this->generate_output(['session_key' => $key]);
            return;
        } elseif ($this->session_key || $this->session_key === '') {
            if (!Access::checkSessionKey($this->session_key)) {
                Tools::getLogger()->warn('Key accepted is incorrect');
                $this->generate_output(['bad_session_key' => true]);
                return;
            }
        } else {
            Access::addFailedAttempt();
            Tools::getLogger()->warn('Authorization error');
            $this->generate_output('auth_error');
            return;
        }

        $request_params = $this->getRequest()->getParams();

        $params = $this->validate_types($request_params, [
            'call_function'               => 'STR',
            'show'                        => 'INT',
            'page'                        => 'INT',
            'search_order_id'             => 'STR',
            'orders_from'                 => 'STR',
            'orders_to'                   => 'STR',
            'order_number'                => 'STR',
            'customers_from'              => 'STR',
            'customers_to'                => 'STR',
            'date_from'                   => 'STR',
            'date_to'                     => 'STR',
            'graph_from'                  => 'STR',
            'graph_to'                    => 'STR',
            'stats_from'                  => 'STR',
            'stats_to'                    => 'STR',
            'products_to'                 => 'STR',
            'products_from'               => 'STR',
            'order_id'                    => 'INT',
            'user_id'                     => 'INT',
            'params'                      => 'STR',
            'val'                         => 'STR',
            'search_val'                  => 'STR',
            'statuses'                    => 'STR',
            'sort_by'                     => 'STR',
            'order_by'                    => 'STR',
            'last_order_id'               => 'STR',
            'product_id'                  => 'INT',
            'get_statuses'                => 'INT',
            'cust_with_orders'            => 'INT',
            'data_for_widget'             => 'INT',
            'registration_id'             => 'STR',
            'registration_id_old'         => 'STR',
            'registration_id_new'         => 'STR',
            'api_key'                     => 'STR',
            'tracking_number'             => 'STR',
            'tracking_title'              => 'STR',
            'action'                      => 'STR',
            'carrier_code'                => 'STR',
            'custom_period'               => 'INT',
            'group_id'                    => 'INT',
            'push_new_customer'           => 'INT',
            'push_new_order'              => 'INT',
            'push_currency_code'          => 'STR',
            'app_connection_id'           => 'STR',
            'push_store_group_id'         => 'STR',
            'push_order_statuses'         => 'STR',
            'currency_code'               => 'STR',
            'is_mail'                     => 'INT',
            'carts_from'                  => 'STR',
            'carts_to'                    => 'STR',
            'cart_id'                     => 'STR',
            'search_carts'                => 'STR',
            'param'                       => 'STR',
            'new_value'                   => 'STR',
            'device_unique_id'            => 'STR',
            'device_name'                 => 'STR',
            'account_email'               => 'STR',
            'show_unregistered_customers' => 'INT',
            'key'                         => 'STR',
            'full_image_size'             => 'INT',
            'check_permission'            => 'STR',
            'group_by_product_id'         => 'INT',
        ]);

        foreach ($params as $k => $value) {
            $this->{$k} = $value;
        }

        if (
            empty($this->currency_code)
            || (string)$this->currency_code == 'base_currency'
            || (string)$this->currency_code == 'not_set'
        ) {
            $this->currency_code = $this->def_currency;
        }

        $this->show = (empty($this->show) || $this->show < 1) ? 17 : $this->show;
        $this->page = (empty($this->page) || $this->page < 1) ? 1 : $this->page;

        if ($this->call_function == 'test_config') {
            $result = ['test' => 1];

            if ($this->check_permission) {
                $this->call_function = $this->check_permission;
                $result['permission_granted'] = $this->isActionAllowed() ? '1' : '0';
            }

            $this->generate_output($result);
            return;
        }

        if (!method_exists($this, $this->call_function)) {
            Tools::getLogger()->warn("Unknown method call '$this->call_function'");
            $this->generate_output('old_module');
            return;
        }

        if (!$this->isActionAllowed()) {
            $this->generate_output('action_forbidden');
            return;
        }

        $result = call_user_func([$this, $this->call_function]);

        if ($this->call_function == 'get_order_pdf') {
            return;
        }

        $this->generate_output($result);
    }

    private function validate_types(&$array, $names)
    {
        if (in_array('without_thumbnails', array_keys($array))) {
            $this->generate_thumbnails = false;
        }

        foreach ($names as $name => $type) {
            if (isset($array["$name"])) {
                switch ($type) {
                    case 'INT':
                        $array["$name"] = (int)$array["$name"];
                        break;
                    case 'FLOAT':
                        $array["$name"] = (float)$array["$name"];
                        break;
                    case 'STR':
                        $array["$name"] = str_replace(
                            ["\r", "\n"],
                            ' ',
                            addslashes(htmlspecialchars(trim(urldecode($array["$name"]))))
                        );
                        break;
                    case 'STR_HTML':
                        $array["$name"] = addslashes(trim($array["$name"]));
                        break;
                    default:
                        $array["$name"] = '';
                }
            } else {
                $array["$name"] = '';
            }
        }

        $array_keys = array_keys($array);

        foreach ($array_keys as $key) {
            if (!isset($names[$key])/* && $key != 'call_function' && $key != 'hash'*/) {
                $array[$key] = '';
            }
        }

        return $array;
    }

    private function isActionAllowed() {
        $allowed_functions_always = UserPermissions::getAlwaysAllowedFunctions();

        if (in_array($this->call_function, $allowed_functions_always)) {
            return true;
        }

        $allowed_actions = Access::getAllowedActionsBySessionKey($this->session_key);
        $restricted_actions_to_functions = UserPermissions::getRestrictedActionsToFunctions();
        $is_allowed = false;

        if ($this->call_function == 'set_order_action') {
            if ($this->action == 'cancel' && in_array('order_cancel', $allowed_actions)) {
                $is_allowed = true;
            } elseif ($this->action == 'hold' && in_array('order_hold', $allowed_actions)) {
                $is_allowed = true;
            } elseif ($this->action == 'unhold' && in_array('order_unhold', $allowed_actions)) {
                $is_allowed = true;
            } elseif ($this->action == 'invoice' && in_array('order_invoice', $allowed_actions)) {
                $is_allowed = true;
            } elseif ($this->action == 'ship' && in_array('order_ship', $allowed_actions)) {
                $is_allowed = true;
            } elseif ($this->action == 'del_track' && in_array('order_delete_track_number', $allowed_actions)) {
                $is_allowed = true;
            }
        } else {
            foreach ($restricted_actions_to_functions as $key => $values) {
                if (in_array($this->call_function, $values)) {
                    if (in_array($key, $allowed_actions)) {
                        $is_allowed = true;
                    }

                    break;
                }
            }
        }

        return $is_allowed;
    }

    private function generate_output($data)
    {
        $add_bridge_version = false;

        if (
        in_array(
            $this->call_function,
            ['test_config', 'get_store_title', 'get_store_stats', 'get_data_graphs', 'get_version']
        )
        ) {
            if (is_array($data) && $data != 'auth_error' && $data != 'connection_error' && $data != 'old_bridge') {
                $add_bridge_version = true;
            }
        }

        if (!is_array($data)) {
            $data = [$data];
        } else {
            $data['module_response'] = '1';
        }

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'reset_null']);
        }

        if ($add_bridge_version) {
            $data['module_version'] = self::MB_VERSION;
            $data['cart_version'] = $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface')
                ->getVersion();
        }

        $data = Tools::jsonEncode($data);

        $this->_response->setHeader('Content-Type', 'text/javascript;charset=utf-8');
        $this->_response->setContent($this->callback ? $this->callback . '(' . $data . ');' : $data);
    }

    private function reset_null(&$item)
    {
        if (empty($item) && $item != 0) {
            $item = '';
        }

        $item = trim($item);
    }

    private function _get_default_currency()
    {
        $currency = Tools::getDefaultCurrency();

        return ['currency' => $currency->getShortName(), 'symbol' => $currency->getSymbol()];
    }

    private function run_self_test()
    {
        $module_list = Tools::getObjectManager()->get('Magento\Framework\Module\ModuleListInterface');
        $module = $module_list->getOne(self::MODULE_NAME);

        $html = '<h2>Mobile Assistant Connector v.' . $module['setup_version'] . ' </h2></table><br/><br/>
            <div style="margin-top: 15px; font-size: 13px;">Mobile Assistant Connector by
            <a href="http://emagicone.com" target="_blank" style="color: #15428B">eMagicOne</a></div>';

        $this->_response->setContent($html);
//        die($html);
    }

    private function updateDeviceLastActivity()
    {
        $account_id = null;

        if ($this->account_email) {
            $account = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\Account')
                ->saveAndGetAccountByEmail($this->account_email);
            $account_id = $account->getId();
        }

        if ($this->device_unique_id) {
            Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\Device')
                ->loadByDeviceUniqueAndAccountId($this->device_unique_id, $account_id)
                ->setData('last_activity', date('Y-m-d H:i:s'))
                ->save();
        }
    }

    private function get_version()
    {
        $session_key = '';

        if ($this->hash) {
            $user_data = Access::checkAuth($this->hash, true);

            if ($user_data) {
                if ($this->session_key) {
                    if (Access::checkSessionKey($this->session_key, $user_data['user_id'])) {
                        $session_key = $this->session_key;
                    } else {
                        $session_key = Access::getSessionKey($this->hash, $user_data['user_id']);
                    }
                } else {
                    $session_key = Access::getSessionKey($this->hash, $user_data['user_id']);
                }
            } else {
                $this->generate_output('auth_error');
            }
        } elseif ($this->session_key && Access::checkSessionKey($this->session_key)) {
            $session_key = $this->session_key;
        }

        $this->generate_output(['session_key' => $session_key]);
    }

    /**
     * Delete push config by registration_id and app_connection_id
     */
    private function delete_push_config()
    {
        if ($this->registration_id && $this->app_connection_id) {
            $result = DeviceAndPushNotification::deletePushSettingByRegAndCon(
                $this->registration_id,
                $this->app_connection_id
            );

            if ($result) {
                $result = ['success' => 'true'];
            } else {
                $result = ['error' => 'Could not delete data'];
            }
        } else {
            $result = ['error' => Tools::translate('Missing parameters')];
        }

        DeviceAndPushNotification::deleteEmptyDevices();
        DeviceAndPushNotification::deleteEmptyAccounts();

        return $result;
    }

    private function push_notification_settings()
    {
        if ((int)$this->app_connection_id < 1) {
            return false;
        }

        $result = ['success' => 'true'];
        $account_id = null;
        $device_name = '';
        $date = date('Y-m-d H:i:s');

        if ($this->registration_id && $this->api_key && $this->device_unique_id) {
            // Update old registration id
            if ($this->registration_id_old) {
                $result = DeviceAndPushNotification::updateOldPushRegId(
                    $this->registration_id_old,
                    $this->registration_id
                );
            }

            if ($this->account_email) {
                $account = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\Account')
                    ->saveAndGetAccountByEmail($this->account_email);
                $account_id = $account->getId();
            }

            if ($this->device_name) {
                $device_name = $this->device_name;
            }

            $device = [
                'device_unique' => $this->device_unique_id,
                'device_name'   => $device_name,
                'last_activity' => $date,
                'account_id'    => $account_id,
            ];

            $device_id = (int)DeviceAndPushNotification::addDevice($device);
            $user_id = (int)Access::getUserIdBySessionKey($this->session_key);
            $user_actions = Access::getAllowedActionsByUserId($user_id);

            // Delete empty record
            if ($this->push_new_order == 0 && empty($this->push_order_statuses) && $this->push_new_customer == 0) {
                $result = DeviceAndPushNotification::deletePushSettingByRegAndCon(
                    $this->registration_id,
                    $this->app_connection_id
                );
                DeviceAndPushNotification::deleteEmptyDevices();
                DeviceAndPushNotification::deleteEmptyAccounts();
            } elseif (
                !empty($user_actions)
                && (
                    in_array('push_notification_settings_new_order', $user_actions)
                    || in_array('push_notification_settings_new_customer', $user_actions)
                    || in_array('push_notification_settings_order_statuses', $user_actions)
                )
            ) {
                $push = [
                    'device_unique_id'  => $device_id,
                    'app_connection_id' => (int)$this->app_connection_id,
                    'store_group_id'    => -1,
                    'currency_code'     => 'base_currency',
                    'order_statuses'    => $this->push_order_statuses,
                    'device_id'         => $this->registration_id,
                    'user_id'           => $user_id,
                ];

                if ($this->group_id) {
                    $push['store_group_id'] = $this->group_id;
                }

                if ($this->push_currency_code) {
                    $push['currency_code'] = $this->push_currency_code;
                } elseif ($this->currency_code) {
                    $push['currency_code'] = $this->currency_code;
                }

                if (in_array('push_notification_settings_new_order', $user_actions)) {
                    $push['new_order'] = (int)$this->push_new_order;
                }

                if (in_array('push_notification_settings_new_customer', $user_actions)) {
                    $push['new_customer'] = (int)$this->push_new_customer;
                }

                $result = DeviceAndPushNotification::addPushNotification($push);
            }

            Tools::saveConfigValue(
                Constants::CONFIG_PATH_API_KEY,
                $this->api_key,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );

            if ($result) {
                $result = ['success' => 'true'];
            } else {
                $result = ['error' => Tools::translate('Could not update data')];
            }
        } else {
            $result = ['error' => Tools::translate('Missing parameters')];
        }

        return $result;
    }

    private function get_store_title()
    {
        if ($this->group_id && (int)$this->group_id > 0) {
            try {
                $name = Tools::getStoreManager()->getGroup((int)$this->group_id)->getName();
            } catch (\Exception $e) {
                $name = Tools::getStoreManager()->getGroup()->getDefaultStore()->getName();
            }
        } else {
            $name = Tools::getStoreManager()->getGroup()->getDefaultStore()->getName();
        }

        return ['test' => 1, 'title' => $name];
    }

    private function is_group_exists($group_id)
    {
        $exists   = false;
        $group_id = (int)$group_id;

        if ($group_id > 0) {
            try {
                Tools::getStoreManager()->getGroup($group_id);
                $exists = true;
            } catch (\Exception $e) {

            }
        }

        return $exists;
    }

    private function get_stores()
    {
        $result   = [];
        $websites = Tools::getStoreManager()->getWebsites();

        foreach ($websites as $website) {
            foreach ($website->getGroups() as $group) {
                $result[] = [
                    'group_id'           => $group->getId(),
                    'name'               => $group->getName(),
                    'default_store_id'   => $group->getDefaultStoreId(),
                    'website'            => $website->getName(),
                    'website_id'         => $website->getWebsiteId(),
                    'default_group_id'   => $website->getDefaultGroupId(),
                    'website_is_default' => $website->getIsDefault()
                ];
            }
        }

        return $result;
    }

    private function get_currencies()
    {
        $currencies = [];

        foreach (Tools::getModelCurrency()->getConfigAllowCurrencies() as $currency_code) {
            $currency = Tools::getLocaleCurrency()->getCurrency($currency_code);
            $currencies[] = [
                'code'   => $currency_code,
                'symbol' => $currency->getSymbol(),
                'name'   => $currency->getName(),
            ];
        }

        return $currencies;
    }

    private function get_store_stats()
    {
        $data_graphs         = '';
        $this->last_order_id = (int)$this->last_order_id;
        $is_group_exists     = $this->is_group_exists($this->group_id);

        $offset = $this->_get_timezone_offset();
        $store_stats = [
            'count_orders'    => '0',
            'total_sales'     => '0',
            'count_customers' => '0',
            'count_products'  => '0',
            'last_order_id'   => '0',
            'new_orders'      => '0',
        ];

        $today     = date('Y-m-d');
        $date_from = $date_to = $today;

        if (!empty($this->stats_from)) {
            $date_from = $this->stats_from;
        }

        if (!empty($this->stats_to)) {
            $date_to = $this->stats_to;
        }

        if (isset($this->custom_period) && !empty($this->custom_period)) {
            $custom_period = $this->get_custom_period($this->custom_period);
            $date_from     = $custom_period['start_date'];
            $date_to       = $custom_period['end_date'];
        }

        if (!empty($date_from)) {
            $date_from .= ' 00:00:00';
        }

        if (!empty($date_to)) {
            $date_to .= ' 23:59:59';
        }

        $orders = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->getCollection()
            ->addAttributeToSelect('base_grand_total')
            ->addAttributeToSelect('entity_id');

        if (!empty($this->statuses)) {
            $this->statuses = "'" . str_replace('|', "','", $this->statuses) . "'";
            $orders->getSelect()->where("main_table.status IN ($this->statuses)");
        }

        if ($is_group_exists) {
            $orders->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = main_table.store_id')
                ->where("cs.group_id = $this->group_id");
        }

        if (!empty($date_from)) {
            $orders->getSelect()->where(
                "(CONVERT_TZ(main_table.created_at, '+00:00',  '$offset')) >= '"
                . (date('Y-m-d H:i:s', (strtotime($date_from)))) . "'"
            );
        }

        if (!empty($date_to)) {
            $orders->getSelect()->where(
                "(CONVERT_TZ(main_table.created_at, '+00:00',  '$offset')) <= '"
                . (date('Y-m-d H:i:s', (strtotime($date_to)))) . "'"
            );
        }

        $orders->addExpressionFieldToSelect('sum_base_grand_total', 'SUM(base_grand_total)', 'base_grand_total')
            ->addExpressionFieldToSelect('count_orders', 'COUNT(entity_id)', 'entity_id');

        $first               = $orders->getFirstItem();
        $orders_sum          = $first['sum_base_grand_total'];
        $row['count_orders'] = $first['count_orders'];
        $row['total_sales']  = Tools::getConvertedAndFormattedPrice(
            $orders_sum,
            $this->def_currency,
            $this->currency_code
        );
        $store_stats         = array_merge($store_stats, $row);

        $items = Tools::getObjectManager()->create('Magento\Sales\Model\Order\Item')->getCollection()
            ->addFieldToSelect(['product_id', 'name', 'sku']);

        $items->getSelect()
            ->joinLeft(
                ['order' => Tools::getDbTablePrefix() . 'sales_order'],
                'order.entity_id = main_table.order_id'
            );

        if ($is_group_exists) {
            $items->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = main_table.store_id'
                )
                ->where("cs.group_id = $this->group_id");
        }

        if (!empty($date_from)) {
            $items->getSelect()->where(
                "(CONVERT_TZ(order.created_at, '+00:00', '$offset')) >= '"
                . (date('Y-m-d H:i:s', (strtotime($date_from)))) . "'"
            );
        }

        if (!empty($date_to)) {
            $items->getSelect()->where(
                "(CONVERT_TZ(order.created_at, '+00:00', '$offset')) <= '"
                . (date('Y-m-d H:i:s', (strtotime($date_to)))) . "'"
            );
        }

        if (!empty($this->statuses)) {
            $items->getSelect()->where("order.status IN ($this->statuses)");
        }

        $store_stats['count_products'] = $items->getSize();

        $items->setOrder('item_id', 'DESC');

        if ($this->last_order_id > 0) {
            $orders = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->getCollection()
                ->addAttributeToFilter('entity_id', ['gt' => $this->last_order_id])
                ->setOrder('entity_id', 'DESC');
            $orders->getSelect()->limit(1);

            $last_order = $orders->getFirstItem();
            $last_order['entity_id'] = (int)$last_order['entity_id'];
            $store_stats['last_order_id'] = $this->last_order_id;

            if ($last_order['entity_id'] > $this->last_order_id) {
                $store_stats['last_order_id'] = $last_order['entity_id'];
            }

            $store_stats['new_orders'] = $orders->count();
        }

        $customers = Tools::getObjectManager()->create('Magento\Customer\Model\Customer')->getCollection();

        if (!empty($date_from)) {
            $customers->getSelect()->where(
                "(CONVERT_TZ(created_at, '+00:00', '$offset')) >= '"
                . date('Y-m-d H:i:s', (strtotime($date_from))) . "'"
            );
        }

        if (!empty($date_to)) {
            $customers->getSelect()->where(
                "(CONVERT_TZ(created_at, '+00:00', '$offset')) <= '"
                . date('Y-m-d H:i:s', (strtotime($date_to))) . "'"
            );
        }

        if ($is_group_exists) {
            $customers->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = e.store_id'
                )
                ->where("cs.group_id = $this->group_id");
        }

        $row['count_customers'] = $customers->count();
        $store_stats = array_merge($store_stats, $row);

        if (!isset($this->data_for_widget) || empty($this->data_for_widget) || $this->data_for_widget != 1) {
            $data_graphs = $this->get_data_graphs();
        }

        $order_status_stats = $this->get_status_stats();

        $result = array_merge(
            $store_stats,
            ['data_graphs' => $data_graphs],
            ['order_status_stats' => $order_status_stats]
        );

        return $result;
    }

    private function get_status_stats()
    {
        $offset          = $this->_get_timezone_offset();
        $is_group_exists = $this->is_group_exists($this->group_id);
        $today           = date('Y-m-d');
        $date_from       = $date_to = $today;

        if (!empty($this->stats_from)) {
            $date_from = $this->stats_from;
        }

        if (!empty($this->stats_to)) {
            $date_to = $this->stats_to;
        }

        if (isset($this->custom_period) && !empty($this->custom_period)) {
            $custom_period = $this->get_custom_period($this->custom_period);
            $date_from     = $custom_period['start_date'];
            $date_to       = $custom_period['end_date'];
        }

        if (!empty($date_from)) {
            $date_from .= ' 00:00:00';
        }

        if (!empty($date_to)) {
            $date_to .= ' 23:59:59';
        }

        $sales = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->getCollection()
            ->addFieldToSelect(
                ['state', 'status', 'store_id', 'base_grand_total', 'base_currency_code', 'order_currency_code']
            );

        $sales->clear();

        if ($is_group_exists) {
            $sales->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = main_table.store_id'
                )
                ->where("cs.group_id = $this->group_id");
        }

        $sales->getSelect()
            ->joinLeft(
                ['sos' => Tools::getDbTablePrefix() . 'sales_order_status'],
                'sos.status = main_table.status',
                ['name' => 'sos.label']
            )
            ->columns(['code' => new \Zend_Db_Expr('main_table.status')])
            ->columns(['count' => new \Zend_Db_Expr('COUNT(main_table.entity_id)')])
            ->columns(['total' => new \Zend_Db_Expr('SUM(main_table.base_grand_total)')]);

        if (!empty($date_from)) {
            $sales->getSelect()->where(
                "(CONVERT_TZ(main_table.created_at, '+00:00', '$offset')) >= '"
                . (date('Y-m-d H:i:s', (strtotime($date_from)))) . "'"
            );
        }

        if (!empty($date_to)) {
            $sales->getSelect()->where(
                "(CONVERT_TZ(main_table.created_at, '+00:00', '$offset')) <= '"
                . (date('Y-m-d H:i:s', (strtotime($date_to)))) . "'"
            );
        }

        $sales->getSelect()->group(new \Zend_Db_Expr('main_table.status'))
            ->order(new \Zend_Db_Expr('count DESC'));

        $orders = [];
        foreach ($sales as $sale) {
            $order = $sale->toArray();
            unset($order['entity_id']);
            unset($order['store_id']);
            unset($order['state']);
            unset($order['base_grand_total']);
            unset($order['base_currency_code']);
            unset($order['order_currency_code']);

            $order['total'] = Tools::getConvertedAndFormattedPrice(
                $order['total'],
                $this->def_currency,
                $this->currency_code
            );
            $orders[] = $order;
        }

        return $orders;
    }

    private function get_data_graphs()
    {
        $customers = [];
        $offset = $this->_get_timezone_offset();
        $is_group_exists = $this->is_group_exists($this->group_id);
        $average = [
            'avg_sum_orders' => 0,
            'avg_orders'     => 0,
            'avg_customers'  => 0,
            'avg_cust_order' => '0.00',
            'tot_customers'  => 0,
            'sum_orders'     => 0,
            'tot_orders'     => 0,
        ];

        if (empty($this->graph_from)) {
            $this->graph_from = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 7, date('Y')));
        }

        if (empty($this->graph_to)) {
            if (!empty($this->stats_to)) {
                $this->graph_to = $this->stats_to;
            } else {
                $this->graph_to = date('Y-m-d');
            }
        }

        $start_date = $this->graph_from . ' 00:00:00';
        $end_date   = $this->graph_to . ' 23:59:59';
        $plus_date  = '+1 day';

        if (isset($this->custom_period) && !empty($this->custom_period)) {
            $custom_period = $this->get_custom_period($this->custom_period);

            if ($this->custom_period == 3) {
                $plus_date = '+3 day';
            } elseif ($this->custom_period == 4 || $this->custom_period == 8) {
                $plus_date = '+1 week';
            } elseif ($this->custom_period == 5 || $this->custom_period == 6 || $this->custom_period == 7) {
                $plus_date = '+1 month';
            }

            if ($this->custom_period == 6) {
                $orders = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->getCollection();
                $orders->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['min_date_add' => 'MIN(`created_at`)', 'max_date_add' => 'MAX(`created_at`)']);

                foreach ($orders as $order) {
                    $orders_info = $order->toArray();
                    $start_date  = $orders_info['min_date_add'];
                    $end_date    = $orders_info['max_date_add'];
                }

            } else {
                $start_date = $custom_period['start_date'] . ' 00:00:00';
                $end_date   = $custom_period['end_date'] . ' 23:59:59';
            }
        }

        $start_date = strtotime($start_date);
        $end_date   = strtotime($end_date);
        $date       = $start_date;
        $d          = 0;
        $orders     = [];
        while ($date <= $end_date) {
            $d++;
            $date_str = date('Y-m-d H:i:s', $date);
            $ordersCollection = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->getCollection();
            $ordersCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);

            if ($is_group_exists) {
                $ordersCollection->getSelect()
                    ->joinLeft(
                        ['cs' => Tools::getDbTablePrefix() . 'store'],
                        'cs.store_id = main_table.store_id',
                        []
                    )
                    ->where("cs.group_id = $this->group_id");
            }

            $ordersCollection->getSelect()
                ->columns(
                    [
                        'date_add'   => "(CONVERT_TZ(main_table.created_at, '+00:00', '{$offset}'))",
                        'value'      => 'SUM(main_table.base_grand_total)',
                        'tot_orders' => 'COUNT(main_table.entity_id)'
                    ]
                )
                ->where(
                    "((CONVERT_TZ(main_table.created_at, '+00:00', '{$offset}' )) >= '{$date_str}'
                    AND (CONVERT_TZ(main_table.created_at, '+00:00', '{$offset}')) < '"
                    . date('Y-m-d H:i:s', (strtotime($plus_date, $date))) . "')"
                );

            if (!empty($this->statuses)) {
                $this->statuses = str_replace('|', "','", $this->statuses);
                $ordersCollection->getSelect()->where("main_table.status IN ($this->statuses)");
            }

            $ordersCollection->getSelect()
                ->group('DATE(main_table.created_at)')
                ->order('main_table.created_at');

            $total_order_per_day = 0;
            foreach ($ordersCollection as $order) {
                (float)$total_order_per_day += (float)$order['value'];
                (int)$average['tot_orders'] += (int)$order['tot_orders'];
                (float)$average['sum_orders'] += (float)$order['value'];
            }

            $total_order_per_day = number_format((float)$total_order_per_day, 2, '.', '');
            $orders[] = [
                $date * 1000,
                Tools::getConvertedPrice(
                    $total_order_per_day,
                    $this->def_currency,
                    $this->currency_code
                )
            ];

            $customersCollection = Tools::getObjectManager()->create('Magento\Customer\Model\Customer')->getCollection();
            $customersCollection->addAttributeToSelect('name');

            if ($is_group_exists) {
                $customersCollection->getSelect()
                    ->joinLeft(
                        ['cs' => Tools::getDbTablePrefix() . 'store'],
                        'cs.store_id = e.store_id'
                    )
                    ->where("cs.group_id = $this->group_id");
            }

            $customersCollection->getSelect()
                ->columns(
                    ['date_add' => "CONVERT_TZ((created_at, '+00:00', {$offset}))"]
                )
                ->where(
                    "((CONVERT_TZ(created_at, '+00:00', '{$offset}')) >= '{$date_str}' AND
                    (CONVERT_TZ(created_at, '+00:00', '{$offset}')) < '"
                    . date('Y-m-d H:i:s', (strtotime($plus_date, $date))) . "')"
                );

            $total_customer_per_day = $customersCollection->getSize();
            $average['tot_customers'] += $total_customer_per_day;
            $customers[] = [$date * 1000, $total_customer_per_day];
            $date = strtotime($plus_date, $date);
        }

        if ($d <= 0) {
            $d = 1;
        }

        $average['avg_sum_orders'] = Tools::getConvertedAndFormattedPrice(
            $average['sum_orders'] / $d,
            $this->def_currency,
            $this->currency_code
        );
        $average['avg_orders']     = number_format($average['tot_orders'] / $d, 1, '.', ' ');
        $average['avg_customers']  = number_format($average['tot_customers'] / $d, 1, '.', ' ');

        if ($average['tot_customers'] > 0) {
            $average['avg_cust_order'] = $average['sum_orders'] / $average['tot_customers'];
        }

        $average['avg_cust_order'] = Tools::getConvertedAndFormattedPrice(
            $average['avg_cust_order'],
            $this->def_currency,
            $this->currency_code
        );
        $average['sum_orders']    = Tools::getConvertedAndFormattedPrice(
            $average['sum_orders'],
            $this->def_currency,
            $this->currency_code
        );
        $average['tot_customers'] = number_format($average['tot_customers'], 1, '.', ' ');
        $average['tot_orders']    = number_format($average['tot_orders'], 1, '.', ' ');

        return [
            'orders'        => $orders,
            'customers'     => $customers,
            'currency_sign' => Tools::getObjectManager()->create('Magento\Directory\Model\Currency')
                ->load($this->currency_code)->getCurrencySymbol(),
            'average'       => $average
        ];
    }

    private function get_orders()
    {
        $offset = $this->_get_timezone_offset();
        $orders = [];

        $ordersCollection = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->getCollection();
        $ordersCollection->getSelect()
            ->joinLeft(
                ['sos' => Tools::getDbTablePrefix() . 'sales_order_status'],
                'sos.status = main_table.status',
                []
            )
            ->joinLeft(
                ['soi' => Tools::getDbTablePrefix() . 'sales_order_item'],
                'soi.order_id = main_table.entity_id',
                ['soi.item_id']
            )
            ->columns(['count_items' => 'COUNT(soi.item_id)'])
            ->group(['main_table.entity_id']);

        if (!empty($this->group_id) && $this->is_group_exists($this->group_id)) {
            $ordersCollection->getSelect()
                ->joinLeft(['cs' => Tools::getDbTablePrefix() . 'store'], 'cs.store_id = main_table.store_id')
                ->where("cs.group_id = $this->group_id");
        }

        if (empty($this->sort_by)) {
            $this->sort_by = 'id';
        }

        switch ($this->sort_by) {
            case 'name':
                $order_by = !empty($this->order_by) ? $this->order_by : 'ASC';
                $ordersCollection->getSelect()->order(["main_table.customer_firstname $order_by"])
                    ->order(["main_table.customer_lastname $order_by"]);
                break;
            case 'date':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $ordersCollection->getSelect()->order(["main_table.created_at $order_by"]);
                break;
            case 'id':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $ordersCollection->getSelect()->order(["main_table.entity_id $order_by"]);
                break;
            case 'total':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $ordersCollection->getSelect()->order(["main_table.base_grand_total $order_by"]);
                break;
            case 'qty':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $ordersCollection->getSelect()->order(["COUNT(soi.item_id) $order_by"]);
                break;
        }

        if (!empty($this->statuses)) {
            $this->statuses = "'" . str_replace('|', "','", $this->statuses) . "'";
            $ordersCollection->getSelect()->where("main_table.status IN ($this->statuses)");
        }

        if (!empty($this->orders_from)) {
            $ordersCollection->getSelect()->where(
                "(CONVERT_TZ(main_table.created_at, '+00:00',  '$offset')) >= '"
                . date('Y-m-d H:i:s', strtotime($this->orders_from . ' 00:00:00')) . "'"
            );
        }

        if (!empty($this->orders_to)) {
            $ordersCollection->getSelect()->where(
                "(CONVERT_TZ(main_table.created_at, '+00:00',  '$offset')) <= '"
                . date('Y-m-d H:i:s', strtotime($this->orders_to . ' 23:59:59')) . "'"
            );
        }

        if (!empty($this->search_order_id)) {
            if (preg_match('/^\d+(?:,\d+)*$/', $this->search_order_id)) {
                $where = "(main_table.entity_id IN ($this->search_order_id) OR main_table.increment_id IN
                    ($this->search_order_id))";
            } else {
                $where = "(main_table.customer_firstname LIKE ('%$this->search_order_id%')
                    OR main_table.customer_lastname LIKE ('%$this->search_order_id%')
                    OR CONCAT(main_table.customer_firstname, ' ', main_table.customer_lastname) LIKE
                    ('%$this->search_order_id%') OR main_table.customer_email = '$this->search_order_id')";
            }

            $ordersCollection->getSelect()->where($where);
        }

        $ordersStatsCollection = clone $ordersCollection;
        $ordersStatsCollection->addAttributeToSelect('base_grand_total')
            ->addAttributeToSelect('global_currency_code')
            ->addAttributeToSelect('created_at');

        $ordersCollection->getSelect()->limit($this->show, ($this->page - 1) * $this->show);

        foreach ($ordersCollection as $order) {
            $order_id           = $order->getEntityId();
            $store              = $order->getStore();
            $customer_firstname = $order->getCustomerFirstName();
            $customer_lastname  = $order->getCustomerLastName();
            $customer_fullname  = trim($customer_firstname . ' ' . $customer_lastname);
            $iso_code           = $order->getGlobalCurrencyCode();

            if ($this->currency_code) {
                $iso_code = $this->currency_code;
            }

            $ordersStatusLabel = $order->getStatusLabel();
            $orders[] = [
                'entity_id'        => $order_id,
                'id_order'         => $order_id,
                'order_number'     => $order->getIncrementId(),
                'id_customer'      => $order->getCustomerId(),
                'ord_status'       => method_exists($ordersStatusLabel, 'getText')
                    ? $ordersStatusLabel->getText()
                    : $ordersStatusLabel,
                'status_code'      => $order->getStatus(),
                'qty_ordered'      => $order->getTotalQtyOrdered(),
                'firstname'        => $customer_firstname,
                'lastname'         => $customer_lastname,
                'customer'         => empty($customer_fullname) ? 'Guest' : $customer_fullname,
                'customer_email'   => $order->getCustomerEmail(),
                'iso_code'         => $iso_code,
                'date_add'         => $order->getCreatedAt(),
                'count_prods'      => $order->getTotalItemCount(),
                'store_id'         => $order->getStoreId(),
                'store_name'       => $store->getName(),
                'store_group_id'   => $store->getGroup()->getId(),
                'store_group_name' => $store->getGroup()->getName(),
                'total_paid'       => Tools::getConvertedAndFormattedPrice(
                    $order->getBaseGrandTotal(),
                    $order->getGlobalCurrencyCode(),
                    $this->currency_code
                ),
            ];
        }

        $orders_count = $ordersStatsCollection->count();
        $orders_total = 0;
        $date_max     = '';
        $date_min     = '';

        foreach ($ordersStatsCollection as $order_stats) {
            $orders_total += Tools::getConvertedPrice(
                $order_stats->getBaseGrandTotal(),
                $order_stats->getGlobalCurrencyCode(),
                $this->currency_code
            );

            $date = strtotime($order_stats->getCreatedAt());

            if (empty($date_min) || $date_min > $date) {
                $date_min = $date;
            }

            if (empty($date_max) || $date_max < $date) {
                $date_max = $date;
            }
        }

        if (!empty($date_min)) {
            $date_min = date('n/j/Y', $date_min);
        }

        if (!empty($date_max)) {
            $date_max = date('n/j/Y', $date_max);
        }

        $orders_status = null;
        if (isset($this->get_statuses) && $this->get_statuses == 1) {
            $orders_status = $this->get_orders_statuses();
        }

        return [
            'orders'        => $orders,
            'orders_count'  => $orders_count,
            'orders_total'  => Tools::getFormattedPrice($orders_total, $this->currency_code),
            'max_date'      => $date_max,
            'min_date'      => $date_min,
            'orders_status' => $orders_status
        ];
    }

    private function get_orders_info()
    {
        if ($this->order_id < 1) {
            return false;
        }

        $order = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);

        if (!$order->getId()) {
            return false;
        }

        $order_store = $order->getStore();
        $order_address_billing  = $order->getBillingAddress();
        $order_address_shipping = $order->getShippingAddress();
        $s_street = !is_null($order_address_shipping) ? $order_address_shipping->getStreet() : '';
        $s_street = is_array($s_street) ? array_shift($s_street) : $s_street;
        $b_street = !is_null($order_address_billing) ? $order_address_billing->getStreet() : '';
        $b_street = is_array($b_street) ? array_shift($b_street) : $b_street;
        $order_global_currency_code = $order->getGlobalCurrencyCode();

        $order_products = [];
        $order_tracking = [];
        $order_actions  = [];

        try {
            $order_payment = $order->getPayment();
            $order_payment_instance = $order_payment->getMethodInstance();
            $order_payment_method_mag = $order_payment_instance->getTitle();
        } catch (Exception $e) {
            $order_payment_method_mag= 'The requested Payment Method is not available.';
        }

        $ordersStatus = $order->getStatusLabel();
        $order_info     = [
            'entity_id'           => $order->getEntityId(),
            'id_order'            => $order->getEntityId(),
            'store_id'            => $order->getStoreId(),
            'order_number'        => $order->getIncrementId(),
            'status'              => method_exists($ordersStatus, 'getText')
                ? $ordersStatus->getText()
                : $ordersStatus,
            'status_code'         => $order->getStatus(),
            'iso_code'            => $order_global_currency_code,
            'date_add'            => $order->getCreatedAt(),
            'id_customer'         => $order->getCustomerId(),
            'email'               => $order->getCustomerEmail(),
            'customer'            => $order->getCustomerName(),
            'store_name'          => $order_store->getName(),
            'store_group_id'      => $order_store->getGroupId(),
            'store_group_name'    => $order->getStoreGroupName(),
            'payment_method_mag'  => $order_payment_method_mag,
            'shipping_method_mag' => $order->getShippingDescription(),
            's_street'            => $s_street,
            'b_street'            => $b_street,
            'total_paid' => Tools::getConvertedAndFormattedPrice(
                $order->getBaseGrandTotal(),
                $order_global_currency_code,
                $this->currency_code
            ),
            'subtotal' => Tools::getConvertedAndFormattedPrice(
                $order->getBaseSubtotal(),
                $order_global_currency_code,
                $this->currency_code
            ),
            'sh_amount' => Tools::getConvertedAndFormattedPrice(
                $order->getBaseShippingAmount(),
                $order_global_currency_code,
                $this->currency_code
            ),
            'tax_amount' => Tools::getConvertedAndFormattedPrice(
                $order->GetBaseTaxAmount(),
                $order_global_currency_code,
                $this->currency_code
            ),
            'd_amount' => Tools::getConvertedAndFormattedPrice(
                $order->getDiscountAmount(),
                $order_global_currency_code,
                $this->currency_code
            ),
            'g_total' => Tools::getConvertedAndFormattedPrice(
                $order->getBaseGrandTotal(),
                $order_global_currency_code,
                $this->currency_code
            ),
            't_paid' => Tools::getConvertedAndFormattedPrice(
                $order->GetBaseTotalPaid(),
                $order_global_currency_code,
                $this->currency_code
            ),
            't_refunded' => Tools::getConvertedAndFormattedPrice(
                $order->getBaseTotalRefunded(),
                $order_global_currency_code,
                $this->currency_code
            ),
            't_due' => Tools::getConvertedAndFormattedPrice(
                $order->getBaseTotalDue(),
                $order_global_currency_code,
                $this->currency_code
            ),
            's_name' => !is_null($order_address_shipping)
                ? $order_address_shipping->getName()
                : '',
            's_company' => !is_null($order_address_shipping)
                ? $order_address_shipping->getCompany()
                : '',
            's_city' => !is_null($order_address_shipping)
                ? $order_address_shipping->getCity()
                : '',
            's_region' => !is_null($order_address_shipping)
                ? $order_address_shipping->getRegion()
                : '',
            's_postcode' => !is_null($order_address_shipping)
                ? $order_address_shipping->getPostcode()
                : '',
            's_country_id' => !is_null($order_address_shipping)
                ? Tools::getObjectManager()->create('Magento\Directory\Model\Country')
                    ->load($order_address_shipping->getCountryId())->getName()
                : '',
            's_telephone' => !is_null($order_address_shipping)
                ? $order_address_shipping->getTelephone()
                : '',
            's_fax' => !is_null($order_address_shipping)
                ? $order_address_shipping->getFax()
                : '',
            'b_name' => !is_null($order_address_billing)
                ? $order_address_billing->getName()
                : '',
            'b_company' => !is_null($order_address_billing)
                ? $order_address_billing->getCompany()
                : '',
            'b_city' => !is_null($order_address_billing)
                ? $order_address_billing->getCity()
                : '',
            'b_region' => !is_null($order_address_billing)
                ? $order_address_billing->getRegion()
                : '',
            'b_postcode' => !is_null($order_address_billing)
                ? $order_address_billing->getPostcode()
                : '',
            'b_country_id' => !is_null($order_address_billing)
                ? Tools::getObjectManager()->create('Magento\Directory\Model\Country')
                    ->load($order_address_billing->getCountryId())->getName()
                : '',
            'b_telephone' => !is_null($order_address_billing)
                ? $order_address_billing->getTelephone()
                : '',
            'b_fax' => !is_null($order_address_billing)
                ? $order_address_billing->getFax()
                : '',
            'telephone' => !is_null($order_address_billing)
                ? $order_address_billing->getFax()
                : '',
        ];

        $items_collection = Tools::getObjectManager()->create('Magento\Sales\Model\Order\Item')->getCollection()
            ->addFieldToFilter('order_id', $order->getEntityId());
        $items_collection->getSelect()->limit($this->show, ($this->page - 1) * $this->show);
        $count_products = $items_collection->getSize();

        foreach ($items_collection as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $imageUrl = '';
            if ($this->generate_thumbnails && ($itemProduct = $item->getProduct()) !== null) {
                $imageUrl = $this->getProductImageUrl($itemProduct, ['width' => 150, 'height' => 150]);
            }

            $product = [
                'product_id'       => $item->getProductId(),
                'id_order'         => $item->getOrderId(),
                'type_id'          => $this->getProductTypeLabelByTypeId($item->getProductType()),
                'product_name'     => $item->getName(),
                'product_quantity' => (int)$item->getQtyOrdered(),
                'product_price'    => Tools::getConvertedAndFormattedPrice(
                    $item->getBasePrice(),
                    $order_global_currency_code,
                    $this->currency_code
                ),
                'sku'              => $item->getSku(),
                'iso_code'         => $order_global_currency_code,
                'thumbnail'        => $imageUrl
            ];

            $product_options = $this->generateProductOptions($item->getProductOptions());

            if (!empty($product_options)) {
                $product['prod_options'] = $product_options;
            }

            $order_products[] = $product;
        }

        foreach ($order->getTracksCollection() as $track) {
            $order_tracking[] = [
                'track_number' => $track->getTrackNumber(),
                'title' => $track->getTitle(),
                'carrier_code' => $track->getCarrierCode(),
                'created_at' => $track->getCreatedAt(),
            ];
        }

        if ($order->canCancel()) {
            $order_actions[] = 'cancel';
        }

        if ($order->canHold()) {
            $order_actions[] = 'hold';
        }

        if ($order->canUnhold()) {
            $order_actions[] = 'unhold';
        }

        if ($order->canShip()) {
            $order_actions[] = 'ship';
        }

        if ($order->canInvoice()) {
            $order_actions[] = 'invoice';
        }

        $order_full_info = [
            'order_info'       => $order_info,
            'order_products'   => $order_products,
            'o_products_count' => $count_products,
            'order_tracking'   => $order_tracking,
            'actions'          => $order_actions,
        ];

        if ($order->hasInvoices()) {
            $order_full_info['pdf_invoice'] = 1;
        }

        return $order_full_info;
    }

    private function get_order_pdf()
    {
        if (!$this->order_id) {
            return;
        }

        $invoices = Tools::getObjectManager()->create('Magento\Sales\Model\Order\Invoice')->getCollection()
            ->setOrderFilter($this->order_id)
            ->load();

        if ($invoices->getSize() < 1) {
            return;
        }

        try {
            $date = Tools::getObjectManager()->create('Magento\Framework\Stdlib\DateTime\DateTime')->date('Y-m-d_H-i-s');
            $pdf = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Helper\PdfInvoice')->getPdf($invoices);
            Tools::getObjectManager()->create('Magento\Framework\App\Response\Http\FileFactory')
                ->create(
                    "invoice$date.pdf",
                    $pdf->render(),
                    \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
                    'application/pdf'
                );
        } catch (\Exception $e) {
//            echo $e->getMessage();
            $this->_response->setContent($e->getMessage());
        }

//        die();
    }

    private function set_order_action()
    {
        if ($this->order_id < 1) {
            return ['error' => Tools::translate('Order id cannot be empty!')];
        }

        if (!$this->action) {
            return ['error' => Tools::translate('Action is not set!')];
        }

        $order = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);

        if (!$order->getId()) {
            return ['error' => Tools::translate('No order was found!')];
        }

        $result = ['error' => Tools::translate('An error occurred!')];

        switch ($this->action) {
            case 'cancel':
                $result = $this->cancel_order();
                break;
            case 'hold':
                $result = $this->hold_order();
                break;
            case 'unhold':
                $result = $this->unhold_order();
                break;
            case 'invoice':
                $result = $this->invoice_order();
                break;
            case 'ship':
                $result = $this->ship_order();
                break;
            case 'del_track':
                $result = $this->delete_track_number();
                break;
        }

        return $result;
    }

    private function invoice_order()
    {
        if (!$this->order_id) {
            return false;
        }

        try {
            $order = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);

            if (!$order->getId()) {
                return ['error' => Tools::translate('The order no longer exists.')];
            }

            if (!$order->canInvoice()) {
                $order->addStatusHistoryComment('Order cannot be invoiced.', false);
                $order->save();

                return ['error' => Tools::translate('Cannot create an invoice!')];
            }

            $invoice = Tools::getObjectManager()->create('Magento\Sales\Model\Service\InvoiceService')
                ->prepareInvoice($order);

            if (!$invoice->getTotalQty()) {
                $order->addStatusHistoryComment('Cannot create an invoice without products.', false);
                $order->save();

                return ['error' => Tools::translate('Cannot create an invoice without products!')];
            }

            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);

            // Save invoice into database
            Tools::getObjectManager()->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

            // Add comment to order
            $order->addStatusHistoryComment('Invoice was created from Mobile Assistant.', false);
            $order->save();

            $result = ['success' => 'true'];
        } catch (\Exception $e) {
            $result = ['error' => Tools::translate('An error occurred!')];
        }

        return $result;
    }

    private function ship_order()
    {
        if (!$this->order_id) {
            return false;
        }

        $result = ['error' => Tools::translate('An error occurred!')];
        $title = '';
        $order = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);

        if (!$order->getId()) {
            return ['error' => Tools::translate('The order no longer exists.')];
        }

        if ($this->tracking_title) {
            $title = $this->tracking_title;
        } elseif ($this->carrier_code) {
            $carriers = $this->get_carriers();

            foreach ($carriers as $carrier) {
                if ($carrier['code'] == $this->carrier_code) {
                    $title = $carrier['label'];
                    break;
                }
            }
        }

        // Set track data
        if ($this->tracking_number) {
            $track = Tools::getObjectManager()->create('Magento\Sales\Model\Order\Shipment\Track')
                ->setTrackNumber($this->tracking_number)
                ->setCarrierCode($this->carrier_code)
                ->setTitle($title);
        }

        if ($order->hasShipments()) {
            if (!isset($track)) {
                $result = ['error' => Tools::translate('Empty tracking number!')];
            } else {
                foreach ($order->getShipmentsCollection() as $shipment) {
                    try {
                        // Save track into database
                        $shipment->addTrack($track)
                            ->save();

                        // Mail customer
                        if ($this->is_mail == 1) {
                            Tools::getObjectManager()->create('Magento\Sales\Model\Service\ShipmentService')
                                ->notify($shipment->getId());
                        }

                        $result = ['success' => 'true'];
                    } catch (\Exception $e) {
                        Tools::getLogger()->err(
                            self::MODULE_NAME . " - adding track number: {$e->getMessage()} ({$e->getCustomMessage()})"
                        );
                        $result = ['error' => $e->getMessage() . ' (' . $e->getCustomMessage() . ')'];
                    }
                }
            }
        } elseif ($order->canShip()) {
            $converter = Tools::getObjectManager()->create('Magento\Sales\Model\Convert\Order');
            $shipment = $converter->toShipment($order);

            foreach ($order->getAllItems() as $order_item) {
                $qty = $order_item->getQtyToShip();

                if (!$qty) {
                    continue;
                }

                if ($order_item->getIsVirtual()) {
                    continue;
                }

                $item = $converter->itemToShipmentItem($order_item);
                $item->setQty($qty);
                $shipment->addItem($item);
            }

            if (isset($track)) {
                $shipment->addTrack($track);
            }

            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);

            // Save shipment into database
            Tools::getObjectManager()->create('Magento\Framework\DB\Transaction')
                ->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();

            // Mail customer
            if ($this->is_mail == 1) {
                Tools::getObjectManager()->create('Magento\Sales\Model\Service\ShipmentService')
                    ->notify($shipment->getId());
            }

            $result = ['success' => 'true'];
        } else {
            $result = ['error' => Tools::translate('Current order cannot be shipped!')];
        }

        return $result;
    }

    private function cancel_order()
    {
        if (!$this->order_id) {
            return false;
        }

        $order = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);

        if ($order->canCancel()) {
            $order->cancel();
            $order->addStatusHistoryComment('Order was canceled by MA', false);
            $order->save();
            $result = ['success' => 'true'];
        } else {
            $order->addStatusHistoryComment('Order cannot be canceled', false);
            $order->save();
            $result = ['error' => Tools::translate('Current order cannot be canceled!')];
        }

        return $result;
    }

    private function hold_order()
    {
        if (!$this->order_id) {
            return false;
        }

        $order = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);

        if ($order->canHold()) {
            $order->hold();
            $order->addStatusHistoryComment('Order was holded by MA', false);
            $order->save();
            $result = ['success' => 'true'];
        } else {
            $order->addStatusHistoryComment('Order cannot be holded', false);
            $order->save();
            $result = ['error' => Tools::translate('Current order cannot be holded!')];
        }

        return $result;
    }

    private function unhold_order()
    {
        if (!$this->order_id) {
            return false;
        }

        $order = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);

        if ($order->canUnhold()) {
            $order->unhold();
            $order->addStatusHistoryComment('Order was unholded by MA', false);
            $order->save();
            $result = ['success' => 'true'];
        } else {
            $order->addStatusHistoryComment('Order cannot be unholded', false);
            $order->save();
            $result = ['error' => Tools::translate('Current order cannot be unholded!')];
        }

        return $result;
    }

    private function delete_track_number()
    {
        if (!isset($this->tracking_number)) {
            return ['error' => Tools::translate('Empty tracking number!')];
        }

        $order     = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->load($this->order_id);
        $matches   = 0;
        $shipments = $order->getShipmentsCollection();

        if ($shipments->getSize() < 1) {
            return ['error' => Tools::translate('Current order does not have shipments!')];
        }

        foreach ($shipments as $shipment) {
            $tracks  = $shipment->getAllTracks();
            $matches = 0;

            foreach ($tracks as $track) {
                if ($track->getNumber() == $this->tracking_number) {
                    $track->delete();
                    $matches++;
                }
            }
        }

        if ($matches > 0) {
            $result = ['success' => 'true'];
        } else {
            $result = ['error' => Tools::translate('Current tracking number was not found')];
        }

        return $result;
    }

    private function get_orders_statuses()
    {
        $statuses = Tools::getCollectionOrderStatuses()->getData();
        $final_statuses = array_map([$this, 'map_order_statuses'], $statuses);

        return $final_statuses;
    }

    private function map_order_statuses($status)
    {
        return [
            'st_id'   => $status['status'],
            'st_name' => $status['label']
        ];
    }

    private function get_carriers()
    {
        $carriers = [];
        $shipping_model = Tools::getObjectManager()->create('Magento\Shipping\Model\Config');

        foreach ($shipping_model->getAllCarriers() as $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $carriers[] = [
                    'code'  => $carrier->getCarrierCode(),
                    'label' => $carrier->getConfigData('title'),
                ];
            }
        }

        return $carriers;
    }

    private function get_customers()
    {
        $query = '';
        $customers = [];
        $query_where_parts = [];
        $offset = $this->_get_timezone_offset();
        $customers_collection = Tools::getObjectManager()->create('Magento\Customer\Model\Customer')->getCollection();
        $customers_collection->getSelect()
            ->joinLeft(
                ['sfo' => Tools::getDbTablePrefix() . 'sales_order'],
                'e.entity_id = sfo.customer_id',
                []
            )
            ->columns(['count_ords' => 'COUNT(sfo.entity_id)'])
            ->group(['e.entity_id']);

        if (!empty($this->group_id) && $this->is_group_exists($this->group_id)) {
            $customers_collection->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = e.store_id',
                    []
                )
                ->where("cs.group_id = $this->group_id");
        }

        if (!empty($this->customers_from)) {
            $query_where_parts[] = sprintf(
                " (CONVERT_TZ(e.created_at, '+00:00', '$offset')) >= '%s'",
                date('Y-m-d H:i:s', (strtotime($this->customers_from . " 00:00:00")))
            );
        }

        if (!empty($this->customers_to)) {
            $query_where_parts[] = sprintf(
                " (CONVERT_TZ(e.created_at, '+00:00', '$offset')) <= '%s'",
                date('Y-m-d H:i:s', (strtotime($this->customers_to . " 23:59:59")))
            );
        }

        if (!empty($query_where_parts)) {
            $query .= implode(' AND ', $query_where_parts);
            $customers_collection->getSelect()->where($query);
        }

        if (!empty($this->search_val)) {
            if (preg_match('/^\d+(?:,\d+)*$/', $this->search_val)) {
                $customers_collection->getSelect()->where("e.entity_id IN ($this->search_val)");
            } else {
                $customers_collection->getSelect()->where(
                    "e.`email` LIKE '%$this->search_val%' OR e.`firstname` LIKE '%$this->search_val%' OR
                    e.`lastname` LIKE '%$this->search_val%' OR
                    CONCAT(e.`firstname`, ' ', e.`lastname`) LIKE '%$this->search_val%'"
                );
            }
        }

        if (!empty($this->cust_with_orders)) {
            $customers_collection->getSelect()->having('count_ords > 0');
        }

        $customers_extra_collection = clone $customers_collection;
        $customers_collection->getSelect()->limit($this->show, ($this->page - 1) * $this->show);

        if (empty($this->sort_by)) {
            $this->sort_by = 'id';
        }

        switch ($this->sort_by) {
            case 'name':
                $order_by = !empty($this->order_by) ? $this->order_by : 'ASC';
                $customers_collection->getSelect()
                    ->order(["firstname $order_by"])
                    ->order(["lastname $order_by"]);
                break;
            case 'date':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $customers_collection->getSelect()->order(["e.created_at $order_by"]);
                break;
            case 'id':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $customers_collection->getSelect()->order(["e.entity_id $order_by"]);
                break;
            case 'qty':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $customers_collection->getSelect()->order(["COUNT(sfo.entity_id) $order_by"]);
                break;
        }

        foreach ($customers_collection as $customer) {
            $reg_date = explode(' ', $customer->getCreatedAt());
            $reg_date = $reg_date[0];

            $customers[] = [
                'id_customer'  => $customer->getId(),
                'firstname'    => $customer->getFirstname(),
                'middlename'   => $customer->getMidlename(),
                'lastname'     => $customer->getLastname(),
                'full_name'    => $customer->getName(),
                'date_add'     => $reg_date,
                'email'        => $customer->getEmail(),
                'total_orders' => $customer->getCount_ords()
            ];
        }

        return [
            'customers_count' => $customers_extra_collection->count(),
            'customers'       => $customers,
        ];
    }

    private function get_customers_info()
    {
        if ($this->user_id < 1) {
            return false;
        }

        $customer = Tools::getObjectManager()->create('Magento\Customer\Model\Customer')->load($this->user_id);

        if (!$customer->getId()) {
            return false;
        }

        $customer_orders = [];
        $orders_sum = 0;
        $user_info = [
            'city'         => '',
            'postcode'     => '',
            'phone'        => '',
            'region'       => '',
            'country'      => '',
            'street'       => '',
            'country_name' => ''
        ];

        $user_info['id_customer'] = $customer->getEntityId();
        $user_info['date_add']    = date('Y-m-d H:i:s', strtotime($customer->getCreatedAt()));
        $user_info['email']       = $customer->getEmail();
        $user_info['firstname']   = $customer->getFirstname();
        $user_info['middlename']  = $customer->getMiddlename();
        $user_info['lastname']    = $customer->getLastname();

        $address_id = $customer->getDefaultBilling();

        if ($address_id) {
            $address = Tools::getObjectManager()->get('Magento\Customer\Model\Address')->load($address_id);
            $country_id = $address->getCountryId();
            $street = $address->getStreet();

            if (is_array($street)) {
                $street = array_shift($street);
            }

            $user_info['city']         = $address->getCity();
            $user_info['postcode']     = $address->getPostcode();
            $user_info['phone']        = $address->getTelephone();
            $user_info['region']       = $address->getRegion();
            $user_info['country']      = $country_id;
            $user_info['street']       = $street;
            $user_info['country_name'] = Tools::getObjectManager()->create('Magento\Directory\Model\Country')
                ->load($country_id)->getName();
        }

        $user_info['address'] = $this->split_values(
            $user_info,
            ['street', 'city', 'region', 'postcode', 'country_name']
        );

        unset($user_info['country_name']);

        $orders_collection = Tools::getObjectManager()->create('Magento\Sales\Model\Order')->getCollection()
            ->addFieldToFilter('customer_id', $this->user_id);

        $orders_extra_collection = clone $orders_collection;
        $orders_extra_collection->getSelect()
            ->columns(['orders_total' => 'SUM(main_table.base_grand_total)']);
        $orders_collection->getSelect()->limit($this->show, ($this->page - 1) * $this->show);
        $orders_count = $orders_collection->getSize();

        foreach ($orders_collection as $order) {
            $store = $order->getStore();

            $customer_orders[] = [
                'store_id'         => $store->getId(),
                'store_name'       => $store->getName(),
                'store_group_id'   => $store->getGroup()->getId(),
                'store_group_name' => $store->getGroup()->getName(),
                'ord_status_code'  => $order->getStatus(),
                'iso_code'         => $order->getGlobalCurrencyCode(),
                'date_add'         => $order->getCreatedAt(),
                'pr_qty'           => $order->getItemsCollection()->getSize(),
                'ord_status'       => $order->getStatusLabel(),
                'id_order'         => $order->getId(),
                'total_paid'       => Tools::getConvertedAndFormattedPrice(
                    $order->getBaseGrandTotal(),
                    $order->getGlobalCurrencyCode(),
                    $this->currency_code
                ),
                'order_number'     => $order->getIncrementId(),
            ];
        }

        foreach ($orders_extra_collection as $data) {
            $orders_sum = $data->getOrdersTotal();
            break;
        }

        return [
            'user_info'       => $user_info,
            'customer_orders' => $customer_orders,
            'c_orders_count'  => $orders_count,
            'sum_ords'        => Tools::getConvertedAndFormattedPrice(
                $orders_sum,
                $this->def_currency,
                $this->currency_code
            )
        ];
    }

    private function search_products()
    {
        $products = [];
        $filters = [];
        $products_collection = Tools::getObjectManager()->create('Magento\Catalog\Model\Product')->getCollection();
        $products_collection->getSelect()
            ->joinLeft(
                ['si' => Tools::getDbTablePrefix() . 'cataloginventory_stock_item'],
                'si.product_id = e.entity_id',
                ['si.qty']
            )
            ->joinLeft(
                ['et_product' => Tools::getDbTablePrefix() . 'eav_entity_type'],
                "et_product.entity_type_code = 'catalog_product'",
                []
            )
            ->joinLeft(
                ['a_name' => Tools::getDbTablePrefix() . 'eav_attribute'],
                "a_name.entity_type_id = et_product.entity_type_id AND a_name.attribute_code = 'name'",
                []
            )
            ->joinLeft(
                ['p_name' => Tools::getDbTablePrefix() . 'catalog_product_entity_varchar'],
                'p_name.entity_id = e.entity_id AND p_name.attribute_id = a_name.attribute_id AND p_name.store_id = 0',
                ['p_name.value AS name']
            )
            ->joinLeft(
                ['a_price' => Tools::getDbTablePrefix() . 'eav_attribute'],
                "a_price.entity_type_id = et_product.entity_type_id AND a_price.attribute_code = 'price'",
                []
            )
            ->joinLeft(
                ['p_price' => Tools::getDbTablePrefix() . 'catalog_product_entity_decimal'],
                'p_price.entity_id = e.entity_id AND p_price.attribute_id = a_price.attribute_id AND p_price.store_id = 0',
                ['p_price.value AS price']
            )
            ->joinLeft(
                ['a_status' => Tools::getDbTablePrefix() . 'eav_attribute'],
                "a_status.entity_type_id = et_product.entity_type_id AND a_status.attribute_code = 'status'",
                []
            )
            ->joinLeft(
                ['p_status' => Tools::getDbTablePrefix() . 'catalog_product_entity_int'],
                'p_status.entity_id = e.entity_id AND p_status.attribute_id = a_status.attribute_id AND p_status.store_id = 0',
                ['p_status.value AS status']
            )
            ->limit($this->show, ($this->page - 1) * $this->show);

        if (!empty($this->params) && !empty($this->val)) {
            $this->params = explode('|', $this->params);

            foreach ($this->params as $param) {
                switch ($param) {
                    case 'pr_id':
                        $filters[] = ['attribute' => 'entity_id', 'eq' => $this->val];
                        break;
                    case 'pr_sku':
                        $filters[] = ['attribute' => 'sku', 'like' => "%$this->val%"];
                        break;
                    case 'pr_name':
                        $filters[] = ['attribute' => 'name', 'like' => "%$this->val%"];
                        break;
                    case 'pr_desc':
                        $filters[] = ['attribute' => 'description', 'like' => "%$this->val%"];
                        break;
                    case 'pr_short_desc':
                        $filters[] = ['attribute' => 'short_description', 'like' => "%$this->val%"];
                        break;
                }
            }
        }

        if (!empty($filters)) {
            $products_collection->addFieldToFilter($filters);
        }

        if (empty($this->sort_by)) {
            $this->sort_by = 'id';
        }

        switch ($this->sort_by) {
            case 'name':
                $order_by = !empty($this->order_by) ? $this->order_by : 'ASC';
                $products_collection->getSelect()->order(["name $order_by"]);
                break;
            case 'id':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $products_collection->getSelect()->order(["e.entity_id $order_by"]);
                break;
            case 'qty':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $products_collection->getSelect()->order(["si.qty $order_by"]);
                break;
            case 'price':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $products_collection->getSelect()->order(["price $order_by"]);
                break;
            case 'status':
                $order_by = !empty($this->order_by) ? $this->order_by : 'ASC';
                $products_collection->getSelect()->order(["status $order_by"]);
                break;
        }

        $products_count = $products_collection->getSize();
        $stockOptions = $this->getStockOptions();
        $statusOptions = $this->getStatusOptions();
        $products_collection = $products_collection->getData();
        foreach ($products_collection as $product) {
            $product_id  = $product['entity_id'];
            $product     = Tools::getObjectManager()->create('Magento\Catalog\Model\Product')->load($product_id);
            $is_in_stock = (int)$product->getExtensionAttributes()->getStockItem()->getIsInStock();

            $products[] = [
                'main_id'      => $product_id,
                'product_id'   => $product_id,
                'name'         => $product->getName(),
                'type_id'      => $this->getProductTypeLabelByTypeId($product->getTypeId()),
                'thumbnail'    => $this->generate_thumbnails
                    ? $this->getProductImageUrl($product, ['width' => 150, 'height' => 150])
                    : '',
                'sku'          => $product->getSku(),
                'quantity'     => $product->getExtensionAttributes()->getStockItem()->getQty(),
                'status_code'  => $product->getStatus(),
                'status_title' => $statusOptions[$product->getStatus()],
                'stock_code'   => $is_in_stock,
                'stock_title'  => $stockOptions[$is_in_stock],
                'price'        => Tools::getConvertedAndFormattedPrice(
                    $product->getData(\Magento\Catalog\Api\Data\ProductInterface::PRICE),
                    $this->def_currency,
                    $this->currency_code
                )
            ];
        }

        return [
            'products_count' => $products_count,
            'products'       => $products,
        ];
    }

    private function search_products_ordered()
    {
        if ((int)$this->group_by_product_id == 1) {
            return $this->search_products_ordered_group_by_product_id();
        }

        $ordered_products = [];
        $date_filter = [];
        $max_date = '';
        $min_date = '';
        $order_items_collection = Tools::getObjectManager()->create('Magento\Sales\Model\Order\Item')->getCollection();

        $order_items_collection->getSelect()
            ->joinLeft(
                ['order' => Tools::getDbTablePrefix() . 'sales_order'],
                'order.entity_id = main_table.order_id',
                []
            )
            ->columns(
                [
                    'total_ordered' => '((main_table.base_price - main_table.base_discount_amount)
                        * main_table.qty_ordered)'
                ]
            )
            ->limit($this->show, ($this->page - 1) * $this->show);

        if (!empty($this->group_id) && $this->is_group_exists($this->group_id)) {
            $order_items_collection->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = order.store_id',
                    []
                )
                ->where("cs.group_id = $this->group_id");
        }

        if (!empty($this->val) && !empty($this->params)) {
            $filter_cols  = [];
            $filters      = [];
            $this->params = explode('|', $this->params);

            foreach ($this->params as $param) {
                switch ($param) {
                    case 'pr_id':
                        $filter_cols[] = 'main_table.product_id';
                        $filters[]     = ['eq' => $this->val];
                        break;
                    case 'pr_sku':
                        $filter_cols[] = 'main_table.sku';
                        $filters[]     = ['like' => "%$this->val%"];
                        break;
                    case 'pr_name':
                        $filter_cols[] = 'main_table.name';
                        $filters[]     = ['like' => "%$this->val%"];
                        break;
                    case 'order_id':
                        $filter_cols[] = 'main_table.order_id';
                        $filters[]     = ['eq' => $this->val];
                        break;
                }
            }

            if (!empty($filter_cols) && !empty($filters)) {
                $order_items_collection->addFieldToFilter($filter_cols, $filters);
            }
        }

        if (!empty($this->products_from)) {
            $date_filter['from'] = $this->products_from . ' 00:00:00';
        }

        if (!empty($this->products_to)) {
            $date_filter['to'] = $this->products_to . ' 23:59:59';
        }

        if (!empty($date_filter)) {
            $date_filter['date'] = true;
            $order_items_collection->addFieldToFilter('order.created_at', $date_filter);
        }

        if (!empty($this->statuses)) {
            $this->statuses = explode('|', $this->statuses);
            $order_items_collection->addFieldToFilter('order.status', ['in' => [$this->statuses]]);
        }

        $order_items_extra_collection = clone $order_items_collection;
        $order_items_extra_collection->getSelect()
            ->columns(
                ['max_date_created' => 'MAX(main_table.created_at)', 'min_date_created' => 'MIN(main_table.created_at)']
            );

        switch ($this->sort_by) {
            case 'name':
                $order_by = !empty($this->order_by) ? $this->order_by : 'ASC';
                $order_items_collection->getSelect()->order(["main_table.name $order_by"]);
                break;
            case 'id':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $order_items_collection->getSelect()->order(["main_table.product_id $order_by"]);
                break;
            case 'qty':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $order_items_collection->getSelect()->order(["main_table.qty_ordered $order_by"]);
                break;
            case 'total':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $order_items_collection->getSelect()->order(["total_ordered $order_by"]);
                break;
        }

        $items_count = $order_items_collection->getSize();

        foreach ($order_items_collection as $order_item) {
            $order               = $order_item->getOrder();
            $item_id             = $order_item->getItemId();
            $qty_ordered         = $order_item->getQtyOrdered();
            $order_currency_code = $order->getGlobalCurrencyCode();
            $product = Tools::getObjectManager()->create('Magento\Catalog\Model\Product')
                ->load($order_item->getProductId());

            $ordered_products[] = [
                'item_id'    => $item_id,
                'product_id' => $order_item->getProductId(),
                'name'       => $order_item->getName(),
                'sku'        => $order_item->getSku(),
                'main_id'    => $item_id,
                'price'      => Tools::getConvertedAndFormattedPrice(
                    $order_item->getTotalOrdered(),
                    $order_currency_code,
                    $this->currency_code
                ),
                'quantity'   => (int)$qty_ordered,
                'type_id'    => $this->getProductTypeLabelByTypeId($order_item->getProductType()),
                'iso_code'   => $order_currency_code,
                'status'     => $order->getStatus(),
                'created_at' => $order->getCreatedAt(),
                'order_id'   => $order_item->getOrderId(),
                'thumbnail'  => $this->generate_thumbnails
                    ? $this->getProductImageUrl($product, ['width' => 150, 'height' => 150])
                    : ''
            ];
        }

        foreach ($order_items_extra_collection as $data) {
            $max_date = $data->getMaxDateCreated();
            $min_date = $data->getMinDateCreated();
            break;
        }

        return [
            'products_count' => $items_count,
            'products'       => $ordered_products,
            'max_date'       => $max_date,
            'min_date'       => $min_date,
        ];
    }

    private function search_products_ordered_group_by_product_id()
    {
        $ordered_products = [];
        $date_filter = [];
        $max_date = '';
        $min_date = '';
        $order_items_collection = Tools::getObjectManager()->create('Magento\Sales\Model\Order\Item')->getCollection();

        $order_items_collection->getSelect()
            ->joinLeft(
                ['order' => Tools::getDbTablePrefix() . 'sales_order'],
                'order.entity_id = main_table.order_id',
                []
            )
            ->columns(
                [
                    'count_ordered' => 'SUM(main_table.qty_ordered)',
                    'total_ordered' => 'SUM((main_table.base_price - main_table.base_discount_amount) * main_table.qty_ordered)'
                ]
            )
            ->group(['main_table.product_id']);

        if (!empty($this->group_id) && $this->is_group_exists($this->group_id)) {
            $order_items_collection->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = order.store_id',
                    []
                )
                ->where("cs.group_id = $this->group_id");
        }

        if (!empty($this->val) && !empty($this->params)) {
            $filter_cols  = [];
            $filters      = [];
            $this->params = explode('|', $this->params);

            foreach ($this->params as $param) {
                switch ($param) {
                    case 'pr_id':
                        $filter_cols[] = 'main_table.product_id';
                        $filters[]     = ['eq' => $this->val];
                        break;
                    case 'pr_sku':
                        $filter_cols[] = 'main_table.sku';
                        $filters[]     = ['like' => "%$this->val%"];
                        break;
                    case 'pr_name':
                        $filter_cols[] = 'main_table.name';
                        $filters[]     = ['like' => "%$this->val%"];
                        break;
                    case 'order_id':
                        $filter_cols[] = 'main_table.order_id';
                        $filters[]     = ['eq' => $this->val];
                        break;
                }
            }

            if (!empty($filter_cols) && !empty($filters)) {
                $order_items_collection->addFieldToFilter($filter_cols, $filters);
            }
        }

        if (!empty($this->products_from)) {
            $date_filter['from'] = $this->products_from . ' 00:00:00';
        }

        if (!empty($this->products_to)) {
            $date_filter['to'] = $this->products_to . ' 23:59:59';
        }

        if (!empty($date_filter)) {
            $date_filter['date'] = true;
            $order_items_collection->addFieldToFilter('order.created_at', $date_filter);
        }

        if (!empty($this->statuses)) {
            $this->statuses = explode('|', $this->statuses);
            $order_items_collection->addFieldToFilter('order.status', ['in' => [$this->statuses]]);
        }

        $order_items_count_collection = clone $order_items_collection;
        $order_items_count_collection->getSelect()
            ->columns(
                ['max_date_created' => 'MAX(main_table.created_at)', 'min_date_created' => 'MIN(main_table.created_at)']
            );
        $order_items_collection->getSelect()->limit($this->show, ($this->page - 1) * $this->show);

        switch ($this->sort_by) {
            case 'name':
                $order_by = !empty($this->order_by) ? $this->order_by : 'ASC';
                $order_items_collection->getSelect()->order(["main_table.name $order_by"]);
                break;
            case 'id':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $order_items_collection->getSelect()->order(["main_table.product_id $order_by"]);
                break;
            case 'qty':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $order_items_collection->getSelect()->order(["count_ordered $order_by"]);
                break;
            case 'total':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $order_items_collection->getSelect()->order(["total_ordered $order_by"]);
                break;
        }

        foreach ($order_items_collection as $order_item) {
            $item_id = $order_item->getItemId();
            $product = Tools::getObjectManager()->create('Magento\Catalog\Model\Product')
                ->load($order_item->getProductId());
            $ordered_products[] = [
                'item_id'    => $item_id,
                'product_id' => $order_item->getProductId(),
                'name'       => $order_item->getName(),
                'sku'        => $order_item->getSku(),
                'main_id'    => $item_id,
                'price'      => Tools::getConvertedAndFormattedPrice(
                    $order_item->getTotalOrdered(),
                    $this->def_currency,
                    $this->currency_code
                ),
                'quantity'   => (int)$order_item->getCountOrdered(),
                'type_id'    => $this->getProductTypeLabelByTypeId($order_item->getProductType()),
                'iso_code'   => $this->currency_code,
                'thumbnail'  => $this->generate_thumbnails
                    ? $this->getProductImageUrl($product, ['width' => 150, 'height' => 150])
                    : ''
            ];
        }

        foreach ($order_items_count_collection as $col) {
            $max_date = $col->getMaxDateCreated();
            $min_date = $col->getMinDateCreated();
            break;
        }

        return [
            'products_count' => $order_items_count_collection->count(),
            'products'       => $ordered_products,
            'max_date'       => $max_date,
            'min_date'       => $min_date,
        ];
    }

    private function get_products_info()
    {
        if ($this->product_id < 1) {
            return false;
        }

        // Get product data
        $product = Tools::getObjectManager()->create('Magento\Catalog\Model\Product')->load($this->product_id);

        // Check if product exists
        if (!$product->getId()) {
            return false;
        }

        $stockOptions = $this->getStockOptions();
        $statusOptions = $this->getStatusOptions();
        $is_in_stock = (int)$product->getExtensionAttributes()->getStockItem()->getIsInStock();
        $product_id = $product->getEntityId();
        $status = $product->getStatus();
        $qty_ordered = Tools::getObjectManager()->create('Magento\Sales\Model\Order\Item')->getCollection()
            ->addAttributeToFilter('product_id', $this->product_id)
            ->getSize();

        // Get all images of product
        $images = [];
        $media_gallery = $product->getData('media_gallery');
        if ($media_gallery) {
            foreach ($media_gallery['images'] as $image) {
                $images[] = [
                    'small' => $this->getProductImageUrl(
                        $product,
                        ['width' => 300, 'height' => 300],
                        $image['file']
                    ),
                    'large' => $this->getProductImageUrl(
                        $product,
                        ($this->full_image_size == 1) ? [] : ['width' => 800, 'height' => 800],
                        $image['file']
                    ),
                ];
            }
        }

        return [
            'name'           => $product->getName(),
            'status_code'    => $status,
            'status_title'   => $statusOptions[$status],
            'stock_code'     => $is_in_stock,
            'stock_title'    => $stockOptions[$is_in_stock],
            'total_ordered'  => $qty_ordered,
            'id_product'     => $product_id,
            'type_id'        => $this->getProductTypeLabelByTypeId($product->getTypeId()),
            'price'          => Tools::getConvertedAndFormattedPrice(
                $product->getData(\Magento\Catalog\Api\Data\ProductInterface::PRICE),
                $this->def_currency,
                $this->currency_code
            ),
            'quantity'       => $product->getExtensionAttributes()->getStockItem()->getQty(),
            'sku'            => $product->getSku(),
            'images'         => $images
        ];
    }

    private function get_products_descr()
    {
        $this->product_id = (int)$this->product_id;

        if ($this->product_id < 1) {
            return false;
        }

        $product = Tools::getObjectManager()->create('Magento\Catalog\Model\Product')->load($this->product_id);

        return [
            'descr'       => $product->getDescription(),
            'short_descr' => $product->getShortDescription()
        ];
    }

    private function get_abandoned_carts_list()
    {
        $quotes = [];
        $quotes_total = 0;

        $quotes_collection = Tools::getObjectManager()->create('Magento\Quote\Model\Quote')->getCollection()
            ->addFieldToFilter('main_table.is_active', ['eq' => 1])
            ->addFieldToFilter('main_table.items_count', ['gt' => 0])
            ->addFieldToFilter('main_table.customer_email', ['notnull' => true]);
        $quotes_collection->getSelect()
            ->columns(['customer_name' => "CONCAT(main_table.customer_firstname, ' ', main_table.customer_lastname)"])
            ->limit($this->show, ($this->page - 1) * $this->show);

        if (!empty($this->group_id) && $this->is_group_exists($this->group_id)) {
            $quotes_collection->getSelect()
                ->joinLeft(
                    ['cs' => Tools::getDbTablePrefix() . 'store'],
                    'cs.store_id = main_table.store_id',
                    []
                )
                ->where("cs.group_id = $this->group_id");
        }

        if (!empty($this->search_carts)) {
            if (preg_match('/^\d+(?:,\d+)*$/', $this->search_carts)) {
                $quotes_collection->getSelect()->where("main_table.entity_id IN ($this->search_carts)");
            } else {
                $quotes_collection->getSelect()
                    ->where(
                        "main_table.`customer_email` LIKE '%$this->search_carts%' OR main_table.`customer_firstname`
                        LIKE '%$this->search_carts%' OR main_table.`customer_lastname` LIKE '%$this->search_carts%'
                        OR CONCAT(`customer_firstname`, ' ', `customer_lastname`) LIKE '%$this->search_carts%'"
                    );
            }
        }

        if (!empty($this->carts_from)) {
            $this->carts_from = Tools::getDateTime()->timestamp(strtotime($this->carts_from));
            $this->carts_from = date('Y-m-d H:i:s', $this->carts_from);
            $quotes_collection->addFieldToFilter('main_table.updated_at', ['from' => $this->carts_from]);
        }

        if (!empty($this->carts_to)) {
            $this->carts_to = Tools::getDateTime()->timestamp(strtotime($this->carts_to));
            $this->carts_to = date('Y-m-d H:i:s', $this->carts_to);
            $quotes_collection->addFieldToFilter('main_table.updated_at', ['to' => $this->carts_to]);
        }

        if (empty($this->show_unregistered_customers)) {
            $quotes_collection->addFieldToFilter('main_table.customer_id', ['notnull' => true]);
            $quotes_collection->getSelect()
                ->joinInner(
                    ['c' => Tools::getDbTablePrefix() . 'customer_entity'],
                    'c.entity_id = main_table.customer_id',
                    []
                );
        }

        $quotes_extra_collection = clone $quotes_collection;
        $quotes_extra_collection->getSelect()
            ->columns(['total' => 'SUM(main_table.base_subtotal_with_discount)']);
        $quotes_count = $quotes_collection->getSize();

        switch ($this->sort_by) {
            case 'id':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $quotes_collection->getSelect()->order(["main_table.entity_id $order_by"]);
                break;
            case 'date':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $quotes_collection->getSelect()->order(["main_table.updated_at $order_by"]);
                break;
            case 'name':
                $order_by = !empty($this->order_by) ? $this->order_by : 'ASC';
                $quotes_collection->getSelect()->order(["customer_name $order_by"]);
                break;
            case 'total':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $quotes_collection->getSelect()->order(["main_table.base_subtotal_with_discount $order_by"]);
                break;
            case 'qty':
                $order_by = !empty($this->order_by) ? $this->order_by : 'DESC';
                $quotes_collection->getSelect()->order(["main_table.items_count $order_by"]);
                break;
        }

        foreach ($quotes_collection as $quote) {
            // Show only real customers
            if (empty($this->show_unregistered_customers) && !$quote->getCustomer()->getId()) {
                continue;
            }

            $quotes[] = [
                'id_cart'             => $quote->getEntityId(),
                'date_add'            => $quote->getCreatedAt(),
                'id_shop'             => $quote->getStoreId(),
                'id_currency'         => $quote->getBaseCurrencyCode(),
                'id_customer'         => $quote->getCustomerId(),
                'email'               => $quote->getCustomerEmail(),
                'customer'            => $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname(),
                'shop_name'           => $quote->getStore()->getName(),
                'carrier_name'        => '',
                'cart_total'          => Tools::getConvertedAndFormattedPrice(
                    $quote->getBaseSubtotalWithDiscount(),
                    $quote->getBaseCurrencyCode(),
                    $this->currency_code
                ),
                'cart_count_products' => $quote->getItemsCount(),
            ];
        }

        foreach ($quotes_extra_collection as $data) {
            $quotes_total = $data->getTotal();
        }

        return [
            'abandoned_carts'       => $quotes,
            'abandoned_carts_count' => $quotes_count,
            'abandoned_carts_total' => Tools::getConvertedAndFormattedPrice(
                $quotes_total,
                $this->def_currency,
                $this->currency_code
            )
        ];
    }

    private function get_abandoned_cart_details()
    {
        $this->cart_id = (int)$this->cart_id;

        if ($this->cart_id < 1) {
            return false;
        }

        $quote = Tools::getObjectManager()->create('Magento\Quote\Model\Quote')->load($this->cart_id);

        if (!$quote->getId()) {
            return false;
        }

        $quote_products = [];
        $phone          = '';
        $customer       = $quote->getCustomer();
        $address_id     = $customer->getDefaultBilling();

        if ($address_id) {
            $address = Tools::getObjectManager()->get('Magento\Customer\Model\Address')->load($address_id);
            $phone   = $address->getTelephone();
        }

        $quote_info = [
            'id_currency'         => $quote->getQuoteCurrencyCode(),
            'id_cart'             => $quote->getEntityId(),
            'id_shop'             => $quote->getStoreId(),
            'date_add'            => $quote->getCreatedAt(),
            'date_up'             => $quote->getUpdatedAt(),
            'id_customer'         => $quote->getCustomerId(),
            'email'               => $quote->getCustomerEmail(),
            'customer'            => $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname(),
            'phone'               => $phone,
            'account_registered'  => $customer->getCreatedAt(),
            'shop_name'           => $quote->getStore()->getName(),
            'carrier_name'        => '',
            'cart_total'          => Tools::getConvertedAndFormattedPrice(
                $quote->getBaseSubtotalWithDiscount(),
                $quote->getBaseCurrencyCode(),
                $this->currency_code
            ),
            'cart_count_products' => $quote->getItemsCount(),
        ];

        $items_collection = $quote->getItemsCollection()->addFieldToFilter('parent_item_id', ['null' => true]);
        $items_collection->getSelect()->limit($this->show, ($this->page - 1) * $this->show);

        foreach ($items_collection as $item) {
            $product = $item->getProduct();
            $options = $this->generateProductOptions($product->getTypeInstance(true)->getOrderOptions($product));

            $quote_product = [
                'id_product'       => $item->getProductId(),
                'product_name'     => $item->getName(),
                'product_type'     => $this->getProductTypeLabelByTypeId($item->getProductType()),
                'product_quantity' => $item->getQty(),
                'sku'              => $item->getSku(),
                'product_price'    => Tools::getConvertedAndFormattedPrice(
                    $item->getBaseRowTotal(),
                    $quote->getBaseCurrencyCode(),
                    $this->currency_code
                ),
                'product_image'    => $this->generate_thumbnails
                    ? $this->getProductImageUrl(
                        Tools::getObjectManager()->create('Magento\Catalog\Model\Product')->load($item->getProductId()),
                        ['width' => 150, 'height' => 150]
                    )
                    : ''
            ];

            if (!empty($options)) {
                $quote_product['prod_options'] = $options;
            }

            $quote_products[] = $quote_product;
        }

        return [
            'cart_info'           => $quote_info,
            'cart_products'       => $quote_products,
            'cart_products_count' => $quote_info['cart_count_products'],
        ];
    }

    private function getQrCode($hash)
    {
        $user = Tools::getObjectManager()->create('Emagicone\Mobassistantconnector\Model\User')
            ->load($hash, 'qr_code_hash');
        $store = Tools::getObjectManager()->get('Magento\Store\Model\Store');
        $urlStatic = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);
        $locale = Tools::getObjectManager()->get('Magento\Framework\Locale\ResolverInterface');
        $localeCode = $locale->getDefaultLocale();
        $urlStaticWithoutVersion = substr($urlStatic, 0, strpos($urlStatic, 'static') + 7);

        if ((int)$user->getStatus() != 1) {
            $this->generate_output('auth_error');
            return;
        }

        $module = Tools::getObjectManager()->get('Magento\Framework\Module\Dir\Reader');
        $moduleDir = $module->getModuleDir('', 'Emagicone_Mobassistantconnector');

        $data_to_qr = Tools::getDataToQrCode($store->getBaseUrl(), $user->getUsername(), $user->getPassword());

        include_once $moduleDir . '/view/frontend/templates/qr_code.phtml';

        $content = '<script type="text/javascript">
                    (function() {
                        var qrcode = new QRCode(document.getElementById("mobassistantconnector_qrcode_img"), {
                            width : 300,
                            height : 300
                        });
                        qrcode.makeCode("' . $data_to_qr . '");
            })();

            </script>';

        $this->_response->setHeader('Content-Type', 'text/javascript;charset=utf-8');
        $this->_response->setContent($content);
    }

    private function getStockOptions()
    {
        $stockOptions = [];
        $options = Tools::getObjectManager()->create('Magento\CatalogInventory\Model\Source\Stock')->getAllOptions();
        $count = count($options);

        for ($i = 0; $i < $count; $i++) {
            $stockOptions[$options[$i]['value']] = $options[$i]['label']->getText();
        }

        return $stockOptions;
    }

    private function getStatusOptions()
    {
        $statuses = Tools::getObjectManager()->create('Magento\Catalog\Model\Product\Attribute\Source\Status')
            ->getOptionArray();

        return [
            Status::STATUS_ENABLED  => $statuses[Status::STATUS_ENABLED]->getText(),
            Status::STATUS_DISABLED => $statuses[Status::STATUS_DISABLED]->getText()
        ];
    }

    private function getProductTypeLabelByTypeId($product_type_id)
    {
        $product_type_label = '';
        $product_types = Tools::getObjectManager()->get('Magento\Catalog\Model\ProductTypeList')
            ->getProductTypes();

        foreach ($product_types as $product_type) {
            if ($product_type->getName() == $product_type_id) {
                $product_type_label = $product_type->getLabel();
                break;
            }
        }

        return $product_type_label;
    }

    private function getProductImageUrl(\Magento\Catalog\Model\Product $product, array $size, $imageFile = false)
    {
        $image = Tools::getObjectManager()->get('Magento\Catalog\Helper\Image')
            ->init(
                $product,
                'image',
                array_merge(['type' => 'image'], $size)
            );

        if ($imageFile) {
            $image->setImageFile($imageFile);
        }

        return $image->getUrl();
    }

    private function generateProductOptions(array $options) {
        $product_options = [];

        // Custom options
        if (isset($options['options'])) {
            $count = count($options['options']);

            for ($i = 0; $i < $count; $i++) {
                $product_options[$options['options'][$i]['label']] = $options['options'][$i]['value'];
            }
        }

        // Configurable products
        if (isset($options['attributes_info'])) {
            $count = count($options['attributes_info']);

            for ($i = 0; $i < $count; $i++) {
                $product_options[$options['attributes_info'][$i]['label']] = $options['attributes_info'][$i]['value'];
            }
        }

        return $product_options;
    }

    private function split_values($arr, $keys, $sign = ', ')
    {
        $new_arr = [];

        foreach ($keys as $key) {
            if (isset($arr[$key]) && !is_null($arr[$key]) && $arr[$key] != '') {
                $new_arr[] = $arr[$key];
            }
        }

        if (empty($new_arr)) {
            return '';
        }

        return implode($sign, $new_arr);
    }

    private function _get_timezone_offset()
    {
        $timezone = Tools::getConfigValue('general/locale/timezone', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

        $origin_dtz = new \DateTimeZone('UTC');
        $remote_dtz = new \DateTimeZone($timezone);
        $origin_dt = new \DateTime('now', $origin_dtz);
        $remote_dt = new \DateTime('now', $remote_dtz);
        $offset = $remote_dtz->getOffset($remote_dt) - $origin_dtz->getOffset($origin_dt);

        $hours = (int)($offset/60/60);
        $mins = $offset/60%60;
        $offset = (($hours >= 0) ? '+' . $hours : $hours) . ':' . $mins;

        return $offset;
    }

    private function get_custom_period($period = 0)
    {
        $custom_period = ['start_date' => '', 'end_date' => ''];
        $format = 'm/d/Y';

        switch ($period) {
            case 0: //3 days
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')));
                $custom_period['end_date'] = date($format, mktime(23, 59, 59, date('m'), date('d'), date('Y')));
                break;
            case 1: //7 days
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date('m'), date('d') - 6, date('Y')));
                $custom_period['end_date'] = date($format, mktime(23, 59, 59, date('m'), date('d'), date('Y')));
                break;
            case 2: //Prev week
                $custom_period['start_date'] = date(
                    $format,
                    mktime(0, 0, 0, date('n'), date('j') - 6, date('Y')) - (date('N') * 3600 * 24)
                );
                $custom_period['end_date'] = date(
                    $format,
                    mktime(23, 59, 59, date('n'), date('j'), date('Y')) - (date('N') * 3600 * 24)
                );
                break;
            case 3: //Prev month
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date('m') - 1, 1, date('Y')));
                $custom_period['end_date'] = date(
                    $format,
                    mktime(23, 59, 59, date('m'), date('d') - date('j'), date('Y'))
                );
                break;
            case 4: //This quarter
                $m = date('n');
                $start_m = 1;
                $end_m = 3;

                if ($m <= 3) {
                    $start_m = 1;
                    $end_m = 3;
                } elseif ($m >= 4 && $m <= 6) {
                    $start_m = 4;
                    $end_m = 6;
                } elseif ($m >= 7 && $m <= 9) {
                    $start_m = 7;
                    $end_m = 9;
                } elseif ($m >= 10) {
                    $start_m = 10;
                    $end_m = 12;
                }

                $custom_period['start_date'] = date($format, mktime(0, 0, 0, $start_m, 1, date('Y')));
                $custom_period['end_date'] = date($format, mktime(23, 59, 59, $end_m + 1, date(1) - 1, date('Y')));
                break;
            case 5: //This year
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date(1), date(1), date('Y')));
                $custom_period['end_date'] = date($format, mktime(23, 59, 59, date(1), date(1) - 1, date('Y') + 1));
                break;
            case 7: //Last year
                $custom_period['start_date'] = date($format, mktime(0, 0, 0, date(1), date(1), date('Y') - 1));
                $custom_period['end_date'] = date($format, mktime(23, 59, 59, date(1), date(1) - 1, date('Y')));
                break;
            case 8: //Previous quarter
                $m = date('n');
                $start_m = 1;
                $end_m = 3;

                if ($m <= 3) {
                    $start_m = 10;
                    $end_m = 12;
                } elseif ($m >= 4 && $m <= 6) {
                    $start_m = 1;
                    $end_m = 3;
                } elseif ($m >= 7 && $m <= 9) {
                    $start_m = 4;
                    $end_m = 6;
                } elseif ($m >= 10) {
                    $start_m = 7;
                    $end_m = 9;
                }

                $custom_period['start_date'] = date($format, mktime(0, 0, 0, $start_m, 1, date('Y')));
                $custom_period['end_date'] = date($format, mktime(23, 59, 59, $end_m + 1, date(1) - 1, date('Y')));
                break;
        }

        return $custom_period;
    }
}
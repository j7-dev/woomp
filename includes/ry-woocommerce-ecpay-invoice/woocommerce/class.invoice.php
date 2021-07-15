<?php
final class RY_WEI_Invoice
{
    public static $log_enabled = false;
    public static $log = false;

    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/abstracts/abstract-ecpay.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/ecpay-invoice-api.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/ecpay-invoice-response.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/class-wc-meta-box-invoice-data.php';

            self::$log_enabled = 'yes' === RY_WEI::get_option('invoice_log', 'no');

            add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_invoice_info'], 9999);

            RY_WEI_Invoice_Response::init();

            switch (RY_WEI::get_option('get_mode')) {
                case 'auto_paid':
                    $paid_statuses = wc_get_is_paid_statuses();
                    foreach ($paid_statuses as $status) {
                        add_action('woocommerce_order_status_' . $status, [__CLASS__, 'auto_get_invoice']);
                    }
                    break;
                case 'auto_completed':
                    $completed_statuses = ['completed'];
                    foreach ($completed_statuses as $status) {
                        add_action('woocommerce_order_status_' . $status, [__CLASS__, 'auto_get_invoice']);
                    }
                    break;
            }
            add_action('ry_wei_auto_get_invoice', ['RY_WEI_Invoice_Api', 'get'], 10, 2);
            add_action('ry_wei_auto_get_delay_invoice', ['RY_WEI_Invoice_Api', 'get_delay'], 10, 2);

            if ('auto_cancell' == RY_WEI::get_option('invalid_mode')) {
                add_action('woocommerce_order_status_cancelled', ['RY_WEI_Invoice_Api', 'invalid']);
                add_action('woocommerce_order_status_refunded', ['RY_WEI_Invoice_Api', 'invalid']);
            }

            if (is_admin()) {
                add_filter('enable_ry_invoice', [__CLASS__, 'add_enable_ry_invoice']);
                add_action('admin_enqueue_scripts', [__CLASS__, 'add_scripts']);

                add_filter('manage_shop_order_posts_columns', [__CLASS__, 'add_admin_invoice_column'], 11);
                add_action('manage_shop_order_posts_custom_column', [__CLASS__, 'show_admin_invoice_column'], 11);
                add_action('woocommerce_admin_order_data_after_billing_address', ['WC_Meta_Box_Invoice_Data', 'output']);
                add_action('woocommerce_update_order', [__CLASS__, 'save_order_update']);

                add_action('wp_ajax_RY_WEI_get', [__CLASS__, 'get_invoice']);
                add_action('wp_ajax_RY_WEI_invalid', [__CLASS__, 'invalid_invoice']);
                add_action('wp_ajax_RY_WEI_clean_delay', [__CLASS__, 'clean_delay_invoice']);
            } else {
                add_filter('default_checkout_invoice_company_name', [__CLASS__, 'set_default_invoice_company_name']);
                add_action('woocommerce_after_checkout_billing_form', [__CLASS__, 'show_invoice_form']);
                add_action('woocommerce_after_checkout_validation', [__CLASS__, 'invoice_checkout_validation'], 10, 2);
                add_action('woocommerce_checkout_create_order', [__CLASS__, 'save_order_invoice'], 10, 2);

                add_action('woocommerce_order_details_after_customer_details', [__CLASS__, 'show_invoice_info']);
                if ('yes' == RY_WEI::get_option('show_invoice_number', 'no')) {
                    add_filter('woocommerce_account_orders_columns', [__CLASS__, 'add_invoice_column']);
                    add_action('woocommerce_my_account_my_orders_column_invoice-number', [__CLASS__, 'show_invoice_column']);
                }
            }
        }
    }

    public static function add_invoice_info($fields)
    {
        $fields['invoice'] = [
            'invoice_type' => [
                'type' => 'select',
                'label' => __('Invoice type', 'ry-woocommerce-ecpay-invoice'),
                'options' => [
                    'personal' => _x('personal', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                    'company' => _x('company', 'invoice type', 'ry-woocommerce-ecpay-invoice'),
                       'donate' => _x('donate', 'invoice type', 'ry-woocommerce-ecpay-invoice')
                ],
                'default' => 'personal',
                'required' => true,
                'priority' => 10
            ],
            'invoice_carruer_type' => [
                'type' => 'select',
                'label' => __('Carruer type', 'ry-woocommerce-ecpay-invoice'),
                'options' => [
                    'none' => _x('none', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'ecpay_host' => _x('ecpay_host', 'carruer type', 'ry-woocommerce-ecpay-invoice') . __(' (send paper when win)', 'ry-woocommerce-ecpay-invoice'),
                    'MOICA' => _x('MOICA', 'carruer type', 'ry-woocommerce-ecpay-invoice'),
                    'phone_barcode' => _x('phone_barcode', 'carruer type', 'ry-woocommerce-ecpay-invoice')
                ],
                'default' => 'ecpay_host',
                'required' => true,
                'priority' => 10
            ],
            'invoice_carruer_no' => [
                'label' => __('Carruer number', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 20
            ],
            'invoice_no' => [
                'label' => __('Tax ID number', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 30
            ],
            'invoice_donate_no' => [
                'label' => __('Donate number', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 40
            ]
        ];

        if ('no' == RY_WEI::get_option('support_carruer_type_none', 'no')) {
            unset($fields['invoice']['invoice_carruer_type']['options']['none']);
        }

        if ('yes' == RY_WEI::get_option('move_billing_company', 'no')) {
            unset($fields['billing']['billing_company']);
            $fields['invoice']['invoice_company_name'] = [
                'label' => __('Company name', 'ry-woocommerce-ecpay-invoice'),
                'required' => true,
                'priority' => 30
            ];
        }

        // default donate no - 財團法人台灣兒童暨家庭扶助基金會 ( CCF )
        $donate_no = apply_filters('ry_wei_default_donate_no', ['7261651', '5900', '8585', '7885', '035', '378585', '2085', '024', '326139', '5875', '5520', '68660', '2100', '323804', '078585', '5584', '70885', '8300', '5678585', '2812085', '6323200', '6361712', '6361716', '8700', '7123', '1785', '3100', '6782', '461234', '818585', '33085', '176176'], '');
        if (is_array($donate_no)) {
            $donate_no = $donate_no[time() / 86400 % count($donate_no)];
        }
        $fields['invoice']['invoice_donate_no']['default'] = $donate_no;

        if (did_action('woocommerce_checkout_process')) {
            $invoice_type = isset($_POST['invoice_type']) ? wc_clean($_POST['invoice_type']) : '';
            $invoice_carruer_type = isset($_POST['invoice_carruer_type']) ? wc_clean($_POST['invoice_carruer_type']) : '';

            switch ($invoice_type) {
                case 'personal':
                    switch ($invoice_carruer_type) {
                        case 'none':
                            $fields['invoice']['invoice_carruer_no']['required'] = false;
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'ecpay_host':
                            $fields['invoice']['invoice_carruer_no']['required'] = false;
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'MOICA':
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'phone_barcode':
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                    }
                    break;
                case 'company':
                    $fields['invoice']['invoice_carruer_no']['required'] = false;
                    $fields['invoice']['invoice_donate_no']['required'] = false;
                break;
                case 'donate':
                    $fields['invoice']['invoice_carruer_no']['required'] = false;
                    $fields['invoice']['invoice_no']['required'] = false;
                    $fields['invoice']['invoice_company_name']['required'] = false;
                break;
            }
        }

        return $fields;
    }

    public static function auto_get_invoice($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $skip_shipping = apply_filters('ry_wei_skip_autoget_invoice_shipping', []);

        if (!empty($skip_shipping)) {
            foreach ($order->get_items('shipping') as $item_id => $item) {
                if (in_array($item->get_method_id(), $skip_shipping)) {
                    return false;
                }
            }
        }

        $delay_days = (int) RY_WEI::get_option('get_delay_days', 0);
        if ($delay_days == 0) {
            WC()->queue()->schedule_single(time() + 3, 'ry_wei_auto_get_invoice', [$order_id], '');
        } else {
            WC()->queue()->schedule_single(time() + 3, 'ry_wei_auto_get_delay_invoice', [$order_id], '');
        }
    }

    public static function add_enable_ry_invoice($enable)
    {
        $enable[] = 'ecpay';

        return $enable;
    }

    public static function add_scripts()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if (in_array($screen_id, ['shop_order', 'edit-shop_order', 'woocommerce_page_wc-settings'])) {
            wp_enqueue_script('ry-wei-admin-script', RY_WEI_PLUGIN_URL . 'style/admin/ry_ecpay_invoice.js', ['jquery'], RY_WEI_VERSION);
            wp_enqueue_style('ry-wei-admin-style', RY_WEI_PLUGIN_URL . 'style/admin/ry_ecpay_invoice.css', [], RY_WEI_VERSION);

            wp_localize_script('ry-wei-admin-script', 'ry_wei_script', [
                'get_loading_text'=> __('Get invoice.<br>Please wait.', 'ry-woocommerce-ecpay-invoice'),
                'invalid_loading_text'=> __('Invalid invoice.<br>Please wait.', 'ry-woocommerce-ecpay-invoice'),
                'clean_delay_loading_text'=> __('Clean order invoice data.<br>Please wait.', 'ry-woocommerce-ecpay-invoice')
            ]);
        }
    }

    public static function add_admin_invoice_column($columns)
    {
        $add_index = array_search('order_status', array_keys($columns)) + 1;
        $pre_array = array_splice($columns, 0, $add_index);
        $array = [
            'invoice-number' => __('Invoice number', 'ry-woocommerce-ecpay-invoice')
        ];
        return array_merge($pre_array, $array, $columns);
    }

    public static function show_admin_invoice_column($column)
    {
        if ($column == 'invoice-number') {
            global $the_order;

            $invoice_number = $the_order->get_meta('_invoice_number');
            if ($invoice_number == 'zero') {
                echo __('Zero no invoice', 'ry-woocommerce-ecpay-invoice');
            } elseif ($invoice_number == 'delay') {
                echo __('Delay get invoice', 'ry-woocommerce-ecpay-invoice');
            } else {
                echo $the_order->get_meta('_invoice_number');
            }
        }
    }

    public static function get_invoice()
    {
        $order_ID = (int) $_POST['id'];

        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        RY_WEI_Invoice_Api::get_delay($order);
    }

    public static function invalid_invoice()
    {
        $order_ID = (int) $_POST['id'];

        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        RY_WEI_Invoice_Api::invalid($order);
    }

    public static function clean_delay_invoice()
    {
        $order_ID = (int) $_POST['id'];

        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        $order->delete_meta_data('_invoice_number');
        $order->delete_meta_data('_invoice_random_number');
        $order->save_meta_data();
    }

    public static function invoice_checkout_validation($data, $errors)
    {
        // 自然人憑證
        if ($data['invoice_type'] == 'personal' && $data['invoice_carruer_type'] == 'MOICA') {
            if (!empty($data['invoice_carruer_no'])) {
                if (!preg_match('/^[A-Z]{2}\d{14}$/', $data['invoice_carruer_no'])) {
                    $errors->add('validation', __('Invalid carruer number', 'ry-woocommerce-ecpay-invoice'));
                }
            }
        }

        // 手機載具
        if ($data['invoice_type'] == 'personal' && $data['invoice_carruer_type'] == 'phone_barcode') {
            if (!preg_match('/^\/{1}[0-9A-Z+-.]{7}$/', $data['invoice_carruer_no'])) {
                $errors->add('validation', __('Invalid carruer number', 'ry-woocommerce-ecpay-invoice'));
            } elseif (!RY_WEI_Invoice_Api::check_mobile_code($data['invoice_carruer_no'])) {
                $errors->add('validation', __('Invalid carruer number', 'ry-woocommerce-ecpay-invoice'));
            }
        }

        // 統一編號
        if ($data['invoice_type'] == 'company') {
            if (!preg_match('/^[0-9]{8}$/', $data['invoice_no'])) {
                $errors->add('validation', __('Invalid tax ID number', 'ry-woocommerce-ecpay-invoice'));
            }
        }

        // 愛心碼
        if ($data['invoice_type'] == 'donate') {
            if (!preg_match('/^[0-9]{3,7}$/', $data['invoice_donate_no'])) {
                $errors->add('validation', __('Invalid donate number', 'ry-woocommerce-ecpay-invoice'));
            } elseif (RY_WEI_Invoice_Api::check_donate_no($data['invoice_donate_no']) === false) {
                $errors->add('validation', __('Invalid donate number', 'ry-woocommerce-ecpay-invoice'));
            }
        }
    }

    public static function get_ecpay_api_info()
    {
        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $MerchantID = '2000132';
            $HashKey = 'ejCk326UnaZWKisg';
            $HashIV = 'q9jcZX8Ib9LM8wYk';
        } else {
            $MerchantID = RY_WEI::get_option('ecpay_MerchantID');
            $HashKey = RY_WEI::get_option('ecpay_HashKey');
            $HashIV = RY_WEI::get_option('ecpay_HashIV');
        }

        return [$MerchantID, $HashKey, $HashIV];
    }

    public static function log($message, $level = 'info')
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }

            self::$log->log($level, $message, [
                'source' => 'ry_ecpay_invoice',
                '_legacy' => true
            ]);
        }
    }

    public static function set_default_invoice_company_name()
    {
        if (is_user_logged_in()) {
            $customer = new WC_Customer(get_current_user_id(), true);

            return $customer->get_billing_company();
        }

        return '';
    }

    public static function show_invoice_form($checkout)
    {
        wp_enqueue_script('ry-wei-checkout', RY_WEI_PLUGIN_URL . 'style/ry_wei_checkout.js', ['jquery'], RY_WEI_VERSION, true);

        wc_get_template('checkout/form-invoice.php', [
            'checkout' => $checkout
        ], '', RY_WEI_PLUGIN_DIR . 'templates/');
    }

    public static function save_order_invoice($order, $data)
    {
        $order->update_meta_data('_invoice_type', isset($data['invoice_type']) ? $data['invoice_type'] : 'personal');
        $order->update_meta_data('_invoice_carruer_type', isset($data['invoice_carruer_type']) ? $data['invoice_carruer_type'] : 'ecpay_host');
        $order->update_meta_data('_invoice_carruer_no', isset($data['invoice_carruer_no']) ? $data['invoice_carruer_no'] : '');
        $order->update_meta_data('_invoice_no', isset($data['invoice_no']) ? $data['invoice_no'] : '');
        $order->update_meta_data('_invoice_donate_no', isset($data['invoice_donate_no']) ? $data['invoice_donate_no'] : '');
        if ('yes' == RY_WEI::get_option('move_billing_company', 'no')) {
            $order->set_billing_company(isset($data['invoice_company_name']) ? $data['invoice_company_name'] : '');
        }
    }

    public static function save_order_update($order_id)
    {
        if ($order = wc_get_order($order_id)) {
            if (isset($_POST['_invoice_type'])) {
                $order->update_meta_data('_invoice_type', wc_clean(wp_unslash($_POST['_invoice_type'])));
                $order->update_meta_data('_invoice_carruer_type', wc_clean(wp_unslash($_POST['_invoice_carruer_type'])));
                $order->update_meta_data('_invoice_carruer_no', wc_clean(wp_unslash($_POST['_invoice_carruer_no'])));
                $order->update_meta_data('_invoice_no', wc_clean(wp_unslash($_POST['_invoice_no'])));
                $order->update_meta_data('_invoice_donate_no', wc_clean(wp_unslash($_POST['_invoice_donate_no'])));
                $order->save_meta_data();
            }
        }
    }

    public static function show_invoice_info($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        $invoice_type = $order->get_meta('_invoice_type');
        $carruer_type = $order->get_meta('_invoice_carruer_type');

        if (!$invoice_type) {
            return ;
        }

        $invoice_info = [];
        if ($invoice_number) {
            if ($invoice_number == 'zero') {
                $invoice_info[] = [
                    'key' => 'zero-info',
                    'name' => __('Zero total fee without invoice', 'ry-woocommerce-ecpay-invoice'),
                    'value' => ''
                ];
            } elseif ($invoice_number != 'delay') {
                $invoice_info[] = [
                    'key' => 'invoice-number',
                    'name' => __('Invoice number', 'ry-woocommerce-ecpay-invoice'),
                    'value' => $invoice_number
                ];
                $invoice_info[] = [
                    'key' => 'invoice-random-number',
                    'name' => __('Invoice random number', 'ry-woocommerce-ecpay-invoice'),
                    'value' => $order->get_meta('_invoice_random_number')
                ];
            }
        }

        $invoice_info[] = [
            'key' => 'invoice-type',
            'name' => __('Invoice type', 'ry-woocommerce-ecpay-invoice'),
            'value' => _x($invoice_type, 'invoice type', 'ry-woocommerce-ecpay-invoice')
        ];

        if ($invoice_type == 'personal') {
            $key = count($invoice_info) - 1;
            $invoice_info[$key]['value'] .= ' (' . _x($carruer_type, 'carruer type', 'ry-woocommerce-ecpay-invoice') . ')';
            if (in_array($carruer_type, ['MOICA', 'phone_barcode'])) {
                $invoice_info[] = [
                    'key' => 'carruer-number',
                    'name' => __('Carruer number', 'ry-woocommerce-ecpay-invoice'),
                    'value' => $order->get_meta('_invoice_carruer_no')
                ];
            }
        }
        if ($invoice_type == 'company') {
            $invoice_info[] = [
                'key' => 'tax-id-number',
                'name' => __('Tax ID number', 'ry-woocommerce-ecpay-invoice'),
                'value' => $order->get_meta('_invoice_no')
            ];
        }
        if ($invoice_type == 'donate') {
            $invoice_info[] = [
                'key' => 'donate-number',
                'name' => __('Donate number', 'ry-woocommerce-ecpay-invoice'),
                'value' => $order->get_meta('_invoice_donate_no')
            ];
        }

        $args = [
            'order' => $order,
            'invoice_info' => apply_filters('ry_wei_order_invoice_info_list', $invoice_info, $order)
        ];
        wc_get_template('order/order-invoice-info.php', $args, '', RY_WEI_PLUGIN_DIR . 'templates/');
    }

    public static function add_invoice_column($columns)
    {
        $add_index = array_search('order-total', array_keys($columns)) + 1;
        $pre_array = array_splice($columns, 0, $add_index);
        $array = [
            'invoice-number' => __('Invoice number', 'ry-woocommerce-ecpay-invoice')
        ];
        return array_merge($pre_array, $array, $columns);
    }

    public static function show_invoice_column($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        if (!in_array($invoice_number, ['delay', 'zero'])) {
            echo $invoice_number;
        }
    }
}

RY_WEI_Invoice::init();

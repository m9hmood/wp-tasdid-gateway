<?php
/**
 *
 * define the hooks and settings of woocommerce payment gateway
 *
 * @package    Tasdid_Gateway
 * @subpackage Tasdid_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
defined('ABSPATH') || exit;

class Tasdid_Gateway_WC extends WC_Payment_Gateway
{
    /**
     * @since 1.0.0
     * @var string
     */
    public $title;
    /**
     * @since 1.0.0
     * @var string
     */
    public $description;
    /**
     * Tasdid Account Username
     *
     * @since 1.0.0
     * @var string
     */
    private $username;
    /**
     * Tasdid Account Password
     *
     * @since 1.0.0
     * @var string
     */
    private $password;
    /**
     * Tasdid Service ID
     *
     * @since 1.0.0
     * @var string
     */
    private $service_id;
    /**
     * @since 1.0.0
     * @var boolean
     */
    private $isTest = false;
    /**
     * Tasdid Token
     * @since 1.0.0
     * @var string
     */
    private $token;

    /**
     * Define the core functionality of the plugin and add Tasdid to woocommerce gateways.
     *.
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->id = 'tasdid-gateway';
        $this->method_title = __('Tasdid Gateway', 'tasdid-gateway');
        $this->method_description = __('Have your customers pay with Tasdid.', 'tasdid-gateway');
        $this->supports = array(
            'products',
        );
        // Load form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        // Define user set variables
        $this->title = $this->get_option('title') ? $this->get_option('title') : 'Tasdid Gateway';
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->username = $this->get_option('username');
        $this->password = $this->get_option('password');
        $this->token = $this->get_option('token');
        $this->service_id = $this->get_option('service_id');
        $this->isTest = $this->get_option('is_test') == 'yes' ? true : false;

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_thankyou_order_received_text', array($this, 'order_received_text'), 10, 2);

        if (empty($this->username) || empty($this->password)) {
            $this->enabled = "no";
            $this->update_option('enabled', 'no');
        } else if (empty($this->token)) {
            $token = Tasdid_Gateway_Helper::login($this->username, $this->password, $this->isTest);
            if (empty($token)) return;
            $this->update_option("token", $token);
            $this->token = $token;
        } else if (!Tasdid_Gateway_Helper::is_token_expired($this->token)) {
            $token = Tasdid_Gateway_Helper::login($this->username, $this->password, $this->isTest);
            if (empty($token)) return;
            $this->update_option("token", $token);
            $this->token = $token;
        }

    }

    /**
     * initial settings fields for the gateway
     *
     * @since 1.0.0
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', 'tasdid-gateway'),
                'label' => __('Enable TASDID Gateway', 'tasdid-gateway'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'tasdid-gateway'),
                'label' => __('Payment Option Title', 'tasdid-gateway'),
                'type' => 'text',
                'default' => 'TASDID Gateway',
            ),
            'description' => array(
                'title' => __('Description', 'tasdid-gateway'),
                'type' => 'textarea',
                'description' => __('This description is what user see when choose TASDID gateway', 'tasdid-gateway'),
                'default' => __('Pay via TASDID'),
            ),
            'username' => array(
                'title' => __('Username', 'tasdid-gateway'),
                'type' => 'text',
            ),
            'password' => array(
                'title' => __('Password', 'tasdid-gateway'),
                'type' => 'text',
            ),
            'service_id' => array(
                'title' => __('Service ID', 'tasdid-gateway'),
                'type' => 'text',
            ),
            'is_test' => array(
                'title' => __('Test Mode', 'tasdid-gateway'),
                'label' => __('Enable Test Mode', 'tasdid-gateway'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
        );
    }

    /**
     * Customize order received text for WooCommerce when customer use tasdid gateway.
     *
     * @since    1.0.0
     */
    public function order_received_text($text, $order)
    {
        if ($order->get_payment_method() === 'tasdid-gateway') {
            echo __('Dear Mr/Mrs.', 'tasdid-gateway') . PHP_EOL . $order->get_billing_first_name() . '<br />';
            echo __('An invoice has been created for your order, please pay the bill through tasdid platform', 'tasdid-gateway') . '<br />';
            echo '<a href="https://pay.tasdid.net/?id='.$order->get_meta("_ts_order").'" style="background: #3A7B70; color: #fff; padding: 10px; display: inline-block; margin-top: 10px; text-decoration: none;font-size:18px">' . __("Pay Bill", "tasdid-gateway") . '</a>';
        } else {
            echo $text;
        }
    }

    /**
     * Handle tasdid payment request
     *
     * @param $order_id
     * @return array|void
     * @since 1.0.0
     */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        if (!$this->validate_phone_number($order->get_billing_phone())) {
            wc_add_notice(__('Error: Invalid phone number', 'tasdid-gateway'), 'error');
            return;
        }

        // create tasdid bill
        $request = $this->create_payment($order);
        // decode request response
        $response = json_decode(wp_remote_retrieve_body($request), true);
        $statusCode = wp_remote_retrieve_response_code($request);

        /**
         * handle request status code
         * 200 - order has been created
         * default - something wrong
         */
        switch ($statusCode) {
            case 200:
                // update order status to pending
                $order->update_status('pending', __('Awaiting bill payment', 'tasdid-gateway'));
                // store tasdid meta data
                add_post_meta($order->get_id(), "_ts_order", $response['data']['payId']);
                // empty cart
                $woocommerce->cart->empty_cart();

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            default:
                wc_add_notice(__('Something Wrong, Please try again later.', 'tasdid-gateway') . $statusCode, 'error');
                break;
        }
    }

    /**
     * validate mobile number with specific rules: mobile should contain 07[x] and length should be 11
     *
     * @param string $mobileNo
     * @return bool
     * @since    1.2.2
     */
    private function validate_phone_number(string $mobileNo): bool
    {
        if (!preg_match_all('/07[3-9][0-9]/', $mobileNo)) {
            return false;
        }
        if (strlen($mobileNo) !== 11) {
            return false;
        }
        return true;
    }

    /**
     * Create bill in TASDID Gateway for the order
     * @since 1.0.0
     * @param WC_Order $order
     * @return array|WP_Error
     */
    private function create_payment(WC_Order $order)
    {
        $dueDate = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 5, date('Y')));

        $base_url = $this->isTest ? TESTING_TASDID_API_DOMAIN : TASDID_API_DOMAIN;

        $args = array(
            'payId' => sprintf("%'.05d", $order->get_order_number()),
            'customerName' => $order->get_formatted_billing_full_name(),
            'dueDate' => $dueDate,
            'payDate' => null,
            'amount' => number_format($order->get_total()),
            'phoneNumber' => $order->get_billing_phone(),
            'serviceId' => $this->service_id,
        );
        // add Authorization to request header
        $headers = array(
            'Authorization' => 'Bearer ' . $this->token,
        );

        return Tasdid_Gateway_Helper::request($base_url . '/Provider/AddBill', 'PUT', $args, $headers);
    }
}

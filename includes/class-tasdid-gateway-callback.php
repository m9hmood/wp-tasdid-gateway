<?php
/**
 * Payment gateway callback handling class
 *
 * @package    Tasdid_Gateway
 * @subpackage Tasdid_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 * @since      1.0.0
 */
defined('ABSPATH') || exit;

class Tasdid_Gateway_Callback
{
    /**
     * Check order status by token
     *
     * @throws \Exception
     */
    public function callback_endpoint_handler(WP_REST_Request $request)
    {
        $params = $request->get_json_params() ?? $request->get_body_params() ?? $request->get_params();
        $payment_gateway = WC()->payment_gateways->payment_gateways()['tasdid-gateway'];
        die(json_encode($payment_gateway));
        // validate request
        if (!isset($params['PayId']) || !isset($params['Status']) || !isset($params['Key'])) {
            return new WP_REST_Response(array(
                'status' => 'failed',
                'reason' => 'missing required parameters'
            ), 400);
        }


        $order = wc_get_orders(array(
            '_ts_order' => $params['PayId']
        ));

        if (count($order) > 0) {
            $privateKey = strtoupper(md5($payment_gateway->settings['username'] . '|' . $params['PayId'] . '|' . $params['Status']));
            if ($privateKey !== $params["Key"]) {
                return new WP_REST_Response(array(
                    'status' => 'failed',
                    'message' => 'Secret key is not valid'
                ), 400);
            }
            $order[0]->update_status("processing");
            return new WP_REST_Response(array(
                'status' => 'success',
                'message' => 'order status updated'
            ), 200);
        }
        return new WP_REST_Response(array(
            'status' => 'failed',
            'message' => 'Order not found'
        ), 400);
    }


    public function add_callback()
    {
        register_rest_route('v1/tasdid', '/order/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'callback_endpoint_handler'),
            'args' => [
            ],
            'permission_callback' => '__return_true'
        ));
    }

}

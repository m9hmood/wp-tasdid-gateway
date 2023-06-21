<?php
/**
 *
 * Define the payment gateway filters for woocommerce;
 *
 * @package    Tasdid_Gateway
 * @subpackage Tasdid_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
defined('ABSPATH') || exit;

class Tasdid_Gateway_Filters
{
    /**
     * Handle a custom '_tasdid_order_id' query var to get orders with the '_tasdid_order_id' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    public function add_tasdid_meta_to_query($query, $query_vars)
    {
        if (!empty($query_vars['_tasdid_order_id'])) {
            $query['meta_query'][] = array(
                'key' => '_tasdid_order_id',
                'value' => esc_attr($query_vars['_tasdid_order_id']),
            );
        }
        return $query;
    }

}
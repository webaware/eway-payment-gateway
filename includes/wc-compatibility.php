<?php
// compatibility layer for WooCommerce versions

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('wc_reduce_stock_levels')) {

	/**
	 * Reduce stock levels for items within an order.
	 * @since 2.7.0
	 * @param int $order_id
	 */
	function wc_reduce_stock_levels( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->reduce_order_stock();
	}

}

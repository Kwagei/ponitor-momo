<?php

 /**
 * Plugin Name: Ponitor
 * Description: Accept Lonestar Cell MTN Mobile Money payments on your WooCommerce
 * Version: 1.0.0
 * Author: Kwagei Group of Companies
 * Author URI: https://kwagei.com
 * Copyright: © 2023 Kwagei Group.
 * License: GNU General Public License v3.0
 * License URL: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: ponitor-momo
 * Tested up to: 6.2.2
 * Stable tag: 5.0
 * WC requires at least: 4.2.0
 * WC tested up to: 7.1
 */


 defined( 'ABSPATH' ) || exit;

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;


add_action( 'plugins_loaded', 'init', 11 );
function init()
{
    if( !class_exists('WC_Payment_Gateway') ) return;

    require_once dirname( __FILE__ ) . '/includes/Uuid.php';
    require_once dirname( __FILE__ ) . '/includes/Helper.php';
    require_once dirname(__FILE__) . '/includes/Momo.php';
    require_once dirname(__FILE__) . '/includes/Templates.php';
    require_once dirname( __FILE__ ) . '/includes/Ponitor.php';
}


/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'add_ponitor_gateway' );
function add_ponitor_gateway(): array
{
    $methods[] = 'Ponitor';
    return $methods;
}


/**
 * Adds settings link for Ponitor
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ponitor_settings' );
function ponitor_settings( $links )
{

    $settings_link = '<a href="' . esc_url_raw(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=ponitor' ) ). '">' . __('Settings', Helper::get_domain() ) . '</a>';

    // Adds the link to the beginning of the array.
    array_unshift($links, $settings_link);

    return $links;
}

add_action( 'wp_ajax_confirm_payment', 'confirm_payment');
add_action( 'wp_ajax_nopriv_confirm_payment', 'confirm_payment');
/**
 * Makes request to confirm a payment
 *  wp_die() is required to terminate immediately and return a proper response
 */
function confirm_payment() {

	$order_id = $_REQUEST['order_id'];

	if ($order_id)
	{
		$order = wc_get_order($order_id);

		if ($order)
		{
			$gateway = WC()->payment_gateways->payment_gateways()[Helper::get_id()];

            $user_id = $gateway->user_id;
			$api_key = $gateway->api_key;
            $subscription_key = $gateway->subscription_key;

			$transaction_id = wc_get_order_item_meta( $order->get_id(), 'transaction_id', true );

            $momo = new Momo($user_id, $api_key, $subscription_key);
			$transaction = $momo->get_transaction($transaction_id);

			if ($transaction)
			{
                $status = $transaction['status'];

                // add momo id
                wc_add_order_item_meta($order_id, 'momo_id', $transaction['financialTransactionId'], true);

                // update status
                wc_update_order_item_meta($order_id, 'momo_status', $status);


				if ($status == $momo->get_success_status())
				{
					// update order status
					$order->update_status('completed', __('Payment Completed', Helper::get_id()));

                    $order->add_order_note( 'Your order is paid! Thank you!', true );

					// empty cart.
					WC()->cart->empty_cart();

					wp_send_json_success(['success' => true], 200);
				}
				else if ($status == $momo->get_failed_status())
				{

                    $order->update_status('failed', __('Payment Completed',Helper::get_id()));

                    $order->add_order_note( 'MoMo payment failed!', true );

					Helper::log('transaction failed: ', $transaction);
					wp_send_json_success(['failed' => true], 200);
				}
				else
				{
					Helper::log('transaction pending: ', $transaction);
					wp_send_json_success(['pending' => true], 200);
				}

			}
			else
			{
				Helper::log("transaction not found: ", $transaction);
				wp_send_json_error(['error' => "transaction not found: "], 404);
			}

		}
		else
		{
			Helper::log("order not found: ", $order);
			wp_send_json_error(['error' => "order not found"], 404);
		}
	}
	else
	{
		Helper::log("order id is required: ", $order_id);
		wp_send_json_error(['error' => "order id is required"], 400);
	}
}


<?php

/**
 * Ponitor MTN Mobile Money Payment Gateway.
 *
 * Provides a MTN Mobile Money Payment Gateway.
 *
 * @class       Ponitor
 * @extends     WC_Payment_Gateway
 * @version     2.3.0
 * @package     WooCommerce\Classes\Payment
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Ponitor extends WC_Payment_Gateway
{

    public $domain;
    public $id;
    public $user_id;
    public $api_key;
    public $merchant_name;
    public $subscription_key;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {

        $this->setupProperties();

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->api_key = $this->get_option('api_key');
        $this->enabled = $this->get_option('enabled');
        $this->user_id = $this->get_option('user_id');
        $this->description = $this->get_option('description');
        $this->subscription_key = $this->get_option('subscription_key');
        $this->merchant_name = $this->get_option('merchant_name');

        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // Add Fields to Order Details page after checkout
        add_action( 'woocommerce_order_details_after_order_table', array($this, 'order_details_fields'));

        // Add Custom Fields to Order Admin Page
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'order_admin_fields'));

        add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ));

        // add js
        add_action('wp_enqueue_scripts', array($this, 'ponitor_scripts'));


    }

    private function setupProperties()
    {
        $this->id = Helper::get_id();
        $this->domain = Helper::get_domain();
        $this->supports = array('subscriptions', 'products');
        $this->has_fields = true;

        // The title of the payment method for the admin page (i.e., “Cheque”)
        $this->method_title = __('Ponitor', $this->domain);

        // The description for the payment method shown to the admins
        $this->method_description = __('The best way to pay with MTN Lonestar Mobile Money.', $this->domain);

        // The link to the image displayed next to the method’s title on the checkout page
        $this->icon = apply_filters('logo', plugins_url('../assets/logos/ponitor-icon.jpg', __FILE__));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', $this->domain ),
                'type' => 'checkbox',
                'label' => __( 'Enable Ponitor Payment', $this->domain ),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Title', $this->domain ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', $this->domain  ),
                'default' => __( 'Ponitor', $this->domain),
                'custom_attributes' => array(
                    'readonly' => 'readonly'
                )
            ),
            'description' => array(
                'title' => __( 'Description', $this->domain ),
                'default' => __('Pay with Lonestar Cell MTN Mobile Money. Please ensure you have enough funds on your MTN Mobile Money number.', $this->domain),
                'description' => __( 'This controls the description which the user sees during checkout.', $this->domain ),
                'type' => 'textarea',
                'custom_attributes' => array(
                    'readonly' => 'readonly'
                )
            ),
            'user_id' => array(
                'title' => __( 'User ID', $this->domain ),
                'type' => 'text',
                'description' => __( 'MoMo API User ID.', $this->domain  ),
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'api_key' => array(
                'title' => __( 'API Key', $this->domain ),
                'type' => 'text',
                'description' => __( 'MoMo API Key.', $this->domain  ),
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'subscription_key' => array(
                'title' => __( 'Ocp Apim Collections Subscription Key', $this->domain ),
                'type' => 'text',
                'description' => __( 'MoMo API Collections Primary Subscription Key from developer portal.', $this->domain  ),

            ),
            'merchant_name' => array(
                'title' => __( 'Merchant Name', $this->domain ),
                'type' => 'text',
                'description' => __( 'Name of merchant shown in the prompt that goes to users.', $this->domain  ),
                'custom_attributes' => array(
                    'required' => 'required'
                )
            )
        );
    }

    public function receipt_page($order_id) {

        $merchant_name = $this->merchant_name;
        $order = wc_get_order($order_id);
        $msisdn = wc_get_order_item_meta( $order->get_id(), 'msisdn', true );
        $order_cancel_url = $order->get_cancel_order_url('');
		$order_complete_url = $this->get_return_url( $order );
        $currency = $order->get_currency();
        $amount = $order->get_total();

        echo Templates::receipt_view($merchant_name, $msisdn, $currency, $amount, $order_id, $order_cancel_url, $order_complete_url);
    }

    /**
     * @throws Exception
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order( $order_id );
        $amount = $order->get_total();
        $currency = $order->get_currency();
        $msisdn = $_POST['msisdn'];
        $transaction_id = Uuid::v4();

        $momo = new Momo($this->user_id, $this->api_key, $this->subscription_key);
        $transaction = $momo->collect($msisdn, $amount, $currency, $transaction_id);

        if ($transaction)
        {

            //save meta data
            wc_add_order_item_meta($order_id, 'transaction_id', $transaction_id, true);
            wc_add_order_item_meta($order_id, 'msisdn', sanitize_text_field($msisdn), true);
            wc_add_order_item_meta($order_id, 'momo_status', sanitize_text_field($momo->get_pending_status()), true);

            $checkout_url = $order->get_checkout_payment_url(true);
            $checkout_edited_url = $checkout_url . "&transactionType=ponitor";

            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order',
                    $order_id,
                    add_query_arg(
                        'key',
                        $order->get_order_key(),
                        $checkout_edited_url
                    )
                )
            );
        }
        else {
            wc_add_notice('Transaction failed. Please try again.', 'error');
            return ['result' => 'fail', 'redirect' => $this->get_return_url()];
        }

    }

    public function process_admin_options()
    {

        $settings = $this->get_post_data();

        $api_key = $settings["woocommerce_".$this->id."_api_key"];
        $user_id = $settings["woocommerce_".$this->id."_user_id"];
        $subscription_key = $settings["woocommerce_".$this->id."_subscription_key"];
        $merchant_name = $settings["woocommerce_".$this->id."_merchant_name"];

        $invalid_data = empty($merchant_name) || empty($subscription_key) || empty($user_id) || empty($api_key);

        if ($invalid_data)
        {
            WC_Admin_Settings::add_error(__('Invalid settings. Please check your inputs. User ID, API Key, Merchant Name, and Ocp Apim Subscription Key are all required!.', $this->domain));

            return false;
        }

        $momo = new Momo($user_id, $api_key, $subscription_key);
        $token = $momo->get_token();

        if (!$token)
        {
            WC_Admin_Settings::add_error(__('Invalid MoMo Credentials. We are unable to verify your User ID, Ocp Apim Subscription Key, and API Key.', $this->domain));
        }


        // If the validation passes, save the settings
        parent::process_admin_options();
    }

    /**
     * Contains the payment gateway form that is show if $this->has_fields is true
     *
     * @return void
     */
    function payment_fields()
    {

        // ok, let's display some description before the payment form
        if ( $this->description ) {
            // display the description with <p> tags etc.
            echo wpautop( wp_kses_post( $this->description ) );
        }

        echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        // Add this action hook if you want your custom payment gateway to support it
        do_action( 'woocommerce_credit_card_form_start', $this->id );

        echo Templates::payment_form();

        do_action( 'woocommerce_credit_card_form_end', $this->id );
        echo '<div class="clear"></div></fieldset>';



    }

    /**
     * Validates the payment gateway form
     *
     * @return bool
     */
    public function validate_fields(): bool
    {
        $msisdn_valid = Helper::validate_msisdn($_POST['msisdn']);

        if ($msisdn_valid) return true;

        if (!$msisdn_valid) wc_add_notice(  'Invalid phone number. Ex: 0880102030!', 'error' );

        return false;

    }

    /**
     * Add Fields to Order Details page after checkout
     *
     * @throws Exception
     */
    public function order_details_fields($order ){

        $momo_number = wc_get_order_item_meta( $order->get_id(), 'msisdn', true );
        $momo_id = wc_get_order_item_meta( $order->get_id(), 'momo_id', true );
        $momo_status = wc_get_order_item_meta( $order->get_id(), 'momo_status', true );
        $currency = $order->get_currency();
        $amount = $order->get_total();

        echo Templates::order_details_view($momo_number, $currency, $amount, $momo_id, $momo_status);
    }


    /**
     * Add Custom Fields to Order Admin Page
     *
     *
     * @throws Exception
     */
    public function order_admin_fields($order)
    {

        $transaction_id = wc_get_order_item_meta( $order->get_id(), 'transaction_id', true );
        $momo_id = wc_get_order_item_meta( $order->get_id(), 'momo_id', true );
        $momo_number = wc_get_order_item_meta( $order->get_id(), 'msisdn', true );
        $momo_status = wc_get_order_item_meta( $order->get_id(), 'momo_status', true );
        $currency = $order->get_currency();

        echo Templates::order_admin_view($transaction_id, $momo_id, $momo_number, $momo_status, $currency);
    }

    public function ponitor_scripts() {


        if ( is_checkout())
        {

            if ('no' === $this->enabled ) return;

            wp_enqueue_script(
                'ponitor-js',
                plugin_dir_url( __FILE__ ) . '../assets/js/ponitor.js',
                array( 'jquery'),
                time()
            );

            /**
             * Additionally, we must use wp_localize_script() to pass values into JavaScript object properties, since PHP cannot directly echo values into our JavaScript file.
             * In JavaScript, object properties are accessed as ponitorParams.ajaxUrl
             */

            wp_localize_script(
                'ponitor-js',
                'params', // it is the name of JavaScript variable (object)
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' )
                )
            );

        }


    }

}
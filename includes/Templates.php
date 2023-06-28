<?php

class Templates
{
    public static function payment_form(): string
    {
        return '
            <div class="form-row form-row-wide">
                <label>MTN Number <span class="required">*</span></label>
                <input minlength="10" maxlength="10" name="msisdn" required id="msisdn" placeholder="0880102030" type="text">
            </div>
            <div class="clear"></div>
        ';
    }

    public static function order_admin_view($transaction_id, $momo_id, $momo_number, $momo_status, $momo_currency): string
    {

        return '
            <div class="address">
                <p>
                    <strong> '.__('Transaction ID:', Helper::get_domain()).'</strong>
                    '. esc_html( $transaction_id ) .'
                </p>
            </div>
            <div class="address">
                <p>
                    <strong>'.__('MoMo ID:', Helper::get_domain()).'</strong>
                    '. esc_html( $momo_id ) .'
                </p>
            </div>
            <div class="address">
                <p>
                    <strong>'.__('MoMo Number:', Helper::get_domain()).'</strong>
                    '. esc_html( $momo_number ) .'
                </p>
            </div>
            <div class="address">
                <p>
                    <strong>'.__('MoMo Status:', Helper::get_domain()).'</strong>
                    '. esc_html( $momo_status ) .'
                </p>
            </div>
            <div class="address">
                <p>
                    <strong>'.__('MoMo Currency:', Helper::get_domain()).'</strong>
                    '. esc_html( $momo_currency) .'
                </p>
            </div>';
    }

    public static function order_details_view($momo_number, $momo_currency, $amount, $momo_id, $momo_status)
    {
        return '
            <secton class="woocommerce-order-details">
                <h2 class="woocommerce-order-details__title">MoMo Details</h2>
                <table class="woocommerce-table shop_table">
                    <tbody>
                        <tr>
                            <th>'.__('MoMo Number', Helper::get_domain()).'</th>
                            <td>'.esc_html($momo_number).'</td>
                        </tr>
                        <tr>
                            <th>'.__('Currency', Helper::get_domain()).'</th>
                            <td>'.$momo_currency.'</td>
                        </tr>
                        <tr>
                            <th>'.__('Amount', Helper::get_domain()).'</th>
                            <td>'. $amount.'</td>
                        </tr>
                        <tr>
                            <th>'.__('MoMo ID', Helper::get_domain()).'</th>
                            <td>'. $momo_id.'</td>
                        </tr>
                        <tr>
                            <th>'.__('MoMo Status', Helper::get_domain()).'</th>
                            <td>'. $momo_status.'</td>
                        </tr>
                    </tbody>
                </table>
            </secton>';
    }

    public static function receipt_view($merchant_name, $msisdn, $currency, $amount, $order_id, $order_cancel_url, $order_complete_url)
    {
        return '<div>

            <p id="message_ponitor" style="display: none;"></p>

            <p>You will receive a prompt from '.$merchant_name.' on <strong>'. $msisdn .'</strong> asking you to confirm a transaction of <strong>'. $currency.' '.$amount .'</strong>. If you do not the get the prompt, please check your approvals list by dialing <strong>*156*8*2#</strong></p>

			<p>Once you have confirmed the payment on your phone, please click the "Confirm Payment" button below to complete this transaction. </p>

            <p><strong>You will not receive the prompt if you do not have enough funds on your MoMo number.</strong></p>


			<button class="button" id="confirm_payment_btn" type="button" style="cursor: pointer">Confirm Payment</button>

            <input type="hidden" id="order_id" value="'.$order_id.'">
			<input type="hidden" id="order_cancel_url" value="'.$order_cancel_url.'">
			<input type="hidden" id="order_complete_url" value="'.$order_complete_url.'">

            <span style="display: none;" class="loader"></span>

		</div>';
    }
}
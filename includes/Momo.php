<?php

/**
 *  MTN Mobile Money Class
 *
 * Generate token
 * Makes Collection requests
 * Get transactions
 *
 * @class Momo
 */

class Momo
{
    private $user_id;
    private $api_key;
    private $subscription_key;
    private $environment = "mtnliberia";
    private $failed_status = "FAILED";
    private $success_status = "SUCCESSFUL";
    private $pending_status = "PENDING";

    public function __construct($user_id, $api_key, $subscription_key)
    {
        $this->user_id = $user_id;
        $this->api_key = $api_key;
        $this->subscription_key = $subscription_key;
    }

    /**
     * Momo BASE URL to request token
     *
     * @var string
     */
    protected $base_url = "https://proxy.momoapi.mtn.com/collection";

    protected function authorization_string()
    {
        return base64_encode($this->user_id.":".$this->api_key);
    }

    public function get_token()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.$this->authorization_string(),
            'Ocp-Apim-Subscription-Key' => $this->subscription_key,
            'X-Target-Environment' => $this->environment
        ];

        $response = wp_remote_post($this->base_url . "/token/", [
            'method' => 'POST',
            'headers' => $headers,
            'timeout' => 60
        ]);

        if (!$this->valid_response($response) || !$this->valid_status_code(200, $response))
            return;

        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        return $json['access_token'];
    }

    public function collect($msisdn, $amount, $currency, $external_id)
    {

        $token = $this->get_token();

        if ($token)
        {
            $payload = [
                'amount' => $amount,
                'externalId' => $external_id,
                'currency' => strtoupper($currency),
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $this->format_msisdn($msisdn)
                ]
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'Ocp-Apim-Subscription-Key' => $this->subscription_key,
                'X-Target-Environment' => $this->environment,
                'X-Reference-Id' => $external_id
            ];

            $response = wp_remote_post($this->base_url."/v1_0/requesttopay", [
                'method' => 'POST',
                'headers' => $headers,
                'body' => wp_json_encode( $payload ),
                'timeout' => 60
            ]);

            if (!$this->valid_response($response) || !$this->valid_status_code(202, $response))
                return;

            return ['success' => true];
        }

        return;
    }

    public function get_transaction($transaction_id)
    {

        $token = $this->get_token();

        if ($token)
        {

            $response = wp_remote_get($this->base_url."/v1_0/requesttopay/".$transaction_id, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Ocp-Apim-Subscription-Key' => $this->subscription_key,
                    'X-Target-Environment' => $this->environment,
                ],
                'timeout' => 60
            ]);

            if (!$this->valid_response($response) || !$this->valid_status_code(200, $response))
                return;

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode($body, true);

            return $data;
        }

        return;
    }


    protected function valid_status_code($code, $response): bool
    {

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code != $code)
        {
            // Log the error.
            Helper::log('Invalid status code:', $response);

            return false;
        }

        return true;
    }

    protected function valid_response($response): bool
    {

        if (is_wp_error($response)) {

            // Log the error.
            $error_message = $response->get_error_message();

            Helper::log('response error: ', $error_message);

            return false;
        }

        return true;
    }

    /**
     * Prepends '231' to a msisdn
     *
     * @param $msisdn
     * @return string
    */
    protected function format_msisdn($msisdn): string
    {
        return '231' . substr($msisdn, 1);
    }

    /**
     * @return string
    */
    public function get_failed_status(): string
    {
        return $this->failed_status;
    }

    /**
     * @return string
     */
    public function get_success_status(): string
    {
        return $this->success_status;
    }

    /**
     * @return string
     */
    public function get_pending_status(): string
    {
        return $this->pending_status;
    }
}
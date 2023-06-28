<?php

/**
 * Helper class for the Ponitor Mobile Money Payment Gateway
 *
 * @class Helper
 *
 */
class Helper
{

    private static $id = 'ponitor';
    private static $domain = 'ponitor-woocommerce';

    /**
     * @return string
     */
    public static function get_id(): string
    {
        return self::$id;
    }

    /**
     * @return string
     */
    public static function get_domain(): string
    {
        return self::$domain;
    }

   /**
     * Logs data
     *
     * @param $key -  details about the log
     * @param $data - the actual log
     * @return void
    */
   public static function log($key, $data) {
        error_log( $key.' '. json_encode($data)."\n");
   }


    /**
     * Checks if msisdn is valid
     *
     * @param $msisdn
     * @return bool
     */
   public static function validate_msisdn($msisdn): bool
   {

        if (!empty($msisdn)) {

            $length_valid = strlen($msisdn) === 10;
            $valid_starting_digits = ["088", "055"];
            $first_three_digits = substr($msisdn, 0, 3);
            $first_three_digits_valid = in_array($first_three_digits, $valid_starting_digits);

            if ($length_valid && $first_three_digits_valid) return true;
        }

        self::log('Invalid msisdn', [
            'msisdn' => $msisdn,
            'valid_starting_digits' => $valid_starting_digits,
            'first_three_digits' => $first_three_digits
        ]);

        return false;
   }
}
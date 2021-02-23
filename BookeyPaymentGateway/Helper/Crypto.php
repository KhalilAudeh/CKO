<?php

namespace Bookey\BookeyPaymentGateway\Helper;

/**
 * Created by PhpStorm.
 * User: jimbur
 * Date: 27/10/2016
 * Time: 5:38 PM
 */
class Crypto 
{
    /**
     * generates a hmac based on an associative array and an api key
     * @param $query array
     * @param $api_key string
     * @return string
     */
    public static function generateSignature($query, $api_key, $api_public_key )
    {
        $clear_text = '';
        ksort($query);
        foreach ($query as $key => $value) {
            $clear_text .= $key . $value;
        }
        $hash = hash_hmac( "sha256", $clear_text, $api_key, $api_public_key);
        $hash = str_replace('-', '', $hash);
        return $hash;
    }

    /**
     * validates and associative array that contains a hmac signature against an api key
     * @param $query array
     * @param $api_key string
     * @return bool
     */
    public static function isValidSignature($query, $api_key)
    {

         $actualSignature = $query['merchantTxnId'];
        // unset($query['x_signature']);

        // $expectedSignature = self::generateSignature($query, $api_key);
        // echo "<pre>";print_r($expectedSignature);
        // echo "<pre>";print_r($api_key);
        // echo "<pre>";print_r($actualSignature);exit;
        return $actualSignature;
    }
}
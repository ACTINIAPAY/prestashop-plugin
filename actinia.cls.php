<?php
/**
 * 2021 Actinia
 *
 *  @author ACTINIA
 *  @copyright  2021 Actinia
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  @version    1.0.0
 */

class ActiniaCls
{
    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';
    const ORDER_EXPIRED = 'expired';

    const ORDER_SEPARATOR = '#';

    const SIGNATURE_SEPARATOR = '|';

    const URL = "https://api.actinia.eu/api/checkout/redirect/";


    public static function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function ($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);

        $str = $password;
        foreach ($data as $v) {
            $str .= self::SIGNATURE_SEPARATOR . $v;
        }

        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }

    public static function isPaymentValid($actiniaSettings, $response)
    {
        if ($actiniaSettings['merchant_id'] != $response['merchant_id']) {
            return 'An error has occurred during payment. Merchant data is incorrect.';
        }

        if ($response['order_status'] == self::ORDER_DECLINED) {
            return 'An error has occurred during payment. Order is declined.';
        }

        $responseSignature = $response['signature'];
        if (isset($response['response_signature_string'])) {
            unset($response['response_signature_string']);
        }
        if (isset($response['signature'])) {
            unset($response['signature']);
        }
        if (self::getSignature($response, $actiniaSettings['secret_key']) != $responseSignature) {
            return 'An error has occurred during payment. Signature is not valid.';
        }

        if ($response['order_status'] != self::ORDER_APPROVED) {
            return 'An error has occurred during payment. Order is expired.';
        }

        return true;
    }
}
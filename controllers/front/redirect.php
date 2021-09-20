<?php
/**
 * 2021 Actinia
 *
 *  @author ACTINIA
 *  @copyright  2021 Actinia
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  @version    1.0.0
 */

require_once(dirname(__FILE__) . '../../../actinia.php');
require_once(dirname(__FILE__) . '../../../actinia.cls.php');
require_once(dirname(__FILE__) . '/ActiniaApi.php');

class ActiniaRedirectModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cookie = $this->context->cookie;
        $link = $this->context->link;

        $language = Language::getIsoById((int)$cookie->id_lang);
        $language = (!in_array($language, ['uk', 'en', 'ru', 'lv', 'fr'])) ? '' : $language;

        $payCurrency = $this->context->currency;
        $cart = $this->context->cart;

        $actinia = $this->module;
        $total = $cart->getOrderTotal();

        $actinia->validateOrder((int)$cart->id, _PS_OS_PREPARATION_, $total, $actinia->displayName);

//        $fields = [
//            'order_id' => $actinia->currentOrder . ActiniaCls::ORDER_SEPARATOR . time(),
//            'merchant_id' => $actinia->getOption('merchant'),
//            'order_desc' => '#' . $actinia->currentOrder,
//            'amount' => round($total * 100),
//            'currency' => $payCurrency->iso_code,
//            'server_callback_url' => $link->getModuleLink('actinia', 'callback'),
//            'response_url' => $link->getModuleLink('actinia', 'result'),
//            'sender_email' => $this->context->customer->email ? $this->context->customer->email : ''
//        ];
        $_sender_email = $this->context->customer->email ? $this->context->customer->email : '';
        $_firstname = $_lastname = '';
        if ($this->context->customer and !$actinia->getOption('form_method')) {
            $_firstname = $this->context->customer->firstname ? $this->context->customer->firstname : '';
            $_lastname = $this->context->customer->lastname ? $this->context->customer->lastname : '';
        }
        if ($language !== '') {
            $_lang = Tools::strtolower($language);
        }

        $_invoice = new Address((int)$this->context->cart->id_address_invoice);
        $_phone = ($_invoice->phone) ? $_invoice->phone : $_invoice->phone_mobile;

        // -------------------------------------------------------------------------------------------------------------
        $actiniaCls = new ActiniaApi();
        $paymentData = [
            'merchantId'        => $actinia->getOption('merchant'),
            'clientName'        => sprintf('%s %s', $_firstname, $_lastname),
            'clientEmail'       => $_sender_email,
            'clientPhone'       => $actiniaCls->preparePhone($_phone),
            'description'       => '#' . $actinia->currentOrder,
            'amount'            => $actiniaCls->getAmount($total),
            'currency'          => strtoupper($payCurrency->iso_code),
//            'clientAccountId'   => $method->clientaccountid,
            'returnUrl'         => $link->getModuleLink('actinia', 'result'),
            'externalID'        => $actinia->currentOrder . ActiniaCls::ORDER_SEPARATOR . time(),
            'locale'            => strtoupper($_lang),
            'expiresInMinutes'  => "45",
            'expireType'        => "minutes",
            'feeCalculationType' => "INNER",
            'time'              => "45",
            'withQR'            => "YES",
        ];
        // -------------------------------------------------------------------------------------------------------------

        $resData = $actiniaCls->setClientCodeName($actinia->getOption('clientcodename'))
            ->setPrivateKey($actinia->getOption('privatekey'))
            ->chkPublicKey()
            ->invoiceCreate($paymentData)
            ->isSuccessException()
            ->getData();

//        die('<pre>' . print_r($resData['link'], true) . '</pre>');

//        if (!$actinia->getOption('form_method')) {
//            $checkoutUrl = $this->generateActiniaUrl($fields);
//            if ($checkoutUrl['result']) {
//                Tools::redirect($checkoutUrl['url']);
//            } else {
//                die($checkoutUrl['message']);
//            }
//        } else {
            $fields['actinia_url'] = $resData['link'];
            $this->context->smarty->assign($fields);
            $this->setTemplate('redirect.tpl');
//        }
    }

    public function generateActiniaUrl($payment_oplata_args)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.actinia.eu/api/checkout/url/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['request' => $payment_oplata_args]));
        $result = json_decode(curl_exec($ch));
        if ($result->response->response_status == 'failure') {
            $out = [
                'result' => false,
                'message' => $result->response->error_message
            ];
        } else {
            $out = [
                'result' => true,
                'url' => $result->response->checkout_url
            ];
        }
        return $out;
    }
}

<?php

class ActiniaApi
{
    const URL_TEST = 'https://api.clients.sandbox.actinia.tech/';
    const URL_PROD = 'https://api.clients.actinia.com.ua/';

    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';

    const ORDER_SEPARATOR = '_';

    const SIGNATURE_SEPARATOR = '|';
    const SESSION_PUBLICKEY_NAME = "actinia_publicKey";

    const URL = "https://api.clients.sandbox.actinia.tech/";

    const ENDPOINTS = [
        'invoiceCreate' => 'v1/invoice/create',
        'invoiceGet' => 'v1/invoice/get',
        'invoiceStatus' => 'v1/invoice/status',
        'invoiceCancel' => 'v1/invoice/cancel',

        'publicKeyGet' => 'v1/host/public/get',
    ];

    protected $url = '';
    protected $clientCodeName = '';
    protected $endpoint = null;
    protected $data = [];
    protected $success = true;
    protected $resultData = [];

    protected $hostPublicKey = null;
    protected $isHostPublicKey = true;
    protected $privateKey = null;

    /**
     * Actinia constructor.
     * @param false $is_test
     */
    public function __construct($is_test = false)
    {
        if($is_test)
            $this->url = self::URL_TEST;
        else
            $this->url = self::URL_PROD;
    }

    public function setPrivateKey($val){
        $this->privateKey = $val;
        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function publicKeyGet(){
        try{
            $this->setEndpoint('publicKeyGet');
            $this->data = [];
            $this->isHostPublicKey = false;
            $this->send();
            $_data = $this->resultData;

            if($_data['hostPublicKey'] ?? false){

                $_SESSION[self::SESSION_PUBLICKEY_NAME] = $this->hostPublicKey = $_data['hostPublicKey'];
                $this->isHostPublicKey = true;
                $this->success = true;
            } else {
                $this->success = false;
            }

            return $this;

        } catch (Exception $e){
            throw $e;
        }
    }

    /**
     * @param string $val
     * @return $this
     */
    public function setClientCodeName(string $val){
        $this->clientCodeName = $val;
        return $this;
    }

    /**
     * @param string $val
     * @return $this
     */
    public function setEndpoint(string $val){
        if(isset(self::ENDPOINTS[$val]))
            $this->endpoint = self::ENDPOINTS[$val];
        else
            $this->endpoint = $val;

        return $this;
    }


    /**
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function invoiceCreate(array $data){
        try{
            $this->setEndpoint('invoiceCreate');
            $this->data = $data;
            $this->send();

            $this->success = !empty($this->resultData);
            return $this;

        } catch (Exception $e){
            throw $e;
        }
    }


    /**
     * @return $this
     * @throws Exception
     */
    public function send(){
        try{
            $_data = (array) json_decode($this->sendToApi(), true);

            if(!empty($_data['data']) && !empty($_data['token'])) {
                if($this->isHostPublicKey)
                    $this->chkHostData($_data['data'], $_data['token']);
                $this->resultData = $_data['data'];
            }
            else
                throw new Exception('Empty data');

            return $this;

        } catch (Exception $e){
            throw $e;
        }
    }


    /**
     * @return string
     */
    public function getErrorMsg():string{
        $_code = $this->resultData['errorData']['code'] ?? false;
        $_msg = $this->resultData['error'] ?? 'undefined';
        if($_code)
            $_msg = sprintf('%s: %s', $_code, $this->getMsgErrorByCode($_code));

        return $_msg;
    }

    /**
     * @return bool
     */
    public function isSuccess():bool{
        return $this->success;
    }

    /**
     * @return $this|bool
     * @throws Exception
     */
    public function isSuccessException(){
        if(!$this->success)
            throw new Exception($this->getErrorMsg());

        return $this;
    }

    /**
     * @return array
     */
    public function getData():array{
        return $this->resultData;// ?? [];
    }

    /**
     * @param $telephone
     * @return string
     */
    public function preparePhone($telephone){
        $_originalLength = 12;
        $_templ = '380';
        $phone = preg_replace('/[^\d]/', '', $telephone);
        $_l = $_originalLength - strlen($phone);
        $phone = substr($_templ, 0, $_l) . $phone;
        return $phone;
    }

    /**
     * @param array $data
     * @param string $token
     * @return bool
     * @throws Exception
     */
    protected function chkHostData(array $data, string $token){
        $tokenDecode = (array) json_decode(json_encode($this->JWTdecode($token)), true);
        $res = array_diff($data, $tokenDecode);

        if(!empty($res))
            throw new Exception('Invalid JWT');

        return true;
    }

    /**
     * @param $jwt
     * @return array
     */
    protected function JWTdecode(string $jwt):array{
        return (array) Firebase\JWT\JWT::decode($jwt, $this->hostPublicKey, ['RS256']);
    }

    /**
     * @param string $code
     * @return string
     */
    protected function getMsgErrorByCode(string $code):string{
        $errors = [
            'CS001' => 'Ошибка проверки входных параметров',
            'CS002' => 'Ошибка базы данных',
            'CS003' => 'Клиент не найден',

            'CS006' => 'Невозможно сохранить ключ',
            'CS007' => 'Неверный формат публичного ключа',

            'CAPI002' => 'Your have not loaded your public key. Please load it first.',
            "CAPI003" => "Token payload and request data do not much! Why?",
            'CAPI004' => 'Token validation error',
            'CAPI010' => 'Ошибка проверки входных параметров',
            'CAPI006' => 'Ошибка базы данных',
            'CAPI008' => 'Адрес уже добавлен в список разрешенных',
            'CAPI007' => 'Неизвестная ошибка системы',

            'I001' => 'Ошибка проверки входных параметров',
            'I002' => 'Ошибка базы данных',
            'I003' => 'Указанная валюта не поддерживается',
            'I004' => 'Ошибка получения информации о валюте счета',
            'I005' => 'Ошибка точности указания суммы счета',
            'I006' => 'Мерчант не найден',
            'I007' => 'Ошибка расчета тарифа для счета',
            'I008' => 'Ошибка создания номера счета',
            'I009' => 'Счет не найден',
            'I013' => 'Счета не может быть отменен, так как он был оплачен',
            'I014' => 'Счет не может быть отменен, так как сейчас совершается оплата',
            'I016' => 'Указанная валюта не поддерживается для этого мерчанта',
            'I020' => 'Указанная валюта не совпадает с указанным счетом',
            'I021' => 'Указанный счет клиента не найден',
            'I036' => 'Нет корневого счета для указанной валюты конвертации',
            'I037' => 'Конвертация на указанную валюту невозможна в настоящий момент',
            'I000' => 'Неизвестная ошибка системы',
        ];

        return $errors[$code] ?? 'undefined';
    }

    /**
     * @throws Exception
     */
    protected function sendToApi(){
        try{
            $fields = $this->prepareData();

            $ch = curl_init($this->url . $this->endpoint);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                'Content-Type:application/json'
            ]);

            $response=curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errno= curl_errno($ch);
            curl_close($ch);

            if($http_status !== 200)
                throw new Exception('Server Error. Code: ' . $http_status . ' | ' . $curl_errno);

            return $response;

        } catch (Exception $e){
            $this->resultData = json_decode(($response ?? ''), true);
            throw new Exception($this->getErrorMsg());
        }
    }

    /**
     * @return false|string
     * @throws Exception
     */
    protected function prepareData(): string{
        try {
            $_obj = (object) $this->data;

            return json_encode([
                "auth" => [
                    "clientCodeName" => $this->clientCodeName,
                    "token" => $this->JWTencode($_obj),
                ],
                "data" => $_obj,
            ]);

        } catch (Exception $e){
            throw $e;
        }
    }

    /**
     * @param $data
     * @return string
     * @throws Exception
     */
    protected function JWTencode($data){
        try{
            include_once(dirname(__FILE__) . "/jwt/src/JWT.php");
            return Firebase\JWT\JWT::encode($data, $this->privateKey, 'RS256');

        } catch (Exception $e){
            throw $e;
        }
    }


    /**
     * @return $this
     * @throws Exception
     */
    public function chkPublicKey(){
        try{
            if(!empty($_SESSION[self::SESSION_PUBLICKEY_NAME]))
                $this->hostPublicKey = $_SESSION[self::SESSION_PUBLICKEY_NAME];
            else
                $this->publicKeyGet();

            return $this;

        } catch (Exception $e){
            throw $e;
        }
    }


    /**
     * @param $data
     * @param false $isData
     * @return array
     */
    public function decodeJsonObjToArr($data, $isData = false):array{
        if($isData)
            $_d = $data;
        else
            $_d = (array)json_decode(html_entity_decode($data), true);

        $_d = (array)json_decode(json_encode($_d, JSON_UNESCAPED_UNICODE), true);

        $res = [];
        foreach ($_d as $key => $item) {
            if(gettype($item) === 'object') {
                $res[$key] = (array)$item;
            }
            else {
                $res[$key] = $item;
            }
        }

        return $res;
    }


    /**
     * @param $_data
     * @return array
     * @throws Exception
     */
    public function isPaymentValid($_data){
        try{
            if(!empty($_data['data']) && !empty($_data['token'])) {
                if($this->isHostPublicKey)
                    $this->chkHostData((array)$_data['data'], (string)$_data['token']);
                $this->resultData = $_data['data'];
            }
            else
                throw new Exception('Empty data');

            return (array)$_data['data'];

        } catch (Exception $e){
            throw new Exception('isPaymentValid: ' . $e->getMessage());
        }

    }

    /**
     * @param $order
     * @return string
     */
    public function getAmount($sum)
    {
        return str_replace(',','.',(string) round($sum, 2));
    }
}

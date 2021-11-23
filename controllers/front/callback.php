<?php
/**
 * 2021 Actinia
 *
 *  @author ACTINIA
 *  @copyright  2021 Actinia
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  @version    1.0.0
 */

require_once __DIR__ . '/../../ActiniaApi.php';
class ActiniaCallbackModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;
    public $display_header = false;
    public $display_footer = false;
    public $ssl = true;

    protected $merchant, $clientcodename, $privatekey, $testmode, $success_status_id,
        $settingsList = [
        'ACTINIA_MERCHANT',
        'ACTINIA_CLIENTCODENAME',
        'ACTINIA_PRIVATEKEY',
        'ACTINIA_TESTMODE',
        'ACTINIA_SUCCESS_STATUS_ID',
    ];

    public function __construct()
    {
        parent::__construct();

        $config = Configuration::getMultiple($this->settingsList);

        if (!empty($config['ACTINIA_MERCHANT'])) {
            $this->merchant = $config['ACTINIA_MERCHANT'];
        }
        if (!empty($config['ACTINIA_CLIENTCODENAME'])) {
            $this->clientcodename = $config['ACTINIA_CLIENTCODENAME'];
        }
        if (!empty($config['ACTINIA_PRIVATEKEY'])) {
            $this->privatekey = $config['ACTINIA_PRIVATEKEY'];
        }
        if (!empty($config['ACTINIA_TESTMODE'])) {
            $this->testmode = $config['ACTINIA_TESTMODE'];
        }
        if (!empty($config['ACTINIA_SUCCESS_STATUS_ID'])) {
            $this->success_status_id = $config['ACTINIA_SUCCESS_STATUS_ID'];
        }
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $callback = [];

        try {
            $callback = (array)json_decode(file_get_contents("php://input", true));
            if (empty($callback)) {
                throw new Exception('callback empty');
            }

            $actiniaApi = new ActiniaApi($this->testmode);
            $callback = $actiniaApi->decodeJsonObjToArr($callback, true);
            $payment = $actiniaApi
                ->setClientCodeName($this->clientcodename)
                ->setPrivateKey($this->privatekey)
                ->chkPublicKey()
                ->isPaymentValid($callback);

            if ($payment['merchantId'] !== $this->merchant) {
                throw new Exception('not valid merchantId (|' . $payment['merchantId'] . ' | ' . $this->merchant . '|)');
            }

            list($externalId,) = explode(ActiniaApi::ORDER_SEPARATOR, $payment['externalId']);
            $order = new Order($externalId);

            $history = new OrderHistory();
            $history->id_order = (int)$order->id;

            $history->changeIdOrderState((int)$this->success_status_id, (int)($order->id));

            exit('OK');

        } catch (Exception $e) {
            exit(get_class($e) . ': ' . $e->getMessage());
        }
    }
}

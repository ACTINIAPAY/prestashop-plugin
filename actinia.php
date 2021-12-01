<?php
/**
 * 2021 Actinia
 *
 * @author ACTINIA
 * @copyright  2021 Actinia
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    1.0.0
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/ActiniaApi.php');

class Actinia extends PaymentModule
{
    private $_html = '';
    private $_postErrors = [];


    private $settingsList = [
        'ACTINIA_MDESCRIPTION',
        'ACTINIA_MERCHANT',
        'ACTINIA_CLIENTCODENAME',
        'ACTINIA_PRIVATEKEY',
        'ACTINIA_TESTMODE',
        'ACTINIA_SUCCESS_STATUS_ID',
    ];

    protected $merchant, $clientcodename, $privatekey, $testmode, $success_status_id;

    public function __construct()
    {
        $this->name = 'actinia';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'ACTINIA';
        $this->need_instance = 0;

        $config = Configuration::getMultiple($this->settingsList);

        if (!empty($config['ACTINIA_MDESCRIPTION'])) {
            $this->mdescription = $config['ACTINIA_MDESCRIPTION'];
        }

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

        parent::__construct();
        $this->bootstrap = true;

        $this->displayName = $this->trans('ACTINIA online payments', [], 'Modules.Actinia.Admin');
        $this->description = $this->trans('Payments via ACTINIA.', [], 'Modules.Actinia.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to delete these details?', [], 'Modules.Actinia.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            ;
    }

    public function uninstall()
    {
        return Configuration::deleteByName('ACTINIA_MERCHANT')
            && Configuration::deleteByName('ACTINIA_CLIENTCODENAME')
            && Configuration::deleteByName('ACTINIA_PRIVATEKEY')
            && parent::uninstall()
            ;
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            if (!Tools::getValue('ACTINIA_MERCHANT')) {
                $this->_postErrors[] = $this->trans('The "MERCHANT" field is required.', [], 'Modules.Actinia.Admin');
            } elseif (!Tools::getValue('ACTINIA_CLIENTCODENAME')) {
                $this->_postErrors[] = $this->trans('The "CLIENTCODENAME" field is required.', [], 'Modules.Actinia.Admin');
            } elseif (!Tools::getValue('ACTINIA_PRIVATEKEY')) {
                $this->_postErrors[] = $this->trans('The "PRIVATEKEY" field is required.', [], 'Modules.Actinia.Admin');
            }
        }
    }

    private function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->name)) {

            $mdescription = strval(Tools::getValue('ACTINIA_MDESCRIPTION'));
            Configuration::updateValue('ACTINIA_MDESCRIPTION', $mdescription);
            $this->mdescription = $mdescription;

            $merchant = strval(Tools::getValue('ACTINIA_MERCHANT'));
            Configuration::updateValue('ACTINIA_MERCHANT', $merchant);
            $this->merchant = $merchant;

            $clientcodename = strval(Tools::getValue('ACTINIA_CLIENTCODENAME'));
            Configuration::updateValue('ACTINIA_CLIENTCODENAME', $clientcodename);
            $this->clientcodename = $clientcodename;

            $privatekey = strval(Tools::getValue('ACTINIA_PRIVATEKEY'));
            Configuration::updateValue('ACTINIA_PRIVATEKEY', $privatekey);
            $this->privatekey = $privatekey;

            $testmode = strval(Tools::getValue('ACTINIA_TESTMODE'));
            Configuration::updateValue('ACTINIA_TESTMODE', $testmode);
            $this->testmode = $testmode;

            $success_status_id = strval(Tools::getValue('ACTINIA_SUCCESS_STATUS_ID'));
            Configuration::updateValue('ACTINIA_SUCCESS_STATUS_ID', $success_status_id);
            $this->success_status_id = $success_status_id;

        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Actinia.Success'));
    }

    private function _displayCheck()
    {
        return $this->display(__FILE__, './views/templates/hook/infos.tpl');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->_html = '';

        if (((bool)Tools::isSubmit('submit'.$this->name)) == true) {
            $this->_postValidation();
            if (!sizeof($this->_postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $err .= $this->displayError($err);
                }
            }
        }

        $this->_html .= $this->_displayCheck();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    /**
     * @param $params
     * @return PaymentOption[]|void
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText(!empty($this->mdescription) ? $this->mdescription : $this->trans('Pay by Actinia', [], 'Modules.Actinia.Admin'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            ->setAdditionalInformation($this->fetch('module:actinia/views/templates/front/payment_infos.tpl'));

        return [$newOption];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $cookie = $this->context->cookie;
        $link = $this->context->link;

        // LANGUAGE
        $language = Language::getIsoById((int)$cookie->id_lang);
        $language = (!in_array($language, ['uk', 'en', 'ru'])) ? '' : $language;

        if ($language !== '') {
            $_lang = Tools::strtolower($language);
        }

        // CUSTOMER NAME
        $_firstname = $this->context->customer->firstname ? $this->context->customer->firstname : '';
        $_lastname = $this->context->customer->lastname ? $this->context->customer->lastname : '';

        // CUSTOMER EMAIL
        $_sender_email = $this->context->customer->email ? $this->context->customer->email : '';

        // CURRENCY
        $payCurrency = $this->context->currency;

        // TOTAL
        $total = $params['order']->getOrdersTotalPaid() - $params['order']->getTotalPaid();


        $actiniaCls = new ActiniaApi($this->testmode);
        $paymentData = [
            'merchantId' => $this->merchant,
            'clientName' => sprintf('%s %s', $_firstname, $_lastname),
            'clientEmail' => $_sender_email,
            'description' => $this->context->shop->name . ' #' . $params['order']->reference,
            'amount' => $actiniaCls->getAmount($total),
            'currency' => strtoupper($payCurrency->iso_code),
            'returnUrl' => Tools::getHttpHost(true).__PS_BASE_URI__,
//            'returnUrl' => $link->getModuleLink('actinia', 'result') . '?order=' . $params['order']->reference,
            'externalId' => $params['order']->id . $actiniaCls::ORDER_SEPARATOR . time(),
            'locale' => strtoupper($_lang),
            'expiresInMinutes' => "45",
            'feeCalculationType' => "INNER",
            'time' => "45",
            'withQR' => "YES",
            'cb' => [
                'serviceName' => 'InvoiceService',
                'serviceAction' => 'invoiceGet',
                'serviceParams' => [
                    'callbackUrl' => $link->getModuleLink('actinia', 'callback'),
                ]
            ]
        ];

        // CUSTOMER PHONE
        $_invoice = new Address((int)$this->context->cart->id_address_invoice);
        $_phone = ($_invoice->phone) ? $_invoice->phone : $_invoice->phone_mobile;
        if(!empty($_phone))
            $paymentData['clientPhone'] = $actiniaCls->preparePhone($_phone);

        $resData = $actiniaCls->setClientCodeName($this->clientcodename)
            ->setPrivateKey($this->privatekey)
            ->chkPublicKey()
            ->invoiceCreate($paymentData)
            ->isSuccessException()
            ->getData();

        $this->smarty->assign([
            'resData' => $resData,
        ]);
        return $this->fetch('module:actinia/views/templates/hook/payment_return.tpl');
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function renderForm()
    {
        global $cookie;

        $options = [];

        foreach (OrderState::getOrderStates($cookie->id_lang) as $state) {  // getting all Prestashop statuses
            if (empty($state['module_name'])) {
                $options[] = ['status_id' => $state['id_order_state'], 'name' => $state['name'] . " [ID: $state[id_order_state]]"];
            }
        }

        $fields_form[0] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Please specify the Actinia account details for customers'),
                    'icon' => 'icon-cogs',
                ],

                'input' => [
                    [
                        'col' => 4,
                        'type' => 'switch',
                        'prefix' => '<i class="icon icon-form"></i>',
                        'name' => 'ACTINIA_TESTMODE',
                        'label' => $this->l('Test mode'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ],
                    ],
                    [
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'ACTINIA_MDESCRIPTION',
                        'label' => $this->l('Description'),
                    ],
                    [
                        'col' => 4,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('Enter a merchant id'),
                        'name' => 'ACTINIA_MERCHANT',
                        'label' => $this->l('Merchant ID'),
                    ],
                    [
                        'col' => 4,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'ACTINIA_CLIENTCODENAME',
                        'desc' => $this->l('Enter ClientCodeName'),
                        'label' => $this->l('ClientCodeName'),
                    ],
                    [
                        'col' => 4,
                        'type' => 'textarea',
                        'rows' => '15',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'ACTINIA_PRIVATEKEY',
                        'desc' => $this->l('Enter PrivateKey'),
                        'label' => $this->l('PrivateKey'),
                    ],
                    [
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'ACTINIA_SUCCESS_STATUS_ID',
                        'label' => $this->l('Status after success payment'),
                        'options' => [
                            'query' => $options,
                            'id' => 'status_id',
                            'name' => 'name'
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];


        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = true;
        $helper->name_controller = $this->name;
        $helper->title = $this->displayName;
        $helper->toolbar_scroll = true;

        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
//        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;

        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value['ACTINIA_MDESCRIPTION'] = $this->mdescription;
        $helper->fields_value['ACTINIA_MERCHANT'] = $this->merchant;
        $helper->fields_value['ACTINIA_CLIENTCODENAME'] = $this->clientcodename;
        $helper->fields_value['ACTINIA_PRIVATEKEY'] = $this->privatekey;
        $helper->fields_value['ACTINIA_TESTMODE'] = $this->testmode;
        $helper->fields_value['ACTINIA_SUCCESS_STATUS_ID'] = $this->success_status_id;

        //-----
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];
        return $helper->generateForm($fields_form);

    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int) ($cart->id_currency));
        $currencies_module = $this->getCurrency((int) $cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }
}

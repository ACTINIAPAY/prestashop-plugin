<?php
/**
 * 2021 Actinia
 *
 * @author ACTINIA
 * @copyright  2021 Actinia
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version    1.0.0
 */

class Actinia extends PaymentModule
{
    private $settingsList = [
        'ACTINIA_MERCHANT',
        'ACTINIA_SECRET_KEY',
        'ACTINIA_CLIENTCODENAME',
        'ACTINIA_PRIVATEKEY',
        'ACTINIA_FORM_METHOD',
        'ACTINIA_BACK_REF'
    ];

    private $_postErrors = [];

    public function __construct()
    {
        $this->name = 'actinia';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'ACTINIA';
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.5', 'max' => _PS_VERSION_];

        parent::__construct();
        $this->displayName = $this->l('ACTINIA online payments');
        $this->description = $this->l('Payments via ACTINIA');
        $this->confirmUninstall = $this->l('Are you want to remove the module?');
    }

    public function install()
    {
        if (!parent::install() or !$this->registerHook('payment')) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        foreach ($this->settingsList as $val) {
            if (!Configuration::deleteByName($val)) {
                return false;
            }
        }
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    public function getOption($name)
    {
        return Configuration::get("ACTINIA_" . Tools::strtoupper($name));
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $err = '';
        if (((bool)Tools::isSubmit('submitActiniaModule')) == true) {
            $this->_postValidation();
            if (!sizeof($this->_postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $err .= $this->displayError($err);
                }
            }
        }

        return $err . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitActiniaModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        global $cookie;

        $options = [];

        foreach (OrderState::getOrderStates($cookie->id_lang) as $state) {  // getting all Prestashop statuses
            if (empty($state['module_name'])) {
                $options[] = ['status_id' => $state['id_order_state'], 'name' => $state['name'] . " [ID: $state[id_order_state]]"];
            }
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Please specify the Actinia account details for customers'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
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
                        'col' => 4,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'ACTINIA_SECRET_KEY',
                        'desc' => $this->l('Enter a secret key'),
                        'label' => $this->l('Secret key'),
                    ],
                    [
                        'type' => 'select',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name' => 'ACTINIA_SUCCESS_STATUS_ID',
                        'desc' => $this->l('Enter a secret key'),
                        'label' => $this->l('Status after success payment'),
                        'options' => [
                            'query' => $options,
                            'id' => 'status_id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'col' => 4,
                        'type' => 'switch',
                        'prefix' => '<i class="icon icon-form"></i>',
                        'name' => 'ACTINIA_FORM_METHOD',
                        'label' => $this->l('Use form method ?'),
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
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            'ACTINIA_MERCHANT' => Configuration::get('ACTINIA_MERCHANT', null),
            'ACTINIA_CLIENTCODENAME' => Configuration::get('ACTINIA_CLIENTCODENAME', null),
            'ACTINIA_PRIVATEKEY' => Configuration::get('ACTINIA_PRIVATEKEY', null),
            'ACTINIA_SECRET_KEY' => Configuration::get('ACTINIA_SECRET_KEY', null),
            'ACTINIA_FORM_METHOD' => Configuration::get('ACTINIA_FORM_METHOD', null),
            'ACTINIA_SUCCESS_STATUS_ID' => Configuration::get('ACTINIA_SUCCESS_STATUS_ID', null),
        ];
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    private function _postValidation()
    {
        if (Tools::isSubmit('submitActiniaModule')) {
            if (empty(Tools::getValue('ACTINIA_MERCHANT'))) {
                $this->_postErrors[] = $this->l('Merchant ID is required.');
            }
            if (empty(Tools::getValue('ACTINIA_CLIENTCODENAME'))) {
                $this->_postErrors[] = $this->l('ClientCodeName is required.');
            }
            if (empty(Tools::getValue('ACTINIA_PRIVATEKEY'))) {
                $this->_postErrors[] = $this->l('PrivateKey is required.');
            }
        }
    }

    /**
     * @param $params
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->_checkCurrency($params['cart'])) {
            return;
        }

        $this->context->smarty->assign([
            'this_path' => $this->_path,
            'id' => (int)$params['cart']->id,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'this_description' => $this->l('Pay via Actinia')
        ]);

        return $this->display(__FILE__, 'views/templates/front/actinia.tpl');
    }

    private function _checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

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

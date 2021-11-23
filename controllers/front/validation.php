<?php

class ActiniaValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if (!($this->module instanceof Actinia)) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'actinia') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('This payment method is not available.', [], 'Modules.Actinia.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $mailVars = [
            '{check_name}' => Configuration::get('CHEQUE_NAME'),
            '{check_address}' => Configuration::get('CHEQUE_ADDRESS'),
            '{check_address_html}' => str_replace("\n", '<br />', Configuration::get('CHEQUE_ADDRESS')), ];

        $this->module->validateOrder(
            (int) $cart->id,
            (int) Configuration::get('PS_OS_CHEQUE'),
            $total,
            $this->module->displayName,
            null,
            $mailVars,
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int) $cart->id . '&id_module=' . (int) $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
    }
}

<?php
/**
 * 2021 Actinia
 *
 *  @author ACTINIA
 *  @copyright  2021 Actinia
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  @version    1.0.0
 */

class ActiniaResultModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->context->smarty->assign([
            'order' => $_GET['order'],
        ]);
        $this->setTemplate('module:actinia/views/templates/front/result.tpl');

    }
}

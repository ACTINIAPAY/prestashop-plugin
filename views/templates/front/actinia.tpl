{*
* 2021 Actinia
*
*  @author ACTINIA
*  @copyright  2021 Actinia
*  @version  1.0.0
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class="" style="background: url({$this_path|escape:'html' nofilter}views/img/logo.png) 11px 31px no-repeat #fbfbfb; background-size: 75px" href="{$link->getModuleLink('actinia', 'redirect', ['id_cart' => {$id}])|escape:'html'}"
               title="{l s='Pay actinia' mod='actinia'}">
                {$this_description|escape:'htmlall':'UTF-8'}
            </a>
        </p>
    </div>
</div>
{*
* 2021 Actinia
*
*  @author ACTINIA
*  @copyright  2021 Actinia
*  @version  1.0.0
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{nocache}
    <p class="payment_module">
        {l s='Wait for redirection, or submit form' mod='actinia'}
        <br/>
        <br/>
        <button style="outline: none;
    border-radius: 7px;
    text-decoration: none;
    box-sizing: content-box;
    width: 200px;
    text-transform: uppercase;
    font-weight: 700;
    padding: 0;
    color: #fcfcfc!important;
    border-color: #62ba46;
    background: #62ba46;
    border-width: 10px;
    border-style: solid;
    border-image-outset: 4px;" onclick="javascript:$('#actinia').submit();" title="{l s='Pay via Actinia' mod='actinia'}">
            {l s='To checkout' mod='actinia'}
        </button>
    </p>
    <form id="actinia" method="get" action="{$actinia_url|escape:'htmlall'}">
        {*<input type="hidden" name="order_id" value="{$order_id|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="merchant_id" value="{$merchant_id|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="order_desc" value="{$order_desc|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="amount" value="{$amount|escape:'htmlall'}"/>
        <input type="hidden" name="currency" value="{$currency|escape:'htmlall'}"/>
        <input type="hidden" name="server_callback_url" value="{$server_callback_url|escape:'htmlall'}"/>
        <input type="hidden" name="response_url" value="{$response_url|escape:'htmlall'}"/>
        <input type="hidden" name="lang" value="{$lang|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="sender_email" value="{$sender_email|escape:'htmlall':'UTF-8'}"/>
        <input type="hidden" name="signature" value="{$signature|escape:'htmlall':'UTF-8'}"/>*}
        <input type="hidden" value="{l s='Оплатить' mod='actinia'}">
    </form>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#actinia').submit();
        });
    </script>
{/nocache}
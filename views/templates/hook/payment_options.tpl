
<form method="post" action="{$action}">
    <P>This is the checkout template of our payment module.</P>
    <P>We can add payment instructions and form fields to it.</P>
</form>


<p class="payment_module">
    <a href="{$link->getModuleLink('actinia', 'redirect', ['id_cart' => {$id}])}" title="{l s='Pay actinia' mod='actinia'}">
        <img src="{$this_path}actinia.png" />{l s='Pay actinia' mod='actinia'}
    </a>
</p>
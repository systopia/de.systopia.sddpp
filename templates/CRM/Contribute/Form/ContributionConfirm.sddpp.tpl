{*-------------------------------------------------------+
| SYSTOPIA SEPA Direct Debit Payment Processor           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de                                 |
| Development based on org.project60.sepapp              |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

{* calculate a more user friendly display of the interval *}
{if $is_recur}
  {if $frequency_unit eq 'month'}
    {if $frequency_interval eq 1}
      {capture assign=frequency_words}{ts domain="org.project60.sepa"}monthly{/ts}{/capture}
    {elseif $frequency_interval eq 3}
      {capture assign=frequency_words}{ts domain="org.project60.sepa"}quarterly{/ts}{/capture}
    {elseif $frequency_interval eq 6}
      {capture assign=frequency_words}{ts domain="org.project60.sepa"}semi-annually{/ts}{/capture}
    {elseif $frequency_interval eq 12}
      {capture assign=frequency_words}{ts domain="org.project60.sepa"}annually{/ts}{/capture}
    {else}
      {capture assign=frequency_words}{ts 1=$frequency_interval domain="org.project60.sepa"}every %1 months{/ts}{/capture}
    {/if}
  {elseif $frequency_unit eq 'year'}
    {if $frequency_interval eq 1}
      {capture assign=frequency_words}{ts domain="org.project60.sepa"}annually{/ts}{/capture}
    {else}
      {capture assign=frequency_words}{ts 1=$frequency_interval domain="org.project60.sepa"}every %1 years{/ts}{/capture}
    {/if}
  {else}
    {capture assign=frequency_words}{ts domain="org.project60.sepa"}on an irregular basis{/ts}{/capture}
  {/if}
{/if}

<input type="hidden" name="account_holder" value="{$account_holder}" />
<input type="hidden" name="bank_name"      value="{$bank_name}"      />

<div id="sepa-new-amount-display" class="display-block">
  <p id="sepa-confirm-text-amount">{ts domain="org.project60.sepa"}Total Amount{/ts}: <strong>{$amount|crmMoney:$currencyID}</strong></p>
  {if $is_recur}
  <p id="sepa-confirm-text-recur"><strong>{ts 1=$frequency_words domain="org.project60.sepa"}I want to contribute this amount %1.{/ts}</strong></p>
  {/if}

  {if $bank_account_number}
  <p id="sepa-confirm-text-account">{ts domain="org.project60.sepa"}This payment will be debited from the following account:{/ts}</p>
  <table class="sepa-confirm-text-account-details display" id="sepa-confirm-text-account-details">
    {if $account_holder}<tr><td>{ts domain="org.project60.sepa"}Account Holder{/ts}</td> <td>{$account_holder}</td> </tr>{/if}
    <tr><td>{ts domain="org.project60.sepa"}IBAN{/ts}</td> <td>{$bank_account_number}</td> </tr>
    <tr><td>{ts domain="org.project60.sepa"}BIC{/ts}</td>  <td>{$bank_identification_number}</td>  </tr>
    {if $bank_name}<tr><td>{ts domain="org.project60.sepa"}Bank Name{/ts}</td> <td>{$bank_name}</td> </tr>{/if}
    {if $mandate_reference}<tr><td>{ts domain="org.project60.sepa"}Mandate Reference{/ts}</td> <td>{$mandate_reference}</td> </tr>{/if}
  </table>
  {/if}
</div>

<script type="text/javascript">
cj("div.amount_display-group > div.display-block").replaceWith(cj("#sepa-new-amount-display"));
cj("div.credit_card-group > div.display-block").remove();
cj("div.credit_card-group > div.header-dark").remove();
</script>


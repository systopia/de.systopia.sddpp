{*-------------------------------------------------------+
| SYSTOPIA SEPA Direct Debit Payment Processor           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de                                 |
| Development based on de.systopia.sddpp              |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+-------------------------------------------------------*}
{crmScope extensionKey='de.systopia.sddpp'}

{* Create a link to the settings page *}
{capture assign=sepa_settings_link}{crmURL p="civicrm/admin/setting/sepa"}{/capture}

{* Create creditors dropdown for pp *}
{if $creditors}
    <select id="creditor_id" name="user_name">
        {foreach from=$creditors item=creditor key=id}
            <option value="{$id}" {if $id eq $user_name}selected{/if}>{$creditor.name}&nbsp;[{$id}]</option>
        {/foreach}
    </select>
{else}
    <span id="creditor_id"><p><strong>{ts domain="de.systopia.sddpp"}No creditor found! Please create a creditor on the
                    <a href="{$sepa_settings_link}">SEPA settings page</a>
                    .{/ts}</strong></p></span>
{/if}

{* Create creditors dropdown help *}
<a id='creditor_id_help'
   onclick='CRM.help("{ts domain="de.systopia.sddpp"}Creditor{/ts}", {literal}{"id":"id-creditor-help","file":"CRM\/Admin\/Form\/PaymentProcessor/SDD"}{/literal}); return false;'
   href="#" title="{ts domain="de.systopia.sddpp"}Help{/ts}" class="helpicon">&nbsp;</a>


{* Create creditors dropdown for test pp *}
{if $creditors}
    <select id="test_creditor_id" name="test_user_name">
        {foreach from=$test_creditors item=creditor key=id}
            <option value="{$id}" {if $id eq $test_user_name}selected{/if}>{$creditor.name}&nbsp;[{$id}]</option>
        {/foreach}
    </select>
{else}
    <span id="test_creditor_id"><p><strong>{ts domain="de.systopia.sddpp"}No creditor found! Please create a creditor on the
                    <a href="{$sepa_settings_link}">SEPA settings page</a>
                    .{/ts}</strong></p></span>
{/if}

{* Create test creditors dropdown help *}
<a id='test_creditor_id_help'
   onclick='CRM.help("{ts domain="de.systopia.sddpp"}Creditor{/ts}", {literal}{"id":"id-creditor-help","file":"CRM\/Admin\/Form\/PaymentProcessor/SDD"}{/literal}); return false;'
   href="#" title="{ts domain="de.systopia.sddpp"}Help{/ts}" class="helpicon">&nbsp;</a>


<script type="text/javascript">

    {literal}
    // remove unnecessary lines
    cj('#url_site').parent().parent().remove();
    cj('#url_recur').parent().parent().remove();
    cj('#test_url_site').parent().parent().remove();
    cj('#test_url_recur').parent().parent().remove();
    cj('.crm-paymentProcessor-form-block-accept_credit_cards').hide();
    cj('tr.crm-paymentProcessor-form-block-payment-instrument-id').hide();

    // adjust help text
    cj('.crm-paymentProcessor-form-block-user_name').find('.helpicon').replaceWith(cj('#creditor_id_help'));
    cj('.crm-paymentProcessor-form-block-test_user_name').find('.helpicon').replaceWith(cj('#test_creditor_id_help'));
    // replace creditor selector
    cj('#user_name').replaceWith(cj('#creditor_id'));
    cj('#test_user_name').replaceWith(cj('#test_creditor_id'));

</script>
{/literal}
{/crmScope}
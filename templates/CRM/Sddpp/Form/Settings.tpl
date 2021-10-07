{*-------------------------------------------------------+
| SYSTOPIA SEPA Direct Debit Payment Processor           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de                                 |
| Development based on org.project60.sepapp              |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+-------------------------------------------------------*}
{crmScope extensionKey='de.systopia.sddpp'}
<div class="crm-block crm-form-block">

<div class="crm-section">
  <div class="label">{$form.sddpp_buffer_days.label}&nbsp;{help id="id-buffer-days" title=$form.sddpp_buffer_days.label}</div>
  <div class="content">{$form.sddpp_buffer_days.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.sddpp_add_mandate_reference.label}&nbsp;{help id="id-add-mandate-reference" title=$form.sddpp_add_mandate_reference.label}</div>
  <div class="content">{$form.sddpp_add_mandate_reference.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.sddpp_fix_interval_text.label}&nbsp;{help id="id-fix-interval-text" title=$form.sddpp_fix_interval_text.label}</div>
  <div class="content">{$form.sddpp_fix_interval_text.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.sddpp_add_prenotification.label}&nbsp;{help id="id-add-prenotification" title=$form.sddpp_add_prenotification.label}</div>
  <div class="content">{$form.sddpp_add_prenotification.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.sddpp_log_level.label}&nbsp;{help id="id-log-level" title=$form.sddpp_log_level.label}</div>
  <div class="content">{$form.sddpp_log_level.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

</div>
{/crmScope}
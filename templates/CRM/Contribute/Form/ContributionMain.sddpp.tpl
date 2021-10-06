{*-------------------------------------------------------+
| SYSTOPIA SEPA Direct Debit Payment Processor           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de                                 |
| Development based on org.project60.sepapp              |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

{* create a better dropdown for intervals *}
<select id="frequency_combined" class="form-select" onChange="_frequency_copy_combined()" disabled="disabled">
  <option value="1">{ts domain="org.project60.sepa"}monthly{/ts}</option>
  <option value="3">{ts domain="org.project60.sepa"}quarterly{/ts}</option>
  <option value="6">{ts domain="org.project60.sepa"}semi-annually{/ts}</option>
  <option value="12">{ts domain="org.project60.sepa"}annually{/ts}</option>
</select>

{* JS Disclaimer *}
<noscript>
<br/><br/>
<span style="color:#ff0000; font-size:150%; font-style:bold;">{ts domain="org.project60.sepa"}THIS PAGE PAGE DOES NOT WORK PROPERLY WITHOUT JAVASCRIPT. PLEASE ENABLE JAVASCRIPT IN YOUR BROWSER{/ts}</span>
</noscript>

<!-- JS Magic -->
<script type="text/javascript">
let label_months = "{ts domain="org.project60.sepa"}monthly{/ts}";
let label_years = "{ts domain="org.project60.sepa"}yearly{/ts}";

{literal}

if (cj("#frequency_interval").length) {
  // this is an custom interval page -> replace dropdown altogether
  cj("#frequency_interval").hide();
  cj("#frequency_unit").hide();
  cj("#frequency_combined").show();
  cj("#frequency_combined").insertBefore(cj("#frequency_interval"));

} else if (cj("#frequency_unit").length) {
  // this is a period only page, just update the labels
  cj("#frequency_combined").remove(); // not needed
  let options = cj("#frequency_unit > option");
  for (let i = 0; i < options.length; i++) {
    let option = cj(options[i]);
    if (option.val() == 'month') {
      option.text(label_months);
    } else if (option.val() == 'year') {
      option.text(label_years);
    } else {
      // this module cannot deal with weekly/daily payments
      option.remove();
    }
  }

} else {
  // this contribution page does NOT feature recurring contributions
  cj("#frequency_combined").remove();
}


// fix interval label
// remark: if there is only one frequency unit available
// frequency_interval is only a static text and no longer
// a select box
if(cj("[name=frequency_interval]").length) {
  cj("[name=frequency_interval]").get(0).nextSibling.textContent = "";
}

// fix recur label
if(cj("label[for='is_recur']").length) {
  cj("label[for='is_recur']").get(0).nextSibling.textContent = ": ";
}

// show currency indicator and move next to field
if (cj(".other_amount-content > input").length) {
  cj("#currency_indicator").show();
  cj(".other_amount-content > input").parent().append(cj("#currency_indicator"));
}


// disable the recur_selector fields if disabled
function _frequency_update_elements() {
  let is_recur = cj("#is_recur").prop('checked');
  cj("#frequency_interval").prop('disabled', !is_recur);
  cj("#frequency_unit").prop('disabled', !is_recur);
  cj("#frequency_combined").prop('disabled', !is_recur);
}

// function to propagate the frequency_combined button into the correct fields
function _frequency_copy_combined() {
  if (!cj("#frequency_combined").length) return;

  let value = cj("#frequency_combined").val();
  if (value == 12) {
    cj("#frequency_unit").val('year');
    cj("[name=frequency_unit]").val('year');
    cj("#frequency_interval").val('1');
  } else {
    cj("#frequency_unit").val('month');
    cj("[name=frequency_unit]").val('month');
    cj("#frequency_interval").val(value);
  }
}

cj(function() {
  cj("#is_recur").change(_frequency_update_elements);
  _frequency_update_elements();
  _frequency_copy_combined();
});

{/literal}
</script>
/*-------------------------------------------------------+
| SYSTOPIA SEPA Direct Debit Payment Processor           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de                                 |
| Development based on org.project60.sepapp              |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

// adjustments to the thank you page
cj(document).ready(function() {
  console.log("YAY");
  // insert new interval text, if requested
  if (CRM.vars.sddpp.new_interval_text) {
    cj("div.amount_display-group div.display-block")
      .find("p")
      .first()
      .text(CRM.vars.sddpp.new_interval_text);
  }

  // append mandate reference if requested
  if (CRM.vars.sddpp.mandate_reference) {
    cj("div.credit_card-group div.display-block")
      .append(CRM.vars.sddpp.mandate_reference)
      .append("<br>");
  }
});
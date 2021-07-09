<?php
/*-------------------------------------------------------+
| SYSTOPIA SEPA Direct Debit Payment Processor           |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de                                 |
| Development based on org.project60.sepapp              |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use Civi\Payment\Exception\PaymentProcessorException;
use CRM_Sepa_ExtensionUtil as E;

/**
 * Configuration logic for SDDPP
 */
class CRM_Core_Payment_SDDPPConfiguration
{
    /**
     * @param \CRM_Core_Form $form
     */
    public static function adjustAdminForm(&$form) {
        $pp_id      = $form->getVar('_id');
        $pp_type_id = $form->getVar('_paymentProcessorType');
        if ($pp_id || $pp_type_id) {
            // check if its ours (looking into pp or pp_type)
            if ($pp_id) {
                $pp            = civicrm_api3("PaymentProcessor", "getsingle", ["id" => $pp_id]);
                $pp_class_name = $pp['class_name'];
            } else {
                $pp_type       = civicrm_api3("PaymentProcessorType", "getsingle", ["id" => $pp_type_id]);
                $pp_class_name = $pp_type['class_name'];
            }

            if ($pp_class_name != 'Payment_SDDPP') {
                // that's not us
                return;
            }


            // find the associated creditor(s)
            $creditor_id      = null;
            $test_creditor_id = null;

            $pp_creditor      = null;
            $test_pp_creditor = null;

            if (!empty($pp_id)) {
                $creditor_id      = CRM_Core_BAO_Setting::getItem('SEPA Direct Debit PP', 'pp' . $pp_id);
                $test_creditor_id = CRM_Core_BAO_Setting::getItem('SEPA Direct Debit PP', 'pp_test' . $pp_id);
            }

            // load settings from creditor
            if ($creditor_id) {
                $pp_creditor = civicrm_api3('SepaCreditor', 'getsingle', array('id' => $creditor_id));
            }
            if ($test_creditor_id) {
                $test_pp_creditor = civicrm_api3('SepaCreditor', 'getsingle', array('id' => $test_creditor_id));
            }

            $creditors = civicrm_api3('SepaCreditor', 'get');
            $creditors = $creditors['values'];

            $test_creditors = civicrm_api3('SepaCreditor', 'get', array('category' => 'TEST'));
            if (empty($test_creditors['values'])) {
                // no test creditors? just offer the regular ones, selecting none is not good
                $test_creditors = civicrm_api3('SepaCreditor', 'get');
            }
            $test_creditors = $test_creditors['values'];

            // use settings
            if ($pp_creditor) {
                $form->assign('user_name', $creditor_id);
            }
            if ($test_pp_creditor) {
                $form->assign('test_user_name', $test_creditor_id);
            }
            $form->assign('creditors', $creditors);
            $form->assign('test_creditors', $test_creditors);

            // add new elements
            CRM_Core_Region::instance('page-body')->add(
                array(
                    'template' => 'CRM/Admin/Form/PaymentProcessor/SDD.tpl'
                )
            );
        }
    }
}

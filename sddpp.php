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

require_once 'sddpp.civix.php';
// phpcs:disable
use CRM_Sddpp_ExtensionUtil as E;
// phpcs:enable


/**
 * buildForm Hook for payment processor
 */
function sddpp_civicrm_buildForm($formName, &$form)
{
    if ($formName == "CRM_Admin_Form_PaymentProcessor") {                    // PAYMENT PROCESSOR CONFIGURATION PAGE
        // get payment class name
        CRM_Core_Payment_SDDPPConfiguration::adjustAdminForm($form);
        }


    } elseif ($formName == "CRM_Contribute_Form_Contribution_Main") {                          // PAYMENT PROCESS MAIN PAGE
        $mendForm = CRM_Core_BAO_Setting::getItem('SEPA Direct Debit Preferences', 'pp_improve_frequency');
        if ($mendForm) {
            // inject improved form logic
            CRM_Core_Region::instance('page-body')->add(
                array(
                    'template' => 'CRM/Contribute/Form/ContributionMain.sepa.tpl'
                )
            );
        }
    } elseif ($formName == "CRM_Contribute_Form_Contribution_Confirm") {                    // PAYMENT PROCESS CONFIRMATION PAGE
        // check if the PP is ours
        $pp_id = CRM_Utils_Array::value('payment_processor', $form->_params);
        if (empty($pp_id)) {
            // there is no payment processor?
            return;
        } else {
            $pp = civicrm_api3('PaymentProcessor', 'getsingle', array('id' => $pp_id));
            if (empty($pp['class_name']) || $pp['class_name'] != 'Payment_SDD') {
                // this is not our processor
                return;
            }
        }

        // this IS our processor -> inject stuff
        CRM_Core_Region::instance('page-body')->add(
            array(
                'template' => 'CRM/Contribute/Form/ContributionConfirm.sepa.tpl'
            )
        );
    } elseif ($formName == "CRM_Event_Form_Registration_Confirm") {                          // EVENT REGISTRATION CONFIRMATION PAGE
        // only for our SDD payment processors:
        $pp = $form->getTemplate()->get_template_vars('paymentProcessor');
        if ($pp['class_name'] != "Payment_SDD") {
            return;
        }

        // FIXME: this is a gross hack, please help me if you know
        //    how to extract bank_bic and bank_iban variables properly...
        $form_data = print_r($form, true);
        $matches   = array();
        if (preg_match(
            '/\[bank_identification_number\] => (?P<bank_identification_number>[\w0-9]+)/i',
            $form_data,
            $matches
        )) {
            $form->assign("bank_identification_number", $matches[1]);
        }
        $matches = array();
        if (preg_match('/\[bank_account_number\] => (?P<bank_account_number>[\w0-9]+)/i', $form_data, $matches)) {
            $form->assign("bank_account_number", $matches[1]);
        }
        unset($form_data);

        CRM_Core_Region::instance('page-body')->add(
            array(
                'template' => 'CRM/Event/Form/RegistrationConfirm.sepa.tpl'
            )
        );
    } elseif ($formName == "CRM_Contribute_Form_Contribution_ThankYou") {                    // PAYMENT PROCESS THANK YOU PAGE
        // check if the PP is ours
        $pp_id = CRM_Utils_Array::value('payment_processor', $form->_params);
        if (empty($pp_id)) {
            // there is no payment processor?
            return;
        } else {
            $pp = civicrm_api3('PaymentProcessor', 'getsingle', array('id' => $pp_id));
            if (empty($pp['class_name']) || $pp['class_name'] != 'Payment_SDD') {
                // this is not our processor
                return;
            }
        }

        // this IS ours
        $mandate_reference = $form->getTemplate()->get_template_vars('trxn_id');
        if ($mandate_reference) {
            $mandate       = civicrm_api3('SepaMandate', 'getsingle', array('reference' => $mandate_reference));
            $creditor      = civicrm_api3('SepaCreditor', 'getsingle', array('id' => $mandate['creditor_id']));
            $contribution  = civicrm_api3('Contribution', 'getsingle', array('trxn_id' => $mandate_reference));
            $rcontribution = array(
                'cycle_day'          => CRM_Utils_Array::value('cycle_day', $form->_params),
                'frequency_interval' => CRM_Utils_Array::value('frequency_interval', $form->_params),
                'frequency_unit'     => CRM_Utils_Array::value('frequency_unit', $form->_params),
                'start_date'         => CRM_Utils_Array::value('start_date', $form->_params)
            );

            $form->assign('mandate_reference', $mandate_reference);
            $form->assign("bank_account_number", $mandate["iban"]);
            $form->assign("bank_identification_number", $mandate["bic"]);
            $form->assign("collection_day", CRM_Utils_Array::value('cycle_day', $form->_params));
            $form->assign("frequency_interval", CRM_Utils_Array::value('frequency_interval', $form->_params));
            $form->assign("frequency_unit", CRM_Utils_Array::value('frequency_unit', $form->_params));
            $form->assign("creditor_id", $creditor['identifier']);
            $form->assign("collection_date", $contribution['receive_date']);
            $form->assign("cycle", CRM_Sepa_Logic_Batching::getCycle($rcontribution));
            $form->assign("cycle_day", CRM_Sepa_Logic_Batching::getCycleDay($rcontribution, $creditor['id']));
        }

        CRM_Core_Region::instance('contribution-thankyou-billing-block')->add(
            array(
                'template' => 'CRM/Contribute/Form/ContributionThankYou.sepa.tpl'
            )
        );
    } elseif ($formName == "CRM_Event_Form_Registration_ThankYou") {                        // EVENT REGISTRATION THANK YOU PAGE
        // only for our SDD payment processors:
        $pp = $form->getTemplate()->get_template_vars('paymentProcessor');
        if ($pp['class_name'] != "Payment_SDD") {
            return;
        }

        $mandate_reference = $form->getTemplate()->get_template_vars('trxn_id');
        if ($mandate_reference) {
            $mandate      = civicrm_api3('SepaMandate', 'getsingle', array('reference' => $mandate_reference));
            $creditor     = civicrm_api3('SepaCreditor', 'getsingle', array('id' => $mandate['creditor_id']));
            $contribution = civicrm_api3('Contribution', 'getsingle', array('trxn_id' => $mandate_reference));
            $form->assign('mandate_reference', $mandate_reference);
            $form->assign("bank_account_number", $mandate["iban"]);
            $form->assign("bank_identification_number", $mandate["bic"]);
            $form->assign("creditor_id", $creditor['identifier']);
            $form->assign("collection_date", $contribution['receive_date']);
        }

        CRM_Core_Region::instance('page-body')->add(
            array(
                'template' => 'CRM/Event/Form/RegistrationThankYou.sepa.tpl'
            )
        );
    }
}


/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function sddpp_civicrm_config(&$config) {
  _sddpp_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function sddpp_civicrm_xmlMenu(&$files) {
  _sddpp_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function sddpp_civicrm_install() {
  _sddpp_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function sddpp_civicrm_postInstall() {
  _sddpp_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function sddpp_civicrm_uninstall() {
  _sddpp_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function sddpp_civicrm_enable() {
  _sddpp_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function sddpp_civicrm_disable() {
  _sddpp_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function sddpp_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sddpp_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function sddpp_civicrm_managed(&$entities) {
  _sddpp_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function sddpp_civicrm_caseTypes(&$caseTypes) {
  _sddpp_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function sddpp_civicrm_angularModules(&$angularModules) {
  _sddpp_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function sddpp_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sddpp_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function sddpp_civicrm_entityTypes(&$entityTypes) {
  _sddpp_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function sddpp_civicrm_themes(&$themes) {
  _sddpp_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function sddpp_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function sddpp_civicrm_navigationMenu(&$menu) {
//  _sddpp_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _sddpp_civix_navigationMenu($menu);
//}

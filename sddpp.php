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

/**
 * Post-Processor hook to wrap up contributions
 */
function sddpp_civicrm_postPP($parameters, $context_data)
{
    try {
        CRM_Core_Payment_SDDPP::postProcess($parameters, $context_data);
    } catch (Exception $ex) {
        CRM_Sddpp_Logger::warning("Exception caught: " . $ex->getMessage());
    }
}

/**
 * buildForm Hook for payment processor
 */
function sddpp_civicrm_buildForm($formName, &$form)
{
    if ($formName == "CRM_Admin_Form_PaymentProcessor") {                    // PAYMENT PROCESSOR CONFIGURATION PAGE
        CRM_Core_Payment_SDDPPHelper::adjustAdminForm($form);

    } elseif ($formName == "CRM_Contribute_Form_Contribution_Main") {        // PAYMENT PROCESS MAIN PAGE
        CRM_Core_Payment_SDDPPHelper::adjustPaymentProcessorMainForm($form);

    } elseif ($formName == "CRM_Contribute_Form_Contribution_Confirm") {     // PAYMENT PROCESS CONFIRMATION PAGE
        CRM_Core_Payment_SDDPPHelper::adjustPaymentProcessorConfirmationForm($form);

    } elseif ($formName == "CRM_Event_Form_Registration_Confirm") {          // EVENT REGISTRATION CONFIRMATION PAGE
        CRM_Core_Payment_SDDPPHelper::adjustEventRegistrationConfirmationForm($form);

    } elseif ($formName == "CRM_Contribute_Form_Contribution_ThankYou") {    // PAYMENT PROCESS THANK YOU PAGE
        CRM_Core_Payment_SDDPPHelper::adjustDonationThankYouForm($form);

    } elseif ($formName == "CRM_Event_Form_Registration_ThankYou") {          // EVENT REGISTRATION THANK YOU PAGE
        CRM_Core_Payment_SDDPPHelper::adjustEventRegistrationThankYouForm($form);
    }
}
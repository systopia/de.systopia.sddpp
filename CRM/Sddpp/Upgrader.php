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


use CRM_Sddpp_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Sddpp_Upgrader extends CRM_Sddpp_Upgrader_Base
{
    /**
     * Install the new payment processor
     */
    public function install()
    {
        $this->enablePP();
    }

    /**
     * (re)enable the new PP
     */
    public function enable()
    {
        $this->enablePP();
    }


    /**
     * (re)enable the new PP
     */
    public function disable()
    {
        $this->disablePP();
    }

    /**
     * Install and enable the SDDPP payment processor type
     *
     * @throws \CiviCRM_API3_Exception if something's fundamentally wrong
     */
    protected function enablePP()
    {
        // INSTALL SDDPP PROCESSOR
        $sddpp = civicrm_api3('PaymentProcessorType', 'get', array('name' => CRM_Core_Payment_SDDPP::TYPE_NAME));
        if (empty($sddpp['id'])) {
            // doesn't exist yet => create
            civicrm_api3('PaymentProcessorType', 'create', [
                "name"                   => CRM_Core_Payment_SDDPP::TYPE_NAME,
                "title"                  => E::ts("SEPA Payment Processor"),
                "description"            => E::ts("CiviSEPA based payment processor"),
                "is_active"              => 1,
                "user_name_label"        => "SEPA Creditor identifier",
                "class_name"             => "Payment_SDDPP",
                "url_site_default"       => "",
                "url_recur_default"      => "",
                "url_site_test_default"  => "",
                "url_recur_test_default" => "",
                "billing_mode"           => "1",
                "is_recur"               => "1",
                "payment_type"           => CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT
            ]);
            Civi::log()->info("Created payment processor SDDPP.");

        } else {
            // already exists => enable if not enabled
            civicrm_api3('PaymentProcessorType', 'create', [
                'id'        => $sddpp['id'],
                'is_active' => 1
            ]);
        }
    }

    /**
     * Disable the SDDPP payment processor type and all instances
     */
    protected function disablePP()
    {
        // DISABLE PROCESSOR
        $sddpp = civicrm_api3('PaymentProcessorType', 'get', array('name' => CRM_Core_Payment_SDDPP::TYPE_NAME));
        if (!empty($sddpp['id'])) {
            // disable
            civicrm_api3(
                'PaymentProcessorType',
                'create',
                [
                    'id' => $sddpp['id'],
                    'is_active' => 0
                ]
            );

            // todo: disable all instances?

        } else {
            Civi::log()->warning("PaymentProcessor type SDDPP has gone!");
        }
    }

    /**
     * Example: Run a couple simple queries.
     *
     * @return TRUE on success
     * @throws Exception
     */
    // public function upgrade_4200() {
    //   $this->ctx->log->info('Applying update 4200');
    //   CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    //   CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    //   return TRUE;
    // }

}

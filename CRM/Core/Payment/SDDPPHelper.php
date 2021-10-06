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
 *
 * @todo most of these are probably not needed any more
 */
class CRM_Core_Payment_SDDPPHelper
{
    /**
     * Get the payment processor specs by id
     *
     * @param $pp_id integer
     *   payment processor
     *
     * @return array payment processor data
     */
    public static function getPaymentProcessor($pp_id)
    {
        static $payment_processors = null;
        if ($payment_processors === null) {
            // load all payment processors with our class name
            $pp_search = civicrm_api3('PaymentProcessor', 'get', [
                'option.limit' => 0,
                'sequential' => 0,
                'return' => 'id,payment_processor_type_id,class_name,user_name'
            ]);
            $payment_processors = $pp_search['values'];
        }

        return $payment_processors[$pp_id] ?? null;
    }

    /**
     * Calculate the first possible collection date, factoring in:
     *  - payment processor buffer days
     *  - cycle days
     *
     * @param integer $sdd_creditor_id
     *
     * @return string date
     */
    public static function firstCollectionDate($sdd_creditor_id)
    {
        // todo
        return date('Y-m-d');
    }

    /**
     * Get a sepa creditor
     *
     * @param integer $creditor_id
     *
     * @return array sepa creditor data
     */
    public static function getCreditor($creditor_id)
    {
        static $creditor_cache = [];

        $creditor_id_int = (int) $creditor_id;
        if (!array_key_exists($creditor_id_int, $creditor_cache)) {
            $creditor_cache[$creditor_id_int] = null;
            try {
                $creditor_cache[$creditor_id_int] = civicrm_api3('SepaCreditor', 'getsingle', [
                    'id' => $creditor_id_int]);
            } catch (CiviCRM_API3_Exception $ex) {
                CRM_Sddpp_Logger::debug("Couldn't load sepa creditor [{$creditor_id}], error was: " . $ex->getMessage());
            }
        }

        return $creditor_cache[$creditor_id_int] ?? null;
    }

    /**
     * Get the SEPA creditor information for a given payment processor
     * @param integer $pp_id
     *
     * @return array sepa creditor data
     */
    public static function getCreditorFromPP($pp_id)
    {
        $pp = self::getPaymentProcessor($pp_id);
        if ($pp) {
            return self::getCreditor($pp['user_name']); // this should be the creditor id
        } else {
            return null;
        }
    }

    /**
     * Check whether the given payment processor type ID
     *  is ours.
     *
     * @param integer $pp_type_id
     *  payment processor type ID
     *
     * @return boolean
     */
    public static function isOurPPType($pp_type_id)
    {
        if (empty($pp_type_id)) return false;

        static $our_pp_types = null;
        if ($our_pp_types === null) {
            // load all pp types with our class name
            $pp_type_search = civicrm_api3('PaymentProcessorType', 'get', [
                'class_name' => CRM_Core_Payment_SDDPP::CLASS_NAME,
                'option.limit' => 0,
                'sequential' => 0,
                'return' => 'id'
            ]);
            $our_pp_types = array_keys($pp_type_search['values']);
        }

        return in_array($pp_type_id, $our_pp_types);
    }

    /**
     * Check whether the given payment processor type ID
     *  is ours.
     *
     * @param integer $pp_id
     *  payment processor ID
     *
     * @return boolean
     *   true if the payment processor referenced is one of ours
     */
    public static function isOurPP($pp_id)
    {
        $payment_processor = self::getPaymentProcessor($pp_id);
        return (isset($payment_processor['payment_processor_type_id'])
           && self::isOurPPType($payment_processor['payment_processor_type_id']));
    }


    /**
     * Adjust the payment processor event confirmation page
     *
     * @param \CRM_Core_Form $form
     */
    public static function adjustDonationThankYouForm(&$form)
    {
        // only for our SDD payment processors:
        $pp = $form->getTemplate()->get_template_vars('paymentProcessor');
        if (self::isOurPP($pp['id'])) {
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

                CRM_Core_Region::instance('page-body')->add(
                    ['template' => 'CRM/Event/Form/RegistrationThankYou.sepa.tpl']);
            }
        }
    }

    /**
     * Adjust the payment processor event confirmation page
     *
     * @param \CRM_Core_Form $form
     */
    public static function adjustEventRegistrationThankYouForm(&$form)
    {
        // check if the PP is ours
        $pp_id = CRM_Utils_Array::value('payment_processor', $form->_params);
        if (self::isOurPP($pp_id)) {
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
                ['template' => 'CRM/Contribute/Form/ContributionThankYou.sepa.tpl']);
        }
    }

    /**
     * Adjust the payment processor event confirmation page
     *
     * @param \CRM_Core_Form $form
     */
    public static function adjustEventRegistrationConfirmationForm(&$form)
    {
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
    }

    /**
     * Adjust the payment processor administration page
     *
     * @param \CRM_Core_Form $form
     */
    public static function adjustPaymentProcessorConfirmationForm(&$form)
    {
        // todo: make this configurable?
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
    }

    /**
     * Adjust the payment processor administration page
     *
     * @param \CRM_Core_Form $form
     */
    public static function adjustPaymentProcessorMainForm(&$form)
    {
        // todo: make this configurable?
        CRM_Core_Region::instance('page-body')->add(
            ['template' => 'CRM/Contribute/Form/ContributionMain.sddpp.tpl']
        );
    }

    /**
     * Adjust the payment processor administration page
     *
     * @param \CRM_Core_Form $form
     */
    public static function adjustAdminForm(&$form)
    {
        $pp_id = $form->getVar('_id');
        $pp_type_id = $form->getVar('_paymentProcessorType');

        if (self::isOurPP($pp_id) || self::isOurPPType($pp_type_id)) {
            // this is for us
            // find the associated creditor(s)
            $creditor_id = null;
            $test_creditor_id = null;

            $pp_creditor = null;
            $test_pp_creditor = null;

            if (!empty($pp_id)) {
                $creditor_id = CRM_Core_BAO_Setting::getItem('SEPA Direct Debit PP', 'pp' . $pp_id);
                $test_creditor_id = CRM_Core_BAO_Setting::getItem('SEPA Direct Debit PP', 'pp_test' . $pp_id);
            }

            // load settings from creditor
            if ($creditor_id) {
                $pp_creditor = civicrm_api3('SepaCreditor', 'getsingle', ['id' => $creditor_id]);
            }
            if ($test_creditor_id) {
                $test_pp_creditor = civicrm_api3('SepaCreditor', 'getsingle', ['id' => $test_creditor_id]);
            }

            $creditors = civicrm_api3('SepaCreditor', 'get');
            $creditors = $creditors['values'];

            $test_creditors = civicrm_api3('SepaCreditor', 'get', ['category' => 'TEST']);
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
                ['template' => 'CRM/Admin/Form/PaymentProcessor/SDDPP.tpl']
            );
        }
    }
}

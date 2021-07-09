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
 * SEPA_Direct_Debit payment processor
 *
 * This is a new approach to implementing a CiviSEPA payment processor,
 *   using an (artificial) hook - that we hope to get into core one day
 *
 * @package CiviCRM_SEPA
 */
class CRM_Core_Payment_SDDPP extends CRM_Core_Payment
{
    const TYPE_NAME = 'sddpp';

    /**
     * Override CRM_Core_Payment function
     */
    public function getPaymentTypeName()
    {
        return self::TYPE_NAME;
    }

    /**
     * Override CRM_Core_Payment function
     */
    public function getPaymentTypeLabel()
    {
        return E::ts('SEPA Direct Debit');
    }

    /**
     * This function checks to see if we have the right config values.
     *
     * @return string
     *   the error message if any
     */
    public function checkConfig()
    {
        // TODO: anything to check?
        return null;
    }

    /**
     * Submit a payment using Advanced Integration Method.
     *
     * @param array $params
     *   Assoc array of input parameters for this transaction.
     *
     * @return array
     *   the result in a nice formatted array (or an error object)
     *
     * @throws \Civi\Payment\Exception\PaymentProcessorException
     */
    public function doDirectPayment(&$params)
    {
        Civi::log()->debug("doDirectPayment called: " . json_encode($params));
        $original_parameters = $params;

        // extract SEPA data
        $params['iban'] = $params['bank_account_number'];
        $params['bic'] = $params['bank_identification_number'];

        // Allow further manipulation of the arguments via custom hooks ..
        CRM_Utils_Hook::alterPaymentProcessorParams($this, $original_parameters, $params);

        // verify IBAN
        $bad_iban = CRM_Sepa_Logic_Verification::verifyIBAN($params['iban']);
        if ($bad_iban) {
            CRM_Sepapp_Configuration::log("IBAN issue: {$bad_iban}");
            throw new PaymentProcessorException($bad_iban);
        }

        // verify BIC
        $bad_bic = CRM_Sepa_Logic_Verification::verifyBIC($params['bic']);
        if ($bad_bic) {
            CRM_Sepapp_Configuration::log("BIC issue: {$bad_bic}");
            throw new PaymentProcessorException($bad_bic);
        }

        return $params;
    }

    /**
     * (custom) Post PaymentProcessor Hook will be invoked after the payment processor
     *   and the page have completed their work
     */
    public static function postProcess($parameters, $context_data)
    {
        // todo: create the mandate and put everything in place
        Civi::log()->debug("ping: " . json_encode($parameters));
    }

    /****************************************************************************
     *    PostProcessing: create mandate and put everything in place            *
     ****************************************************************************/

    /***********************************************
     *            Form-building duty               *
     ***********************************************/

    function buildForm(&$form)
    {
        // add rules
        $form->registerRule('sepa_iban_valid', 'callback', 'rule_valid_IBAN', 'CRM_Sepa_Logic_Verification');
        $form->registerRule('sepa_bic_valid', 'callback', 'rule_valid_BIC', 'CRM_Sepa_Logic_Verification');

        // BUFFER DAYS / TODO: MOVE TO SERVICE
        $creditor = $this->getCreditor();
        $buffer_days = (int)CRM_Sepa_Logic_Settings::getSetting("pp_buffer_days");
        $frst_notice_days = (int)CRM_Sepa_Logic_Settings::getSetting("batching.FRST.notice", $creditor['id']);
        $ooff_notice_days = (int)CRM_Sepa_Logic_Settings::getSetting("batching.OOFF.notice", $creditor['id']);
        $earliest_rcur_date = strtotime("now + $frst_notice_days days + $buffer_days days");
        $earliest_ooff_date = strtotime("now + $ooff_notice_days days");

        // find the next cycle day
        $cycle_days = CRM_Sepa_Logic_Settings::getListSetting("cycledays", range(1, 28), $creditor['id']);
        $earliest_cycle_day = $earliest_rcur_date;
        while (!in_array(date('j', $earliest_cycle_day), $cycle_days)) {
            $earliest_cycle_day = strtotime("+ 1 day", $earliest_cycle_day);
        }

        $form->assign('earliest_rcur_date', date('Y-m-d', $earliest_rcur_date));
        $form->assign('earliest_ooff_date', date('Y-m-d', $earliest_ooff_date));
        $form->assign('earliest_cycle_day', date('j', $earliest_cycle_day));
        $form->assign('sepa_hide_bic', CRM_Sepa_Logic_Settings::getSetting("pp_hide_bic"));
        $form->assign('sepa_hide_billing', CRM_Sepa_Logic_Settings::getSetting("pp_hide_billing"));
        $form->assign('bic_extension_installed', CRM_Sepa_Logic_Settings::isLittleBicExtensionAccessible());

        CRM_Core_Region::instance('billing-block')->add(
            ['template' => 'CRM/Core/Payment/SEPA/SDDPP.tpl', 'weight' => -1]
        );
    }

    /**
     * Get the creditor currently involved in the process
     *
     * @return array|void
     */
    protected function getCreditor()
    {
        if (!$this->_creditor) {
            $pp = $this->getPaymentProcessor();
            $creditor_id = $pp['user_name'];
            try {
                $this->_creditor = civicrm_api3('SepaCreditor', 'getsingle', ['id' => $creditor_id]);
            } catch (Exception $ex) {
                // probably no creditor set, or creditor has been deleted - use default
                CRM_Sepapp_Configuration::log(
                    "Creditor [{$creditor_id}] not found, SDDNG using default/any",
                    CRM_Sepapp_Configuration::LOG_LEVEL_ERROR
                );
                $default_creditor_id = (int)CRM_Sepa_Logic_Settings::getSetting('batching_default_creditor');
                $creditors = civicrm_api3('SepaCreditor', 'get', ['id' => $default_creditor_id]);
                $this->_creditor = reset($creditors['values']);
            }
        }
        return $this->_creditor;
    }

    /**
     * Override custom PI validation
     *  to make billing information NOT mandatory (see SEPA-372)
     *
     * @author N. Bochan
     */
    public function validatePaymentInstrument($values, &$errors)
    {
        // first: call parent's implementation
        parent::validatePaymentInstrument($values, $errors);

        // if this feature is not active, we do nothing:
        $pp_hide_billing = CRM_Sepa_Logic_Settings::getSetting("pp_hide_billing");
        if (empty($pp_hide_billing)) {
            return;
        }

        // now: by removing all the errors on the billing fields, we
        //   effectively render the billing block "not mandatory"
        if (isset($errors)) {
            foreach ($errors as $fieldname => $error_message) {
                if (substr($fieldname, 0, 8) == 'billing_') {
                    unset($errors[$fieldname]);
                }
            }
        }
    }

    /**
     * Override CRM_Core_Payment function
     */
    public function _getPaymentFormFields()
    {
        if (version_compare(CRM_Utils_System::version(), '4.6.10', '<')) {
            return parent::getPaymentFormFields();
        } else {
            return [
                'cycle_day',
                'start_date',
                'account_holder',
                'bank_account_number',
                'bank_identification_number',
                'bank_name',
            ];
        }
    }

    /**
     * Return an array of all the details about the fields potentially required for payment fields.
     *
     * Only those determined by getPaymentFormFields will actually be assigned to the form
     *
     * @return array
     *   field metadata
     */
    public function getPaymentFormFieldsMetadata()
    {
        if (version_compare(CRM_Utils_System::version(), '4.6.10', '<')) {
            return parent::getPaymentFormFieldsMetadata();
        } else {
            $creditor = $this->getCreditor();
            return [
                'account_holder' => [
                    'htmlType' => 'text',
                    'name' => 'account_holder',
                    'title' => ts('Account Holder', ['domain' => 'org.project60.sepa']),
                    'cc_field' => true,
                    'attributes' => [
                        'size' => 20,
                        'maxlength' => 34,
                        'autocomplete' => 'on',
                    ],
                    'is_required' => false,
                ],
                //e.g. IBAN can have maxlength of 34 digits
                'bank_account_number' => [
                    'htmlType' => 'text',
                    'name' => 'bank_account_number',
                    'default' => 'DE91100000000123456789',
                    'title' => E::ts('IBAN'),
                    'cc_field' => true,
                    'attributes' => [
                        'size' => 34,
                        'maxlength' => 34,
                        'autocomplete' => 'off',
                    ],
                    'rules' => [
                        [
                            'rule_message' => E::ts('This is not a correct IBAN.'),
                            'rule_name' => 'sepa_iban_valid',
                            'rule_parameters' => null,
                        ],
                    ],
                    'is_required' => true,
                ],
                //e.g. SWIFT-BIC can have maxlength of 11 digits
                'bank_identification_number' => [
                    'htmlType' => 'text',
                    'name' => 'bank_identification_number',
                    'title' => E::ts('BIC'),
                    'cc_field' => true,
                    'attributes' => [
                        'size' => 20,
                        'maxlength' => 11,
                        'autocomplete' => 'off',
                    ],
                    'is_required' => true,
                    'rules' => [
                        [
                            'rule_message' => E::ts('This is not a correct BIC.'),
                            'rule_name' => 'sepa_bic_valid',
                            'rule_parameters' => null,
                        ],
                    ],
                ],
                'bank_name' => [
                    'htmlType' => 'text',
                    'name' => 'bank_name',
                    'title' => ts('Bank Name', ['domain' => 'org.project60.sepa']),
                    'cc_field' => true,
                    'attributes' => [
                        'size' => 34,
                        'maxlength' => 64,
                        'autocomplete' => 'off',
                    ],
                    'is_required' => false,
                ],
                'cycle_day' => [
                    'htmlType' => 'select',
                    'name' => 'cycle_day',
                    'title' => E::ts('Collection Day'),
                    'cc_field' => true,
                    'attributes' => CRM_Sepa_Logic_Settings::getListSetting(
                        "cycledays",
                        range(1, 28),
                        $creditor['id']
                    ),
                    'is_required' => false,
                ],
                'start_date' => [
                    'htmlType' => 'text',
                    'name' => 'start_date',
                    'title' => E::ts('Start Date'),
                    'cc_field' => true,
                    'attributes' => [],
                    'is_required' => true,
                    'rules' => [],
                ],
            ];
        }
    }

    /**
     * Should the first payment date be configurable when setting up back office recurring payments.
     * In the case of Authorize.net this is an option
     *
     * @return bool
     */
    protected function supportsFutureRecurStartDate()
    {
        return true;
    }

    /**
     * Can recurring contributions be set against pledges.
     *
     * However, only enabling for processors it has been tested against.
     *
     * @return bool
     */
    protected function supportsRecurContributionsForPledges()
    {
        return true;
    }
}

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
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Sddpp_Form_Settings extends CRM_Core_Form
{
    /** @var string SETTING for the number of buffer days */
    const BUFFER_DAYS = 'sddpp_buffer_days';

    /** @var string TOGGLE to fix the rendered interval text (mixed languages) */
    const FIX_INTERVAL_TEXT = 'sddpp_fix_interval_text';

    /** @var string setting for the log level detail */
    const LOG_LEVEL = 'sddpp_log_level';

    /** @var string TOGGLE to add the newly generated mandate reference to the generated thank you page */
    const ADD_MANDATE_REFERENCE = 'sddpp_add_mandate_reference';

    /** @var string TOGGLE to add a prenotification text to the generated thank you page */
    const ADD_PRENOTIFICATION = 'sddpp_add_prenotification';

    public function buildQuickForm()
    {
        $this->add(
            'select',
            self::LOG_LEVEL,
            E::ts('Log Level'),
            [
                'debug' => E::ts('debug'),
                'warning' => E::ts('warning'),
                'error' => E::ts('error'),
                'off' => E::ts('off'),
            ],
            true
        );

        $this->add(
            'select',
            self::BUFFER_DAYS,
            E::ts('Buffer Days'),
            range(0, 30),
            true
        );
        $this->addRule(self::BUFFER_DAYS, E::ts('Please enter a valid integer.'), 'integer');

        $this->add(
            'checkbox',
            self::FIX_INTERVAL_TEXT,
            E::ts('Fix Interval Text')
        );

        $this->add(
            'checkbox',
            self::ADD_MANDATE_REFERENCE,
            E::ts('Show Mandate Reference')
        );

        $this->add(
            'checkbox',
            self::ADD_PRENOTIFICATION,
            E::ts('Show Prenotification Text')
        );

        $this->setDefaults([
           self::LOG_LEVEL => Civi::settings()->get(self::LOG_LEVEL),
           self::BUFFER_DAYS => Civi::settings()->get(self::BUFFER_DAYS),
           self::FIX_INTERVAL_TEXT => Civi::settings()->get(self::FIX_INTERVAL_TEXT),
           self::ADD_MANDATE_REFERENCE => Civi::settings()->get(self::ADD_MANDATE_REFERENCE),
           self::ADD_PRENOTIFICATION => Civi::settings()->get(self::ADD_PRENOTIFICATION),
        ]);

        $this->addButtons([
              [
                  'type' => 'submit',
                  'name' => E::ts('Save'),
                  'isDefault' => true,
              ],
          ]);

        // export form elements
        parent::buildQuickForm();
    }


    public function postProcess()
    {
        $values = $this->exportValues();
        Civi::settings()->set(self::LOG_LEVEL, $values[self::LOG_LEVEL] ?? false);
        Civi::settings()->set(self::BUFFER_DAYS, $values[self::BUFFER_DAYS] ?? 0);
        Civi::settings()->set(self::FIX_INTERVAL_TEXT, $values[self::FIX_INTERVAL_TEXT] ?? false);
        Civi::settings()->set(self::ADD_MANDATE_REFERENCE, $values[self::ADD_MANDATE_REFERENCE] ?? false);
        Civi::settings()->set(self::ADD_PRENOTIFICATION, $values[self::ADD_PRENOTIFICATION] ?? false);

        CRM_Core_Session::setStatus(
            E::ts("Your SDDPP payment processor configuration has been updated. This applies to all payment processor instances of this type."),
            E::ts("Changes Saved"),
            'info'
        );
        parent::postProcess();
    }

}

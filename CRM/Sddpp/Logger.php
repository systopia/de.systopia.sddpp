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
class CRM_Sddpp_Logger
{
    public static function debug($log_message) {
        Civi::log()->debug('[SDDPP] ' . $log_message);
    }

    public static function warning($log_message) {
        Civi::log()->warning('[SDDPP] ' . $log_message);
    }

    public static function error($log_message) {
        Civi::log()->error('[SDDPP] ' . $log_message);
    }
}

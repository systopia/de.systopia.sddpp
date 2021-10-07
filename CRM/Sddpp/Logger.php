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
    public static function shouldLog($log_level)
    {
        static $min_log_level = null;
        if ($min_log_level === null) {
            $min_log_level = Civi::settings()->get(CRM_Sddpp_Form_Settings::LOG_LEVEL);
        }
        switch ($min_log_level) {
            case 'off':
                return false;

            case 'debug':
                return true;

            case 'warning':
                return $log_level != 'debug';

            case 'error':
                return $log_level == 'error';
        }
    }

    public static function debug($log_message)
    {
        if (self::shouldLog('debug')) {
            Civi::log()->debug('[SDDPP] ' . $log_message);
        }
    }

    public static function warning($log_message)
    {
        if (self::shouldLog('warning')) {
            Civi::log()->warning('[SDDPP] ' . $log_message);
        }
    }

    public static function error($log_message)
    {
        if (self::shouldLog('error')) {
            Civi::log()->error('[SDDPP] ' . $log_message);
        }
    }
}

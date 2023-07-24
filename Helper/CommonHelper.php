<?php
/**
 * TikTokShop CommonHelper
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Helper;

/**
 * CommonHelper helper function.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class CommonHelper
{
    /**
     * Check if is running under PerformanceTest
     *
     * @return bool
     */
    public function isRunningUnderPerformanceTest()
    {
        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace) {
            if (isset($trace['function'])
                && isset($trace['file'])
                && (strpos($trace['file'], 'GenerateFixturesCommand') !== false)
            ) {
                return true;
            }
        }
        return false;
    }
}

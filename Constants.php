<?php
/**
 * TikTokShop Constants

 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop;

/**
 * All Constants use for this plugin.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class Constants
{
    public const INTEGRATION_APPS = ['feed'];

    public const AFTERSHIP_TIKTOK_SHOP_VERSION = '1.0.7';

    public const WEBHOOK_CONFIG_SCOPE_PATH = 'aftership/webhooks/webhooks';

    public const WEBHOOK_TOPIC_ORDERS_UPDATE = 'orders/update';
    public const WEBHOOK_TOPIC_PRODUCTS_UPDATE = 'products/update';
    public const WEBHOOK_TOPIC_VARIANTS_UPDATE = 'variants/update';
    public const WEBHOOK_TOPIC_PRODUCTS_DELETE = 'products/delete';
    public const WEBHOOK_TOPIC_VARIANTS_DELETE = 'variants/delete';
}

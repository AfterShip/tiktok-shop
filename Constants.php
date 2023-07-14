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
 * Constants
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class Constants
{
    const INTEGRATION_APPS = ['feed'];

    const AFTERSHIP_TIKTOK_SHOP_VERSION = '1.0.7';

    const WEBHOOK_CONFIG_SCOPE_PATH = 'aftership/webhooks/webhooks';

    const WEBHOOK_TOPIC_ORDERS_UPDATE = 'orders/update';
    const WEBHOOK_TOPIC_PRODUCTS_UPDATE = 'products/update';
    const WEBHOOK_TOPIC_VARIANTS_UPDATE = 'variants/update';
    const WEBHOOK_TOPIC_PRODUCTS_DELETE = 'products/delete';
    const WEBHOOK_TOPIC_VARIANTS_DELETE = 'variants/delete';
}

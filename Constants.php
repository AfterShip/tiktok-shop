<?php
/**
 * TikTokShop Constants

 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop;

/**
 * All Constants use for this plugin.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class Constants
{
    public const INTEGRATION_APPS = ['feed'];

    public const HEADER_INVENTORY_BEHAVIOUR = 'as-inventory-behaviour';

    public const WEBHOOK_MESSAGE_QUEUE_TOPIC = 'aftership.webhook.events';
    public const WEBHOOK_EVENT_UPDATE = 'update';
    public const WEBHOOK_EVENT_DELETE = 'delete';

    public const WEBHOOK_RESOURCE_ORDERS = 'orders';
    public const WEBHOOK_RESOURCE_PRODUCTS = 'products';

    public const WEBHOOK_RESOURCE_CREDITMEMOS = 'creditmemos';

    public const AFTERSHIP_TIKTOK_SHOP_VERSION = '1.0.19';

    public const WEBHOOK_CONFIG_SCOPE_PATH = 'aftership/webhooks/webhooks';

    public const WEBHOOK_TOPIC_ORDERS_UPDATE = 'orders/update';
    public const WEBHOOK_TOPIC_ORDERS_DELETE = 'orders/delete';
    public const WEBHOOK_TOPIC_PRODUCTS_UPDATE = 'products/update';
    public const WEBHOOK_TOPIC_PRODUCTS_DELETE = 'products/delete';
    public const WEBHOOK_TOPIC_VARIANTS_DELETE = 'variants/delete';
    public const WEBHOOK_TOPIC_CREDITMEMOS_UPDATE = 'creditmemos/update';
}

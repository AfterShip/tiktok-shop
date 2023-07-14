<?php
/**
 * TikTokShop WebhookManagementInterface
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Api;

use AfterShip\TikTokShop\Api\WebhookEntityInterface;

/**
 * Interface for WebhookManagement.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
interface WebhookManagementInterface
{
    /**
     * Register webhook.
     *
     * @param WebhookEntityInterface $webhook
     *
     * @return WebhookEntityInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function registerWebhook(WebhookEntityInterface $webhook);
    /**
     * List webhook.
     *
     * @return WebhookEntityInterface[]
     */
    public function listWebhooks();
    /**
     * Delete webhook by id.
     *
     * @param string $webhookId
     *
     * @return WebhookEntityInterface|null
     */
    public function deleteWebhook($webhookId);
    /**
     * Get webhook by id.
     *
     * @param string $webhookId
     *
     * @return WebhookEntityInterface|null
     */
    public function getWebhook($webhookId);
}

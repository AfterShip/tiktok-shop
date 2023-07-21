<?php

namespace AfterShip\TikTokShop\Model\Queue;

use AfterShip\TikTokShop\Api\Data\WebhookEventInterface;
use AfterShip\TikTokShop\Constants;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * WebhookPublisher for webhook.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class WebhookPublisher
{
    /**
     * Publisher Instance
     *
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * Construct
     *
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Send webhook event to Queue
     *
     * @param WebhookEventInterface $event
     *
     * @return void
     */
    public function execute(WebhookEventInterface $event)
    {
        $this->publisher->publish(Constants::WEBHOOK_MESSAGE_QUEUE_TOPIC, $event);
    }
}

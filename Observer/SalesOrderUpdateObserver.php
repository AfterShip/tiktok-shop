<?php
/**
 * TikTokShop SalesOrderUpdateObserver
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Helper\WebhookHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Send webhook when get order update event.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class SalesOrderUpdateObserver implements ObserverInterface
{
    /**
     * LoggerInterface
     *
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * WebhookHelper
     *
     * @var WebhookHelper
     */
    protected $webhookHelper;

    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param WebhookHelper   $webhookHelper
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookHelper   $webhookHelper
    ) {
        $this->logger = $logger;
        $this->webhookHelper = $webhookHelper;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $orderId = $order->getId();
            $orderStatus = $order->getStatus();
            $this->webhookHelper->makeWebhookRequest(
                Constants::WEBHOOK_TOPIC_ORDERS_UPDATE,
                [
                'id' => $orderId,
                'status' => $orderStatus,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[AfterShip TikTokShop] Faield to send order webhook on OrderUpdateObserver, %s', $e->getMessage()));
        }
    }
}

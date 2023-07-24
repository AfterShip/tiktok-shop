<?php
/**
 * TikTokShop SalesOrderUpdateObserver
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;
use Psr\Log\LoggerInterface;

/**
 * Send webhook when get order update event.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class SalesOrderUpdateObserver implements ObserverInterface
{
    /**
     * LoggerInterface Instance.
     *
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * Publisher Instance.
     *
     * @var WebhookPublisher
     */
    protected $publisher;

    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param WebhookPublisher $publisher
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookPublisher $publisher
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

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
            if ($this->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] SalesOrderUpdateObserver do not sync inventory during performance test'
                );
                return;
            }
            $order = $observer->getEvent()->getOrder();
            $orderId = $order->getId();
            $event = new WebhookEvent();
            $event->setId($orderId)
                ->setResource(Constants::WEBHOOK_RESOURCE_ORDERS)
                ->setEVent(Constants::WEBHOOK_EVENT_UPDATE);
            $this->publisher->execute($event);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] Faield to send order webhook on OrderUpdateObserver, %s',
                    $e->getMessage()
                )
            );
        }
    }
}

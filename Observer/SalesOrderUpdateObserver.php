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
use AfterShip\TikTokShop\Helper\CommonHelper;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;
use Magento\Sales\Api\OrderRepositoryInterface;
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
     * Common Helper Instance.
     * @var CommonHelper
     */
    protected $commonHelper;

    /**
     * Order Repository Instance.
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param WebhookPublisher $publisher
     * @param CommonHelper $commonHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
        $this->orderRepository = $orderRepository;
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
            if ($this->commonHelper->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] SalesOrderUpdateObserver do not sync inventory during performance test'
                );
                return;
            }
            $order = null;
            $webhookEvent = Constants::WEBHOOK_EVENT_UPDATE;
            $eventName = $observer->getEvent()->getName();
            switch ($eventName) {
                case 'sales_order_shipment_save_after':
                    $shipment = $observer->getEvent()->getShipment();
                    $order = $shipment->getOrder();
                    break;
                case 'sales_order_shipment_track_save_after':
                case 'sales_order_shipment_track_delete_after':
                    $track = $observer->getEvent()->getTrack();
                    $shipment = $track->getShipment();
                    $order = $shipment->getOrder();
                    break;
                case 'sales_order_save_after':
                    $order = $observer->getEvent()->getOrder();
                    break;
                case 'sales_order_status_history_save_after':
                    $orderComment = $observer->getStatusHistory();
                    $order = $this->orderRepository->get($orderComment->getParentId());
                    break;
                default:
                    break;
            }
            if (!$order) {
                return;
            }
            $orderId = $order->getId();
            $event = new WebhookEvent();
            $event->setId($orderId)
                ->setResource(Constants::WEBHOOK_RESOURCE_ORDERS)
                ->setEvent($webhookEvent);
            $this->publisher->execute($event);
            // Send product update event for each order item.
            $orderItems = $order->getAllItems();
            foreach ($orderItems as $orderItem) {
                try {
                    $productId = $orderItem->getProductId();
                    if (!$productId) {
                        continue;
                    }
                    $event = new WebhookEvent();
                    $event->setId($productId)
                        ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                        ->setEvent(Constants::WEBHOOK_EVENT_UPDATE);
                    $this->publisher->execute($event);
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            '[AfterShip TikTokShop] Failed to send order related products webhook on OrderUpdateObserver, %s',
                            $e->getMessage()
                        )
                    );
                }
            }
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

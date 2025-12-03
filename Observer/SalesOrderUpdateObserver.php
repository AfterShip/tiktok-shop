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
use Magento\Framework\Api\ExtensionAttributesFactory;
use AfterShip\TikTokShop\Api\Data\ProductChangeType;
use Magento\Framework\ObjectManagerInterface;

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
     * Extension attributes factory
     *
     * @var ExtensionAttributesFactory
     */
    protected $extensionAttributesFactory;

    /**
     * Object manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param WebhookPublisher $publisher
     * @param CommonHelper $commonHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper,
        OrderRepositoryInterface $orderRepository,
        ExtensionAttributesFactory $extensionAttributesFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
        $this->orderRepository = $orderRepository;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->objectManager = $objectManager;
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
            $eventName = $observer->getEvent()->getName();
            $this->logger->debug(
                sprintf(
                    '[AfterShip TikTokShop] SalesOrderUpdateObserver triggered by event: %s',
                    $eventName
                )
            );
            if ($this->commonHelper->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] SalesOrderUpdateObserver do not sync inventory during performance test'
                );
                return;
            }
            $order = null;
            $webhookEvent = Constants::WEBHOOK_EVENT_UPDATE;
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
            $event = $this->objectManager->create(WebhookEvent::class);
            $event->setId($orderId)
                ->setResource(Constants::WEBHOOK_RESOURCE_ORDERS)
                ->setEvent($webhookEvent);
            $this->publisher->execute($event);
            // Send product update event for each order item only on sales_order_save_after event.
            if ($eventName === 'sales_order_save_after') {
                $orderItems = $order->getAllItems();
                foreach ($orderItems as $orderItem) {
                    try {
                        $productId = $orderItem->getProductId();
                        if (!$productId) {
                            continue;
                        }
                        $event = $this->objectManager->create(WebhookEvent::class);
                        $event->setId($productId)
                            ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                            ->setEvent(Constants::WEBHOOK_EVENT_UPDATE);
                        
                        $extensionAttributes = null;
                        if (method_exists($event, 'getExtensionAttributes')) {
                            $extensionAttributes = $event->getExtensionAttributes();
                        }
                        if (!$extensionAttributes) {
                            $extensionAttributes = $this->extensionAttributesFactory->create(
                                WebhookEvent::class
                            );
                        }
                        $extensionAttributes->setProductChangeType(ProductChangeType::STOCK);
                        if (method_exists($event, 'setExtensionAttributes')) {
                            $event->setExtensionAttributes($extensionAttributes);
                        }
                        
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

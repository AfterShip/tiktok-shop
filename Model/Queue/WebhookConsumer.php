<?php
/**
 * TikTokShop Webhook Consumer
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Model\Queue;

use Psr\Log\LoggerInterface;
use AfterShip\TikTokShop\Constants;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;
use AfterShip\TikTokShop\Api\Data\WebhookEventInterface;
use AfterShip\TikTokShop\Helper\WebhookHelper;
use AfterShip\TikTokShop\Api\Data\ProductChangeType;

/**
 * Consumer for webhook events.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class WebhookConsumer
{

    /**
     * ProductRepository Instance
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * Configurable Instance
     *
     * @var Configurable
     */
    protected $configurable;
    /**
     * @var Grouped
     */
    protected $grouped;
    /**
     * @var Bundle
     */
    protected $bundle;
    /**
     * Logger Instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * WebhookHelper Instance
     *
     * @var WebhookHelper
     */
    protected $webhookHelper;

    /**
     * Construct
     *
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurable
     * @param Grouped $grouped
     * @param Bundle $bundle
     * @param WebhookHelper $webhookHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Configurable               $configurable,
        Grouped                    $grouped,
        Bundle                     $bundle,
        WebhookHelper              $webhookHelper,
        LoggerInterface            $logger
    ) {
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->grouped = $grouped;
        $this->bundle = $bundle;
        $this->logger = $logger;
        $this->webhookHelper = $webhookHelper;
    }

    /**
     * GetParentProductIds
     *
     * @param string $productId 'productId'
     *
     * @return array
     */
    public function getParentProductIds($productId)
    {
        $configurableParentIds = $this->configurable->getParentIdsByChild($productId);
        $groupedParentIds = $this->grouped->getParentIdsByChild($productId);
        $bundleParentIds = $this->bundle->getParentIdsByChild($productId);
        return array_merge($configurableParentIds, $groupedParentIds, $bundleParentIds);
    }

    /**
     * Consumer for messages.
     *
     * @param WebhookEventInterface $message
     *
     * @return void
     */
    public function execute(WebhookEventInterface $message)
    {
        try {
            $this->logger->debug(sprintf(
                "[AfterShip TikTokShop] webhook event received: id:%s,resource:%s,event:%s,date:%s",
                $message->getId(),
                $message->getResource(),
                $message->getEvent(),
                date('Y-m-d H:i:s')
            ));
            switch ($message->getResource()) {
                case Constants::WEBHOOK_RESOURCE_PRODUCTS:

                    $this->sendProductWebhook($message->getEvent(), $message->getId(), $message);
                    break;

                case Constants::WEBHOOK_RESOURCE_ORDERS:
                    $this->logger->debug(sprintf(
                        "[AfterShip TikTokShop] Processing order webhook: id:%s,event:%s",
                        $message->getId(),
                        $message->getEvent()
                    ));
                    $this->sendOrderWebhook($message->getEvent(), $message->getId());
                    break;

                case Constants::WEBHOOK_RESOURCE_CREDITMEMOS:
                    $this->logger->debug(sprintf(
                        "[AfterShip TikTokShop] Processing creditmemo webhook: id:%s,event:%s",
                        $message->getId(),
                        $message->getEvent()
                    ));
                    $this->sendCreditmemoWebhook($message->getEvent(), $message->getId());
                    break;

                default:
                    $this->logger->debug(sprintf(
                        "[AfterShip TikTokShop] Unknown webhook resource: %s,id:%s,event:%s",
                        $message->getResource(),
                        $message->getId(),
                        $message->getEvent()
                    ));
                    break;

            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                "[AfterShip TikTokShop] webhook send failed. error: %s, message_id:%s, resource:%s, event:%s, trace:%s",
                $e->getMessage(),
                $message->getId(),
                $message->getResource(),
                $message->getEvent(),
                $e->getTraceAsString()
            ));
        }
    }

    /**
     * Sending order webhook
     *
     * @param string $event
     * @param string $orderId
     *
     * @return void
     */
    public function sendOrderWebhook($event, $orderId)
    {
        if ($event == Constants::WEBHOOK_EVENT_DELETE) {
            $this->logger->debug(sprintf(
                "[AfterShip TikTokShop] Sending order delete webhook: order_id:%s",
                $orderId
            ));
            return $this->webhookHelper->makeWebhookRequest(
                Constants::WEBHOOK_TOPIC_ORDERS_DELETE,
                [
                    'id' => $orderId
                ]
            );
        }
        $this->logger->debug(sprintf(
            "[AfterShip TikTokShop] Sending order update webhook: order_id:%s",
            $orderId
        ));
        $this->webhookHelper->makeWebhookRequest(
            Constants::WEBHOOK_TOPIC_ORDERS_UPDATE,
            [
                'id' => $orderId
            ]
        );
    }

    /**
     * Sending products webhook
     *
     * @param string $event
     * @param string $productId
     * @param WebhookEventInterface|null $webhookEvent
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendProductWebhook($event, $productId, $webhookEvent = null)
    {
        if ($event == Constants::WEBHOOK_EVENT_DELETE) {
            $this->logger->debug(sprintf(
                "[AfterShip TikTokShop] Sending product delete webhook: product_id:%s",
                $productId
            ));
            return $this->webhookHelper->makeWebhookRequest(
                Constants::WEBHOOK_TOPIC_PRODUCTS_DELETE,
                [
                    "id" => $productId
                ]
            );
        }
        $this->logger->debug(sprintf(
            "[AfterShip TikTokShop] Processing product update webhook: product_id:%s",
            $productId
        ));
        $product = $this->productRepository->getById($productId);
        $parentIds = $this->getParentProductIds($productId);
        $topic = Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE;
        
        // Get change_type from extension attributes
        $changeType = null;
        $isStockChange = false;
        if ($webhookEvent && method_exists($webhookEvent, 'getExtensionAttributes')) {
            $extensionAttributes = $webhookEvent->getExtensionAttributes();
            if ($extensionAttributes && method_exists($extensionAttributes, 'getProductChangeType')) {
                $changeType = $extensionAttributes->getProductChangeType();
                $isStockChange = ($changeType === ProductChangeType::STOCK);
            }
        }
        
        // Send webhook.
        $this->logger->debug(sprintf(
            "[AfterShip TikTokShop] Sending product update webhook: product_id:%s,change_type:%s",
            $productId,
            $changeType ?? 'null'
        ));
        $this->webhookHelper->makeWebhookRequest(
            $topic,
            [
                "id" => $productId,
                "type_id" => $product->getTypeId(),
                "sku" => $product->getSku(),
                "visibility" => (string)$product->getVisibility(),
                "parent_ids" => array_map('strval', $parentIds),
                "change_type" => $changeType,
            ]
        );
        
        // Skip parent product webhooks if change_type is STOCK
        if (!$isStockChange) {
            foreach ($parentIds as $parentId) {
                $parentProduct = $this->productRepository->getById($parentId);
                $this->webhookHelper->makeWebhookRequest(
                    $topic,
                    [
                        "id" => (string)$parentId,
                        "type_id" => $parentProduct->getTypeId(),
                        "sku" => $parentProduct->getSku(),
                        "visibility" => (string)$parentProduct->getVisibility(),
                        "change_type" => $changeType,
                    ]
                );
            }
        }
    }

    /**
     * Sending creditmemo webhook
     *
     * @param string $event
     * @param string $creditmemoId
     *
     * @return void
     */
    public function sendCreditmemoWebhook($event, $creditmemoId)
    {
        if ($event == Constants::WEBHOOK_EVENT_UPDATE) {
            $this->logger->debug(sprintf(
                "[AfterShip TikTokShop] Sending creditmemo update webhook: creditmemo_id:%s",
                $creditmemoId
            ));
             $this->webhookHelper->makeWebhookRequest(
                 Constants::WEBHOOK_TOPIC_CREDITMEMOS_UPDATE,
                 [
                    'id' => $creditmemoId
                ]
            );
        } else {
            $this->logger->debug(sprintf(
                "[AfterShip TikTokShop] Skipping creditmemo webhook (event not UPDATE): creditmemo_id:%s,event:%s",
                $creditmemoId,
                $event
            ));
        }
    }
}

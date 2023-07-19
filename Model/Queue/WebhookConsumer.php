<?php
/**
 * TikTokShop Webhook Consumer
 *
 * @author    AfterShip <apps@aftership.com>
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

/**
 * Consumer for webhook events.
 *
 * @author   AfterShip <apps@aftership.com>
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
//             $this->logger->info(sprintf(
//                 "[AfterShip TikTokShop] webhook event received: id:%s,resource:%s,event:%s,date:%s",
//                 $message->getId(),
//                 $message->getResource(),
//                 $message->getEvent(),
//                 date('Y-m-d H:i:s')
//             ));
            switch ($message->getResource()) {
                case Constants::WEBHOOK_RESOURCE_PRODUCTS:
                    $this->sendProductWebhook($message->getEvent(), $message->getId());
                    break;
                
                case Constants::WEBHOOK_RESOURCE_ORDERS:
                    $this->sendOrderWebhook($message->getEvent(), $message->getId());
                    break;

                default:
                    break;

            }
        } catch (\Exception $e) {
            $this->logger->info(sprintf("[AfterShip TikTokShop] webhook send failed. error:", $e->getMessage()));
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
            return $this->webhookHelper->makeWebhookRequest(
                Constants::WEBHOOK_TOPIC_ORDERS_DELETE,
                [
                    'id' => $orderId
                ]
            );
        }
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
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendProductWebhook($event, $productId)
    {
        if ($event == Constants::WEBHOOK_EVENT_DELETE) {
            return $this->webhookHelper->makeWebhookRequest(
                Constants::WEBHOOK_TOPIC_PRODUCTS_DELETE,
                [
                    "id" => $productId
                ]
            );
        }
        $product = $this->productRepository->getById($productId);
        $parentIds = $this->getParentProductIds($productId);
        $topic = (count($parentIds) === 0) ?
            Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE :
            Constants::WEBHOOK_TOPIC_VARIANTS_UPDATE;
        // Send webhook.
        $this->webhookHelper->makeWebhookRequest(
            $topic,
            [
                "id" => $productId,
                "type_id" => $product->getTypeId(),
                "sku" => $product->getSku(),
                "visibility" => (string)$product->getVisibility(),
            ]
        );
        foreach ($parentIds as $parentId) {
            $parentProduct = $this->productRepository->getById($parentId);
            $this->webhookHelper->makeWebhookRequest(
                Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE,
                [
                    "id" => $parentProduct->getId(),
                    "type_id" => $parentProduct->getTypeId(),
                    "sku" => $parentProduct->getSku(),
                    "visibility" => (string)$parentProduct->getVisibility(),
                ]
            );
        }
    }
}

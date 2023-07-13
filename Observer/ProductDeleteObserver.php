<?php

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use AfterShip\TikTokShop\Helper\WebhookHelper;
use Psr\Log\LoggerInterface;

class ProductDeleteObserver implements ObserverInterface
{
	/** @var ProductRepositoryInterface */
	private $productRepository;
	/** @var WebhookHelper */
	private $webhookHelper;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		ProductRepositoryInterface $productRepository,
		WebhookHelper              $webhookHelper,
		LoggerInterface            $logger
	)
	{
		$this->productRepository = $productRepository;
		$this->logger = $logger;
		$this->webhookHelper = $webhookHelper;
	}


	/**
	 * @param Observer $observer
	 * @return void
	 */
	public function execute(Observer $observer)
	{
		try {
			/* @var \Magento\Catalog\Model\Product $product */
			$product = $observer->getEvent()->getProduct();
			$productId = $product->getId();
			// Send webhook.
			$this->webhookHelper->makeWebhookRequest(Constants::WEBHOOK_TOPIC_PRODUCTS_DELETE, [
				"id" => $productId,
				"type_id" => $product->getTypeId(),
				"sku" => $product->getSku(),
				"visibility" => (string)$product->getVisibility(),
			]);
		} catch (\Exception $e) {
			$this->logger->error(sprintf('[AfterShip TikTokShop] Faield to send webhook on ProductDeleteObserver, %s', $e->getMessage()));
		}
	}
}
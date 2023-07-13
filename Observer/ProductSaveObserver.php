<?php

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;
use AfterShip\TikTokShop\Helper\WebhookHelper;
use Psr\Log\LoggerInterface;

class ProductSaveObserver implements ObserverInterface
{
	/** @var ProductRepositoryInterface */
	private $productRepository;
	/** @var Configurable */
	private $configurable;
	/** @var Grouped */
	private $grouped;
	/** @var Bundle */
	private $bundle;
	/** @var WebhookHelper */
	private $webhookHelper;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		ProductRepositoryInterface $productRepository,
		Configurable               $configurable,
		Grouped                    $grouped,
		Bundle                     $bundle,
		WebhookHelper              $webhookHelper,
		LoggerInterface            $logger
	)
	{
		$this->productRepository = $productRepository;
		$this->configurable = $configurable;
		$this->grouped = $grouped;
		$this->bundle = $bundle;
		$this->logger = $logger;
		$this->webhookHelper = $webhookHelper;
	}

	/**
	 * @param string $productId
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
	 * @param Observer $observer
	 * @return void
	 */
	public function execute(Observer $observer)
	{
		try {
			/* @var \Magento\Catalog\Model\Product $product */
			$product = $observer->getEvent()->getProduct();
			$productId = $product->getId();
			$parentIds = $this->getParentProductIds($productId);
			$topic = (count($parentIds) === 0) ? Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE : Constants::WEBHOOK_TOPIC_VARIANTS_UPDATE;
			// Send webhook.
			$this->webhookHelper->makeWebhookRequest($topic, [
				"id" => $productId,
				"type_id" => $product->getTypeId(),
				"sku" => $product->getSku(),
				"visibility" => (string)$product->getVisibility(),
			]);
			// Fix updated time for parent product.
			foreach ($parentIds as $parentId) {
				$parentProduct = $this->productRepository->getById($parentId);
				$parentProduct->setData('updated_at', date('Y-m-d H:i:s'));
				$this->productRepository->save($parentProduct);
			}
		} catch (\Exception $e) {
			$this->logger->error(sprintf('[AfterShip TikTokShop] Faield to update product data on ProductSaveObserver, %s', $e->getMessage()));
		}
	}
}
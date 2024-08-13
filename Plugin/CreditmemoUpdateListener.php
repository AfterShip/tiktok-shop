<?php

namespace AfterShip\TikTokShop\Plugin;

use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Helper\CommonHelper;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\ProcessRefundItemsInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Metadata;
use Psr\Log\LoggerInterface;

class CreditmemoUpdateListener
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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var ItemsToRefundInterfaceFactory
     */
    private $itemsToRefundFactory;

    /**
     * @var ProcessRefundItemsInterface
     */
    private $processRefundItems;

    public function __construct(
        LoggerInterface  $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper,
        RequestInterface $request,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        ItemsToRefundInterfaceFactory $itemsToRefundFactory,
        ProcessRefundItemsInterface $processRefundItems,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
        $this->request = $request;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToRefundFactory = $itemsToRefundFactory;
        $this->processRefundItems = $processRefundItems;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
    }

    /**
     * send creditmemo webhook when saving creditmemo
     *
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $subject, $result
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param ItemsToRefundInterfaceFactory $itemsToRefundFactory
     * @param ProcessRefundItemsInterface $processRefundItems
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @return \Magento\Sales\Api\Data\CreditmemoRepositoryInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CreditmemoRepositoryInterface $subject, $result) {
        try {
            $creditmemoId = $result->getEntityId();

            // @var \Magento\Sales\Model\Order
            $order = $result->getOrder();

            // send creditmemo webhook
            $event = new WebhookEvent();
            $event->setId($creditmemoId)
                ->setResource(Constants::WEBHOOK_RESOURCE_CREDITMEMOS)
                ->setEVent(Constants::WEBHOOK_EVENT_UPDATE);
            $this->publisher->execute($event);

        } catch (\Exception $e){
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] send creditmemo webhook failed after saving, %s',
                    $e->getMessage()
                )
            );
        }
        return  $result;
    }


    /**
     * Verify is item valid for return qty to stock.
     *
     * @param string $sku
     * @param string|null $typeId
     * @return bool
     */
    private function isValidItem(string $sku, ?string $typeId): bool
    {
        //TODO: https://github.com/magento-engcom/msi/issues/1761
        // If product type located in table sales_order_item is "grouped" replace it with "simple"
        if ($typeId === 'grouped') {
            $typeId = 'simple';
        }

        $productType = $typeId ?: $this->getProductTypesBySkus->execute(
            [$sku]
        )[$sku];

        return $this->isSourceItemManagementAllowedForProductType->execute($productType);
    }
}

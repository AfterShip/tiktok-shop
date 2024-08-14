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

    public function __construct(
        LoggerInterface  $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
    }

    /**
     * send creditmemo webhook when saving creditmemo
     *
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $subject, $result
     * @return \Magento\Sales\Api\Data\CreditmemoRepositoryInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CreditmemoRepositoryInterface $subject, $result) {
        try {
            $creditmemoId = $result->getEntityId();

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
}

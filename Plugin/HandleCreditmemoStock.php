<?php

namespace AfterShip\TikTokShop\Plugin;

use AfterShip\TikTokShop\Constants;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Framework\App\RequestInterface;
use Magento\CatalogInventory\Model\StockManagement;
use Psr\Log\LoggerInterface;

class HandleCreditmemoStock
{
    /**
     * LoggerInterface Instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StockManagement
     */
    protected $stockManagement;

    public function __construct(
        LoggerInterface  $logger,
        RequestInterface $request,
        StockManagement $stockManagement
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->stockManagement = $stockManagement;
    }

    /**
     * Before plugin for refund method
     *
     * @param CreditmemoManagementInterface $subject
     * @param CreditmemoInterface $creditmemo
     * @param bool $offlineRequested
     * @return array
     */
    public function beforeRefund(
        CreditmemoManagementInterface $subject,
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        try {
            $actions = $this->request->getHeader(Constants::HEADER_INVENTORY_BEHAVIOUR, '');
                    $actions = explode(',', $actions);
            if (in_array(Constants::HEADER_INVENTORY_BEHAVIOUR_VALUE_INCREMENT, $actions)) {
                foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                    $creditmemoItem->setBackToStock(true);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] handle stock before refund failed, error msg: %s',
                    $e->getMessage()
                )
            );
        }
        return [$creditmemo, $offlineRequested];
    }
}

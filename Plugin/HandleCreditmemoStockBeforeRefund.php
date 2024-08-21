<?php

namespace AfterShip\TikTokShop\Plugin;

use AfterShip\TikTokShop\Constants;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class HandleCreditmemoStockBeforeRefund
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

    public function __construct(
        LoggerInterface  $logger,
        RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->request = $request;
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
        // todo@gerald debug log
        $this->logger->info(
            sprintf('[AfterShip TikTokShop HandleCreditmemoBeforeRefund] begin to handle stock'));

        $actions = $this->request->getHeader(Constants::HEADER_INVENTORY_BEHAVIOUR, '');
        $actions = explode(',', $actions);
        if (in_array(Constants::HEADER_INVENTORY_BEHAVIOUR_VALUE_INCREMENT, $actions)){
            // todo@gerald debug log
            $this->logger->info(
                sprintf('[AfterShip TikTokShop HandleCreditmemoBeforeRefund] begin to set back to stock'));

            foreach ($creditmemo->getAllItems() as $creditmemoItem){
                $creditmemoItem->setBackToStock(true);
            }
        }

        // 返回参数数组
        return [$creditmemo, $offlineRequested];
    }
}

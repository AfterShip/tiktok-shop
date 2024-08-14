<?php

namespace AfterShip\TikTokShop\Plugin;

use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Helper\CommonHelper;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;
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

    public function __construct(
        LoggerInterface  $logger,
        WebhookPublisher $publisher
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
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

<?php
/**
 * TikTokShop InventoryUpdateObserver
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Observer;

use Psr\Log\LoggerInterface;
use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AfterShip\TikTokShop\Helper\CommonHelper;
use Magento\Authorization\Model\UserContextInterface;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;

/**
 * Send webhook when get inventory update event.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class InventoryUpdateObserver implements ObserverInterface
{
    /**
     * UserContext Instance.
     *
     * @var UserContextInterface
     */
    protected $userContext;
    /**
     * Logger Instance.
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
     * Construct
     *
     * @param UserContextInterface $userContext
     * @param LoggerInterface $logger
     * @param WebhookPublisher $publisher
     * @param CommonHelper $commonHelper
     */
    public function __construct(
        UserContextInterface        $userContext,
        LoggerInterface $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper
    ) {
        $this->userContext = $userContext;
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
    }

    /**
     * IsRestfulApiRequest
     *
     * @return bool
     */
    public function isRestfulApiRequest()
    {
        $userType = $this->userContext->getUserType();
        return ($userType === UserContextInterface::USER_TYPE_INTEGRATION);
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
            if (!$this->isRestfulApiRequest()) {
                return;
            }
            if ($this->commonHelper->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] InventoryUpdateObserver do not sync inventory during performance test'
                );
                return;
            }
            $stockItem = $observer->getItem();
            $productId = $stockItem->getProductId();
            $event = new WebhookEvent();
            $event->setId($productId)
                ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                ->setEvent(Constants::WEBHOOK_EVENT_UPDATE);
            $this->publisher->execute($event);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] Failed to update product data on InventoryUpdateObserver, %s',
                    $e->getMessage()
                )
            );
        }
    }
}

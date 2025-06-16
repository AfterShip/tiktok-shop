<?php
/**
 * TikTokShop InventorySourceItemAfterExecute
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */
namespace AfterShip\TikTokShop\Plugin;

use Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class InventorySourceItemAfterExecute
 *
 * @package AfterShip\TikTokShop\Plugin
 */
class InventorySourceItemAfterExecute
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Event manager instance
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * Request instance
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * Product repository instance
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param ManagerInterface $eventManager
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        LoggerInterface $logger,
        ManagerInterface $eventManager,
        RequestInterface $request,
        ProductRepositoryInterface $productRepository
    ) {
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->productRepository = $productRepository;
    }

    /**
     * After execute plugin
     *
     * @param SaveMultiple $subject
     * @param mixed $result
     * @param array $sourceItems
     *
     * @return mixed
     */
    public function afterExecute(
        SaveMultiple $subject,
        $result,
        array $sourceItems
    ) {
        try {

            if (empty($sourceItems)) {
                return $result;
            }

            $this->eventManager->dispatch(
                'aftership_inventory_source_item_save_after',
                ['items' => $sourceItems]
            );
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf('[AfterShip TikTokShop] Inventory Source Item afterExecute error: %s', $e->getMessage())
            );
        }

        return $result;
    }

}

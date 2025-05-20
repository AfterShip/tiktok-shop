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
        // 检查是否来自 Stock Sources CSV import
        $isFromStockSourcesCsvImport = $this->_isFromStockSourcesCsvImport();
        
        $this->logger->info(
            'InventorySourceItemAfterExecute - 开始执行',
            [
                'source_items_count' => count($sourceItems),
                'is_from_csv' => $isFromStockSourcesCsvImport,
                'result' => $result
            ]
        );

        if (empty($sourceItems) || !$isFromStockSourcesCsvImport) {
            $this->logger->info(
                'InventorySourceItemAfterExecute - 跳过处理: ' . 
                (empty($sourceItems) ? 'sourceItems为空' : '非CSV导入操作')
            );
            return $result;
        }

        $this->eventManager->dispatch(
            'aftership_inventory_source_item_save_after',
            ['items' => $sourceItems]
        );

        return $result;
    }

    /**
     * 判断是否来自CSV导入
     *
     * @return bool
     */
    private function _isFromStockSourcesCsvImport()
    {
        // 通过请求路径判断
        $requestPath = $this->request->getPathInfo();
        
        // 检查是否为导入操作
        if (strpos($requestPath, '/admin/import/start/') !== false) {
            // 获取请求参数
            $entityType = $this->request->getParam('entity');
            
            $this->logger->info(
                'InventorySourceItemAfterExecute - 检查导入参数',
                [
                    'path' => $requestPath,
                    'entity_type' => $entityType,
                    'is_stock_sources' => $entityType === 'stock_sources'
                ]
            );

            // 检查是否为库存源导入
            if ($entityType === 'stock_sources') {
                return true;
            }
        }

        return false;
    }
}
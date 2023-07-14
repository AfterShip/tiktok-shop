<?php
/**
 * TikTokShop RecurringData
 * php version 7.1.0
 *
 * @category  AfterShip
 * @package   TikTokShop
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Setup;

use AfterShip\TikTokShop\Constants;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Integration\Model\Integration;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * RecurringData
 *
 * @category AfterShip
 * @package  TikTokShop
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class RecurringData implements InstallDataInterface
{

    /**
     * StoreRepositoryInterface
     *
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * Integration service
     *
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * AuthorizationServiceInterface
     *
     * @var AuthorizationServiceInterface $authorizationService
     */
    protected $authorizationService;

    /**
     * Construct
     *
     * @param StoreRepositoryInterface      $storeRepository      '$storeRepository'
     * @param IntegrationServiceInterface   $integrationService   '$integrationService'
     * @param AuthorizationServiceInterface $authorizationService '$authorizationService'
     */
    public function __construct(
        StoreRepositoryInterface      $storeRepository,
        IntegrationServiceInterface   $integrationService,
        AuthorizationServiceInterface $authorizationService
    ) {
        $this->storeRepository = $storeRepository;
        $this->integrationService = $integrationService;
        $this->authorizationService = $authorizationService;
    }

    /**
     * Isnstall
     *
     * @param ModuleDataSetupInterface $setup   '$setup'
     * @param ModuleContextInterface   $context '$context'
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\IntegrationException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $storeList = $this->storeRepository->getList();
        foreach ($storeList as $index => $item) {
            $storeId = $item->getId();
            if ($storeId == 0) {
                continue;
            }
            foreach (Constants::INTEGRATION_APPS as $app) {
                $this->createIntegration($this->buildIntegrationData($app, $storeId, $item->getCode()));
            }
        }
    }

    /**
     * BuildIntegrationData
     *
     * @param $app       'app'
     * @param $storeId   'store_id'
     * @param $storeCode 'storeCode'
     *
     * @return array
     */
    public function buildIntegrationData($app, $storeId, $storeCode)
    {
        $name = sprintf("AfterShip %s For Store: %s", ucfirst($app), $storeCode);
        $identityLinkUrl = sprintf("https://accounts.aftership.com/oauth/%s/magento-2/identity", $app);
        $endpoint = sprintf("https://accounts.aftership.com/oauth/%s/magento-2/callback?store_id=%d", $app, $storeId);
        $integrationData = [
        'name' => $name,
        'email' => 'apps@aftership.com',
        'endpoint' => $endpoint,
        'identity_link_url' => $identityLinkUrl
        ];
        return $integrationData;
    }

    /**
     * CreateIntegration
     *
     * @param $integrationData 'integrationData'
     *
     * @return Integration
     *
     * @throws \Magento\Framework\Exception\IntegrationException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createIntegration($integrationData)
    {
        $integration = $this->integrationService->findByName($integrationData['name']);
        if ($integration->getId()) {
            $integrationData[Integration::ID] = $integration->getId();
            $this->integrationService->update($integrationData);
        } else {
            $integration = $this->integrationService->create($integrationData);
        }
        $this->authorizationService->grantAllPermissions($integration->getId());
        return $integration;
    }
}

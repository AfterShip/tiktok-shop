<?php

namespace AfterShip\TikTokShop\Setup;

use AfterShip\TikTokShop\Constants;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Integration\Model\Integration;
use Magento\Store\Api\StoreRepositoryInterface;

class RecurringData implements InstallDataInterface
{

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * Integration service
     *
     * @var IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * @var AuthorizationServiceInterface $authorizationService
     */
    protected $authorizationService;

    public function __construct(
        StoreRepositoryInterface      $storeRepository,
        IntegrationServiceInterface   $integrationService,
        AuthorizationServiceInterface $authorizationService
    )
    {
        $this->storeRepository = $storeRepository;
        $this->integrationService = $integrationService;
        $this->authorizationService = $authorizationService;
    }

    /**
     * {@inheritdoc}
     */

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
		$storeList = $this->storeRepository->getList();
		foreach ($storeList as $index => $item) {
			$storeId = $item->getId();
			if ($storeId == 0) continue;
			foreach (Constants::INTEGRATION_APPS as $app) {
				$this->createIntegration($this->buildIntegrationData($app, $storeId, $item->getCode()));
			}
		}
    }

	/**
	 * @param $app
	 * @param $storeId
	 * @param $storeCode
	 * @return array
	 */
	private function buildIntegrationData($app, $storeId, $storeCode)
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
	 * @param $integrationData
	 * @return Integration
	 * @throws \Magento\Framework\Exception\IntegrationException
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	private function createIntegration($integrationData)
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

<?php

namespace AfterShip\TikTokShop\Model\Api;

use AfterShip\TikTokShop\Api\WebhookManagementInterface;
use AfterShip\TikTokShop\Api\WebhookEntityInterface;
use AfterShip\TikTokShop\Constants;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;

class WebhookManagement implements WebhookManagementInterface
{

	private $integrationId;
	/** @var ScopeConfigInterface */
	private $scopeConfig;
	/** @var WriterInterface */
	private $configWriter;
	/** @var TypeListInterface */
	private $cacheTypeList;

	/** @var array */
	private $webhooks = [];

	public function __construct(
		UserContextInterface $userContext,
		ScopeConfigInterface $scopeConfig,
		WriterInterface      $configWriter,
		TypeListInterface $cacheTypeList
	)
	{
		$this->integrationId = $userContext->getUserId();
		$this->cacheTypeList = $cacheTypeList;
		$this->scopeConfig = $scopeConfig;
		$this->configWriter = $configWriter;
		$webhooksJson = $this->scopeConfig->getValue(
			Constants::WEBHOOK_CONFIG_SCOPE_PATH,
			'default'
		);
		$this->webhooks = $webhooksJson ? json_decode($webhooksJson) : [];
	}

	/**
	 * @param WebhookEntityInterface $request
	 * @return mixed|null
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function registerWebhook($request = null)
	{
		if (!$request || !$request->getTopic() || !$request->getAddress() || !$request->getAppKey()) {
			throw new LocalizedException(
				new Phrase('The necessary parameters for creating a webhook are missing.'),
				null,
				400
			);
		}
		$webhookId = $request->getId();
		$request->setIntegrationId($this->integrationId);
		$webhook = [
			"id" => $webhookId,
			"topic" => $request->getTopic(),
			"app_key" => $request->getAppKey(),
			"address" => $request->getAddress(),
			"integration_id" => $this->integrationId,
		];
		$results = array_filter($this->webhooks, function ($item) use ($webhookId) {
			return $item->id === $webhookId;
		});
		$done = !count($results) && array_push($this->webhooks, $webhook) && $this->configWriter->save(
				Constants::WEBHOOK_CONFIG_SCOPE_PATH,
				json_encode($this->webhooks),
				'default'
			);
		$this->cacheTypeList->cleanType('config');
		return $request;
	}

	/**
	 * @return WebhookEntityInterface[]|array
	 */
	public function listWebhooks()
	{
		$webhooks = [];
		foreach ($this->webhooks as $webhook) {
			$entity = new WebhookRequest();
			$entity->setId($webhook->id);
			$entity->setTopic($webhook->topic);
			$entity->setAddress($webhook->address);
			$entity->setAppKey($webhook->app_key);
			$entity->setIntegrationId($webhook->integration_id);
			array_push($webhooks, $entity);
		}
		return $webhooks;
	}

	/**
	 * @param $webhookId
	 * @return WebhookRequest|null
	 */
	public function getWebhook($webhookId)
	{
		$entity = null;
		foreach ($this->webhooks as $webhook) {
			if ($webhook->id === $webhookId) {
				$entity = new WebhookRequest();
				$entity->setId($webhook->id);
				$entity->setTopic($webhook->topic);
				$entity->setAddress($webhook->address);
				$entity->setAppKey($webhook->app_key);
				$entity->setIntegrationId($webhook->integration_id);
			}
		}
		return $entity;
	}

	/**
	 * @param $webhookId
	 * @return WebhookRequest|null
	 */
	public function deleteWebhook($webhookId)
	{
		$entity = null;
		$filteredWebhooks = [];
		foreach ($this->webhooks as $webhook) {
			if ($webhook->id === $webhookId) {
				$entity = new WebhookRequest();
				$entity->setId($webhook->id);
				$entity->setTopic($webhook->topic);
				$entity->setAddress($webhook->address);
				$entity->setAppKey($webhook->app_key);
				$entity->setIntegrationId($webhook->integration_id);
			}else {
				array_push($filteredWebhooks, $webhook);
			}
		}
		$this->webhooks = $filteredWebhooks;
		$this->configWriter->save(
			Constants::WEBHOOK_CONFIG_SCOPE_PATH,
			json_encode($filteredWebhooks),
			'default'
		);
		$this->cacheTypeList->cleanType('config');
		return $entity;
	}
}

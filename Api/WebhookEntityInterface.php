<?php
namespace AfterShip\TikTokShop\Api;
interface WebhookEntityInterface
{
	const DATA_ID = 'id';
	const DATA_TOPIC = 'topic';
	const DATA_APP_KEY = 'app_key';
	const DATA_ADDRESS = 'address';
	const DATA_INTEGRATION_ID = 'integration_id';

	/**
	 * @return string
	 */
	public function getId();
	/**
	 * @return string
	 */
	public function getTopic();
	/**
	 * @return string
	 */
	public function getAppKey();
	/**
	 * @return string
	 */
	public function getAddress();
	/**
	 * @return string
	 */
	public function getIntegrationId();


	/**
	 * @param string $id
	 * @return string
	 */
	public function setId($id);
	/**
	 * @param string $topic
	 * @return $this
	 */
	public function setTopic($topic);
	/**
	 * @param string $app_key
	 * @return $this
	 */
	public function setAppKey($app_key);
	/**
	 * @param string $address
	 * @return $this
	 */
	public function setAddress($address);
	/**
	 * @param string $integrationId
	 * @return $this
	 */
	public function setIntegrationId($integrationId);
}

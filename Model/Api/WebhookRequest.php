<?php

namespace AfterShip\TikTokShop\Model\Api;

use Magento\Framework\DataObject;
use AfterShip\TikTokShop\Api\WebhookEntityInterface;

class WebhookRequest extends DataObject implements WebhookEntityInterface
{
	public function getId()
	{
		return md5($this->_getData(self::DATA_APP_KEY).'-'.$this->_getData(self::DATA_TOPIC).'-'.$this->_getData(self::DATA_ADDRESS));
	}

	public function getTopic()
	{
		return $this->_getData(self::DATA_TOPIC);
	}

	public function getAppKey()
	{
		return $this->_getData(self::DATA_APP_KEY);
	}

	public function getAddress()
	{
		return $this->_getData(self::DATA_ADDRESS);
	}

	public function getIntegrationId()
	{
		return $this->_getData(self::DATA_INTEGRATION_ID);
	}

	public function setId($id)
	{
		return $this->setData(self::DATA_ID, $id);
	}

	public function setTopic($topic)
	{
		return $this->setData(self::DATA_TOPIC, $topic);
	}

	public function setAppKey($app_key)
	{
		return $this->setData(self::DATA_APP_KEY, $app_key);
	}

	public function setAddress($address)
	{
		return $this->setData(self::DATA_ADDRESS, $address);
	}

	public function setIntegrationId($integration_id)
	{
		return $this->setData(self::DATA_INTEGRATION_ID, $integration_id);
	}
}

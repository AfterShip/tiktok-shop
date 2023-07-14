<?php
/**
 * TikTokShop WebhookRequest
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Model\Api;

use Magento\Framework\DataObject;
use AfterShip\TikTokShop\Api\WebhookEntityInterface;

/**
 * WebhookRequest model for webhook.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class WebhookRequest extends DataObject implements WebhookEntityInterface
{
    /**
     * GetId
     *
     * @return string
     */
    public function getId()
    {
        return hash_hmac(
            'sha256',
            sprintf(
                "%s-%s-%s",
                $this->_getData(self::DATA_APP_KEY),
                $this->_getData(self::DATA_TOPIC),
                $this->_getData(self::DATA_ADDRESS)
            ),
            'id'
        );
    }
    /**
     * GetTopic
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->_getData(self::DATA_TOPIC);
    }

    /**
     * GetAppKey
     *
     * @return string
     */
    public function getAppKey()
    {
        return $this->_getData(self::DATA_APP_KEY);
    }
    /**
     * GetAddress
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->_getData(self::DATA_ADDRESS);
    }

    /**
     * GetIntegrationId
     *
     * @return string
     */
    public function getIntegrationId()
    {
        return $this->_getData(self::DATA_INTEGRATION_ID);
    }

    /**
     * SetId.
     *
     * @param string $id
     *
     * @return WebhookRequest|string
     */
    public function setId($id)
    {
        return $this->setData(self::DATA_ID, $id);
    }

    /**
     * SetTopic
     *
     * @param string $topic
     *
     * @return WebhookRequest
     */
    public function setTopic($topic)
    {
        return $this->setData(self::DATA_TOPIC, $topic);
    }

    /**
     * SetAppKey
     *
     * @param string  $app_key
     *
     * @return WebhookRequest
     */
    public function setAppKey($app_key)
    {
        return $this->setData(self::DATA_APP_KEY, $app_key);
    }

    /**
     * SetAddress
     *
     * @param string $address
     *
     * @return WebhookRequest
     */
    public function setAddress($address)
    {
        return $this->setData(self::DATA_ADDRESS, $address);
    }

    /**
     * SetIntegrationId
     *
     * @param string $integration_id
     *
     * @return WebhookRequest
     */
    public function setIntegrationId($integration_id)
    {
        return $this->setData(self::DATA_INTEGRATION_ID, $integration_id);
    }
}

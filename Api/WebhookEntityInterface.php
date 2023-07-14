<?php
/**
 * TikTokShop WebhookEntityInterface
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Api;

/**
 * Interface for WebhookEntity.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
interface WebhookEntityInterface
{
    public const DATA_ID = 'id';
    public const DATA_TOPIC = 'topic';
    public const DATA_APP_KEY = 'app_key';
    public const DATA_ADDRESS = 'address';
    public const DATA_INTEGRATION_ID = 'integration_id';

    /**
     * GetId
     *
     * @return string
     */
    public function getId();
    /**
     * GetTopic
     *
     * @return string
     */
    public function getTopic();
    /**
     * GetAppKey
     *
     * @return string
     */
    public function getAppKey();
    /**
     * GetAddress
     *
     * @return string
     */
    public function getAddress();
    /**
     * GetIntegrationId
     *
     * @return string
     */
    public function getIntegrationId();

    /**
     * SetId
     *
     * @param string $id
     *
     * @return string
     */
    public function setId($id);
    /**
     * SetTopic
     *
     * @param string $topic
     *
     * @return $this
     */
    public function setTopic($topic);
    /**
     * SetAppKey
     *
     * @param string $app_key
     *
     * @return $this
     */
    public function setAppKey($app_key);
    /**
     * SetAddress
     *
     * @param string $address
     *
     * @return $this
     */
    public function setAddress($address);
    /**
     * SetIntegrationId
     *
     * @param string $integrationId
     *
     * @return $this
     */
    public function setIntegrationId($integrationId);
}

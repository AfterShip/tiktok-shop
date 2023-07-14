<?php
/**
 * TikTokShop WebhookEntityInterface
 * php version 7.1.0
 *
 * @category  AfterShip
 * @package   TikTokShop
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Api;
/**
 * WebhookEntityInterface
 *
 * @category AfterShip
 * @package  TikTokShop
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
interface WebhookEntityInterface
{
    const DATA_ID = 'id';
    const DATA_TOPIC = 'topic';
    const DATA_APP_KEY = 'app_key';
    const DATA_ADDRESS = 'address';
    const DATA_INTEGRATION_ID = 'integration_id';

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
     * @param string $id 'id'
     * 
     * @return string
     */
    public function setId($id);
    /**
     * SetTopic
     *
     * @param string $topic 'topic'
     *
     * @return $this
     */
    public function setTopic($topic);
    /**
     * SetAppKey
     *
     * @param string $app_key 'app_key'
     *
     * @return $this
     */
    public function setAppKey($app_key);
    /**
     * SetAddress
     *
     * @param string $address 'address'
     *
     * @return $this
     */
    public function setAddress($address);
    /**
     * SetIntegrationId
     *
     * @param string $integrationId 'integration_id'
     *
     * @return $this
     */
    public function setIntegrationId($integrationId);
}

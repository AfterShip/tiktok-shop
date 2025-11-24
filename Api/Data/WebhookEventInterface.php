<?php
/**
 * TikTokShop WebhookEventInterface
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for WebhookEvent.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
interface WebhookEventInterface extends ExtensibleDataInterface
{
    public const DATA_ID = 'id';
    public const DATA_RESOURCE = 'resource';
    public const DATA_EVENT = 'event';

    /**
     * GetId
     *
     * @return string
     */
    public function getId();
    /**
     * GetResource
     *
     * @return string
     */
    public function getResource();
    /**
     * GetEvent
     *
     * @return string
     */
    public function getEvent();

    /**
     * SetId
     *
     * @param string $id
     *
     * @return string
     */
    public function setId($id);
    /**
     * SetResource
     *
     * @param string $resource
     *
     * @return $this
     */
    public function setResource($resource);
    /**
     * SetEvent
     *
     * @param string $event
     *
     * @return $this
     */
    public function setEvent($event);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \AfterShip\TikTokShop\Api\Data\WebhookEventExtensionInterface|null
     * @see \AfterShip\TikTokShop\Api\Data\WebhookEventExtensionInterface
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \AfterShip\TikTokShop\Api\Data\WebhookEventExtensionInterface $extensionAttributes
     * @return $this
     * @see \AfterShip\TikTokShop\Api\Data\WebhookEventExtensionInterface
     */
    public function setExtensionAttributes(
        \AfterShip\TikTokShop\Api\Data\WebhookEventExtensionInterface $extensionAttributes
    );
}

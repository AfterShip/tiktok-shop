<?php
/**
 * TikTokShop WebhookEvent
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Model\Api;

use Magento\Framework\Api\AbstractExtensibleObject;
use AfterShip\TikTokShop\Api\Data\WebhookEventInterface;
use AfterShip\TikTokShop\Api\Data\WebhookEventExtensionInterface;

/**
 * WebhookEvent model for webhook.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class WebhookEvent extends AbstractExtensibleObject implements WebhookEventInterface
{
    /**
     * GetId
     *
     * @return string
     */
    public function getId()
    {
        return $this->_data[self::DATA_ID] ?? null;
    }
    /**
     * GetResource
     *
     * @return string
     */
    public function getResource()
    {
        return $this->_data[self::DATA_RESOURCE] ?? null;
    }

    /**
     * GetEvent
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->_data[self::DATA_EVENT] ?? null;
    }

    /**
     * SetId.
     *
     * @param string $id
     *
     * @return WebhookEvent|string
     */
    public function setId($id)
    {
        return $this->setData(self::DATA_ID, $id);
    }

    /**
     * SetResource
     *
     * @param string $resource
     *
     * @return WebhookEvent
     */
    public function setResource($resource)
    {
        return $this->setData(self::DATA_RESOURCE, $resource);
    }

    /**
     * SetEVent
     *
     * @param string  $event
     *
     * @return WebhookEvent
     */
    public function setEvent($event)
    {
        return $this->setData(self::DATA_EVENT, $event);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return WebhookEventExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     *
     * @param WebhookEventExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        WebhookEventExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

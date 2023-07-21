<?php
/**
 * TikTokShop WebhookEvent
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Model\Api;

use Magento\Framework\DataObject;
use AfterShip\TikTokShop\Api\Data\WebhookEventInterface;

/**
 * WebhookEvent model for webhook.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class WebhookEvent extends DataObject implements WebhookEventInterface
{
    /**
     * GetId
     *
     * @return string
     */
    public function getId()
    {
        return $this->_getData(self::DATA_ID);
    }
    /**
     * GetResource
     *
     * @return string
     */
    public function getResource()
    {
        return $this->_getData(self::DATA_RESOURCE);
    }

    /**
     * GetEvent
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->_getData(self::DATA_EVENT);
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
}

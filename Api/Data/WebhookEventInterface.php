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

/**
 * Interface for WebhookEvent.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
interface WebhookEventInterface
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
}

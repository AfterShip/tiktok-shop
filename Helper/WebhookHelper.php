<?php
/**
 * TikTokShop WebhookHelper
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Helper;

use AfterShip\TikTokShop\Constants;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Main helper function.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class WebhookHelper
{


    /**
     * Webhoks
     *
     * @var array|mixed
     */
    protected $webhooks = [];
    /**
     * ScopeConfig
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * OauthService
     *
     * @var OauthServiceInterface
     */
    protected $oauthService;
    /**
     * IntegrationService
     *
     * @var IntegrationServiceInterface
     */
    protected $integrationService;
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Construct
     *
     * @param ScopeConfigInterface        $scopeConfig
     * @param IntegrationServiceInterface $integrationService
     * @param OauthServiceInterface       $oauthService
     * @param LoggerInterface             $logger
     */
    public function __construct(
        ScopeConfigInterface        $scopeConfig,
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface       $oauthService,
        LoggerInterface             $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->oauthService = $oauthService;
        $this->logger = $logger;
        $this->integrationService = $integrationService;
        $webhooksJson = $this->scopeConfig->getValue(
            Constants::WEBHOOK_CONFIG_SCOPE_PATH,
            'default'
        );
        $this->webhooks = $webhooksJson ? json_decode($webhooksJson) : [];
    }

    /**
     * MakeWebhookRequest
     *
     * @param string $topic
     * @param array  $data
     *
     * @return void
     */
    public function makeWebhookRequest($topic, $data)
    {
        foreach ($this->webhooks as $webhook) {
            if ($webhook->topic !== $topic) {
                continue;
            }
            $this->sendWebhook($webhook, $data);
        }
    }

    /**
     * SendWebhook
     *
     * @param string $webhook
     * @param array $data
     *
     * @return bool|string
     */
    public function sendWebhook($webhook, $data)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
            CURLOPT_URL => $webhook->address,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Magento-Hmac-Sha256: ' . $this->createWebhookSecurity($webhook->integration_id, $data),
            'Content-Length: ' . strlen(json_encode($data)),
            'X-Webhook-Topic: ' . $webhook->topic,
            'X-App-Key: ' . $webhook->app_key
            ],
            ]
        );
        $response = curl_exec($curl);
        $err = curl_errno($curl);
        if ($err) {
            $this->logger->error(sprintf('[AfterShip TikTokShop] Unable to send webhook to %s with data: %s, response: %s', $webhook->address, json_encode($data), $response));
        }
        curl_close($curl);
        return $response;
    }

    /**
     * CreateWebhookSecurity
     *
     * @param string $integrationId
     * @param array  $data
     *
     * @return string
     */
    public function createWebhookSecurity($integrationId, $data)
    {
        $integration = $this->integrationService->get($integrationId);
        $consumer = $this->oauthService->loadConsumer($integration->getConsumerId());
        return hash_hmac('sha256', json_encode($data), $consumer->getKey());
    }
}

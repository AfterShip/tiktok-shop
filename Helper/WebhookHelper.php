<?php
/**
 * TikTokShop WebhookHelper
 * php version 7.1.0
 *
 * @category  AfterShip
 * @package   TikTokShop
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
 * WebhookHelper
 *
 * @category AfterShip
 * @package  TikTokShop
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
     * @param ScopeConfigInterface        $scopeConfig        'ScopeConfig'
     * @param IntegrationServiceInterface $integrationService 'IntegrationService'
     * @param OauthServiceInterface       $oauthService       'OauthService'
     * @param LoggerInterface             $logger             'Logger'
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
     * @param string $topic 'topoc'
     * @param array  $data  'data'
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
     * @param $webhook 'webhook'
     * @param $data    'data'
     *
     * @return bool|string
     */
    public function sendWebhook($webhook, $data)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl, [
            CURLOPT_URL => $webhook->address,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-Magento-Hmac-Sha256: ' . $this->createWebhookSecurity($webhook->integration_id, $data),
            'Content-Length: ' . strlen(json_encode($data)),
            'X-Webhook-Topic: ' . $webhook->topic,
            'X-App-Key: ' . $webhook->app_key
            ),
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
     * @param string $integrationId 'integrationId'
     * @param array  $data          'data'
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
<?php

namespace AfterShip\TikTokShop\Cron;

use AfterShip\TikTokShop\Constants;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Notification\NotifierInterface;
use Psr\Log\LoggerInterface;


class VersionCheck
{
    /**
     * @var NotifierInterface
     */
    protected $notifier;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;


    public function __construct(
        LoggerInterface $logger,
        NotifierInterface $notifier,
        ResourceConnection $resourceConnection
    ) {
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $packageName
     * @return int|string|null
     */
    private function getLatestVersionFromPackagist($packageName)
    {
        $url = 'https://packagist.org/packages/' . $packageName . '.json';
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        $versions = $data['package']['versions'];
        foreach ($versions as $version => $versionData) {
            if (strpos($version, 'dev-') !== false) {
                continue;
            }
            return $version;
        }
        return null;
    }

    public function execute()
    {
        try {
            $packageName = 'aftership/tiktok-shop';
            $currentVersion = Constants::AFTERSHIP_TIKTOK_SHOP_VERSION;
            $latestVersion = $this->getLatestVersionFromPackagist($packageName);
            // If failed to get the latest version, do not show the notification
            if (!$latestVersion) {
                return;
            }
            // If the current version is the same or newer than the latest version, do not show the notification
            if (version_compare($currentVersion, $latestVersion, '>=')) {
                return;
            }
            $title = 'TikTok Shop Plugin Update Available';
            $description = 'New version ' . $latestVersion . ' is available. Please update the plugin to the latest version.';
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('adminnotification_inbox');
            $select = $connection->select()
                ->from($tableName)
                ->where('title = ?', $title)->where('is_read = ?', 0)->where('is_remove = ?', 0);

            $records = $connection->fetchAll($select);
            // If the notification already exists, do not show the notification
            if (count($records) > 0) {
                return;
            }
            $this->notifier->addNotice($title, $description, sprintf('https://packagist.org/packages/%s', $packageName));
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('[AfterShip TikTokShop] Failed to check the latest version of the plugin: %s', $e->getMessage())
            );
        }
    }
}

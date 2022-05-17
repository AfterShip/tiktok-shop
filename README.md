# tiktok-shop

Aftership TikTok Shop extension for Magento 2. Allows connect with TikTok Shop and more.

## Prerequisites

Magento 2

### Install

  -  log into the Magento 2 server and cd into the root directory of the Magento app:
    -  Execute the following commands:
      - composer require aftership/tiktok-shop
      - php bin/magento module:enable AfterShip_TikTokShop --clear-static-content
      - php bin/magento setup:upgrade
      - php bin/magento setup:static-content:deploy -f

### Setup
  - From admin:
    - Go to stores > configuration
    - Find "TikTok Shop" in sidebar
    - Open Connect
    - Click the "Start now", Navigate to "TikTok Shop" and connect store
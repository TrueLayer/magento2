# TrueLayer for Magento® 2

The TrueLayer plugin makes it effortless to connect your Magento® 2 catalog with the TrueLayer Payment Services.

## Installation
Before you start the installation process, we recommend that you make a backup of your store, as well as the database.

You can use Composer to install this package. First, check if your server has Composer installed by running the following command:
```
composer –v
```

If your server doesn't have composer installed, you can easily install by following the instructions here: https://getcomposer.org/doc/00-intro.md

You can then install this Magento® 2 extension through Composer:

1.	Connect to your server running Magento® 2 using SSH or other method (make sure you have access to the command line).
2.	Locate your Magento® 2 project root.
3.	Install the extension through composer:
```
composer require truelayer/magento2
```
4.	Once completed run the following commands:
```
bin/magento module:enable TrueLayer_Connect
bin/magento setup:upgrade
bin/magento cache:flush
```
6.  If Magento® is running in production mode you also need to redeploy the static content:
```
bin/magento setup:static-content:deploy
```
7.  After the installation, go to your Magento® admin portal and open ‘Stores’ > ‘Configuration’ > ‘Sales’ > ‘TrueLayer’.

# Local development
A basic docker-compose configuration is provided to make local development easier. To start it, run the following:

```bash
DOCKER_DEFAULT_PLATFORM=linux/amd64 docker-compose up
```
You can login as an admin user at http://localhost:1234/admin using the following credentials:

| Username | Password |
| -------- | -------- |
| exampleuser | examplepassword123 |

# Testing webhooks
Webhook signature includes the path so make sure the webhook URL is configured in your Console and the path is set to rest/V1/webhook/transfer. 
The domain does not matter, as we will be using `truelayer-cli` to forward webhooks.

Run the following to forward webhooks to your local instance:
```
docker run --network="host" truelayer/truelayer-cli route-webhooks --to-addr http://localhost:1234/rest/V1/webhook/transfer --client-secret <client_secret> --client-id <client_id>
```

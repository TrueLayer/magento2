# TrueLayer for Magento® 2

The TrueLayer plugin makes it effortless to connect your Magento® 2 catalog with the TrueLayer Payment Services.

## Installation
To make the integration process as easy as possible for you, we have developed various plugins for your webshop software package.
This is the manual for installing the Magento® 2 Plugin.
Before you start up the installation process, we recommend that you make a backup of your webshop files, as well as the database.

There are 2 different methods to install the Magento® 2 extension.
1.	Install by using Composer
2.	Install by using the Magento® Marketplace (coming soon!)

### Installation using Composer ###
Magento® 2 use the Composer to manage the module package and the library. Composer is a dependency manager for PHP. Composer declare the libraries your project depends on and it will manage (install/update) them for you.

Check if your server has composer installed by running the following command:
```
composer –v
```
If your server doesn’t have composer installed, you can easily install it by using this manual: https://getcomposer.org/doc/00-intro.md

Step-by-step to install the Magento® 2 extension through Composer:

1.	Connect to your server running Magento® 2 using SSH or other method (make sure you have access to the command line).
2.	Locate your Magento® 2 project root.
3.	Install the Magento® 2 extension through composer and wait till it's completed:
```
composer require truelayer/magento2
```
4.	Once completed run the Magento® module enable command:
```
bin/magento module:enable TrueLayer_Connect
```
5.	After that run the Magento® upgrade and clean the caches:
```
php bin/magento setup:upgrade
php bin/magento cache:flush
```
6.  If Magento® is running in production mode you also need to redeploy the static content:
```
php bin/magento setup:static-content:deploy
```
7.  After the installation: Go to your Magento® admin portal and open ‘Stores’ > ‘Configuration’ > ‘Payment Methods’ > ‘TrueLayer’.

## Development by Magmodules

We are a Dutch agency dedicated to the development of extensions for Magento and Shopware. All our extensions are coded by our own team and our support team is always there to help you out.

[Visit Magmodules.eu](https://www.magmodules.eu/)

## Developed for TrueLayer

The TrueLayer plugin solves this, enabling businesses with a Magento 2 webshop a seamless way to integrate instant bank payments into their website with minimal technical and developer resources required.
[Visit Truelayer.com](https://truelayer.com/)

# Local development
A basic docker-compose configuration is provided to make local development easier. To start it, run the following:

```bash
DOCKER_DEFAULT_PLATFORM=linux/amd64 docker-compose up
```
You can login as an admin user at http://localhost:1234/admin using the following credentials:

| Username | Password |
| -------- | -------- |
| exampleuser | examplepassword123 |
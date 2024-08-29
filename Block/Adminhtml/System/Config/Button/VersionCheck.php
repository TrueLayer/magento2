<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Block\Adminhtml\System\Config\Button;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\HTTP\Client\Curl;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\LogServiceInterface;

/**
 * Version check class
 */
class VersionCheck extends Field
{

    /**
     * @var string
     */
    protected $_template = 'TrueLayer_Connect::system/config/button/version.phtml';

    /**
     * VersionCheck constructor.
     * @param Context $context
     * @param ConfigRepository $configRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        private ConfigRepository $configRepository,
        private Curl $curl,
        private LogServiceInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @inheritDoc
     */
    public function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Return saved version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->configRepository->getExtensionVersion();
    }

    public function getLatestVersion()
    {
        $curlVersion = $this->getCurlVersion();
        $this->curl->addHeader('Accept', 'application/vnd.github+json');
        $this->curl->addHeader('User-Agent', 'curl/'.$curlVersion);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->get('https://api.github.com/repos/TrueLayer/magento2/releases');
        $responseStatus = $this->curl->getStatus();
        if ($responseStatus !== 200) {
            $this->logger->error('Cron failed', [
                'response_status' => $responseStatus,
                'response_body' => $this->curl->getBody()
            ]);
            return false;
        } 
        $response = $this->curl->getBody();
        try {
            $releases = json_decode($response, true, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            $this->logger->error('Cannot decode response', [
                'response_body' => $response,
                'json_exception' => $e->getMessage()
            ]);
            return false;
        }
        foreach ($releases as $release) {
            if (!$release['draft'] && !$release['prerelease']) {
                $latestRelease = $release;
                break;
            }
        }
        if (!isset($latestRelease)) {
            $this->logger->error('Could not find latest release');
            return false;
        }
        $latestVersion = ltrim($latestRelease['name'], 'v');
        return $latestVersion;
    }

    private function getCurlVersion()
    {
        $curlVersion = curl_version();
        if (is_array($curlVersion) && array_key_exists('version', $curlVersion)) {
            $curlVersion = $curlVersion['version'];
        } else {
            $curlVersion = 'unknown';
        }
        return $curlVersion;
    }
}

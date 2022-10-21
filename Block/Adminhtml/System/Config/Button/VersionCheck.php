<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Block\Adminhtml\System\Config\Button;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Version check button class
 */
class VersionCheck extends Field
{

    /**
     * @var string
     */
    protected $_template = 'TrueLayer_Connect::system/config/button/version.phtml';

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * VersionCheck constructor.
     * @param Context $context
     * @param ConfigRepository $configRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigRepository $configRepository,
        array $data = []
    ) {
        $this->configRepository = $configRepository;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->configRepository->getExtensionVersion();
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getVersionCheckUrl(): string
    {
        return $this->getUrl('truelayer/versionCheck/index');
    }

    /**
     * @return string
     */
    public function getChangeLogUrl(): string
    {
        return $this->getUrl('truelayer/versionCheck/changelog');
    }

    /**
     * @return string
     */
    public function getButtonHtml(): string
    {
        try {
            return $this->getLayout()
                ->createBlock(Button::class)
                ->setData([
                    'id' => 'mm-truelayer-button_version',
                    'label' => __('Check for latest versions')
                ])
                ->toHtml();
        } catch (Exception $e) {
            return '';
        }
    }
}

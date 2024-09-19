<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Block\Adminhtml\System\Config\Button;

use Exception;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Error log check button class
 */
class ErrorCheck extends Field
{

    /**
     * @var string
     */
    protected $_template = 'TrueLayer_Connect::system/config/button/error.phtml';

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getErrorCheckUrl(): string
    {
        return $this->getUrl('truelayer/log/stream', ['type' => 'error']);
    }

    /**
     * @return string
     */
    public function getButtonHtml(): string
    {
        try {
            /** @var \Magento\Framework\View\Element\AbstractBlock $block */
            $block = $this->getLayout()->createBlock(Button::class);
            $block->setData([
                'id' => 'truelayer-button_error',
                'label' => __('Check last 100 error log records')
            ]);
            return $block->toHtml();
        } catch (Exception $e) {
            return '';
        }
    }
}

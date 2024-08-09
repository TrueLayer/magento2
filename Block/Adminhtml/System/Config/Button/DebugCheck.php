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
 * Debug log check button class
 */
class DebugCheck extends Field
{

    /**
     * @var string
     */
    protected $_template = 'TrueLayer_Connect::system/config/button/debug.phtml';

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
    public function getDebugCheckUrl(): string
    {
        return $this->getUrl('truelayer/log/stream', ['type' => 'debug']);
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
                    'id' => 'truelayer-button_debug',
                    'class' => 'tl-btn',
                    'label' => __('View debug log')
                ])->toHtml();
        } catch (Exception $e) {
            return '';
        }
    }
}

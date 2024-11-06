<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use TrueLayer\Connect\Model\Config\Source\Mode;

/**
 * Color picker for admin config field
 */
class Base64FileUpload extends Field
{
    protected $_template = 'TrueLayer_Connect::system/config/button/base64-file-upload.phtml';

    // public function
    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $htmlTextInputId = $element->getHtmlId();
        $mode = $element->getData('field_config')['depends']['fields']['mode']['value'] ?? Mode::SANDBOX;
        $fieldType = $element->getData('field_config')['type'] ?? 'text';
        $tooltip = $element->getTooltip();
        $element->setTooltip();
        $displayValue = $element->getValue();
        if ($displayValue && $fieldType == 'obscure') {
            $displayValue = '******';
        }

        $this->setData([
            'htmlTextInputId' => $htmlTextInputId,
            'mode' => $mode,
            'fieldType' => $fieldType,
            'displayValue' => $displayValue,
            'tooltip' => $tooltip,
        ]);
        return $this->_toHtml();
    }
}

<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Color picker for admin config field
 */
class ColorPicker extends Field
{

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $element->getElementHtml() .
            '<script type="text/javascript">
            require(["jquery","jquery/colorpicker/js/colorpicker"], function ($) {
                $(document).ready(function () {
                    var thisElement = $("#' . $element->getHtmlId() . '");
                    thisElement.css("backgroundColor", "' . $element->getData('value') . '");
                    thisElement.ColorPicker({
                        color: "' . $element->getData('value') . '",
                        onChange: function (hsb, hex, rgb) {
                            thisElement.css("backgroundColor", "#" + hex).val("#" + hex);
                        }
                    });
                });
            });
            </script>';
    }
}

<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Block\Adminhtml\Design;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * System Configuration Module Heading Render Block
 */
class Header extends Field
{

    /**
     * @var string
     */
    protected $_template = 'TrueLayer_Connect::system/config/fieldset/header.phtml';

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->addClass('truelayer');

        return $this->toHtml();
    }
}

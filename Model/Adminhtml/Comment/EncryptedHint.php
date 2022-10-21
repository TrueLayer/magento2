<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Adminhtml\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;

class EncryptedHint extends AbstractBlock implements CommentInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * CurrentClientKey constructor.
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $elementValue
     * @return Phrase|string
     */
    public function getCommentText($elementValue)
    {
        if (empty($elementValue)) {
            return '';
        }

        return __(
            'The current value starts with %1 ....',
            '<strong>' . substr($this->encryptor->decrypt($elementValue), 0, 3) . '</strong>'
        );
    }
}

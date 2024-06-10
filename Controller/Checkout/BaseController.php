<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;

abstract class BaseController implements HttpGetActionInterface
{
    protected Context $context;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * @param string $to
     * @param array $arguments
     * @return ResponseInterface
     */
    protected function redirect(string $to, array $arguments = []): ResponseInterface
    {
        $response = $this->context->getResponse();
        $this->context->getRedirect()->redirect($response, $to, $arguments);
        return $response;
    }
}

<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Plugin\Payment;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use Magento\Payment\Model\MethodList as Subject;

/**
 * Restrict payment depends on grand-total
 */
class MethodList
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * MethodList constructor.
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @param Subject $subject
     * @param $availableMethods
     * @param CartInterface|null $quote
     * @return mixed
     */
    public function afterGetAvailableMethods(
        Subject $subject,
        $availableMethods,
        CartInterface $quote = null
    ) {
        $minTotal = $this->configRepository->getMinimumOrderTotal();
        if ($minTotal && ($minTotal >= $quote->getGrandTotal())) {
            $availableMethods = $this->excludeTruelayer($availableMethods);
        }

        $maxTotal = $this->configRepository->getMaximumOrderTotal();
        if ($maxTotal && ($maxTotal <= $quote->getGrandTotal())) {
            $availableMethods = $this->excludeTruelayer($availableMethods);
        }

        return $availableMethods;
    }

    /**
     * @param $availableMethods
     * @return MethodInterface[]
     */
    private function excludeTruelayer($availableMethods): array
    {
        foreach ($availableMethods as $key => $method) {
            if ($method->getCode() == 'truelayer') {
                unset($availableMethods[$key]);
            }
        }
        return $availableMethods;
    }
}

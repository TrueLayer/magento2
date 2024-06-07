<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Service\Order\PaymentFailureReasonService;
use TrueLayer\Interfaces\Payment\PaymentFailedInterface;
use TrueLayer\Interfaces\Payment\PaymentRetrievedInterface;
use TrueLayer\Interfaces\Payment\PaymentSettledInterface;

/**
 * Process Controller
 */
class Process extends BaseController
{
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @var TransactionRepository
     */
    private TransactionRepository $transactionRepository;

    /**
     * @var PaymentFailureReasonService
     */
    private PaymentFailureReasonService $paymentFailureReasonService;

    /**
     * @var LogRepository
     */
    private LogRepository $logger;



    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ClientFactory $clientFactory,
        TransactionRepository $transactionRepository,
        PaymentFailureReasonService $paymentFailureReasonService,
        LogRepository $logRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->clientFactory = $clientFactory;
        $this->transactionRepository = $transactionRepository;
        $this->paymentFailureReasonService = $paymentFailureReasonService;
        $this->logger = $logRepository->prefix('Process');
        parent::__construct($context);
    }

    public function execute()
    {
        $payment = $this->getTruelayerPayment();

        if (!$payment) {
            $this->logger->error('Could not load TL payment');
            $this->context->getMessageManager()->addErrorMessage(__('No payment found'));
            return $this->redirect('checkout/cart/index');
        }

        if ($payment instanceof PaymentSettledInterface) {
            return $this->redirect('checkout/onepage/success');
        }

        if ($payment instanceof PaymentFailedInterface) {
            $message = $this->paymentFailureReasonService->getHumanReadableLabel($payment->getFailureReason());
            $this->context->getMessageManager()->addErrorMessage($message);
            return $this->redirect('checkout/onepage/failure');
        }

        return $this->redirect('truelayer/checkout/pending', ['payment_id' => $payment->getId()]);
    }

    /**
     * @return PaymentRetrievedInterface|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\SignerException
     */
    private function getTruelayerPayment(): ?PaymentRetrievedInterface
    {
        $paymentId = $this->context->getRequest()->getParam('payment_id');

        if (!$paymentId) {
            return null;
        }

        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($paymentId);
            $order = $this->orderRepository->get($transaction->getOrderId());
            $client = $this->clientFactory->create((int)$order->getStoreId());
            return $client->getPayment($paymentId);
        } catch (\Exception $e) {
            $this->logger->error('Could not load TL payment', $e);
        }

        return null;
    }
}

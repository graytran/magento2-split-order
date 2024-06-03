<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Quote;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface as Logger;

class Deactivator
{
    public final const EXCEPTION_MESSAGE = 'split order deactivate original quote error: ';
    public function __construct(
        protected EventManager $eventManager,
        protected CartRepositoryInterface $quoteRepository,
        protected CheckoutSession $checkoutSession,
        protected Logger $logger,
    ) {
    }

    public function execute(Quote|CartInterface $quote, array $orderIds, array $realOrderIds): void
    {
        try {
            $quote->setIsActive(false);
            $this->eventManager->dispatch(
                'split_order_deactivate_quote_success',
                [
                    'quote' => $quote
                ]
            );
            $this->quoteRepository->save($quote);
            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->checkoutSession->setLastSplitOrderIds($orderIds);
            $this->checkoutSession->setLastSplitRealOrderIds($realOrderIds);
            $this->checkoutSession->setLastOrderId(reset($orderIds));
        } catch (\Exception $exception) {
            $this->logger->critical(
                self::EXCEPTION_MESSAGE . $exception->getMessage(),
                [
                    'trace' => $exception->getTraceAsString()
                ]
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Local\SplitOrder\Plugin\Magento\Quote\Model;

use Local\SplitOrder\Model\Config;
use Local\SplitOrder\Model\Quote\Deactivator;
use Local\SplitOrder\Model\Quote\Splitter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteManagement as Subject;

class QuoteManagement
{

    public function __construct(
        protected CartRepositoryInterface $quoteRepository,
        protected Splitter $quoteSplitter,
        protected Config $config,
        protected Deactivator $quoteDeactivator,
    ) {
    }

    public function aroundPlaceOrder(
        Subject $subject,
        callable $proceed,
        int $cartId,
        PaymentInterface $paymentMethod = null
    ): int|string {
        if (!$this->config->isSplitOrderEnabled()) {
            return $proceed();
        }

        $quote = $this->quoteRepository->getActive($cartId);
        $quoteSplitters = $this->quoteSplitter->execute($quote, $quote->getPayment());

        if (empty($quoteSplitters)) {
            return $proceed();
        }

        foreach ($quoteSplitters as $quoteSplitter) {
            $order = $subject->submit($quoteSplitter);
        }
        $this->quoteDeactivator->execute($quote, $order);

        return $order->getId();
    }
}

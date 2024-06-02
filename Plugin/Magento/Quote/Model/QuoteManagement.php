<?php

declare(strict_types=1);

namespace Local\SplitOrder\Plugin\Magento\Quote\Model;

use Local\SplitOrder\Model\Config;
use Local\SplitOrder\Model\Order\Item\Handler;
use Local\SplitOrder\Model\Order\Item\Resolver;
use Local\SplitOrder\Model\Quote\Splitter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement as Subject;

class QuoteManagement
{

    public function __construct(
        protected Resolver $orderItemResolver,
        protected QuoteFactory $quoteFactory,
        protected CartRepositoryInterface $quoteRepository,
        protected Splitter $quoteSplitter,
        protected Config $config,
    ) {
    }


    public function afterPlaceOrder(
        Subject $subject,
        string|int $result,
        int $cartId,
        PaymentInterface $paymentMethod = null
    ): int|string {
        if (!$this->config->isSplitOrderEnabled()) {
            return $result;
        }

        $quote = $this->quoteRepository->get($cartId);
        $quoteSplitters = $this->quoteSplitter->execute($quote, $paymentMethod);
        $orderIds = [];

        foreach ($quoteSplitters as $quoteSplitter) {
            $orderIds[] = $subject->submit($quoteSplitter)->getId();
        }

        return implode(',', $orderIds);
    }
}

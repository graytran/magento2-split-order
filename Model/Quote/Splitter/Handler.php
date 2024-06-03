<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Quote\Splitter;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;

class Handler
{
    public final const SPLIT_ORDER_MARK_KEY = 'is_split_order';

    public function __construct(
        protected CartRepositoryInterface $quoteRepository,
        protected QuoteFactory $quoteFactory,
    ) {
    }

    public function execute(Quote $originalQuote, array $splitQuoteItems, PaymentInterface $paymentMethod = null): Quote
    {
        $quoteSplit = $this->quoteFactory->create();
        $this->handleQuoteData($quoteSplit, $originalQuote);
        $this->handleItem($quoteSplit, $splitQuoteItems);
        $quoteSplit->setTotalsCollectedFlag(false)->collectTotals();
        $this->handleQuotePayment($quoteSplit, $originalQuote, $paymentMethod);
        $this->quoteRepository->save($quoteSplit);

        return $quoteSplit;
    }

    protected function handleQuotePayment(Quote $quoteSplit, Quote $originalQuote, PaymentInterface $paymentMethod = null): void
    {
        $paymentMethodString = $originalQuote->getPayment()->getMethod();
        $quoteSplit->getPayment()->setMethod($paymentMethodString);
        if ($paymentMethod) {
            $quoteSplit->getPayment()->setQuote($quoteSplit);
            $data = $paymentMethod->getData();
            $quoteSplit->getPayment()->importData($data);
        }
    }

    protected function handleQuoteData(Quote $quoteSplit, Quote $originalQuote): void
    {
        list($billingAddress, $shippingAddress) = $this->cloneAddress($originalQuote);
        $quoteSplit->setStoreId($originalQuote->getStoreId());
        $quoteSplit->setCustomer($originalQuote->getCustomer());
        $quoteSplit->setCustomerIsGuest($originalQuote->getCustomerIsGuest());
        $quoteSplit->getBillingAddress()->setData($billingAddress);
        $quoteSplit->getShippingAddress()->setData($shippingAddress);
        $quoteSplit->setData(self::SPLIT_ORDER_MARK_KEY, true);
        if ($originalQuote->getCheckoutMethod() === Onepage::METHOD_GUEST) {
            $quoteSplit->setCustomerEmail($originalQuote->getBillingAddress()->getEmail());
            $quoteSplit->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }
    }

    protected function handleItem(Quote $quoteSplit, array $splitQuoteItems): void
    {
        // save quoteSplit in order to have a quote id for item
        $this->quoteRepository->save($quoteSplit);
        foreach ($splitQuoteItems as $item) {
            $item->setId(null);
            $quoteSplit->addItem($item);
        }
    }

    protected function cloneAddress(Quote $quote): array
    {
        $billingAddress = $quote->getBillingAddress()->getData();
        $shippingAddress = $quote->getShippingAddress()->getData();
        unset($billingAddress['id']);
        unset($billingAddress['quote_id']);
        unset($shippingAddress['id']);
        unset($shippingAddress['quote_id']);

        return [$billingAddress, $shippingAddress];
    }
}

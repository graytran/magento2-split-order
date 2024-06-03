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
    public final const CACHED_ITEMS_ALL = 'cached_items_all';

    public function __construct(
        protected CartRepositoryInterface $quoteRepository,
        protected QuoteFactory $quoteFactory,
    ) {
    }

    public function execute(Quote $originalQuote, array $splitQuoteItems, PaymentInterface $paymentMethod): Quote
    {
        $quoteSplit = $this->quoteFactory->create();
        $this->handleGeneralData($quoteSplit, $originalQuote);
        $this->handleItem($quoteSplit, $splitQuoteItems);
        $this->handleAddress($quoteSplit, $originalQuote, $splitQuoteItems);
        $this->handleQuotePayment($quoteSplit, $paymentMethod);
        $this->quoteRepository->save($quoteSplit);

        return $quoteSplit;
    }

    protected function handleQuotePayment(Quote $quoteSplit, PaymentInterface $paymentMethod): void
    {
        $paymentMethodString = $paymentMethod->getMethod();
        $quoteSplit->getPayment()->setMethod($paymentMethodString);
        $quoteSplit->getPayment()->setQuote($quoteSplit);
        $data = $paymentMethod->getData();
        $quoteSplit->getPayment()->importData($data);

        $quoteSplit->setTotalsCollectedFlag(false)->collectTotals();
    }

    protected function handleGeneralData(Quote $quoteSplit, Quote $originalQuote): void
    {
        $quoteSplit->setStoreId($originalQuote->getStoreId());
        $quoteSplit->setCustomer($originalQuote->getCustomer());
        $quoteSplit->setCustomerIsGuest($originalQuote->getCustomerIsGuest());
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

    protected function handleAddress(Quote $quoteSplit, Quote $originalQuote, array $splitQuoteItems): void
    {
        $billingAddress = $originalQuote->getBillingAddress()->getData();
        $shippingAddress = $originalQuote->getShippingAddress()->getData();
        unset($billingAddress['id']);
        unset($billingAddress['quote_id']);
        unset($shippingAddress['id']);
        unset($shippingAddress['quote_id']);
        $shippingAddress[self::CACHED_ITEMS_ALL] = $splitQuoteItems;
        $quoteSplit->getBillingAddress()->setData($billingAddress);
        $quoteSplit->getShippingAddress()->setData($shippingAddress);
    }
}

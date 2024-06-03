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
    public final const CACHED_ITEMS_ALL = 'cached_items_all';

    public function __construct(
        protected CartRepositoryInterface $quoteRepository,
        protected QuoteFactory $quoteFactory,
    ) {
    }

    public function execute(Quote $originalQuote, array $splitQuoteItems, PaymentInterface $paymentMethod): Quote
    {
        $splitQuote = $this->quoteFactory->create();
        $this->handleGeneralData($splitQuote, $originalQuote);
        $this->handleItem($splitQuote, $splitQuoteItems);
        $this->handleAddress($splitQuote, $originalQuote, $splitQuoteItems);
        $this->handleQuotePayment($splitQuote, $paymentMethod);
        $this->quoteRepository->save($splitQuote);

        return $splitQuote;
    }

    protected function handleQuotePayment(Quote $splitQuote, PaymentInterface $paymentMethod): void
    {
        $paymentMethodString = $paymentMethod->getMethod();
        $splitQuote->getPayment()->setMethod($paymentMethodString);
        $splitQuote->getPayment()->setQuote($splitQuote);
        $data = $paymentMethod->getData();
        $splitQuote->getPayment()->importData($data);

        $splitQuote->setTotalsCollectedFlag(false)->collectTotals();
    }

    protected function handleGeneralData(Quote $splitQuote, Quote $originalQuote): void
    {
        $splitQuote->setStoreId($originalQuote->getStoreId());
        $splitQuote->setCustomer($originalQuote->getCustomer());
        $splitQuote->setCustomerIsGuest($originalQuote->getCustomerIsGuest());
        if ($originalQuote->getCheckoutMethod() === Onepage::METHOD_GUEST) {
            $splitQuote->setCustomerEmail($originalQuote->getBillingAddress()->getEmail());
            $splitQuote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        }
    }

    protected function handleItem(Quote $splitQuote, array $splitQuoteItems): void
    {
        // save quoteSplit in order to have a quote id for item
        $this->quoteRepository->save($splitQuote);
        foreach ($splitQuoteItems as $item) {
            $item->setId(null);
            $splitQuote->addItem($item);
        }
    }

    protected function handleAddress(Quote $splitQuote, Quote $originalQuote, array $splitQuoteItems): void
    {
        $billingAddress = $originalQuote->getBillingAddress()->getData();
        $shippingAddress = $originalQuote->getShippingAddress()->getData();
        unset($billingAddress['id']);
        unset($billingAddress['quote_id']);
        unset($shippingAddress['id']);
        unset($shippingAddress['quote_id']);
        $shippingAddress[self::CACHED_ITEMS_ALL] = $splitQuoteItems;
        $splitQuote->getBillingAddress()->setData($billingAddress);
        $splitQuote->getShippingAddress()->setData($shippingAddress);
    }
}

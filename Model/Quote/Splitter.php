<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Quote;

use Local\SplitOrder\Model\Quote\Item\Splitter as ItemSplitter;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface as Logger;

class Splitter
{
    public final const SPLIT_ORDER_ITEM_NUMBER_THRESHOLD = 2;

    public function __construct(
        protected ItemSplitter $quoteItemSpliter,
        protected QuoteFactory $quoteFactory,
        protected CartRepositoryInterface $quoteRepository,
        protected Logger $logger,
    ) {
    }

    public function execute(Quote|CartInterface $quote, PaymentInterface $paymentMethod = null): array
    {
        $items = $quote->getAllItems();
        if (count($items) < self::SPLIT_ORDER_ITEM_NUMBER_THRESHOLD) {
            return [];
        }

        list($billingAddress, $shippingAddress) = $this->cloneAddress($quote);
        $splitQuoteItems = $this->quoteItemSpliter->execute($items);
        $paymentMethodString = $quote->getPayment()->getMethod();

        $quoteSplits = [];
        try {
            foreach ($splitQuoteItems as $splitQuoteItem) {
                $quoteSplit = $this->quoteFactory->create();
                $quoteSplit->setStoreId($quote->getStoreId());
                $quoteSplit->setCustomer($quote->getCustomer());
                $quoteSplit->setCustomerIsGuest($quote->getCustomerIsGuest());
                if ($quote->getCheckoutMethod() === Onepage::METHOD_GUEST) {
                    $quoteSplit->setCustomerEmail($quote->getBillingAddress()->getEmail());
                    $quoteSplit->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
                }

                // save quoteSplit in order to have a quote id for item
                $this->quoteRepository->save($quoteSplit);
                foreach ($splitQuoteItem as $item) {
                    $item->setId(null);
                    $item->setFreeShipping(true);
                    $quoteSplit->addItem($item);
                }

                $quoteSplit->getBillingAddress()->setData($billingAddress);
                $quoteSplit->getShippingAddress()->setData($shippingAddress);
                $quoteSplit->setData('is_split_order', true);

                $quoteSplit->setTotalsCollectedFlag(false)->collectTotals();

                $quoteSplit->getPayment()->setMethod($paymentMethodString);
                if ($paymentMethod) {
                    $quoteSplit->getPayment()->setQuote($quoteSplit);
                    $data = $paymentMethod->getData();
                    $quoteSplit->getPayment()->importData($data);
                }
                $this->quoteRepository->save($quoteSplit);

                $quoteSplits[] = $quoteSplit;
            }
        } catch (\Exception $exception) {
            $this->logger->critical(
                'there is an error happened when split quote: ' . $exception->getMessage(),
                [
                    'trace' => $exception->getTraceAsString()
                ]
            );
        }

        return $quoteSplits;
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

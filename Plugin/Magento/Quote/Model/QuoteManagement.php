<?php

declare(strict_types=1);

namespace Local\SplitOrder\Plugin\Magento\Quote\Model;

use Local\SplitOrder\Model\Order\Item\Handler;
use Local\SplitOrder\Model\Order\Item\Resolver;
use Local\SplitOrder\Model\Order\Item\Splitter as ItemSplitter;
use Local\SplitOrder\Model\Quote\Item\Spliter;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement as Subject;

class QuoteManagement
{

    public function __construct(
        protected Resolver $orderItemResolver,
        protected Spliter $orderItemSpliter,
        protected QuoteFactory $quoteFactory,
        protected CartRepositoryInterface $quoteRepository,
        protected ItemSplitter $quoteItemSpliter,
    ) {
    }


    public function afterPlaceOrder(
        Subject $subject,
        string|int $result,
        int $cartId,
        PaymentInterface $paymentMethod = null
    ) {
        $quote = $this->quoteRepository->get($cartId);
        $billingAddress = $quote->getBillingAddress()->getData();
        $shippingAddress = $quote->getShippingAddress()->getData();
        unset($billingAddress['id']);
        unset($billingAddress['quote_id']);
        unset($shippingAddress['id']);
        unset($shippingAddress['quote_id']);
        $splitOrderItems = $this->quoteItemSpliter->execute($quote);
        $paymentMethodString = $quote->getPayment()->getMethod();

        foreach ($splitOrderItems as $splitOrderItem) {
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
            foreach ($splitOrderItem as $item) {
                $item->setId(null);
                $quoteSplit->addItem($item);
            }

            $quoteSplit->getBillingAddress()->setData($billingAddress);
            $quoteSplit->getShippingAddress()->setData($shippingAddress);

            $quoteSplit->setTotalsCollectedFlag(false)->collectTotals();

            $quoteSplit->getPayment()->setMethod($paymentMethodString);
            if ($paymentMethod) {
                $quoteSplit->getPayment()->setQuote($quoteSplit);
                $data = $paymentMethod->getData();
                $quoteSplit->getPayment()->importData($data);
            }
            $this->quoteRepository->save($quoteSplit);
            $subject->submit($quoteSplit);
        }
    }
}

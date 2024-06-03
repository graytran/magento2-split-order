<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Model\Quote;

use Local\SplitOrder\Model\Quote\Item\Splitter as ItemSplitter;
use Local\SplitOrder\Model\Quote\Splitter\Handler;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Payment;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface as Logger;
use Magento\Quote\Model\Quote\Address\Total\Shipping as Subject;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\Model\Quote\Splitter as SubjectUnderTest;
use Magento\Quote\Api\CartRepositoryInterface;


class TestSplitter extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;
    protected ItemSplitter|MockInterface $quoteItemSpliter;
    protected QuoteFactory|MockInterface $quoteFactory;
    protected CartRepositoryInterface|MockInterface $quoteRepository;
    protected Logger|MockInterface $logger;

    public function setUp(): void
    {
        $this->quoteItemSpliter = $this->getItemSplitterMock();
        $this->quoteFactory = $this->getQuoteFactoryMock();
        $this->quoteRepository = $this->getCartRepositoryMock();
        $this->logger = $this->getLoggerMock();
        $this->subjectUnderTest = new SubjectUnderTest(
            $this->quoteItemSpliter,
            $this->quoteFactory,
            $this->quoteRepository,
            $this->logger,
        );
    }

    protected function mockData()
    {
        yield "case guest checkout" => [
            'is_guest' => true,
            'checkout_method' => Onepage::METHOD_GUEST,
        ];
        yield "case not guest checkout" => [
            'is_guest' => false,
            'checkout_method' => Onepage::METHOD_CUSTOMER,
        ];
    }

    /**
     * @dataProvider mockData
     */
    public function testExecute(bool $isGuest, string $checkoutMethod)
    {
        $quoteItem1 = $this->getQuoteItemMock();
        $quoteItem2 = $this->getQuoteItemMock();
        $quoteItem3 = $this->getQuoteItemMock();
        $addressData = [
            'id' => 1,
            'quote_id' => 1,
            'address_data' => 'test'
        ];
        $items = [
            $quoteItem1,
            $quoteItem2,
            $quoteItem3,
        ];
        $splitItems = [
            [
                $quoteItem1,
                $quoteItem2,
            ],
            [
                $quoteItem3
            ],
        ];

        $quote = $this->getQuoteMock();
        $shippingAddress = $this->getAddressMock();
        $billingAddress = $this->getAddressMock();
        $quotePayment = $this->getPaymentMock();
        $paymentMethod = $this->getPaymentMethodMock();
        $quoteSplit1 = $this->mockQuoteSplit($quote, $paymentMethod, $isGuest, $checkoutMethod, $billingAddress);
        $quoteSplit2 = $this->mockQuoteSplit($quote, $paymentMethod, $isGuest, $checkoutMethod, $billingAddress);

        $quote->shouldReceive('getAllItems')->andReturn($items);
        $quote->shouldReceive('getShippingAddress')->andReturn($shippingAddress);
        $quote->shouldReceive('getBillingAddress')->andReturn($billingAddress);
        $shippingAddress->shouldReceive('getData')->andReturn($addressData);
        $billingAddress->shouldReceive('getData')->andReturn($addressData);

        $this->quoteItemSpliter->shouldReceive('execute')->with($items)->andReturn($splitItems);
        $quote->shouldReceive('getPayment')->andReturn($quotePayment)->once();
        $quotePayment->shouldReceive('getMethod')->andReturn('flatrate_flatrate')->once();

        $quoteItem1->shouldReceive('setid')->with(null)->andReturnSelf();
        $quoteItem2->shouldReceive('setid')->with(null)->andReturnSelf();
        $quoteItem3->shouldReceive('setid')->with(null)->andReturnSelf();
        $quoteSplit1->shouldReceive('addItem')->andReturnSelf();
        $quoteSplit1->shouldReceive('addItem')->andReturnSelf();
        $quoteSplit2->shouldReceive('addItem')->andReturnSelf();

        $result = $this->subjectUnderTest->execute($quote);

        $this->assertEquals(2, count($result));
        $this->assertInstanceOf(Quote::class, reset($result));
    }

    protected function mockQuoteSplit($quote, $paymentMethod, $isGuest, $checkoutMethod, $billingAddress)
    {
        $quoteSplit = $this->getQuoteMock();
        $customer = $this->getCustomerMock();
        $quoteSplitShippingAddress = $this->getAddressMock();
        $quoteSplitBillingAddress = $this->getAddressMock();
        $quoteSplitPayment = $this->getPaymentMock();
        $this->quoteFactory->shouldReceive('create')->andReturn($quoteSplit);
        $quote->shouldReceive('getStoreId')->andReturn(1);
        $quote->shouldReceive('getCustomer')->andReturn($customer);
        $quote->shouldReceive('getCustomerIsGuest')->andReturn($isGuest);
        $quoteSplit->shouldReceive('setStoreId')->with(1)->andReturnSelf()->once();
        $quoteSplit->shouldReceive('setCustomer')->with($customer)->andReturnSelf()->once();
        $quoteSplit->shouldReceive('setCustomerIsGuest')->with($isGuest)->andReturnSelf()->once();

        $quote->shouldReceive('getCheckoutMethod')->andReturn($checkoutMethod);
        if ($checkoutMethod === Onepage::METHOD_GUEST) {
            $billingAddress->shouldReceive('getEmail')->andReturn('test@gmail.com');
            $quoteSplit->shouldReceive('setCustomerEmail')->with('test@gmail.com')->andReturnSelf();
            $quoteSplit->shouldReceive('setCustomerGroupId')->with(GroupInterface::NOT_LOGGED_IN_ID)->andReturnSelf();
        }

        $this->quoteRepository->shouldReceive('save')->with($quoteSplit)->andReturnNull()->twice();
        $quoteSplit->shouldReceive('getBillingAddress')->andReturn($quoteSplitBillingAddress);
        $quoteSplit->shouldReceive('getShippingAddress')->andReturn($quoteSplitShippingAddress);
        $quoteSplitBillingAddress->shouldReceive('setData')->with(['address_data' => 'test'])->andReturnSelf();
        $quoteSplitShippingAddress->shouldReceive('setData')->with(['address_data' => 'test'])->andReturnSelf();
        $quoteSplit->shouldReceive('setData')->with(Handler::SPLIT_ORDER_MARK_KEY, true)->andReturnSelf();
        $quoteSplit->shouldReceive('setTotalsCollectedFlag')->with(false)->andReturnSelf();
        $quoteSplit->shouldReceive('collectTotals')->andReturnSelf();
        $quoteSplit->shouldReceive('getPayment')->andReturn($quoteSplitPayment);
        $quoteSplitPayment->shouldReceive('setMethod')->with('flatrate_flatrate')->andReturnSelf();
        $quoteSplitPayment->shouldReceive('setQuote')->with($quoteSplit)->andReturnSelf();
        $paymentMethod->shouldReceive('getData')->andReturn(['payment_data']);
        $quoteSplitPayment->shouldReceive('importData')->with(['payment_data'])->andReturnSelf();

        return $quoteSplit;
    }

    public function testExecuteCaseNotSplit()
    {
        $quote = $this->getQuoteMock();
        $quoteItem = $this->getQuoteItemMock();
        $items = [$quoteItem];
        $quote->shouldReceive('getAllItems')->andReturn($items);
        $result = $this->subjectUnderTest->execute($quote);
        $this->assertEquals([], $result);
    }

    protected function getCustomerMock(): CustomerInterface|MockInterface
    {
        return Mockery::mock(CustomerInterface::class);
    }

    protected function getItemSplitterMock(): ItemSplitter|MockInterface
    {
        return Mockery::mock(ItemSplitter::class);
    }

    protected function getQuoteFactoryMock(): QuoteFactory|MockInterface
    {
        return Mockery::mock(QuoteFactory::class);
    }

    protected function getQuoteMock(): Quote|MockInterface
    {
        return Mockery::mock(Quote::class);
    }

    protected function getCartRepositoryMock(): CartRepositoryInterface|MockInterface
    {
        return Mockery::mock(CartRepositoryInterface::class);
    }

    protected function getLoggerMock(): Logger|MockInterface
    {
        return Mockery::mock(Logger::class);
    }

    protected function getPaymentMethodMock(): PaymentInterface|MockInterface
    {
        return Mockery::mock(PaymentInterface::class);
    }

    protected function getPaymentMock(): Payment|MockInterface
    {
        return Mockery::mock(Payment::class);
    }

    protected function getAddressMock(): Address|MockInterface
    {
        return Mockery::mock(Address::class);
    }

    protected function getQuoteItemMock(): Item|MockInterface
    {
        return Mockery::mock(Item::class);
    }
}

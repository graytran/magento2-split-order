<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Model\Quote\Splitter;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Customer;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\Model\Quote\Splitter\Handler as SubjectUnderTest;
use Magento\Quote\Model\Quote\Address;

class TestHandler extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;
    protected CartRepositoryInterface|MockInterface $quoteRepository;
    protected QuoteFactory|MockInterface $quoteFactory;

    public function setUp(): void
    {
        $this->quoteFactory = $this->getQuoteFactoryMock();
        $this->quoteRepository = $this->getCartRepositoryMock();

        $this->subjectUnderTest = new SubjectUnderTest(
            $this->quoteRepository,
            $this->quoteFactory,
        );
    }

    protected function mockData()
    {
        yield "case checkout guest" => [
            'is_guest' => true,
            'checkout_method' => Onepage::METHOD_GUEST
        ];
    }

    /**
     * @dataProvider mockData
     */
    public function testExecute($isGuest, $checkoutMethod)
    {
        $originalQuote = $this->getQuoteMock();
        $splitQuote = $this->getQuoteMock();
        $customer = $this->getCustomerMock();
        $originalQuoteBillingAddress = $this->getAddressMock();
        $originalQuoteShippingAddress = $this->getAddressMock();
        $splitQuoteBillingAddress = $this->getAddressMock();
        $splitQuoteShippingAddress = $this->getAddressMock();
        $splitQuoteItem1 = $this->getQuoteItemMock();
        $splitQuoteItem2 = $this->getQuoteItemMock();
        $splitQuoteItems = [$splitQuoteItem1, $splitQuoteItem2];
        $addressData = ['id'=>1, 'quote_id' => 1, 'test_data'];
        $splitQuoteShippingAddressData = ['test_data', SubjectUnderTest::CACHED_ITEMS_ALL => $splitQuoteItems];
        $storeId = 1;
        $paymentMethodString = 'checkmo';
        $paymentMethod = $this->getPaymentMethodMock();
        $paymentData = [];
        $splitQuotePayment = $this->getPaymentMethodMock();

        $this->quoteFactory->shouldReceive('create')->andReturn($splitQuote);
        //handleGeneralData
        $splitQuote->shouldReceive('setStoreId')->with($storeId)->andReturnSelf();
        $splitQuote->shouldReceive('setCustomer')->with($customer)->andReturnSelf();
        $splitQuote->shouldReceive('setCustomerIsGuest')->with($isGuest)->andReturnSelf();

        $originalQuote->shouldReceive('getStoreId')->andReturn($storeId);
        $originalQuote->shouldReceive('getCustomer')->andReturn($customer);
        $originalQuote->shouldReceive('getCustomerIsGuest')->andReturn($isGuest);
        $originalQuote->shouldReceive('getCheckoutMethod')->andReturn($checkoutMethod);
        if ($checkoutMethod === Onepage::METHOD_GUEST) {
            $originalQuote->shouldReceive('getBillingAddress')->andReturn($originalQuoteBillingAddress);
            $splitQuote->shouldReceive('setCustomerEmail')->with('email@gmail.com')->andReturnSelf();
            $splitQuote->shouldReceive('setCustomerGroupId')->with(GroupInterface::NOT_LOGGED_IN_ID)->andReturnSelf();
            $originalQuoteBillingAddress->shouldReceive('getEmail')->andReturn('email@gmail.com');
        }
        //handleItem
        $this->quoteRepository->shouldReceive('save')->with($splitQuote)->andReturnNull()->twice();
        $splitQuoteItem1->shouldReceive('setId')->with(null)->andReturnSelf();
        $splitQuoteItem2->shouldReceive('setId')->with(null)->andReturnSelf();
        $splitQuote->shouldReceive('addItem')->with($splitQuoteItem1)->andReturnSelf();
        $splitQuote->shouldReceive('addItem')->with($splitQuoteItem2)->andReturnSelf();
        //handleAddress
        $originalQuote->shouldReceive('getBillingAddress')->andReturn($originalQuoteBillingAddress);
        $originalQuote->shouldReceive('getShippingAddress')->andReturn($originalQuoteShippingAddress);
        $originalQuoteBillingAddress->shouldReceive('getData')->andReturn($addressData);
        $originalQuoteShippingAddress->shouldReceive('getData')->andReturn($addressData);

        $splitQuote->shouldReceive('getBillingAddress')->andReturn($splitQuoteBillingAddress);
        $splitQuote->shouldReceive('getShippingAddress')->andReturn($splitQuoteShippingAddress);
        $splitQuoteBillingAddress->shouldReceive('setData')->with(['test_data']);
        $splitQuoteShippingAddress->shouldReceive('setData')->with($splitQuoteShippingAddressData);
        //handleQuotePayment
        $paymentMethod->shouldReceive('getMethod')->andReturn($paymentMethodString);
        $splitQuote->shouldReceive('getPayment')->andReturn($splitQuotePayment);
        $splitQuotePayment->shouldReceive('setMethod')->with($paymentMethodString)->andReturnSelf();
        $splitQuotePayment->shouldReceive('setQuote')->with($splitQuote)->andReturnSelf();
        $paymentMethod->shouldReceive('getData')->andReturn($paymentData);
        $splitQuotePayment->shouldReceive('importData')->with($paymentData)->andReturnSelf();
        $splitQuote->shouldReceive('setTotalsCollectedFlag')->with(false)->andReturnSelf();
        $splitQuote->shouldReceive('collectTotals')->andReturnSelf();

        $result = $this->subjectUnderTest->execute($originalQuote, $splitQuoteItems, $paymentMethod);
        $this->assertEquals($splitQuote, $result);
    }

    protected function getPaymentMethodMock(): PaymentInterface|MockInterface
    {
        return Mockery::mock(PaymentInterface::class);
    }

    protected function getQuoteItemMock(): Item|MockInterface
    {
        return Mockery::mock(Item::class);
    }

    protected function getAddressMock(): Address|MockInterface
    {
        return Mockery::mock(Address::class);
    }

    protected function getCustomerMock(): CustomerInterface|MockInterface
    {
        return Mockery::mock(CustomerInterface::class);
    }

    protected function getQuoteMock(): Quote|MockInterface
    {
        return Mockery::mock(Quote::class);
    }

    protected function getCartRepositoryMock(): CartRepositoryInterface|MockInterface
    {
        return Mockery::mock(CartRepositoryInterface::class);
    }

    protected function getQuoteFactoryMock(): QuoteFactory|MockInterface
    {
        return Mockery::mock(QuoteFactory::class);
    }
}

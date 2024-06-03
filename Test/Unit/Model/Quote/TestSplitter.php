<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Model\Quote;

use Exception;
use Local\SplitOrder\Model\Quote\Item\Splitter as ItemSplitter;
use Local\SplitOrder\Model\Quote\Splitter\Handler;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Exception\LocalizedException;
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
    protected Handler|MockInterface $quoteSplitHandler;
    protected CartRepositoryInterface|MockInterface $quoteRepository;
    protected Logger|MockInterface $logger;

    public function setUp(): void
    {
        $this->quoteItemSpliter = $this->getItemSplitterMock();
        $this->quoteSplitHandler = $this->getQuoteSplitHandlerMock();
        $this->quoteRepository = $this->getCartRepositoryMock();
        $this->logger = $this->getLoggerMock();
        $this->subjectUnderTest = new SubjectUnderTest(
            $this->quoteItemSpliter,
            $this->quoteSplitHandler,
            $this->quoteRepository,
            $this->logger,
        );
    }

    public function testExecuteException()
    {
        $quote = $this->getQuoteMock();
        $quoteItem = $this->getQuoteItemMock();
        $quoteItem2 = $this->getQuoteItemMock();
        $quoteItem3 = $this->getQuoteItemMock();
        $items = [$quoteItem, $quoteItem2, $quoteItem3];
        $paymentMethod =$this->getPaymentMethodMock();
        $quote->shouldReceive('getAllItems')->andReturn($items);
        $splitQuoteItemsArray = [
            [$quoteItem, $quoteItem2],
            [$quoteItem3]
        ];
        $splitQuote1 = $this->getQuoteMock();

        $this->quoteItemSpliter->shouldReceive('execute')->andReturn($splitQuoteItemsArray);
        $this->quoteSplitHandler->shouldReceive('execute')->with($quote, [$quoteItem, $quoteItem2], $paymentMethod)->andReturn($splitQuote1);
        $exception = new Exception('test');
        $this->quoteSplitHandler->shouldReceive('execute')->with($quote, [$quoteItem3], $paymentMethod)->andThrow($exception);
        $this->logger->shouldReceive('critical')->with(
            SubjectUnderTest::EXCEPTION_MESSAGE . 'test',
            [
                'trace' => $exception->getTraceAsString()
            ]
        );
        $this->expectException(LocalizedException::class);
        $this->subjectUnderTest->execute($quote, $paymentMethod);
    }

    public function testExecute()
    {
        $quote = $this->getQuoteMock();
        $quoteItem = $this->getQuoteItemMock();
        $quoteItem2 = $this->getQuoteItemMock();
        $quoteItem3 = $this->getQuoteItemMock();
        $items = [$quoteItem, $quoteItem2, $quoteItem3];
        $paymentMethod =$this->getPaymentMethodMock();
        $quote->shouldReceive('getAllItems')->andReturn($items);
        $splitQuoteItemsArray = [
            [$quoteItem, $quoteItem2],
            [$quoteItem3]
        ];
        $splitQuote1 = $this->getQuoteMock();
        $splitQuote2 = $this->getQuoteMock();

        $this->quoteItemSpliter->shouldReceive('execute')->andReturn($splitQuoteItemsArray);
        $this->quoteSplitHandler->shouldReceive('execute')->with($quote, [$quoteItem, $quoteItem2], $paymentMethod)->andReturn($splitQuote1);
        $this->quoteSplitHandler->shouldReceive('execute')->with($quote, [$quoteItem3], $paymentMethod)->andReturn($splitQuote2);

        $result = $this->subjectUnderTest->execute($quote, $paymentMethod);

        $this->assertEquals(2, count($result));
        $this->assertEquals([$splitQuote1, $splitQuote2], ($result));
        $this->assertInstanceOf(Quote::class, reset($result));
    }


    public function testExecuteCaseNotSplit()
    {
        $quote = $this->getQuoteMock();
        $quoteItem = $this->getQuoteItemMock();
        $items = [$quoteItem];
        $paymentMethod =$this->getPaymentMethodMock();
        $quote->shouldReceive('getAllItems')->andReturn($items);
        $result = $this->subjectUnderTest->execute($quote, $paymentMethod);
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

    protected function getQuoteSplitHandlerMock(): Handler|MockInterface
    {
        return Mockery::mock(Handler::class);
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

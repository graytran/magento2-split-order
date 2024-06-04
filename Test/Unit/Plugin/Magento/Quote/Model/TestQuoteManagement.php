<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Plugin\Magento\Quote\Model;

use Local\SplitOrder\Model\Config;
use Local\SplitOrder\Model\Quote\Deactivator;
use Local\SplitOrder\Model\Quote\Splitter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement as Subject;
use Magento\Sales\Api\Data\OrderInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\Plugin\Magento\Quote\Model\QuoteManagement as SubjectUnderTest;

class TestQuoteManagement extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;
    protected CartRepositoryInterface|MockInterface $quoteRepository;
    protected Splitter|MockInterface $quoteSplitter;
    protected Config|MockInterface $config;
    protected Deactivator|MockInterface $quoteDeactivator;

    public function setUp(): void
    {
        $this->config = $this->getConfigMock();
        $this->quoteSplitter = $this->getQuoteSplitterMock();
        $this->quoteRepository = $this->getQuoteRepositoryMock();
        $this->quoteDeactivator = $this->getQuoteDeactivatorMock();
        $this->subjectUnderTest = new SubjectUnderTest(
            $this->quoteRepository,
            $this->quoteSplitter,
            $this->config,
            $this->quoteDeactivator
        );
    }

    public function testAroundPlaceOrderCaseDisableSplitOrder()
    {
        $this->config->shouldReceive('isSplitOrderEnabled')->andReturn(false)->once();
        $orderId = '123';
        $cartId = 1;
        $paymentMethod = $this->getPaymentMethodMock();
        $subject = $this->getSubjectMock();
        $proceed = function () {
            return '123';
        };

        $result = $this->subjectUnderTest->aroundPlaceOrder($subject, $proceed, $cartId, $paymentMethod);
        $this->assertEquals($orderId, $result);
    }

    public function testAroundPlaceOrderCaseEnableSplitOrderMoreThanTwoItem()
    {
        $this->config->shouldReceive('isSplitOrderEnabled')->andReturn(true)->once();
        $cartId = 1;
        $quote = $this->getQuoteMock();
        $paymentMethod = $this->getPaymentMethodMock();
        $subject = $this->getSubjectMock();
        $proceed = function () {
            return '123';
        };

        $splitQuote1 = $this->getQuoteMock();
        $splitQuote2 = $this->getQuoteMock();
        $splitOrder1 = $this->getOrderMock();
        $splitOrder2 = $this->getOrderMock();
        $splitQuotes = [$splitQuote1, $splitQuote2];

        $this->quoteRepository->shouldReceive('getActive')->with($cartId)->andReturn($quote);
        $quote->shouldReceive('getPayment')->andReturn($paymentMethod);
        $this->quoteSplitter->shouldReceive('execute')->with($quote, $paymentMethod)->andReturn($splitQuotes);
        $subject->shouldReceive('submit')->with($splitQuote1)->andReturn($splitOrder1);
        $subject->shouldReceive('submit')->with($splitQuote2)->andReturn($splitOrder2);
        $splitOrder1->shouldReceive('getId')->andReturn('1');
        $splitOrder1->shouldReceive('getIncrementId')->andReturn('001');
        $splitOrder2->shouldReceive('getId')->andReturn('2');
        $splitOrder2->shouldReceive('getIncrementId')->andReturn('002');
        $this->quoteDeactivator->shouldReceive('execute')->with($quote, ['1', '2'], ['001', '002']);

        $result = $this->subjectUnderTest->aroundPlaceOrder($subject, $proceed, $cartId);
        $this->assertEquals('2', $result);
    }


    public function testAroundPlaceOrderCaseEnableSplitOrderLessThanTwoItem()
    {
        $this->config->shouldReceive('isSplitOrderEnabled')->andReturn(true)->once();
        $cartId = 1;
        $quote = $this->getQuoteMock();
        $paymentMethod = $this->getPaymentMethodMock();
        $subject = $this->getSubjectMock();
        $proceed = function () {
            return '1';
        };

        $this->quoteRepository->shouldReceive('getActive')->with($cartId)->andReturn($quote);
        $quote->shouldReceive('getPayment')->andReturn($paymentMethod);
        $this->quoteSplitter->shouldReceive('execute')->with($quote, $paymentMethod)->andReturn([]);

        $result = $this->subjectUnderTest->aroundPlaceOrder($subject, $proceed, $cartId, $paymentMethod);
        $this->assertEquals('1', $result);
    }

    protected function getOrderMock()
    {
        return Mockery::mock(OrderInterface::class);
    }

    protected function getQuoteMock(): Quote|MockInterface
    {
        return Mockery::mock(Quote::class);
    }

    protected function getSubjectMock(): Subject|MockInterface
    {
        return Mockery::mock(Subject::class);
    }

    protected function getPaymentMethodMock(): PaymentInterface|MockInterface
    {
        return Mockery::mock(PaymentInterface::class);
    }

    protected function getConfigMock(): Config|MockInterface
    {
        return Mockery::mock(Config::class);
    }


    protected function getQuoteSplitterMock(): Splitter|MockInterface
    {
        return Mockery::mock(Splitter::class);
    }

    protected function getQuoteRepositoryMock(): CartRepositoryInterface|MockInterface
    {
        return Mockery::mock(CartRepositoryInterface::class);
    }
    protected function getQuoteDeactivatorMock(): Deactivator|MockInterface
    {
        return Mockery::mock(Deactivator::class);
    }

}

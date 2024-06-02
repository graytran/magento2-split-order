<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Plugin\Magento\Quote\Model;

use Local\SplitOrder\Model\Config;
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

    public function setUp(): void
    {
        $this->config = $this->getConfigMock();
        $this->quoteSplitter = $this->getQuoteSplitterMock();
        $this->quoteRepository = $this->getQuoteRepositoryMock();
        $this->subjectUnderTest = new SubjectUnderTest(
            $this->quoteRepository,
            $this->quoteSplitter,
            $this->config
        );
    }

    public function testAfterPlaceOrderCaseDisableSplitOrder()
    {
        $this->config->shouldReceive('isSplitOrderEnabled')->andReturn(false)->once();
        $orderId = '1';
        $cartId = 1;
        $paymentMethod = $this->getPaymentMethodMock();
        $subject = $this->getSubjectMock();

        $result = $this->subjectUnderTest->afterPlaceOrder($subject, $orderId, $cartId, $paymentMethod);
        $this->assertEquals($orderId, $result);
    }

    protected function mockSplitQuoteData()
    {
        $splitOrder1 = $this->getOrderMock();
        $splitOrder2 = $this->getOrderMock();
        $splitOrder1->shouldReceive('getId')->andReturn('2');
        $splitOrder2->shouldReceive('getId')->andReturn('3');

        yield "case item < 2 not split quote" => [
            'split_quotes' => [],
            'split_orders' => [],
            'expected' => '1'
        ];

        yield "case item > 2 not split quote" => [
            'split_quotes' => [
                $this->getQuoteMock(),
                $this->getQuoteMock()
            ],
            'split_orders' => [
                $splitOrder1,
                $splitOrder2,
            ],
            'expected' => '2,3'
        ];
    }

    /**
     * @dataProvider mockSplitQuoteData
     */
    public function testAfterPlaceOrderCaseEnableSplitOrder($splitQuotes, $splitOrders, $expeccted)
    {
        $this->config->shouldReceive('isSplitOrderEnabled')->andReturn(true)->once();
        $orderId = '1';
        $cartId = 1;
        $quote = $this->getQuoteMock();
        $paymentMethod = $this->getPaymentMethodMock();
        $subject = $this->getSubjectMock();

        $this->quoteRepository->shouldReceive('get')->with($cartId)->andReturn($quote);
        $this->quoteSplitter->shouldReceive('execute')->with($quote, $paymentMethod)->andReturn($splitQuotes);
        foreach ($splitQuotes as $key => $splitQuote) {
            $subject->shouldReceive('submit')->with($splitQuote)->andReturn($splitOrders[$key])->once();
        }

        $result = $this->subjectUnderTest->afterPlaceOrder($subject, $orderId, $cartId, $paymentMethod);
        $this->assertEquals($expeccted, $result);
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

}

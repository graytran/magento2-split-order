<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Model\Quote;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface as Logger;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\Model\Quote\Deactivator as SubjectUnderTest;
use Magento\Quote\Api\CartRepositoryInterface;


class TestDeactivator extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;
    protected EventManager|MockInterface $eventManager;
    protected CheckoutSession|MockInterface $checkoutSession;
    protected CartRepositoryInterface|MockInterface $quoteRepository;
    protected Logger|MockInterface $logger;

    public function setUp(): void
    {
        $this->eventManager = $this->getEventManagerMock();
        $this->checkoutSession = $this->getCheckoutSessionMock();
        $this->quoteRepository = $this->getCartRepositoryMock();
        $this->logger = $this->getLoggerMock();
        $this->subjectUnderTest = new SubjectUnderTest(
            $this->eventManager,
            $this->quoteRepository,
            $this->checkoutSession,
            $this->logger,
        );
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testExecute()
    {
        $quote = $this->getQuoteMock();
        $quoteId = 1;
        $orderIds = ['12', '24'];
        $realOrderIds = ['0012', '0024'];
        $quote->shouldReceive('getId')->andReturn($quoteId)->twice();
        $quote->shouldReceive('setIsActive')->with(false)->andReturnSelf();
        $this->eventManager->shouldReceive('dispatch')->with(
            'split_order_deactivate_quote_success',
            [
                'quote' => $quote
            ]
        );
        $this->quoteRepository->shouldReceive('save')->with($quote)->andReturnNull();
        $this->checkoutSession->shouldReceive('setLastQuoteId')->with($quoteId);
        $this->checkoutSession->shouldReceive('setLastSuccessQuoteId')->with($quoteId);
        $this->checkoutSession->shouldReceive('setLastSplitOrderIds')->with($orderIds);
        $this->checkoutSession->shouldReceive('setLastSplitRealOrderIds')->with($realOrderIds);
        $this->checkoutSession->shouldReceive('setLastOrderId')->with(reset($orderIds));

        $this->subjectUnderTest->execute($quote, $orderIds, $realOrderIds);
    }

    public function testExecuteException()
    {
        $quote = $this->getQuoteMock();
        $quoteId = 1;
        $orderIds = ['12', '24'];
        $realOrderIds = ['0012', '0024'];
        $quote->shouldReceive('getId')->andReturn($quoteId)->twice();
        $quote->shouldReceive('setIsActive')->with(false)->andReturnSelf();
        $this->eventManager->shouldReceive('dispatch')->with(
            'split_order_deactivate_quote_success',
            [
                'quote' => $quote
            ]
        );
        $exception = new Exception('test');
        $this->quoteRepository->shouldReceive('save')->with($quote)->andThrow($exception);

        $this->logger->shouldReceive('critical')->with(
            SubjectUnderTest::EXCEPTION_MESSAGE . 'test',
            [
                'trace' => $exception->getTraceAsString()
            ]
        );

        $this->expectException(LocalizedException::class);
        $this->subjectUnderTest->execute($quote, $orderIds, $realOrderIds);
    }

    protected function getEventManagerMock(): EventManager|MockInterface
    {
        return Mockery::mock(EventManager::class);
    }

    protected function getCheckoutSessionMock(): CheckoutSession|MockInterface
    {
        return Mockery::mock(CheckoutSession::class);
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
}

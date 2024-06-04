<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\ViewModel\SplitOrderSuccess as SubjectUnderTest;

class TestSplitOrderSuccess extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;
    protected CheckoutSession|MockInterface $checkoutSession;
    protected UrlInterface|MockInterface $urlBuilder;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->getCheckoutSessionMock();
        $this->urlBuilder = $this->getUrlBuilderMock();

        $this->subjectUnderTest = new SubjectUnderTest(
            $this->checkoutSession,
            $this->urlBuilder
        );
    }

    public function testGetLastSplitOrderIds()
    {
        $this->checkoutSession->shouldReceive('getLastSplitOrderIds')->andReturn(['1', '2']);
        $this->checkoutSession->shouldReceive('getLastSplitRealOrderIds')->andReturn(['001', '002']);

        $this->assertEquals(['1' => '001', '2' => '002'], $this->subjectUnderTest->getLastSplitOrderIds());
    }

    public function testGetViewOrderUrl()
    {
        $orderId = 1;
        $this->urlBuilder->shouldReceive('getUrl')->with(
            'sales/order/view/',
            ['order_id' => $orderId, '_secure' => true]
        )->andReturn('baseurl/sales/order/view/id/1');

        $this->assertEquals('baseurl/sales/order/view/id/1', $this->subjectUnderTest->getViewOrderUrl($orderId));
    }

    protected function getCheckoutSessionMock()
    {
        return Mockery::mock(CheckoutSession::class);
    }
    protected function getUrlBuilderMock()
    {
        return Mockery::mock(UrlInterface::class);
    }
}

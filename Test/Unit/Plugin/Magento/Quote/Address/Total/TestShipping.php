<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Plugin\Magento\Quote\Address\Total;

use Local\SplitOrder\Model\Quote\Splitter;
use Local\SplitOrder\Model\Quote\Splitter\Handler;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\Shipping as Subject;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\Plugin\Magento\Quote\Model\Quote\Address\Total\Shipping as SubjectUnderTest;


class TestShipping extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;

    public function setUp(): void
    {
        $this->subjectUnderTest = new SubjectUnderTest();
    }

    protected function mockData()
    {
        yield "case split order" => [
            'is_split_order' => true
        ];
        yield "case not split order" => [
            'is_split_order' => null
        ];
    }

    /**
     * @dataProvider mockData
     * @doesNotPerformAssertions
     */
    public function testAfterCollect(bool|null $isSplitOrder)
    {
        $quote = $this->getQuoteMock();
        $quote->shouldReceive('getData')->with(Handler::SPLIT_ORDER_MARK_KEY)->andReturn($isSplitOrder);
        $total = $this->getTotalMock();
        $shippingAssignment = $this->getShippingAssignmentMock();
        $subject = $this->getSubjectMock();
        if ($isSplitOrder) {
            $total->shouldReceive('setBaseShippingAmount')->with(0)->andReturnSelf();
            $total->shouldReceive('setShippingAmount')->with(0)->andReturnSelf();
        }

        $this->subjectUnderTest->afterCollect($subject, $subject, $quote, $shippingAssignment, $total);
    }

    protected function getSubjectMock(): Subject|MockInterface
    {
        return Mockery::mock(Subject::class);
    }

    protected function getTotalMock(): Total|MockInterface
    {
        return Mockery::mock(Total::class);
    }

    protected function getQuoteMock(): Quote|MockInterface
    {
        return Mockery::mock(Quote::class);
    }

    protected function getShippingAssignmentMock(): ShippingAssignmentInterface|MockInterface
    {
        return Mockery::mock(ShippingAssignmentInterface::class);
    }
}

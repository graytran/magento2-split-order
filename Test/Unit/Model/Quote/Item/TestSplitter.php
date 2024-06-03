<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Model\Quote\Item;

use Magento\Quote\Model\Quote\Item;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\Model\Quote\Item\Splitter as SubjectUnderTest;

class TestSplitter extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;
    protected ScopeConfigInterface|MockInterface $config;

    public function setUp(): void
    {
        $this->subjectUnderTest = new SubjectUnderTest();
    }

    public function testIsSplitOrder()
    {
        $quoteItem1 = $this->getItemMock();
        $quoteItem2 = $this->getItemMock();
        $quoteItem3 = $this->getItemMock();

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
            ]
        ];

        $this->assertEquals($splitItems, $this->subjectUnderTest->execute($items));
    }

    protected function getItemMock(): Item|MockInterface
    {
        return Mockery::mock(Item::class);
    }

}

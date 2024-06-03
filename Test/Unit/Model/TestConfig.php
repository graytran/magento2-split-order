<?php

declare(strict_types=1);

namespace Local\SplitOrder\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Local\SplitOrder\Model\Config as SubjectUnderTest;

class TestConfig extends TestCase
{
    protected SubjectUnderTest $subjectUnderTest;
    protected ScopeConfigInterface|MockInterface $config;

    public function setUp(): void
    {
        $this->config = $this->getScopeConfigMock();
        $this->subjectUnderTest = new SubjectUnderTest(
            $this->config,
        );
    }

    protected function mockData()
    {
        yield "case enable" => [
            'is_enable' => true
        ];

        yield "case disable" => [
            'is_enable' => false
        ];
    }

    /**
     * @dataProvider mockData
     */
    public function testIsSplitOrder($isEnable)
    {
        $this->config->shouldReceive('getValue')->with(
            SubjectUnderTest::SPLIT_ORDER_FEATURE_FLAG_CONFIG_PATH,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            null
        )->andReturn($isEnable)->once();

        $this->assertEquals($isEnable, $this->subjectUnderTest->isSplitOrderEnabled());
    }

    protected function getScopeConfigMock(): ScopeConfigInterface|MockInterface
    {
        return Mockery::mock(ScopeConfigInterface::class);
    }

}

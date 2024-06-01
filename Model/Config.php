<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public final const SPLIT_ORDER_FEATURE_FLAG_CONFIG_PATH = 'split_order/general/enable';

    public function __construct(
        protected ScopeConfigInterface $config
    ) {
    }

    public function isSplitOrderEnabled(string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, string|int|null $scopeCode = null): bool
    {
        return boolval($this->config->getValue(self::SPLIT_ORDER_FEATURE_FLAG_CONFIG_PATH, $scopeType, $scopeCode));
    }
}

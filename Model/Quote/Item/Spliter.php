<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Quote\Item;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

class Spliter
{
    public final const SPLIT_ORDER_ITEM_NUMBER_THRESHOLD = 2;

    /**
     * @param Quote $quote
     * @return Item[]
     */
    public function execute(Quote $quote): array
    {
        $items = $quote->getAllItems();
        if (count($items) <= self::SPLIT_ORDER_ITEM_NUMBER_THRESHOLD) {
            return $items;
        }

        $midpoint = intval(ceil(count($items) / 2));

        $firstOrderItems = array_slice($items, 0, $midpoint);
        $secondOrderItems = array_slice($items, $midpoint);

        return [$firstOrderItems, $secondOrderItems];
    }
}

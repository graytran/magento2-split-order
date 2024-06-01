<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Order\Item;

use Magento\Sales\Api\Data\OrderItemInterface;

class Splitter
{
    public final const SPLIT_ORDER_ITEM_NUMBER_THRESHOLD = 2;

    /**
     * @param OrderItemInterface[] $items
     * @return OrderItemInterface[]
     */
    public function execute(array $items): array
    {
        if (count($items) <= self::SPLIT_ORDER_ITEM_NUMBER_THRESHOLD) {
            return $items;
        }

        $midpoint = intval(ceil(count($items) / 2));

        $firstOrderItems = array_slice($items, 0, $midpoint);
        $secondOrderItems = array_slice($items, $midpoint);

        return [$firstOrderItems, $secondOrderItems];
    }
}

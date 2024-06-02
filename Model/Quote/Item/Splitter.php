<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Quote\Item;

use Magento\Quote\Model\Quote\Item;

class Splitter
{

    /**
     * @param Item[] $items
     * @return Item[]
     */
    public function execute(array $items): array
    {
        $midpoint = intval(ceil(count($items) / 2));

        $firstOrderItems = array_slice($items, 0, $midpoint);
        $secondOrderItems = array_slice($items, $midpoint);

        return [$firstOrderItems, $secondOrderItems];
    }
}

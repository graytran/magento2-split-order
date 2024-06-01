<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Order\Item;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Sales\Api\Data\OrderItemInterface;

class Resolver
{

    public function __construct(
        protected ToOrderItemConverter $quoteItemToOrderItem
    ) {
    }

    /**
     * @return OrderItemInterface[]
     */
    public function execute(Quote $quote): array
    {
        foreach ($quote->getAllItems() as $quoteItem) {
            $itemId = $quoteItem->getId();

            if (!empty($orderItems[$itemId])) {
                continue;
            }

            $parentItemId = $quoteItem->getParentItemId();
            /** @var Item $parentItem */
            if ($parentItemId && !isset($orderItems[$parentItemId])) {
                $orderItems[$parentItemId] = $this->quoteItemToOrderItem->convert(
                    $quoteItem->getParentItem(),
                    ['parent_item' => null]
                );
            }
            $parentItem = isset($orderItems[$parentItemId]) ? $orderItems[$parentItemId] : null;
            $orderItems[$itemId] = $this->quoteItemToOrderItem->convert($quoteItem, ['parent_item' => $parentItem]);
        }

        return array_values($orderItems);
    }
}

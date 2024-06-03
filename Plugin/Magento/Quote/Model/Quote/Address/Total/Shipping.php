<?php

declare(strict_types=1);

namespace Local\SplitOrder\Plugin\Magento\Quote\Model\Quote\Address\Total;

use Local\SplitOrder\Model\Quote\Splitter\Handler;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use \Magento\Quote\Model\Quote\Address\Total\Shipping as Subject;

class Shipping
{
    public function afterCollect(
        Subject $subject,
        Subject $result,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total,
    ) {
        if (!$quote->getData(Handler::SPLIT_ORDER_MARK_KEY)) {
            return $result;
        }

        $total->setBaseShippingAmount(0);
        $total->setShippingAmount(0);
        return $result;
    }
}

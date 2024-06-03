<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Quote;

use Local\SplitOrder\Model\Quote\Item\Splitter as ItemSplitter;
use Local\SplitOrder\Model\Quote\Splitter\Handler;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface as Logger;

class Splitter
{
    public final const SPLIT_ORDER_ITEM_NUMBER_THRESHOLD = 2;
    public final const EXCEPTION_MESSAGE = 'there is an error happened when split quote: ';

    public function __construct(
        protected ItemSplitter $quoteItemSpliter,
        protected Handler $quoteSplitHandler,
        protected CartRepositoryInterface $quoteRepository,
        protected Logger $logger,
    ) {
    }

    /**
     * @return Quote[]
     */
    public function execute(Quote|CartInterface $quote, PaymentInterface $paymentMethod): array
    {
        $items = $quote->getAllItems();
        if (count($items) < self::SPLIT_ORDER_ITEM_NUMBER_THRESHOLD) {
            return [];
        }

        $splitQuoteItemsArray = $this->quoteItemSpliter->execute($items);

        $quoteSplits = [];
        try {
            foreach ($splitQuoteItemsArray as $splitQuoteItems) {
                $quoteSplits[] = $this->quoteSplitHandler->execute($quote, $splitQuoteItems, $paymentMethod);
            }
        } catch (\Exception $exception) {
            $this->logger->critical(
                self::EXCEPTION_MESSAGE . $exception->getMessage(),
                [
                    'trace' => $exception->getTraceAsString()
                ]
            );
        }

        return $quoteSplits;
    }

}

<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;

class Handler
{
    public function __construct(
        protected LockManagerInterface $lockManager,
        protected OrderManagement $orderManagement,

    ) {
    }

    public function execute(Quote $quote, OrderInterface $order)
    {
        $lockedName = self::LOCK_PREFIX . $quote->getId();
        if (!$this->lockManager->lock($lockedName, self::LOCK_TIMEOUT)) {
            throw new LocalizedException(__(
                'A server error stopped your order from being placed. Please try to place your order again.'
            ));
        }
        try {
            $order = $this->orderManagement->place($order);
            $quote->setIsActive(false);
            $this->eventManager->dispatch(
                'sales_model_service_quote_submit_success',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->lockManager->unlock($lockedName);
            $this->rollbackAddresses($quote, $order, $e);
            throw $e;
        }
    }
}

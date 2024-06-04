<?php

declare(strict_types=1);

namespace Local\SplitOrder\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class SplitOrderSuccess implements ArgumentInterface
{
    public function __construct(
        protected CheckoutSession $checkoutSession,
        protected UrlInterface $urlBuilder,
    ) {
    }

    public function getLastSplitOrderIds(): array
    {
        $lastSplitOrderIds = $this->checkoutSession->getLastSplitOrderIds();
        $lastSplitRealOrderIds = $this->checkoutSession->getLastSplitRealOrderIds();

        return array_combine($lastSplitOrderIds, $lastSplitRealOrderIds);
    }

    public function getViewOrderUrl(int|string $orderId): string
    {
        return $this->urlBuilder->getUrl('sales/order/view/', ['order_id' => $orderId, '_secure' => true]);
    }
}

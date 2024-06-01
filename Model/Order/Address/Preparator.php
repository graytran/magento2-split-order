<?php

declare(strict_types=1);

namespace Local\SplitOrder\Model\Order\Address;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;

class Preparator
{
    public function __construct(
        protected ToOrderAddressConverter $quoteAddressToOrderAddress,
    ) {
    }


    public function execute(Quote $quote): array
    {
        return [
            $this->handleAddress($quote->getShippingAddress(), $quote->getCustomerEmail(), 'shipping'),
            $this->handleAddress($quote->getBillingAddress(), $quote->getCustomerEmail(), 'billing')
        ];
    }

    protected function handleAddress(Address $address, string $email, string $type): \Magento\Sales\Api\Data\OrderAddressInterface
    {
        $shippingAddress = $this->quoteAddressToOrderAddress->convert(
            $address,
            [
                'address_type' => $type,
                'email' => $email
            ]
        );
        $shippingAddress->setData('quote_address_id', $address->getId());
        return $shippingAddress;
    }
}

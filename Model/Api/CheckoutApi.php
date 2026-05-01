<?php
namespace Bambora\Online\Model\Api;

class CheckoutApi
{
    public const API_MERCHANT = Checkout\Merchant::class;
    public const API_CHECKOUT = Checkout\Checkout::class;
    public const API_TRANSACTION = Checkout\Transaction::class;
    public const API_ASSETS = Checkout\Assets::class;
    public const API_DATA = Checkout\Data::class;
}

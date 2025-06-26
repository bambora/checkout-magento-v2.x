<?php
namespace Bambora\Online\Model\Api;

class CheckoutApi
{
    public const string API_MERCHANT = Checkout\Merchant::class;
    public const string API_CHECKOUT = Checkout\Checkout::class;
    public const string API_TRANSACTION = Checkout\Transaction::class;
    public const string API_ASSETS = Checkout\Assets::class;
    public const string API_DATA = Checkout\Data::class;
}

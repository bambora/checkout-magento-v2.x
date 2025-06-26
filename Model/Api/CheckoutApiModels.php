<?php
namespace Bambora\Online\Model\Api;

class CheckoutApiModels
{
    // Request
    public const string REQUEST_CHECKOUT = Checkout\Request\Checkout::class;
    public const string REQUEST_CAPTURE = Checkout\Request\Capture::class;
    public const string REQUEST_CREDIT = Checkout\Request\Credit::class;

    // Request Models
    public const string REQUEST_MODEL_ADDRESS = Checkout\Request\Models\Address::class;
    public const string REQUEST_MODEL_CALLBACK = Checkout\Request\Models\Callback::class;
    public const string REQUEST_MODEL_CUSTOMER = Checkout\Request\Models\Customer::class;
    public const string REQUEST_MODEL_ORDER = Checkout\Request\Models\Order::class;
    public const string REQUEST_MODEL_LINE = Checkout\Request\Models\Line::class;
    public const string REQUEST_MODEL_URL = Checkout\Request\Models\Url::class;

    //Response
    public const string RESPONSE_CHECKOUT = Checkout\Response\Checkout::class;
    public const string RESPONSE_LISTPAYMENTTYPES = Checkout\Response\ListPaymentTypes::class;
    public const string RESPONSE_LISTTRANSACTIONOPERATIONS = Checkout\Response\ListTransactionOperations::class;
    public const string RESPONSE_TRANSACTION = Checkout\Response\Transaction::class;
    public const string RESPONSE_CAPTURE = Checkout\Response\Capture::class;
    public const string RESPONSE_CREDIT = Checkout\Response\Credit::class;
    public const string RESPONSE_DELETE = Checkout\Response\Delete::class;

    //Response Models
    public const string RESPONSE_MODEL_META = Checkout\Response\Models\Meta::class;
    public const string RESPONSE_MODEL_MESSAGE = Checkout\Response\Models\Message::class;
    public const string RESPONSE_MODEL_PAYMENTCOLLECTION = Checkout\Response\Models\PaymentCollection::class;
    public const string RESPONSE_MODEL_PAYMENTGROUP = Checkout\Response\Models\PaymentGroup::class;
    public const string RESPONSE_MODEL_PAYMENTYPE = Checkout\Response\Models\PaymentType::class;
    public const string RESPONSE_MODEL_FEE = Checkout\Response\Models\Fee::class;
    public const string RESPONSE_MODEL_AVAILABLE = Checkout\Response\Models\Available::class;
    public const string RESPONSE_MODEL_CURRENCY = Checkout\Response\Models\Currency::class;
    public const string RESPONSE_MODEL_INFORMATION = Checkout\Response\Models\Information::class;
    public const string RESPONSE_MODEL_ACQUIRER = Checkout\Response\Models\Acquirer::class;
    public const string RESPONSE_MODEL_WALLET = Checkout\Response\Models\Wallet::class;
    public const string RESPONSE_MODEL_ACQUIRERREFERENCE = Checkout\Response\Models\AcquirerReference::class;
    public const string RESPONSE_MODEL_PRIMARYACCOUNTNUMBER = Checkout\Response\Models\PrimaryAccountnumber::class;
    public const string RESPONSE_MODEL_ECI = Checkout\Response\Models\Eci::class;
    public const string RESPONSE_MODEL_EXEMPTION = Checkout\Response\Models\Exemption::class;
    public const string RESPONSE_MODEL_LINKS = Checkout\Response\Models\Links::class;
    public const string RESPONSE_MODEL_SUBSCRIPTION = Checkout\Response\Models\Subscription::class;
    public const string RESPONSE_MODEL_TOTAL = Checkout\Response\Models\Total::class;
    public const string RESPONSE_MODEL_TRANSACTION = Checkout\Response\Models\Transaction::class;
    public const string RESPONSE_MODEL_TRANSACTIONOPERATION = Checkout\Response\Models\TransactionOperation::class;
    public const string RESPONSE_MODEL_RESPONSE_CODE = Checkout\Response\Models\ResponseCode::class;
}

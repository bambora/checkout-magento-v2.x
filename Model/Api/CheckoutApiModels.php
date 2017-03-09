<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
namespace Bambora\Online\Model\Api;

class CheckoutApiModels
{
    // Request
    const REQUEST_CHECKOUT = 'Request\Checkout';
    const REQUEST_CAPTURE = 'Request\Capture';
    const REQUEST_CREDIT = 'Request\Credit';

    // Request Models
    const REQUEST_MODEL_ADDRESS = 'Request\Models\Address';
    const REQUEST_MODEL_CALLBACK = 'Request\Models\Callback';
    const REQUEST_MODEL_CUSTOMER = 'Request\Models\Customer';
    const REQUEST_MODEL_ORDER = 'Request\Models\Order';
    const REQUEST_MODEL_LINE = 'Request\Models\Line';
    const REQUEST_MODEL_URL = 'Request\Models\Url';

    //Response
    const RESPONSE_CHECKOUT = 'Response\Checkout';
    const RESPONSE_LISTPAYMENTTYPES = 'Response\ListPaymentTypes';
    const RESPONSE_TRANSACTION = 'Response\Transaction';
    const RESPONSE_CAPTURE = 'Response\Capture';
    const RESPONSE_CREDIT = 'Response\Credit';
    const RESPONSE_DELETE = 'Response\Delete';

    //Response Models
    const RESPONSE_MODEL_META = 'Response\Models\Meta';
    const RESPONSE_MODEL_MESSAGE = 'Response\Models\Message';
    const RESPONSE_MODEL_PAYMENTCOLLECTION = 'Response\Models\PaymentCollection';
    const RESPONSE_MODEL_PAYMENTGROUP = 'Response\Models\PaymentGroup';
    const RESPONSE_MODEL_PAYMENTYPE = 'Response\Models\PaymentType';
    const RESPONSE_MODEL_FEE = 'Response\Models\Fee';
    const RESPONSE_MODEL_AVAILABLE = 'Response\Models\Available';
    const RESPONSE_MODEL_CURRENCY = 'Response\Models\Currency';
    const RESPONSE_MODEL_INFORMATION = 'Response\Models\Information';
    const RESPONSE_MODEL_ACQUIRER = 'Response\Models\Acquirer';
    const RESPONSE_MODEL_PRIMARYACCOUNTNUMBER = 'Response\Models\PrimaryAccountnumber';
    const RESPONSE_MODEL_LINKS = 'Response\Models\Links';
    const RESPONSE_MODEL_SUBSCRIPTION = 'Response\Models\Subscription';
    const RESPONSE_MODEL_TOTAL = 'Response\Models\Total';
    const RESPONSE_MODEL_TRANSACTION = 'Response\Models\Transaction';
    const RESPONSE_MODEL_TRANSACTIONOPERATION = 'Response\Models\TransactionOperation';
}

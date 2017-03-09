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

class EpayApiModels
{
    //Request
    const REQUEST_PAYMENT = 'Request\Payment';

    //Request Models
    const REQUEST_MODEL_AUTH = 'Request\Models\Auth';
    const REQUEST_MODEL_URL = 'Request\Models\Url';
    const REQUEST_MODEL_INVOICE = 'Request\Models\Invoice';
    const REQUEST_MODEL_CUSTOMER = 'Request\Models\Customer';
    const REQUEST_MODEL_SHIPPINGADDRESS = 'Request\Models\ShippingAddress';

    //Response
    const RESPONSE_CAPTURE = 'Response\Capture';
    const RESPONSE_CREDIT = 'Response\Credit';
    const RESPONSE_DELETE = 'Response\Delete';
    const RESPONSE_TRANSACTION = 'Response\Transaction';
    const RESPONSE_EPAYERROR = 'Response\EpayError';
    const RESPONSE_PBSERROR = 'Response\PbsError';

    //Response Models
    const RESPONSE_MODEL_TRANSACTIONHISTORYINFO = 'Response\Models\TransactionHistoryInfo';
    const RESPONSE_MODEL_TRANSACTIONINFORMATIONTYPE = 'Response\Models\TransactionInformationType';
}

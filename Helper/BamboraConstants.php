<?php
namespace Bambora\Online\Helper;

class BamboraConstants
{
    //Surcharge
    public const BAMBORA_SURCHARGE = 'surcharge_fee';
    public const SURCHARGE_SHIPMENT = "surcharge_shipment";
    public const SURCHARGE_ORDER_LINE = "surcharge_order_line";

    //Rounding
    public const ROUND_UP = "round_up";
    public const ROUND_DOWN = "round_down";
    public const ROUND_DEFAULT = "round_default";

    //Config constants
    public const ORDER_STATUS = 'order_status';
    public const MASS_CAPTURE_INVOICE_MAIL = 'masscaptureinvoicemail';
    public const TITLE = 'title';
    public const MERCHANT_NUMBER = 'merchantnumber';
    public const ACCESS_TOKEN = 'accesstoken';
    public const SECRET_TOKEN = 'secrettoken';
    public const MD5_KEY = 'md5key';
    public const PAYMENT_WINDOW_ID = 'paymentwindowid';
    public const INSTANT_CAPTURE = 'instantcapture';
    public const INSTANT_INVOICE = 'instantinvoice';
    public const INSTANT_INVOICE_MAIL = 'instantinvoicemail';
    public const IMMEDIATEREDI_REDIRECT_TO_ACCEPT = 'immediateredirecttoaccept';
    public const ADD_SURCHARGE_TO_PAYMENT = 'addsurchargetopayment';
    public const SURCHARGE_MODE = 'surchargemode';
    public const SEND_MAIL_ORDER_CONFIRMATION = 'sendmailorderconfirmation';
    public const WINDOW_STATE = 'windowstate';
    public const ENABLE_MOBILE_PAYMENT_WINDOW = 'enablemobilepaymentwindow';
    public const REMOTE_INTERFACE = 'remoteinterface';
    public const REMOTE_INTERFACE_PASSWORD = 'remoteinterfacepassword';
    public const PAYMENT_GROUP = 'paymentgroup';
    public const OWN_RECEIPT = 'ownreceipt';
    public const ENABLE_INVOICE_DATA = 'enableinvoicedata';
    public const ROUNDING_MODE = 'roundingmode';
    public const UNCANCEL_ORDER_LINES = 'uncancelorderlines';
    public const ALLOW_LOW_VALUE_EXEMPTION = 'allowlowvalueexemption';
    public const LIMIT_LOW_VALUE_EXEMPTION = 'limitlowvalueexemption';
    //Actions
    public const CAPTURE = 'capture';
    public const REFUND = 'refund';
    public const VOID = 'void';
    public const GET_TRANSACTION = 'gettransaction';

    //Action lock
    public const PAYMENT_STATUS_ACCEPTED = 'payment_status_accepted';
}

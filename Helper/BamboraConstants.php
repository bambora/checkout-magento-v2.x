<?php
namespace Bambora\Online\Helper;

class BamboraConstants
{
    //Surcharge
    public const string BAMBORA_SURCHARGE = 'surcharge_fee';
    public const string SURCHARGE_SHIPMENT = "surcharge_shipment";
    public const string SURCHARGE_ORDER_LINE = "surcharge_order_line";

    //Rounding
    public const string ROUND_UP = "round_up";
    public const string ROUND_DOWN = "round_down";
    public const string ROUND_DEFAULT = "round_default";

    //Config constants
    public const string ORDER_STATUS = 'order_status';
    public const string MASS_CAPTURE_INVOICE_MAIL = 'masscaptureinvoicemail';
    public const string TITLE = 'title';
    public const string MERCHANT_NUMBER = 'merchantnumber';
    public const string ACCESS_TOKEN = 'accesstoken';
    public const string SECRET_TOKEN = 'secrettoken';
    public const string MD5_KEY = 'md5key';
    public const string PAYMENT_WINDOW_ID = 'paymentwindowid';
    public const string INSTANT_CAPTURE = 'instantcapture';
    public const string INSTANT_INVOICE = 'instantinvoice';
    public const string INSTANT_INVOICE_MAIL = 'instantinvoicemail';
    public const string IMMEDIATEREDI_REDIRECT_TO_ACCEPT = 'immediateredirecttoaccept';
    public const string ADD_SURCHARGE_TO_PAYMENT = 'addsurchargetopayment';
    public const string SURCHARGE_MODE = 'surchargemode';
    public const string SEND_MAIL_ORDER_CONFIRMATION = 'sendmailorderconfirmation';
    public const string WINDOW_STATE = 'windowstate';
    public const string ENABLE_MOBILE_PAYMENT_WINDOW = 'enablemobilepaymentwindow';
    public const string REMOTE_INTERFACE = 'remoteinterface';
    public const string REMOTE_INTERFACE_PASSWORD = 'remoteinterfacepassword';
    public const string PAYMENT_GROUP = 'paymentgroup';
    public const string OWN_RECEIPT = 'ownreceipt';
    public const string ENABLE_INVOICE_DATA = 'enableinvoicedata';
    public const string ROUNDING_MODE = 'roundingmode';
    public const string UNCANCEL_ORDER_LINES = 'uncancelorderlines';
    public const string ALLOW_LOW_VALUE_EXEMPTION = 'allowlowvalueexemption';
    public const string LIMIT_LOW_VALUE_EXEMPTION = 'limitlowvalueexemption';
    //Actions
    public const string CAPTURE = 'capture';
    public const string REFUND = 'refund';
    public const string VOID = 'void';
    public const string GET_TRANSACTION = 'gettransaction';

    //Action lock
    public const string PAYMENT_STATUS_ACCEPTED = 'payment_status_accepted';
}

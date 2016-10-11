<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Api\Epay;

use Bambora\Online\Model\Api\Epay\ApiEndpoints;
use Bambora\Online\Model\Api\EpayApiModels;

class Error extends Base
{
    /**
     * Get ePay error text
     *
     * @param mixed $errorcode
     * @param string $language
     * @param \Bambora\Online\Model\Api\Epay\Request\Models\Auth $auth
     * @return string
     */
    function getEpayErrorText($errorcode, $language, $auth)
    {
        $res = "Unable to lookup errorcode";
        try{
            $param = array
                (
                    'merchantnumber' => $auth->merchantNumber,
                    'language' => $language,
                    'epayresponsecode' => $errorcode,
                    'epayresponsestring' => -1,
                    'epayresponse' => -1,
                    'pwd' => $auth->pwd
                );
            $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_REMOTE).'/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);

			$result = $client->getEpayError($param);

            if($result->getEpayErrorResult == 1)
			{
				$res = $result->epayresponsestring;
			}
        }
        catch (\Exception $e)
        {
            return $res;
        }

        return $res;
    }


    /**
     * Get PBS error text
     *
     * @param mixed $errorcode
     * @param string $language
     * @param \Bambora\Online\Model\Api\Epay\Request\Models\Auth $auth
     * @return string
     */
    public function getPbsErrorText($errorcode, $language, $auth)
    {
        $res = "Unable to lookup errorcode";
		try
		{
            $param = array
            (
                'merchantnumber' => $auth->merchantNumber,
                'language' => $language,
                'pbsresponsecode' => $errorcode,
                'epayresponsestring' => 0,
                'epayresponse' => 0,
                'pwd' => $auth->pwd
            );
            $url = $this->_getEndpoint(ApiEndpoints::ENDPOINT_REMOTE).'/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);
            $result = $client->getPbsError($param);

            if($result->getPbsErrorResult == 1)
            {
                $res = $result->pbsresponsestring;
            }
		}
		catch (\Exception $e)
		{
			return $res;
		}

	    return $res;
    }
}
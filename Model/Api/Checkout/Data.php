<?php
namespace Bambora\Online\Model\Api\Checkout;

use Bambora\Online\Model\Api\Checkout\ApiEndpoints;
use Bambora\Online\Model\Api\CheckoutApiModels;

class Data extends Base
{
    /**
     * Create the data request
     *
     * @param string $source
     * @param string $actioncode
     * @param string $apiKey
     * @return \Bambora\Online\Model\Api\Checkout\Response\Models\ResponseCode | null
     */
    public function getResponseCodeDetails($source, $actioncode, $apiKey)
    {
        try {
            $serviceEndpoint = $this->_getEndpoint(ApiEndpoints::ENDPOINT_DATA);
            $serviceUrl = "{$serviceEndpoint}/responsecodes/{$source}/{$actioncode}";
            $jsonData = null;
            $dataResponseJson = $this->_callRestService(
                $serviceUrl,
                $jsonData,
                Base::GET,
                $apiKey
            );
            $dataResponseJsonArray = json_decode($dataResponseJson, true);
            $dataResponse = $this->_bamboraHelper->getCheckoutModel(
                CheckoutApiModels::RESPONSE_MODEL_RESPONSE_CODE
            );
            $dataResponse->meta = $this->_mapMeta($dataResponseJsonArray);
            if ($dataResponse->meta->result) {
                $dataResponse->merchantlabel = $dataResponseJsonArray['responsecode']['merchantlabel'];
                $dataResponse->actioncode = $dataResponseJsonArray['responsecode']['actioncode'];
                $dataResponse->source = $dataResponseJsonArray['responsecode']['source'];
                $dataResponse->enduserlabel = $dataResponseJsonArray['responsecode']['enduserlabel'];
            }
            return $dataResponse;
        } catch (\Exception $ex) {
            $this->_bamboraLogger->addCommonError("-1", $ex->getMessage());
            return null;
        }
    }
}

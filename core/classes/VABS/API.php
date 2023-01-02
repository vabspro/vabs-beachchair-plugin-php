<?php

namespace VABS;

use DD\Exceptions\ValidationException;
use DD\Helper\Date;
use Exception;

class API
{

	private string $apiURL = '';
	private string $apiToken = '';
	private string $apiClientId = '';
	private array $header = [];

	/**
	 * @throws Exception
	 */
	public function __construct () {

		$Settings          = new Settings();
		if(!$Settings->Load ()){
			throw new Exception("Settings could not be loaded. Error: ".$Settings->errorMessage);
		}
		$row = $Settings->row;
		if(!$row instanceof Settings){
			throw new Exception("row wasn't instance of Settings1");
		}
		$this->apiToken    = $row->apiToken ?? '';
		$this->apiClientId = $row->apiClientId ?? '';
		$this->apiURL      = $row->apiURL ?? '';

		$this->header = ['Token: '.$this->apiToken];

	}

	/**
	 * @return string
	 */
	public function GetReferrer() : string {

		$requestUrl = '/account/referrer';

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @param int $locationId
	 * @return string
	 */
	public function GetBeachChairs(int $locationId = 0) : string {

		$requestUrl = empty($locationId) ? '/beachchair/chair' : '/beachchair/chair/location/'.$locationId;

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @return string
	 */
	public function GetDSGVO() : string {

		$requestUrl = '/account/dsgvo';
		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @return string
	 */
	public function GetAGBS() : string {

		$requestUrl = '/account/agbs';

		return $this->SendGetCurlRequest ($requestUrl);


	}

	/**
	 * @param $string
	 * @return bool
	 */
	private static function IsJson ($string) : bool {

		json_decode ($string);

		return json_last_error () === JSON_ERROR_NONE;
	}

	/**
	 * @param string $requestUrl
	 * @return bool|string
	 */
	private function SendGetCurlRequest(string $requestUrl) : string {

		$curl = curl_init ($this->apiURL.$requestUrl);

		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($curl, CURLOPT_HTTPGET, 1);
		curl_setopt ($curl, CURLOPT_HTTPHEADER, $this->header);

		$response = curl_exec ($curl);
		$err      = curl_error ($curl);

		curl_close ($curl);

		if ($err) {
			return json_encode (["error" => "cURL Error #:".$err]);
		} else {
			return self::IsJson ($response) ? $response : json_encode (["error" => "Response for Request with URL: ".$this->apiURL.$requestUrl."  wasn't a json valide string. Response: ".$response]);
		}

	}

	/**
	 * @param string $requestUrl
	 * @param array $param
	 * @return bool|string
	 */
	private function SendPostCurlRequest (string $requestUrl, array $param): string {

		$curl = curl_init ($this->apiURL.$requestUrl);
		$param['target_client_hash'] = $this->apiClientId;

		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($curl, CURLOPT_HTTPHEADER, $this->header);
		curl_setopt ($curl, CURLOPT_POST, true);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, $param);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec ($curl);
		$err      = curl_error ($curl);

		curl_close ($curl);

		if ($err) {
			return json_encode (["error" => "cURL Error #:".$err]);
		} else {
			return self::IsJson ($response) ? $response : json_encode (["error" => "Response wasn't a json valide string. Response: ".$response]);
		}

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return bool|string
	 */
	public function GetLocations (string $dateFrom = "", string $dateTo = "") {

		$requestUrl = '/beachchair/location';

		if(!empty($dateFrom)){
			if (!empty($dateTo)) {
				$requestUrl .= "/0/".$dateFrom."/".$dateTo;
			}else{
				return json_encode (["error" => "dateTo must be provided if dateFrom is provided"]);
			}
		}

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @return bool|string
	 */
	public function GetBeachChairTypes () {

		$requestUrl = '/beachchair/type';

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return bool|string
	 */
	public function GetBookableLocations (string $dateFrom, string $dateTo) {

		$requestUrl = "/beachchair/location/bookable/".$dateFrom."/".$dateTo;

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @param int $locationId
	 * @return bool|string
	 */
	public function GetFreeChairs (string $dateFrom, string $dateTo, int $locationId) {

		if(empty($locationId)){
			$locationId = 0;
		}

		$requestUrl = '/beachchair/free/location/'.$locationId.'/dateFrom/'.$dateFrom.'/dateTo/'.$dateTo;

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @param int $id
	 * @return bool|string
	 */
	public function GetRows (int $id = 0) {

		$requestUrl = '/beachchair/row';

		if (!empty($id)) {
			$requestUrl .= '/'.$id;
		}

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @param $id
	 * @param $dateFrom
	 * @param $dateTo
	 * @return bool|string
	 * @throws ValidationException
	 */
	public function GetPrice ($id, $dateFrom, $dateTo) {

		$requestUrl = sprintf('/beachchair/price/%d/%s/%s',$id,Date::FormatDateToFormat ($dateFrom, Date::DATE_FORMAT_SQL_DATE),Date::FormatDateToFormat ($dateTo, Date::DATE_FORMAT_SQL_DATE));

		return $this->SendGetCurlRequest ($requestUrl);

	}

	public function IsBooked(int $id, string $dateFrom, string $dateTo){

		$requestUrl = sprintf ('/beachchair/booking/isBooked/%d/%s/%s', $id, Date::FormatDateToFormat ($dateFrom, Date::DATE_FORMAT_SQL_DATE), Date::FormatDateToFormat ($dateTo, Date::DATE_FORMAT_SQL_DATE));

		return $this->SendGetCurlRequest ($requestUrl);
	}

	/**
	 * @param Contact $Contact
	 * @return bool|string
	 */
	public function CreateContact (Contact $Contact) {

		$requestUrl = '/contact';

		return $this->SendPostCurlRequest($requestUrl, (array)$Contact);

	}

	/**
	 * @param int $contactId
	 * @param string $comment
	 * @return bool|string
	 */
	public function CreateSalesOrderHeader (int $contactId, string $comment) {

		$requestUrl = '/sales/order';

		return $this->SendPostCurlRequest ($requestUrl, [
			'sellto_contact_id' => $contactId,
			'comment'            => htmlspecialchars (strip_tags ($comment))
		]);

	}

	/**
	 * @param int $salesHeaderId
	 * @param int $id
	 * @param int $quantity
	 * @param int $objectCodeId
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return bool|string
	 */
	public function CreateSalesOrderLine (int $salesHeaderId, int $id, int $quantity, int $objectCodeId, string $dateFrom, string $dateTo) {

		$requestUrl = '/sales/line';

		return $this->SendPostCurlRequest ($requestUrl, [
			'sales_header_id' => $salesHeaderId,
			'object_id'       => $id,
			'object_code'     => $objectCodeId,
			'quantity'        => $quantity,
			'date_from'       => $dateFrom,
			'date_to'         => $dateTo,

		]);

	}

	/**
	 * @param int $salesHeaderId
	 * @return bool|string
	 */
	public function CreateSalesInvoice (int $salesHeaderId) {

		$requestUrl = '/sales/invoice';

		return $this->SendPostCurlRequest ($requestUrl, [
			'sales_header_id' => $salesHeaderId
		]);

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @param int|array $locationIds
	 * @param int|array $beachChairTypeIds
	 * @return bool|string
	 */
	public function GetVacancy (string $dateFrom, string $dateTo, $locationIds, $beachChairTypeIds) {

		$locationIds = is_array ($locationIds) ? implode (',', $locationIds) : $locationIds;
		$beachChairTypeIds = is_array ($beachChairTypeIds) ? implode (',', $beachChairTypeIds) : $beachChairTypeIds;

		$locationIds = $locationIds ?: 0;
		$beachChairTypeIds = $beachChairTypeIds ?: 0;

		$requestUrl = '/beachchair/booking/vacancy/'.$dateFrom.'/'.$dateTo.'/location/'.$locationIds.'/type/'.$beachChairTypeIds;

		return $this->SendGetCurlRequest ($requestUrl);
	}

	/**
	 * @param int $salesInvoiceId
	 * @param string $totalAmountFormatted
	 * @param int $paymentMethodId
	 * @param string $token
	 * @param string $PayerID
	 * @param string $captureId
	 * @return bool|string
	 */
	public function AddPayment (int $salesInvoiceId, string $totalAmountFormatted, int $paymentMethodId, string $token, string $PayerID, string $captureId) {

		$requestUrl = '/sales/invoice/payment';

		return $this->SendPostCurlRequest ($requestUrl, [
			'sales_invoice_id'  => $salesInvoiceId,
			'payment_method_id' => $paymentMethodId,
			'complete_payment'  => 1,
			'paypal_token'      => $token,
			'paypal_payer_id'   => $PayerID,
			'paypal_capture_id' => $captureId,
			'amount'            => $totalAmountFormatted,

		]);

	}

	/**
	 * @param int $salesHeaderId
	 * @param int $salesInvoiceId
	 * @param int $statusId
	 * @return bool|string
	 */
	public function UpdateSalesInvoiceStatus (int $salesHeaderId, int $salesInvoiceId, int $statusId) {

		$requestUrl = '/sales/invoice/statusupdate';

		return $this->SendPostCurlRequest ($requestUrl, [
			'sales_header_id'  => $salesHeaderId,
			'sales_invoice_id' => $salesInvoiceId,
			'status_id'        => $statusId,
		]);


	}

	/**
	 * @param int $salesHeaderId
	 * @return bool|string
	 */
	public function SendInvoice (int $salesHeaderId) {

		$requestUrl = '/sales/invoice/send';

		return $this->SendPostCurlRequest ($requestUrl, [
			'sales_header_id'  => $salesHeaderId,
		]);

	}

}

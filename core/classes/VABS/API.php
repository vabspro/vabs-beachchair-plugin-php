<?php

namespace VABS;

use DD\Exceptions\ValidationException;
use DD\Helper\Date;
use Exception;

class API
{

	#region CONSTANTS
	const EP_SYSTEM_CONSTANTS              = '/system';
	const EP_BEACH_CHAIR                   = '/beachchair/chair';
	const EP_BEACH_CHAIR_AT_LOCATION       = '/beachchair/chair/location';
	const EP_BEACH_CHAIR_LOCATION          = '/beachchair/location';
	const EP_BEACH_CHAIR_TYPE              = '/beachchair/type';
	const EP_BEACH_CHAIR_ROW               = '/beachchair/row';
	const EP_BEACH_CHAIR_PRICE             = '/beachchair/price/%d/%s/%s';
	const EP_BEACH_CHAIR_LOCATION_BOOKABLE = '/beachchair/location/bookable/%s/%s';
	const EP_BEACH_CHAIR_LOCATION_FREE     = '/beachchair/free/location/%s/%s/%s';
	const EP_BEACH_CHAIR_IS_BOOKED         = '/beachchair/booking/isBooked/%d/%s/%s';
	const EP_BEACH_CHAIR_VACANCY           = '/beachchair/booking/vacancy/%s/%s/location/%s/type/%s';
	const EP_ACCOUNT_REFERRER              = '/account/referrer';
	const EP_ACOOUNT_DSGVO                 = '/account/dsgvo';
	const EP_ACCOUNT_AGBS                  = '/account/agbs';
	const EP_CONTACT                       = '/contact';
	const EP_SALES_ORDER                   = '/sales/order';
	const EP_SALES_ORDER_LINE              = '/sales/line';
	const EP_SALES_INVOICE                 = '/sales/invoice';
	const EP_SALES_INVOICE_PAYMENT         = '/sales/invoice/payment';
	const EP_SALES_INVOICE_SEND            = '/sales/invoice/send';
	#endregion

	#region PRIVATE PROPERTIES
	private string $apiURL      = '';
	private string $apiToken    = '';
	private string $apiClientId = '';
	private array  $header      = [];
	#endregion

	/**
	 * @throws Exception
	 */
	public function __construct () {

		$Settings = new Settings();
		if (!$Settings->Load ()) {
			throw new Exception("Settings could not be loaded. Error: ".$Settings->errorMessage);
		}
		$row = $Settings->row;
		if (!$row instanceof Settings) {
			throw new Exception("row wasn't instance of Settings");
		}
		$this->apiToken    = $row->apiToken ?? '';
		$this->apiClientId = $row->apiClientId ?? '';
		$this->apiURL      = $row->apiURL ?? '';

		$this->header = ['Token: '.$this->apiToken];

	}

	#region PUBLIC METHODS

	/**
	 * @return string
	 */
	public function GetConstants (): string {

		return $this->SendGetCurlRequest (self::EP_SYSTEM_CONSTANTS);

	}

	/**
	 * @return string
	 */
	public function GetReferrer (): string {

		return $this->SendGetCurlRequest (self::EP_ACCOUNT_REFERRER);

	}

	/**
	 * @param int $locationId
	 * @return string
	 */
	public function GetBeachChairs (int $locationId = 0): string {

		$requestUrl = empty($locationId) ? self::EP_BEACH_CHAIR : self::EP_BEACH_CHAIR_AT_LOCATION.'/'.$locationId;

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @return string
	 */
	public function GetDSGVO (): string {

		return $this->SendGetCurlRequest (self::EP_ACOOUNT_DSGVO);

	}

	/**
	 * @return string
	 */
	public function GetAGBS (): string {

		return $this->SendGetCurlRequest (self::EP_ACCOUNT_AGBS);

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return string
	 */
	public function GetLocations (string $dateFrom = "", string $dateTo = ""): string {

		$requestUrl = self::EP_BEACH_CHAIR_LOCATION;

		/*if (!empty($dateFrom)) {
			if (!empty($dateTo)) {
				$requestUrl .= "/0/".$dateFrom."/".$dateTo;
			} else {
				return json_encode (["error" => "dateTo must be provided if dateFrom is provided"]);
			}
		}*/

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @return string
	 */
	public function GetBeachChairTypes (): string {

		return $this->SendGetCurlRequest (self::EP_BEACH_CHAIR_TYPE);

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return bool|string
	 */
	public function GetBookableLocations (string $dateFrom, string $dateTo): bool|string {

		return $this->SendGetCurlRequest (sprintf (self::EP_BEACH_CHAIR_LOCATION_BOOKABLE, $dateFrom, $dateTo));

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @param int $locationId
	 * @return bool|string
	 */
	public function GetFreeChairs (string $dateFrom, string $dateTo, int $locationId = 0): bool|string {

		if (empty($locationId)) {
			$locationId = 0;
		}

		return $this->SendGetCurlRequest (sprintf (self::EP_BEACH_CHAIR_LOCATION_FREE, $locationId, $dateFrom, $dateTo));

	}

	/**
	 * @param int $id
	 * @return bool|string
	 */
	public function GetRows (int $id = 0): bool|string {

		$requestUrl = self::EP_BEACH_CHAIR_ROW;

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
	public function GetPrice ($id, $dateFrom, $dateTo): bool|string {

		$requestUrl = sprintf (self::EP_BEACH_CHAIR_PRICE, $id, Date::FormatDateToFormat ($dateFrom, Date::DATE_FORMAT_SQL_DATE), Date::FormatDateToFormat ($dateTo, Date::DATE_FORMAT_SQL_DATE));

		return $this->SendGetCurlRequest ($requestUrl);

	}

	/**
	 * @throws ValidationException
	 */
	public function IsBooked (int $id, string $dateFrom, string $dateTo): bool|string {

		return $this->SendGetCurlRequest (sprintf (self::EP_BEACH_CHAIR_IS_BOOKED, $id, Date::FormatDateToFormat ($dateFrom, Date::DATE_FORMAT_SQL_DATE), Date::FormatDateToFormat ($dateTo, Date::DATE_FORMAT_SQL_DATE)));
	}

	/**
	 * @param Contact $Contact
	 * @return bool|string
	 */
	public function CreateContact (Contact $Contact): bool|string {

		return $this->SendPostCurlRequest (self::EP_CONTACT, (array)$Contact);

	}

	/**
	 * @param int    $contactId
	 * @param string $comment
	 * @param int    $referrerId
	 *
	 * @return bool|string
	 */
	public function CreateSalesOrderHeader (int $contactId, string $comment, int $referrerId = 0): bool|string {

		$params = [
			'referrerId' => $referrerId,
			'sellto_contact_id' => $contactId,
			'comment'           => htmlspecialchars (strip_tags ($comment))
		];

		return $this->SendPostCurlRequest (self::EP_SALES_ORDER, $params);

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
	public function CreateSalesOrderLine (int $salesHeaderId, int $id, int $quantity, int $objectCodeId, string $dateFrom, string $dateTo): bool|string {

		$params = [
			'sales_header_id' => $salesHeaderId,
			'object_id'       => $id,
			'object_code'     => $objectCodeId,
			'quantity'        => $quantity,
			'dateFrom'       => $dateFrom,
			'dateTo'         => $dateTo,

		];

		return $this->SendPostCurlRequest (self::EP_SALES_ORDER_LINE, $params);

	}

	/**
	 * @param int $salesHeaderId
	 * @return bool|string
	 */
	public function CreateSalesInvoice (int $salesHeaderId): bool|string {

		$params = [
			'sales_header_id' => $salesHeaderId
		];

		return $this->SendPostCurlRequest (self::EP_SALES_INVOICE, $params);

	}

	/**
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @param array|int $locationIds
	 * @param array|int $beachChairTypeIds
	 * @return bool|string
	 */
	public function GetVacancy (string $dateFrom, string $dateTo, array|int $locationIds, array|int $beachChairTypeIds): bool|string {

		$locationIds       = is_array ($locationIds) ? implode (',', $locationIds) : $locationIds;
		$beachChairTypeIds = is_array ($beachChairTypeIds) ? implode (',', $beachChairTypeIds) : $beachChairTypeIds;

		$locationIds       = $locationIds ? : 0;
		$beachChairTypeIds = $beachChairTypeIds ? : 0;

		return $this->SendGetCurlRequest (sprintf (self::EP_BEACH_CHAIR_VACANCY, $dateFrom, $dateTo, $locationIds, $beachChairTypeIds));
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
	public function AddPayment (int $salesInvoiceId, int $salesHeaderId, int $paymentMethodId, string $token, string $PayerID, string $captureId): bool|string {

		$params = [
			'sales_invoice_id'  => $salesInvoiceId,
			'sales_header_id'  => $salesHeaderId,
			'payment_method_id' => $paymentMethodId,
			'complete_payment'  => 1,
			'paypal_token'      => $token,
			'paypal_payer_id'   => $PayerID,
			'paypal_capture_id' => $captureId,

		];

		return $this->SendPostCurlRequest (self::EP_SALES_INVOICE_PAYMENT, $params);

	}

	/**
	 * @param int $salesHeaderId
	 * @return bool|string
	 */
	public function SendInvoice (int $salesHeaderId): bool|string {

		$params = [
			'sales_header_id' => $salesHeaderId,
		];

		return $this->SendPostCurlRequest (self::EP_SALES_INVOICE_SEND, $params);

	}
	#endregion

	#region PRIVATE METHODS

	/**
	 * @param $string
	 * @return bool
	 */
	private static function IsJson ($string): bool {

		json_decode ($string);

		return json_last_error () === JSON_ERROR_NONE;
	}

	/**
	 * @param string $endpoint
	 * @return string
	 */
	private function SendGetCurlRequest (string $endpoint): string {

		$endpoint = $this->FormatEndpoint ($endpoint);

		$curl = curl_init ($this->apiURL.$endpoint);

		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($curl, CURLOPT_HTTPGET, 1);
		curl_setopt ($curl, CURLOPT_HTTPHEADER, $this->header);

		$response = curl_exec ($curl);
		$err      = curl_error ($curl);

		curl_close ($curl);

		if ($err) {
			return json_encode (["error" => "cURL Error #:".$err]);
		} else {
			return self::IsJson ($response) ? $response : json_encode (["error" => "Response for Request with URL: ".$this->apiURL.$endpoint."  wasn't a json valide string. Response: ".$response]);
		}

	}

	/**
	 * @param string $endpoint
	 * @param array $param
	 * @return string
	 */
	private function SendPostCurlRequest (string $endpoint, array $param): string {

		$endpoint = $this->FormatEndpoint ($endpoint);

		$curl                        = curl_init ($this->apiURL.$endpoint);
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
	 * @param string $endpoint
	 * @return string
	 */
	private function FormatEndpoint (string $endpoint): string {

		return str_starts_with ($endpoint, '/') ? $endpoint : '/'.$endpoint;
	}
	#endregion

}

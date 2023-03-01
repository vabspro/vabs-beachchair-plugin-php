<?php

use DD\Exceptions\ValidationException;
use DD\Helper\Date;
use DD\Mailer\Mailer;
use DD\PayMent\PayPal\PayPalAddress;
use DD\PayMent\PayPal\PayPalAmount;
use DD\PayMent\PayPal\PayPalApplicationContext;
use DD\PayMent\PayPal\PayPalBreakDown;
use DD\PayMent\PayPal\PayPalItem;
use DD\PayMent\PayPal\PayPalName;
use DD\PayMent\PayPal\PayPalOrder;
use DD\PayMent\PayPal\PayPalPurchaseUnit;
use DD\PayMent\PayPal\PayPalRequestBody;
use DD\PayMent\PayPal\PayPalShipping;
use DD\PayMent\PayPal\PayPalValues;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\IOException;
use VABS\API;
use VABS\Contact;
use VABS\Settings;
use VABS\Email;

require_once '../../vendor/autoload.php';
require_once '../../config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/wp-config.php';

$method = $_POST['method'] ?? '';

$responseArray['error'] = '';

const OBJECT_TYPE_BEACH_CHAIR = 7;

const PAYMENT_METHOD_INVOICE = 1;
const PAYMENT_METHOD_PAYPAL = 2;

try {

	//SETTINGS

	if ($method == 'SaveSettings') {

		$settings                                  = new Settings();
		$settings->apiToken                        = $_POST['apiToken'] ? : '';
		$settings->apiClientId                     = $_POST['apiClientId'] ? : '';
		$settings->apiURL                          = $_POST['apiURL'] ? : '';
		$settings->dsgvoLink                       = $_POST['dsgvoLink'] ? : '';
		$settings->agbLink                         = $_POST['agbLink'] ? : '';
		$settings->redirectLink                    = $_POST['redirectLink'] ? : '';
		$settings->textBeforeBooking               = $_POST['textBeforeBooking'] ? : '';
		$settings->referrerId                      = $_POST['referrerId'] ? (int)$_POST['referrerId'] : 0;
		$settings->payPal                          = isset($_POST['payPal']) ? (int)$_POST['payPal'] : 0;
		$settings->payPalSandbox                   = isset($_POST['payPalSandbox']) ? (int)$_POST['payPalSandbox'] : 1;
		$settings->payPalClientId                  = $_POST['payPalClientId'] ? : '';
		$settings->payPalClientSecret              = $_POST['payPalClientSecret'] ? : '';
		$settings->debug                           = $_POST['debug'] == "true" ? 1 : 0;
		$settings->smtpServer                      = $_POST['smtpServer'] ? : '';
		$settings->smtpUser                        = $_POST['smtpUser'] ? : '';
		$settings->smtpPass                        = $_POST['smtpPass'] ? : '';
		$settings->blockBookingEnabled             = $_POST['blockBookingEnabled'] ? : 0;
		$settings->blockBookingFrom                = $_POST['blockBookingFrom'] ? Date::FormatDateToFormat ($_POST['blockBookingFrom'], Date::DATE_FORMAT_SQL_DATE) : '';
		$settings->blockBookingTo                  = $_POST['blockBookingTo'] ? Date::FormatDateToFormat ($_POST['blockBookingTo'], Date::DATE_FORMAT_SQL_DATE) : '';
		$settings->blockBookingText                = $_POST['blockBookingText'] ? : '';
		$settings->additionalCalendarStartDays     = $_POST['additionalCalendarStartDays'] ? : 0;
		$settings->additionalCalendarStartDaysText = $_POST['additionalCalendarStartDaysText'] ? : 0;
		if (!$settings->Save ()) {
			throw new Exception("Data could not be saved. Error: ".$settings->errorMessage);
		}

	}

	if ($method == "GetReferrer") {

		$settingsReferrerId = $_POST['settingsReferrerId'] ?? '';

		$API = new API();
		$response = $API->GetReferrer ();
		$array = json_decode ($response,true );
		ob_start ();
		foreach($array as $element){
			?>
			<option value="<?php echo $element['id'] ?? 0; ?>" <?php echo $element['id'] == $settingsReferrerId ? "selected" : ""; ?>><?php echo $element['name'] ?? 'n/a'; ?></option>
			<?php
		}
		$content = ob_get_contents ();
		ob_end_clean ();

		$responseArray['data'] = $content;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetDSGVO") {

		$API = new API();
		$response = $API->GetDSGVO ();
		$array = json_decode ($response,true );

		$responseArray['data'] = $array['link'] ?? '';
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetConstants") {

		$API = new API();
		$response = $API->GetConstants ();
		$array = json_decode ($response,true );

		$responseArray['BEACHCHAIR_TYPES_BASE_PATH'] = $array['BEACHCHAIR_TYPES_BASE_PATH'] ?? '';
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetAGBS") {

		$API      = new API();
		$response = $API->GetAGBS ();
		$array    = json_decode ($response, true);

		$responseArray['data'] = $array['link'] ?? '';
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetBeachChairTypes") {

		$API                    = new API();
		$response               = $API->GetBeachChairTypes ();
		$responseArray['data']  = json_decode ($response, true);
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetLocations") {

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';

		$API                    = new API();
		$response               = $API->GetLocations ($dateFrom, $dateTo);
		$responseArray['data']  = json_decode ($response, true);
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetBookableLocations") {

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';

		$API                    = new API();
		$response               = $API->GetBookableLocations ($dateFrom, $dateTo);
		$responseArray['data']  = json_decode ($response, true);
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetRows") {

		$locationId = $_POST['locationId'] ?? '';

		$API      = new API();
		$response = $API->GetRows ();
		$array    = json_decode ($response, true);

		$responseArray['data'] = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetPrice") {

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';
		$id = $_POST['id'] ?? '';

		$API      = new API();
		$response = $API->GetPrice ($id, $dateFrom, $dateTo);
		$array    = json_decode ($response, true);

		$responseArray['data'] = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "LoadAdditionalStartDaysValue") {

		$value    = 0;
		$settings = new Settings();
		$responseArray['data'] = 0;
		$responseArray['error'] = '';

		try {

			if(!$settings->Load ()){
				throw new ValidationException("Fehler beim Laden der Einstellungen");
			}

			if ($settings->row instanceof Settings) {
				$value = $settings->row->additionalCalendarStartDays;
				$responseArray['data'] = $value;
			} else {
				throw new ValidationException("row wasn't instance of Settings");
			}

		} catch(ValidationException|Exception $e) {
			$responseArray['error'] = $e->getMessage ();
		}

	}

	if ($method == "GetBeachChairs") {

		$locationId = $_POST['locationId'] ?? '';

		$API      = new API();
		$response = $API->GetBeachChairs ($locationId);
		$array    = json_decode ($response, true);

		$responseArray['data'] = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetFreeChairs") {

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';
		$locationId = $_POST['locationId'] ?? '';

		$API      = new API();
		$response = $API->GetFreeChairs ($dateFrom, $dateTo, $locationId);
		$array    = json_decode ($response, true);

		$responseArray['data'] = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetVacancy") {

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';
		$locationIds = $_POST['locationIds'] ?? '';
		$beachChairTypeIds = $_POST['beachChairTypeIds'] ?? '';

		$API      = new API();
		$response = $API->GetVacancy ($dateFrom, $dateTo, $locationIds, $beachChairTypeIds);
		$array    = json_decode ($response, true);

		$responseArray['data'] = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "ValidateAndSendOrder") {

		$totalAmount = 0;
		$totalNetAmount = 0;
		$totalTaxAmount = 0;
		$tax = 19;

		$dateFrom        = $_POST['dateFrom'] ?? '';
		$dateTo          = $_POST['dateTo'] ?? '';
		$formData        = $_POST['formData'] ?? '';
		$shoppingCart    = $_POST['shoppingCart'] ?? '';

		$formDataDecoded = json_decode (str_replace ("\\","",$formData), true);
		$lines           = json_decode (str_replace ("\\", "", $shoppingCart), true);

		#region Validation of JSON
		if ($formDataDecoded === null) {
			throw new ValidationException('Das übermittelte Kontaktformular ist kein valides JSON.');
		}

		if ($lines === null) {
			throw new ValidationException('Der Lines-String ist kein valides JSON');
		}
		#endregion

		#region Validate Values

		//Date
		$dateFrom = Date::FormatDateToFormat ($dateFrom, Date::DATE_FORMAT_SQL_DATE);
		$dateTo   = Date::FormatDateToFormat ($dateTo, Date::DATE_FORMAT_SQL_DATE);
		$compare  = Date::CompareDates ($dateTo, $dateFrom);
		if($compare == Date::DATE_COMPARE_1_LT_2){
			throw new ValidationException("Datum von darf nicht größer sein, als Datom bis");
		}

		//Contact
		$message = [];

		$firstName = $formDataDecoded['firstName'] ? : '';
		$lastName  = $formDataDecoded['lastName'] ? : '';
		$email     = $formDataDecoded['email'] ? : '';
		$tel       = $formDataDecoded['tel'] ? : '';
		$postCode  = $formDataDecoded['postCode'] ? : '';
		$city      = $formDataDecoded['city'] ? : '';
		$street    = $formDataDecoded['street'] ? : '';
		$number    = $formDataDecoded['number'] ? : '';

		if(empty($firstName)){
			$message[] = "Vorname war leer";
		}

		if(empty($lastName)){
			$message[] = "Nachname war leer";
		}

		if(empty($email)){
			$message[] = "Email war leer";
		}else{

			if(!filter_var ($email, FILTER_VALIDATE_EMAIL)){

				$message[] = "Email ist nicht gültig";
			}

		}

		if(empty($tel)){
			$message[] = "Telefon war leer";
		}

		if(empty($postCode)){
			$message[] = "Plz war leer";
		}

		if(empty($city)){
			$message[] = "Ort war leer";
		}

		if(empty($street)){
			$message[] = "Strassenname war leer";
		}

		if(empty($number)){
			$message[] = "Hausnummer war leer";
		}

		$paymentMethodId = $formDataDecoded['paymentMethodId'] ?? 0;

		if (empty($paymentMethodId)) {
			$message[] = "Bitte wählen Sie eine Zahlungsmethode aus!";
		}

		$confirm = $formDataDecoded['confirm'] ? : 0;

		if (empty($confirm)) {
			$message[] = "Bitte bestätigen Sie die AGBs und die Datenschutzvereinbarung!";
		}

		//Comment
		$comment = $formDataDecoded['comment'] ?: '';

		//Lines
		$i=0;
		foreach ($lines as $line){

			$i++;
			$id        = $line['id'] ? : 0;
			$unitPrice = $line['unitPrice'] ? : 0;
			$dateFrom  = $line['dateFrom'] ? : '';
			$dateTo    = $line['dateTo'] ? : '';

			if (empty($id)) {
				$message[] = sprintf("ID war leer für Zeile %d",$i);
			}

			if (empty($unitPrice)) {
				$message[] = sprintf ("Preis war leer für Zeile %d", $i);
			}

			if (empty($dateFrom)) {
				$message[] = sprintf ("Das Datum Von war leer für Zeile %d", $i);
			}

			if (empty($dateTo)) {
				$message[] = sprintf ("Das Datum Bis war leer für Zeile %d", $i);
			}

			//Check, if chairs have already been booked in the meanwhile
			$API      = new API();
			$response = $API->IsBooked ($id, $dateFrom, $dateTo);
			$array    = json_decode ($response, true);

			$isBooked = (int)$array['isBooked'];
			$chairName = $array['chairNumber'];
			if($isBooked){
				$message[] = sprintf ("Sorry, aber der Korb %s wurde in der Zwischenzeit bereits gebucht.", $chairName);

			}

		}

		reset ($lines);

		#endregion

		if(!empty($message)){
			$output = "";
			foreach($message as $item){
				$output.= "- ".$item."<br>";
			}
			throw new ValidationException($output);
		}


		############
		### BOOK ###
		############

		#region Create Contact

		$Contact            = new Contact();
		$Contact->firstname = $firstName;
		$Contact->lastname  = $lastName;
		$Contact->email     = $email;
		$Contact->mobile    = $tel;
		$Contact->zip_code  = $postCode;
		$Contact->city      = $city;
		$Contact->street    = $street;
		$Contact->number    = $number;

		$API      = new API();
		$response = $API->CreateContact ($Contact);
		$array    = json_decode ($response, true);

		$contactId = $array['contact_id'] ? : 0;
		if(empty($contactId)){
			throw new ValidationException("Keine Kontakt-ID als Response zurückbekommen. Response:".$response);
		}

		#endregion

		#region Create SalesOrderHeader

		$API      = new API();
		$response = $API->CreateSalesOrderHeader ($contactId,$comment);
		$array    = json_decode ($response, true);

		$salesHeaderId = $array['sales_header_id'] ? : 0;
		if (empty($salesHeaderId)) {
			throw new ValidationException("Keine SalesHeader-ID als Response zurückbekommen");
		}

		$_SESSION['salesHeaderId'] = $salesHeaderId;

		#endregion

		#region Create SalesOrderLines

		foreach ($lines as $line) {

			$quantity = 1;
			$unitPrice = $line['unitPrice'] ?? 0;
			$dateFrom = $line['dateFrom'] ?? 0;
			$dateTo = $line['dateTo'] ?? 0;
			$id = $line['id'] ? : 0;
			if(empty($unitPrice)){
				throw new ValidationException("Kein Preis übergeben");
			}

			$API      = new API();
			$response = $API->CreateSalesOrderLine ($salesHeaderId,$id, $quantity,OBJECT_TYPE_BEACH_CHAIR,$dateFrom,$dateTo);
			$array    = json_decode ($response, true);

			$salesLineId = $array['sales_line_id'] ? : 0;
			if (empty($salesLineId)) {
				throw new ValidationException("Keine SalesLine-ID als Response zurückbekommen.".$response);
			}

			$totalAmount    += $unitPrice;
			$netAmount      = $unitPrice / 1.19;
			$totalNetAmount += $netAmount;
			$taxamount      = $unitPrice - $netAmount;
			$totalTaxAmount += $taxamount;


		}

		#endregion

		#region Create Invoice

		$API      = new API();
		$response = $API->CreateSalesInvoice ($salesHeaderId);
		$array    = json_decode ($response, true);

		$salesInvoiceId = $array['sales_invoice_id'] ? : 0;
		if (empty($salesInvoiceId)) {
			throw new ValidationException("Keine SalesInvoice-ID als Response zurückbekommen. Response:".$response);
		}

		$_SESSION['salesInvoiceId'] = $salesInvoiceId;

		//Send Invoice via PDF
		$API->SendInvoice ($salesHeaderId);

		#endregion

		//Redirect?
		$Settings = new Settings();
		if(!$Settings->Load ()){
			throw new Exception("Einstellungen konnten nicht geladen werden");
		}
		$row = $Settings->row;
		if(!$row instanceof Settings){
			throw new Exception("row wasn't instance of Settings");
		}
		$responseArray['redirectLink'] = $row->redirectLink ?: '';

		#region PAYPAL

		if ($paymentMethodId == PAYMENT_METHOD_PAYPAL) {

			$payPalErrorMessage = 'PayPal steht momentan nicht zur Verfügung. Bitte überweisen Sie den Betrag laut Aufforderung in der Email, die wir Ihnen gerade geschickt haben!';

			$usePayPal = $row->payPal == 1;
			$isSandBox = (int)$row->payPalSandbox === 1;

			if($row->payPal != 1){
				throw new ValidationException("Sie haben PayPal ausgewählt, aber dies ist eigentlich gar nicht eingeschaltet");
			}

			//Make PayPal Request
			if ($isSandBox) {
				$environment = new SandboxEnvironment($row->payPalClientId, $row->payPalClientSecret);
			} else {
				$environment = new ProductionEnvironment($row->payPalClientId, $row->payPalClientSecret);
			}

			$client = new PayPalHttpClient($environment);

			// Construct a request object and set desired parameters
			// Here, OrdersCreateRequest() creates a POST request to /v2/checkout/orders

			$request = new OrdersCreateRequest();
			$request->prefer ('return=representation');

			//Shipping Node
			$PayPalAddress                 = new PayPalAddress();
			$PayPalAddress->address_line_1 = $street." ".$number;
			$PayPalAddress->admin_area_2   = $city;
			$PayPalAddress->postal_code    = $postCode;
			$PayPalAddress->country_code   = 'DE';

			$PayPalName            = new PayPalName();
			$PayPalName->full_name = $firstName." ".$lastName;

			$PayPalShipping          = new PayPalShipping();
			$PayPalShipping->type    = PayPalShipping::SHIPPING;
			$PayPalShipping->name    = (array)$PayPalName;
			$PayPalShipping->address = (array)$PayPalAddress;

			//Items

			$totalTaxAmountFormatted = number_format($totalTaxAmount,2);
			$totalNetAmountFormatted = number_format($totalNetAmount,2);
			$totalAmountFormatted = number_format($totalNetAmount + $totalTaxAmount,2);

			$UnitAmount                = new PayPalAmount();
			$UnitAmount->value         = "$totalNetAmountFormatted";
			$UnitAmount->currency_code = 'EUR';

			$Tax                = new PayPalAmount();
			$Tax->currency_code = 'EUR';
			$Tax->value         = "$totalTaxAmountFormatted";

			$PayPalItem              = new PayPalItem();
			$PayPalItem->name        = "Strandkorbbuchung";
			$PayPalItem->description = "Komplett";
			$PayPalItem->unit_amount = (array)$UnitAmount;
			$PayPalItem->tax         = (array)$Tax;

			$quantity = 1;
			$PayPalItem->quantity    = "$quantity";

			$items[] = (array)$PayPalItem;

			//Amount

			$TotalAmount                = new PayPalValues();
			$TotalAmount->currency_code = 'EUR';
			$TotalAmount->value         = "$totalNetAmountFormatted";

			$TaxTotal                = new PayPalValues();
			$TaxTotal->currency_code = 'EUR';
			$TaxTotal->value         = "$totalTaxAmountFormatted";

			$Breakdown             = new PayPalBreakDown();
			$Breakdown->item_total = (array)$TotalAmount;
			$Breakdown->tax_total  = (array)$TaxTotal;

			$PayPalAmount                = new PayPalAmount();
			$PayPalAmount->value         = "$totalAmountFormatted";
			$PayPalAmount->currency_code = 'EUR';
			$PayPalAmount->breakdown     = (array)$Breakdown;

			//PayPalPurchaseUnit
			$PayPalPurchaseUnit               = new PayPalPurchaseUnit();
			$PayPalPurchaseUnit->amount       = (array)$PayPalAmount;
			$PayPalPurchaseUnit->reference_id = "$salesHeaderId";
			$PayPalPurchaseUnit->items        = (array)$items;
			$PayPalPurchaseUnit->shipping = (array)$PayPalShipping;

			//PayPalApplicationContext
			$PayPalApplicationContext              = new PayPalApplicationContext();
			$PayPalApplicationContext->return_url  = $_SESSION['payPalSuccessRedirectLink'] ?: '';
			$PayPalApplicationContext->cancel_url  = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; //TODO: CANCEL URI needs to be defined
			$PayPalApplicationContext->brand_name  = 'VABS-CUSTOMER :: Strandkorbbuchung';
			$PayPalApplicationContext->user_action = PayPalApplicationContext::PAY_NOW;

			$PayPalOrder                      = new PayPalOrder();
			$PayPalOrder->intent              = PayPalOrder::CAPTURE;
			$PayPalOrder->application_context = (array)$PayPalApplicationContext;
			$purchaseUnits                    = [];
			$purchaseUnits[]                  = (array)$PayPalPurchaseUnit;
			$PayPalOrder->purchase_units      = $purchaseUnits;

			$PayPalRequestBody = new PayPalRequestBody($PayPalOrder);
			$requestBody       = (array)$PayPalOrder; //$PayPalRequestBody->requestBody;

			$request->body = $requestBody;

			try {

				// Call API with your client and get a response for your call
				$response = $client->execute ($request);

				if ($response->statusCode == 201) {
					$token = $response->result->id ?? '';
					$link  = $response->result->links[1]->href ?? '';
					if ($link != '' && $token != '') {

						$responseArray['confirmationUrl'] = $link;

					} else {
						$responseArray['error'] = $payPalErrorMessage;
						Mailer::SendAdminMail ("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__." Error: Fehler: Kein Token oder Confirmation - Link zur Verfügung gestellt", Mailer::EMAIL_SUBJECT_EXCEPTION);
					}
				} else {
					if ($isSandBox) {
						$responseArray['error'] = json_encode ($response);
					} else {
						$responseArray['error'] = $payPalErrorMessage;
						Mailer::SendAdminMail ("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__." Error: ".json_encode ($response), Mailer::EMAIL_SUBJECT_EXCEPTION);
						//TODO: Update SalesOrder/Invoice with Status


					}
				}

			} catch (\PayPalHttp\HttpException $e) {

				if ($isSandBox) {
					$responseArray['error'] = "3::StatusCode: ".$e->statusCode." Error: ".$e->getMessage ();
				} else {
					$responseArray['error'] = $payPalErrorMessage;
					Mailer::SendAdminMail ("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__." Error: 3::StatusCode: ".$e->statusCode." Error: ".$e->getMessage (), Mailer::EMAIL_SUBJECT_EXCEPTION);
				}

			} catch (IOException $e) {

				if ($isSandBox) {
					$responseArray['error'] = "IOException. Error: ".$e->getMessage ();
				} else {
					$responseArray['error'] = $payPalErrorMessage;
					Mailer::SendAdminMail ("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__." Error: 3::StatusCode: ".$e->getMessage ()." Error: ".$e->getMessage (), Mailer::EMAIL_SUBJECT_EXCEPTION);
				}

			}

		}

		#endregion

	}

	if ($method == "GenerateShortCode") {

		$formType     = $_POST['formType'] ?? '';

		$Settings = new Settings();
		if (!$Settings->Load ()) {
			throw new Exception("Einstellungen konnten nicht geladen werden");
		}
		$row = $Settings->row;
		if (!$row instanceof Settings) {
			throw new Exception("row wasn't instance of Settings");
		}
		$agbsLink = $row->agbLink;
		$dsgvoLink = $row->dsgvoLink;
		$redirectLink = $row->redirectLink;

		if(empty($formType)){
			throw new Exception("Ein Formular-Art muss schon gewählt werden");
		}

		$responseArray['data'] = '[generate_vabs_form type="'.$formType.'" agb="'.$row->agbLink.'" datenschutz="'.$row->dsgvoLink.'" redirectLink="'.$row->redirectLink.'"]';

	}

	if ($method == 'SendTestEmail') {

		$responseArray['error'] = "";

		try {

			$smtpServer = $_POST['smtpServer'] ?? '';
			$smtpUser   = $_POST['smtpUser'] ?? '';
			$smtpPass   = $_POST['smtpPass'] ?? '';
			$to         = $_POST['to'] ?? EMAIL_DEVELOPER;

			define ("SMTP_USER", $smtpUser);
			define ("SMTP_PASS", $smtpPass);
			define ("SMTP_SERVER", $smtpServer);

			Email::SendAdminMail ("Das ist eine Testmail. Diese wurde von ".$_SERVER['HTTP_HOST']." gesendet");

		} catch(Exception $e) {

			$responseArray['error'] = "Fehler beim Emailversand: " . $e->getMessage ();

		}

	}

} catch (Exception $e) {

	$message = $e->getMessage ();
	Email::SendAdminMail("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__." Error: ".$message, Mailer::EMAIL_SUBJECT_EXCEPTION);
	$responseArray['error'] = $message;

} // ENDE try {

echo json_encode ($responseArray);


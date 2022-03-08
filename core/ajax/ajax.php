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

$allowedMethods = [
		'SaveSettings',
		'GetReferrer',
		'GetDSGVO',
		'GetAGBS',
		'GenerateShortCode',
		'GetLocations',
];

$method = $_POST['method'] ?? '';

$responseArray['error'] = '';

const OBJECT_TYPE_BEACH_CHAIR = 7;

const PAYMENT_METHOD_INVOICE = 1;
const PAYMENT_METHOD_PAYPAL = 2;

try {

	//SETTINGS

	if ($method == 'SaveSettings') {

		$Config                   = new Settings();
		$Config->agbLink            = $_POST['agbLink'] ? : '';
		$Config->dsgvoLink          = $_POST['dsgvoLink'] ? : '';
		$Config->apiURL             = $_POST['apiURL'] ? : '';
		$Config->apiToken           = $_POST['apiToken'] ? : '';
		$Config->apiClientId        = $_POST['apiClientId'] ? : '';
		$Config->referrerId         = $_POST['referrerId'] ? (int)$_POST['referrerId'] : 0;
		$Config->redirectLink       = $_POST['redirectLink'] ? : '';
		$Config->payPal             = isset($_POST['payPal']) ? (int)$_POST['payPal'] : 0;
		$Config->payPalSandbox      = isset($_POST['payPalSandbox']) ? (int)$_POST['payPalSandbox'] : 1;
		$Config->payPalClientId     = $_POST['payPalClientId'] ? : '';
		$Config->payPalClientSecret = $_POST['payPalClientSecret'] ? : '';
		$Config->textBeforeBooking  = $_POST['textBeforeBooking'] ? : '';
		$Config->zoom  = $_POST['zoom'] ? : 15;
		$Config->latCenter  = $_POST['latCenter'] ? : '';
		$Config->lonCenter  = $_POST['lonCenter'] ? : '';
		$Config->smtpServer  = $_POST['smtpServer'] ? : '';
		$Config->smtpUser  = $_POST['smtpUser'] ? : '';
		$Config->smtpPass  = $_POST['smtpPass'] ? : '';
		if (!$Config->Save ()) {
			throw new Exception("Data could not be saved");
		}

	}

	if($method == "GetReferrer"){

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

	if($method == "GetDSGVO"){

		$API = new API();
		$response = $API->GetDSGVO ();
		$array = json_decode ($response,true );


		$responseArray['data'] = $array['link'] ?? '';
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetAGBS") {

		$API      = new API();
		$response = $API->GetAGBS ();
		$array    = json_decode ($response, true);

		//print_r($array);

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

		//print_r($array);

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

		//print_r($array);

		$responseArray['data'] = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetBeachChairs") {

		$locationId = $_POST['locationId'] ?? '';

		$API      = new API();
		$response = $API->GetBeachChairs ($locationId);
		$array    = json_decode ($response, true);

		//print_r($array);

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

		//print_r($array);

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

		//print_r($array);

		$responseArray['data'] = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "ValidateAndSendOrder") {

		$totalAmount = 0;
		$totalNetAmount = 0;
		$totalTaxAmount = 0;
		$tax = 19;

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';
		$formData        = $_POST['formData'] ?? '';
		$formDataDecoded = json_decode ($formData, true);

		$shoppingCart = $_POST['shoppingCart'] ?? '';
		$lines        = json_decode ($shoppingCart, true);

		#region Validation of JSON
		if ($formDataDecoded === null) {
			throw new ValidationException('Der Kontakt-String ist kein valides JSON');
		}

		if ($lines === null) {
			throw new ValidationException('Der Lines-String ist kein valides JSON');
		}
		#endregion

		#region Validate Values

		//Date
		$dateFrom = Date::FormatDateToFormat ($dateFrom, Date::DATE_FORMAT_SQL_DATE);
		$dateTo = Date::FormatDateToFormat ($dateTo, Date::DATE_FORMAT_SQL_DATE);
		$compare = Date::CompareDates ($dateTo, $dateFrom);
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
		//$message[] = print_r($lines, true);
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
			throw new ValidationException("Keine Kontakt-ID als Response zurückbekommen");
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

			//print_r ($array);

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
			throw new ValidationException("Keine SalesInvoice-ID als Response zurückbekommen");
		}

		$_SESSION['salesInvoiceId'] = $salesInvoiceId;

		#endregion

		//Redirect?
		$Settings = new Settings();
		$array = $Settings->Load ();
		$responseArray['redirectLink'] = $array['redirectLink'] ?: '';

		#region PAYPAL

		if ($paymentMethodId == PAYMENT_METHOD_PAYPAL) {

			$Settings = new Settings();
			$settings = $Settings->Load ();
			$usePayPal = $settings['payPal'] == 1;
			$isSandBox = (int)$settings['payPalSandbox'] === 1;

			if($settings['payPal'] != 1){
				throw new ValidationException("Sie haben PayPal ausgewählt, aber dies ist eigentlich gar nicht eingeschaltet");
			}

			//Make PayPal Request
			if ($isSandBox) {
				$environment = new SandboxEnvironment($settings['payPalClientId'], $settings['payPalClientSecret']);
			} else {
				$environment = new ProductionEnvironment($settings['payPalClientId'], $settings['payPalClientSecret']);
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

			//TODO: Get Unit NET Price and TaxAmount
			$totalTaxAmountFormatted = number_format($totalTaxAmount,2);
			$totalNetAmountFormatted = number_format($totalNetAmount,2);
			$totalAmountFormatted = number_format($totalNetAmount + $totalTaxAmount,2);

			$UnitAmount                = new PayPalAmount();
			$UnitAmount->value         = "$totalNetAmountFormatted";
			$UnitAmount->currency_code = 'EUR';

			$Tax                = new PayPalAmount();
			$Tax->currency_code = 'EUR';
			$Tax->value         = "$totalTaxAmountFormatted";

			//Todo: get Ticket properties

			$PayPalItem              = new PayPalItem();
			$PayPalItem->name        = "Strandkorbbuchung";
			$PayPalItem->description = "Komplett";
			$PayPalItem->unit_amount = (array)$UnitAmount;
			$PayPalItem->tax         = (array)$Tax;

			//Todo: get quantity for each item instead of complete order
			$quantity = 1;
			$PayPalItem->quantity    = "$quantity";

			$items[] = (array)$PayPalItem;

			//Amount

			//TODO: Get NET Price and TaxAmount

			$TotalAmount                = new PayPalValues();
			$TotalAmount->currency_code = 'EUR';
			$TotalAmount->value         = "$totalNetAmountFormatted";

			$TaxTotal                = new PayPalValues();
			$TaxTotal->currency_code = 'EUR';
			$TaxTotal->value         = "$totalTaxAmountFormatted";

			$Breakdown             = new PayPalBreakDown();
			$Breakdown->item_total = (array)$TotalAmount;
			$Breakdown->tax_total  = (array)$TaxTotal;

			//TODO: Get TOTAL Price and Tax

			$PayPalAmount                = new PayPalAmount();
			$PayPalAmount->value         = "$totalAmountFormatted";
			$PayPalAmount->currency_code = 'EUR';
			$PayPalAmount->breakdown     = (array)$Breakdown;

			//PayPalPurchaseUnit
			$PayPalPurchaseUnit               = new PayPalPurchaseUnit();
			$PayPalPurchaseUnit->amount       = (array)$PayPalAmount;
			$PayPalPurchaseUnit->reference_id = "$salesHeaderId";
			$PayPalPurchaseUnit->items        = (array)$items;
			//$PayPalPurchaseUnit->description = 'Beschreibung'; //TODO Check if useless
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

			//Show Array with pre
			/*echo '<pre>';
			print_r($PayPalOrder);
			echo '</pre>';*/

			$PayPalRequestBody = new PayPalRequestBody($PayPalOrder);
			$requestBody       = (array)$PayPalOrder; //$PayPalRequestBody->requestBody;

			//$request->body = $body;
			$request->body = $requestBody;

			/*echo '<pre>';
			print_r($requestBody);
			echo '</pre>';*/

			try {

				// Call API with your client and get a response for your call
				$response = $client->execute ($request);
				$var      = print_r ($response, true);

				//\DD\Mailer\Mailer::SendAdminMail ("File: ".__FILE__."<br>Place: Request<br>Response: ".$var, \DD\Mailer\Mailer::EMAIL_SUBJECT_DEBUG);

				if ($response->statusCode == 201) {
					$token = $response->result->id ?? '';
					$link  = $response->result->links[1]->href ?? '';
					if ($link != '' && $token != '') {

						$responseArray['confirmationUrl'] = $link;

					} else {
						$responseArray['error'] = '1::PayPal steht momentan nicht zur Verfügung. Bitte wählen Sie eine andere Zahlungsmethode. Fehler: Kein Token oder Confirmation-Link zur Verfügung gestellt';
					}
				} else {
					if ($isSandBox) {
						$responseArray['error'] = json_encode ($response);
					} else {
						$responseArray['error'] = '2::PayPal steht momentan nicht zur Verfügung. Bitte wählen Sie eine andere Zahlungsmethode. Fehler: Falscher StatusCode.';
						Mailer::SendAdminMail ("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__." Error: ".json_encode ($response), Mailer::EMAIL_SUBJECT_EXCEPTION);
						//TODO: Update SalesOrder/Invoice with Status


					}
				}

			} catch (\PayPalHttp\HttpException $e) {

				if ($isSandBox) {
					$responseArray['error'] = "3::StatusCode: ".$e->statusCode." Error: ".$e->getMessage ();
				} else {
					$responseArray['error'] = '3::PayPal steht momentan nicht zur Verfügung. Bitte wählen Sie eine andere Zahlungsmethode. Fehler beim Ausführen des Requests.';
				}

			} catch (IOException $e) {

				if ($isSandBox) {
					$responseArray['error'] = "IOException. Error: ".$e->getMessage ();
				} else {
					$responseArray['error'] = '4::PayPal steht momentan nicht zur Verfügung. Bitte wählen Sie eine andere Zahlungsmethode. Fehler beim Ausführen des Requests.';
				}

			}

		}else{
			$API = new API();
			$API->SendInvoice ($salesHeaderId);
		}

		#endregion

	}

	//SHORTCODE

	if ($method == "GenerateShortCode") {

		$formType = $_POST['formType'] ?? '';
		$redirectLink = $_POST['redirectLink'] ?? '';
		$Settings = new Settings();
		$settings = $Settings->Load ();
		$agbsLink = $settings['agbLink'];
		$dsgvoLink = $settings['dsgvoLink'];
		$redirectLink = $settings['redirectLink'];

		if(empty($formType)){
			throw new Exception("Ein Formular-Art muss schon gewählt werden");
		}

		$responseArray['data'] = '[generate_vabs_form type="'.$formType.'" agb="'.$agbsLink.'" datenschutz="'.$dsgvoLink.'" redirectLink="'.$redirectLink.'"]';

	}

	if($method == 'LoadMapSettings'){

		$Settings = new Settings();
		$array = $settings = $Settings->Load ();

		$responseArray['data']['zoom']  = $array['zoom'];
		$responseArray['data']['latCenter']  = $array['latCenter'];
		$responseArray['data']['lonCenter']  = $array['lonCenter'];
		$responseArray['error'] = $array['error'] ?? '';

	}

} catch (Exception $e) {

	$message = $e->getMessage ();
	Email::SendAdminMail("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__." Error: ".$message, Mailer::EMAIL_SUBJECT_EXCEPTION);
	$responseArray['error'] = $message;

} // ENDE try {

echo json_encode ($responseArray);


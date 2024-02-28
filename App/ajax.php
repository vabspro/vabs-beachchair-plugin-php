<?php

use VABS\API;
use VABS\Contact;
use DD\Exceptions\ValidationException;
use DD\Helper\Date;
use DD\PayPal\Contact as PayPalContact;
use DD\PayPal\Header;
use DD\PayPal\Line;
use DD\PayPal\Process;
use DD\WordPress\VABS\VABSAPIWPSettings;

require_once '../vendor/autoload.php';
require_once '../config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';

$method = $_POST['method'] ?? '';

$responseArray['error'] = '';

const OBJECT_TYPE_BEACH_CHAIR = 7;

try {

	if (!defined ("DB_PASSWORD")) {
		throw new Exception("Kein Password für die Datanbank angegeben");
	} else {
		if (!defined ("DB_PASS")) {
			define ('DB_PASS', DB_PASSWORD);
		}
	}

	//SETTINGS

	$Settings = new VABSAPIWPSettings(SETTINGS_TABLE);
	$Settings->Load ();

	$API = new API($Settings->row->apiURL, $Settings->row->apiToken, $Settings->row->apiClientId);

	if ($method == 'SaveSettings') {

		$Settings                                  = new VABSAPIWPSettings(SETTINGS_TABLE);
		$Settings->apiToken                        = $_POST['apiToken'] ?: '';
		$Settings->apiClientId                     = $_POST['apiClientId'] ?: '';
		$Settings->apiURL                          = $_POST['apiURL'] ?: '';
		$Settings->dsgvoLink                       = $_POST['dsgvoLink'] ?: '';
		$Settings->agbLink                         = $_POST['agbLink'] ?: '';
		$Settings->successPage                     = $_POST['successPage'] ?: '';
		$Settings->cancelPage                      = $_POST['cancelPage'] ?: '';
		$Settings->textBeforeBooking               = $_POST['textBeforeBooking'] ?: '';
		$Settings->referrerId                      = $_POST['referrerId'] ? (int)$_POST['referrerId'] : 0;
		$Settings->payPal                          = isset($_POST['payPal']) ? (int)$_POST['payPal'] : 0;
		$Settings->payPalSandbox                   = isset($_POST['payPalSandbox']) ? (int)$_POST['payPalSandbox'] : 1;
		$Settings->payPalClientId                  = $_POST['payPalClientId'] ?: '';
		$Settings->payPalClientSecret              = $_POST['payPalClientSecret'] ?: '';
		$Settings->blockBookingEnabled             = $_POST['blockBookingEnabled'] ?: 0;
		$Settings->blockBookingFrom                = $_POST['blockBookingFrom'] ? Date::FormatDateToFormat ($_POST['blockBookingFrom'], Date::DATE_FORMAT_SQL_DATE) : '';
		$Settings->blockBookingTo                  = $_POST['blockBookingTo'] ? Date::FormatDateToFormat ($_POST['blockBookingTo'], Date::DATE_FORMAT_SQL_DATE) : '';
		$Settings->blockBookingText                = $_POST['blockBookingText'] ?: '';
		$Settings->additionalCalendarStartDays     = $_POST['additionalCalendarStartDays'] ?: 0;
		$Settings->additionalCalendarStartDaysText = $_POST['additionalCalendarStartDaysText'] ?: 0;
		if (!$Settings->Save ()) {
			throw new Exception("Data could not be saved. Error: " . $Settings->errorMessage);
		}

	}

	if ($method == "GetReferrer") {

		$SettingsReferrerId = $_POST['settingsReferrerId'] ?? '';

		$response = $API->GetReferrer ();
		$array    = json_decode ($response, true);
		ob_start ();
		foreach ($array as $element) {
			?>
            <option value="<?php
			echo $element['id'] ?? 0; ?>" <?php
			echo $element['id'] == $SettingsReferrerId ? "selected" : ""; ?>><?php
				echo $element['name'] ?? 'n/a'; ?></option>
			<?php
		}
		$content = ob_get_contents ();
		ob_end_clean ();

		$responseArray['data']  = $content;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetDSGVO") {

		$response = $API->GetDSGVO ();
		$array    = json_decode ($response, true);

		$responseArray['data']  = $array['link'] ?? '';
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetConstants") {

		$response = $API->GetConstants ();
		$array    = json_decode ($response, true);

		$responseArray['BEACHCHAIR_TYPES_BASE_PATH'] = $array['BEACHCHAIR_TYPES_BASE_PATH'] ?? '';
		$responseArray['error']                      = $array['error'] ?? '';

	}

	if ($method == "GetAGBS") {

		$response = $API->GetAGBS ();
		$array    = json_decode ($response, true);

		$responseArray['data']  = $array['link'] ?? '';
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetBeachChairTypes") {

		$response               = $API->GetBeachChairTypes ();
		$responseArray['data']  = json_decode ($response, true);
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetLocations") {

		$response               = $API->GetLocations ();
		$responseArray['data']  = json_decode ($response, true);
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetBookableLocations") {

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';

		$response = $API->GetBookableLocations ($dateFrom, $dateTo);
		$array    = json_decode ($response, true);

		$responseArray['data']     = $array;
		$responseArray['error']    = $array['error'] ?? '';
		$responseArray['noseason'] = !empty($array['noseason']);

	}

	if ($method == "GetRows") {

		$locationId = $_POST['locationId'] ?? '';

		$response = $API->GetRows ();
		$array    = json_decode ($response, true);

		$responseArray['data']  = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetPrice") {

		$dateFrom = $_POST['dateFrom'] ?? '';
		$dateTo   = $_POST['dateTo'] ?? '';
		$id       = $_POST['id'] ?? '';

		$response = $API->GetPrice ($id, $dateFrom, $dateTo);
		$array    = json_decode ($response, true);

		$responseArray['data']  = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "LoadAdditionalStartDaysValue") {

        $responseArray['data'] = $Settings->row->additionalCalendarStartDays;

	}

	if ($method == "GetBeachChairs") {

		$locationId = $_POST['locationId'] ?? '';

		$response = $API->GetBeachChairs ($locationId);
		$array    = json_decode ($response, true);

		$responseArray['data']  = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetFreeChairs") {

		$dateFrom   = $_POST['dateFrom'] ?? '';
		$dateTo     = $_POST['dateTo'] ?? '';
		$locationId = $_POST['locationId'] ?? '';

		$response = $API->GetFreeChairs ($dateFrom, $dateTo, $locationId);
		$array    = json_decode ($response, true);

		$responseArray['data']  = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "GetVacancy") {

		$dateFrom          = $_POST['dateFrom'] ?? '';
		$dateTo            = $_POST['dateTo'] ?? '';
		$locationIds       = $_POST['locationIds'] ?? '';
		$beachChairTypeIds = $_POST['beachChairTypeIds'] ?? '';

		$response = $API->GetVacancy ($dateFrom, $dateTo, $locationIds, $beachChairTypeIds);
		$array    = json_decode ($response, true);

		$responseArray['data']  = $array;
		$responseArray['error'] = $array['error'] ?? '';

	}

	if ($method == "ValidateAndSendOrder") {

		$referrerId = $Settings->row->referrerId;

		$totalAmount    = 0;
		$totalNetAmount = 0;
		$totalTaxAmount = 0;
		$tax            = 19;

		$dateFrom     = $_POST['dateFrom'] ?? '';
		$dateTo       = $_POST['dateTo'] ?? '';
		$formData     = $_POST['formData'] ?? '';
		$shoppingCart = $_POST['shoppingCart'] ?? '';

		$formDataDecoded = json_decode (str_replace ("\\", "", $formData), true);
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
		if ($compare == Date::DATE_COMPARE_1_LT_2) {
			throw new ValidationException("Datum von darf nicht größer sein, als Datom bis");
		}

		//Contact
		$message = [];

		$firstName = $formDataDecoded['firstName'] ?: '';
		$lastName  = $formDataDecoded['lastName'] ?: '';
		$email     = $formDataDecoded['email'] ?: '';
		$tel       = $formDataDecoded['tel'] ?: '';
		$postCode  = $formDataDecoded['postCode'] ?: '';
		$city      = $formDataDecoded['city'] ?: '';
		$street    = $formDataDecoded['street'] ?: '';
		$number    = $formDataDecoded['number'] ?: '';

		if (empty($firstName)) {
			$message[] = "Vorname war leer";
		}

		if (empty($lastName)) {
			$message[] = "Nachname war leer";
		}

		if (empty($email)) {
			$message[] = "Email war leer";
		} else {

			if (!filter_var ($email, FILTER_VALIDATE_EMAIL)) {

				$message[] = "Email ist nicht gültig";
			}

		}

		if (empty($tel)) {
			$message[] = "Telefon war leer";
		}

		if (empty($postCode)) {
			$message[] = "Plz war leer";
		}

		if (empty($city)) {
			$message[] = "Ort war leer";
		}

		if (empty($street)) {
			$message[] = "Strassenname war leer";
		}

		if (empty($number)) {
			$message[] = "Hausnummer war leer";
		}

		$paymentMethodId = $formDataDecoded['paymentMethodId'] ?? 0;

		if (empty($paymentMethodId)) {
			$message[] = "Bitte wählen Sie eine Zahlungsmethode aus!";
		}

		$confirm = $formDataDecoded['confirm'] ?: 0;

		if (empty($confirm)) {
			$message[] = "Bitte bestätigen Sie die AGBs und die Datenschutzvereinbarung!";
		}

		//Comment
		$comment = $formDataDecoded['comment'] ?: '';

		//Lines
		$i          = 0;
		$chairNames = [];
		foreach ($lines as $line) {

			$i++;
			$id        = $line['id'] ?: 0;
			$unitPrice = $line['unitPrice'] ?: 0;
			$dateFrom  = $line['dateFrom'] ?: '';
			$dateTo    = $line['dateTo'] ?: '';

			if (empty($id)) {
				$message[] = sprintf ("ID war leer für Zeile %d", $i);
			}

			if (empty($unitPrice)) {
				$message[] = sprintf ("Preis war leer für Zeile %d", $i);
			}

			if (empty($dateFrom)) {
				$message[] = sprintf ("Das Datum Von war leer für Zeile %d", $i);
			} else if (!Date::ValidateDate ($dateFrom, Date::DATE_FORMAT_SQL_DATE)) {
				$message[] = sprintf ("Das Datum Von war im falschen Format für Zeile %d", $i);
			}

			if (empty($dateTo)) {
				$message[] = sprintf ("Das Datum Bis war leer für Zeile %d", $i);
			} else if (!Date::ValidateDate ($dateTo, Date::DATE_FORMAT_SQL_DATE)) {
				$message[] = sprintf ("Das Datum Bis war im falschen Format für Zeile %d", $i);
			}

			//Check, if chairs have already been booked in the meanwhile
			$response = $API->IsBooked ($id, $dateFrom, $dateTo);
			$array    = json_decode ($response, true);

			$isBooked  = (int)$array['isBooked'];
			$chairName = $array['chairNumber'];
			if ($isBooked) {
				$message[] = sprintf ("Sorry, aber der Korb %s wurde in der Zwischenzeit bereits gebucht.", $chairName);

			}
			$chairNames[] = $chairName;

		}

		reset ($lines);

		#endregion

		if (!empty($message)) {
			$output = "";
			foreach ($message as $item) {
				$output .= "- " . $item . "<br>";
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

		$response = $API->PostContact ($Contact);
		$array    = json_decode ($response, true);

		$contactId = $array['contact_id'] ?: 0;
		if (empty($contactId)) {
			throw new ValidationException("Keine Kontakt-ID als Response zurückbekommen. Response:" . $response);
		}

		#endregion

		#region Create SalesOrderHeader

		$response = $API->PostSalesOrderHeader ($contactId, $comment, $referrerId);
		$array    = json_decode ($response, true);

		$salesHeaderId = $array['sales_header_id'] ?: 0;
		if (empty($salesHeaderId)) {
			throw new ValidationException("Keine SalesHeader-ID als Response zurückbekommen");
		}

		$_SESSION['salesHeaderId'] = $salesHeaderId;

		#endregion

		#region Create SalesOrderLines

		$createdSalesLines = [];
		$i                 = 0;
		foreach ($lines as $line) {

			$quantity  = 1;
			$unitPrice = $line['unitPrice'] ?? 0;
			$dateFrom  = $line['dateFrom'] ?? 0;
			$dateTo    = $line['dateTo'] ?? 0;
			$id        = $line['id'] ?: 0;
			if (empty($unitPrice)) {
				throw new ValidationException("Kein Preis übergeben");
			}

			$response = $API->PostSalesOrderLine ($salesHeaderId, $id, $quantity, OBJECT_TYPE_BEACH_CHAIR, $dateFrom, $dateTo);
			$array    = json_decode ($response, true);

			$salesLineId = $array['sales_line_id'] ?: 0;
			if (empty($salesLineId)) {
				throw new ValidationException("Keine SalesLine-ID als Response zurückbekommen." . $response);
			}

			$totalAmount    += $unitPrice;
			$netAmount      = $unitPrice / 1.19;
			$totalNetAmount += $netAmount;
			$taxamount      = $unitPrice - $netAmount;
			$totalTaxAmount += $taxamount;

			$createdSalesLines[] = [
				'taxPercent'  => $tax,
				'taxAmount'   => $unitPrice - ($unitPrice / (1 + ($tax / 100))),
				'netAmount'   => $unitPrice / (1 + ($tax / 100)),
				'grossAmount' => $unitPrice,
				'objectName'  => $chairNames[$i] ?? 'Strandkorb'
			];
			$i++;

		}

		#endregion

		#region Create Invoice

		$response = $API->PostSalesInvoice ($salesHeaderId);
		$array    = json_decode ($response, true);

		$salesInvoiceId = $array['sales_invoice_id'] ?: 0;
		if (empty($salesInvoiceId)) {
			throw new ValidationException("Keine SalesInvoice-ID als Response zurückbekommen. Response:" . $response);
		}

		$_SESSION['salesInvoiceId'] = $salesInvoiceId;

		//Send Invoice via PDF
		$API->SendInvoice ($salesHeaderId);

		#endregion

		//Redirect?
		$responseArray['successPage'] = $Settings->row->successPage ?: '';

		#region PAYPAL

		if ($paymentMethodId == PAYMENT_METHOD_PAYPAL) {

			$usePayPal = $Settings->row->payPal == 1;
			$isSandBox = $Settings->row->payPalSandbox === 1;

			if ($Settings->row->payPal != 1) {
				throw new ValidationException("Sie haben PayPal ausgewählt, aber dies ist eigentlich gar nicht eingeschaltet");
			}

			//Make PayPal Request

			$PayPal = new Process($Settings->row->payPalClientId, $Settings->row->payPalClientSecret, $Settings->row->payPalSandbox);

			$OrderHeader               = new Header();
			$OrderHeader->referenceId  = $salesHeaderId;
			$OrderHeader->currencyCode = 'EUR';

			$Contact = new PayPalContact();
			$Contact->setFirstName ($firstName);
			$Contact->setLastName ($lastName);
			$Contact->setEmail ($email);
			$Contact->setMobile ($tel);
			$Contact->setPostCode ($postCode);
			$Contact->setCity ($city);
			$Contact->setStreet ($street);
			$Contact->setNumber ($number);
			$Contact->setAddress ($address ?? '');

			$items = [];
			foreach ($createdSalesLines as $item) {

				$PayPalItem              = new Line();
				$PayPalItem->name        = $item['objectName'] ?? 'Strandkorb';
				$PayPalItem->quantity    = 1; //IMPORTANT!!!!! We have to change this to the actual quantity
				$PayPalItem->unitPrice   = $item['netAmount'] ?? 0;
				$PayPalItem->taxPercent  = $item['taxPercent'] ?? 0;
				$PayPalItem->description = $item['description'] ?? 'Keine Beschreibung....';

				$items[] = $PayPalItem;

			}

			$PayPal->CreateOrder ($OrderHeader, $Contact, $items, $Settings->row->successPage, $Settings->row->cancelPage ?? '');

			//if all went well we will get back a confirmation url
			$responseArray['confirmationUrl'] = $PayPal->confirmationUrl ?? '';
			$responseArray['debug']           = $PayPal->debug;

		}

		#endregion

	}

	if ($method == "GenerateShortCode") {

		$formType = $_POST['formType'] ?? '';

		$agbsLink    = $Settings->row->agbLink;
		$dsgvoLink   = $Settings->row->dsgvoLink;
		$successPage = $Settings->row->successPage;

		if (empty($formType)) {
			throw new Exception("Eine Formular-Art muss schon gewählt werden");
		}

		$responseArray['data'] = '[generate_vabs_form type="' . $formType . '" agb="' . $Settings->row->agbLink . '" datenschutz="' . $Settings->row->dsgvoLink . '" successPage="' . $Settings->row->successPage . '"]';

	}

} catch (Exception $e) {

	$message = $e->getMessage ();
	$responseArray['error'] = $message;

} // ENDE try {

echo json_encode ($responseArray);

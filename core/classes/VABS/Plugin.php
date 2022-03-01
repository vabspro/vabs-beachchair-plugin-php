<?php

namespace VABS;

use DD\Mailer\Mailer;
use Exception;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalHttp\HttpException;

class Plugin
{

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $pluginName The string used to uniquely identify this plugin.
	 */
	public string $pluginName;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	public string $version;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var Settings
	 */
	private Settings $Settings;

	private string $jquery = 'jquery';
	//private string $jquery = 'jquery-360';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct () {

		$this->pluginName = 'vabs-wp-plugin';
		$this->version    = '1.0.0';
		$this->Settings = new Settings();

	}

	#region ACTIVATE/DEACTIVATE

	public static function Activate () {

	}

	public static function DeActivate () {

	}

	#endregion

	#region ENQUE/REGISTER

	#region STYLES
	public function StylesAll () {

		wp_enqueue_style ('bootstrap', "https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css", [], false);

	}

	public function StylesFrontPage () {

		wp_enqueue_style ('flatpickr', VABS_PLUGIN_PATH.'/assets/css/flatpickr.css', [], false);
		wp_enqueue_style ('public', VABS_PLUGIN_PATH.'/assets/css/public.css', [], false);
		wp_enqueue_style ('leaflet', VABS_PLUGIN_PATH.'/assets/css/leaflet.css', [], false);
		wp_enqueue_style ('select2', VABS_PLUGIN_PATH.'/assets/css/select2.css', [], false);

	}

	public function StylesAdmin () {

		wp_enqueue_style ('admin', VABS_PLUGIN_PATH.'/assets/css/admin.css', ['bootstrap'], false);

	}
	#endregion

	#region SCRIPTS
	public function ScriptsAll () {

		wp_enqueue_script ('library', VABS_PLUGIN_PATH.'/assets/js/library.js', $this->jquery, '1.0.0', true);
		wp_enqueue_script ('bootstrap', "https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js", $this->jquery, '1.0.0', true);

	}

	public function ScriptsFrontPage () {

		wp_enqueue_script ('loadingOverlay', VABS_PLUGIN_PATH."/assets/js/loadingOverlay.js", $this->jquery, '1.0.0', true);
		wp_enqueue_script ('leaflet', VABS_PLUGIN_PATH."/assets/js/leaflet.js", $this->jquery, '1.0.0', true);
		wp_enqueue_script ('flatpickr', VABS_PLUGIN_PATH."/assets/js/flatpickr.js", $this->jquery, '1.0.0', true);
		wp_enqueue_script ('flatpickr-de', VABS_PLUGIN_PATH."/assets/js/flatpickr-de.js", [
				$this->jquery,
				'flatpickr'
		], '1.0.0', true);
		wp_enqueue_script ('flatpickr-rangePlugin', VABS_PLUGIN_PATH."/assets/js/flatpickr-rangePlugin.js", [
				$this->jquery,
				'flatpickr'
		], '1.0.0', true);
		wp_enqueue_script ('select2', VABS_PLUGIN_PATH."/assets/js/select2.js", $this->jquery, '1.0.0', true);
		wp_enqueue_script ('script', VABS_PLUGIN_PATH.'/assets/js/script.js', [
				$this->jquery
		], '1.0.0', true);

	}

	public function ScriptsAdmin () {

		wp_enqueue_script ('admin', VABS_PLUGIN_PATH.'/assets/js/admin.js', $this->jquery, '1.0.0', true);

	}
	#endregion

	#region Register and Enqueue
	public function RegisterScripts () {

		//For all
		add_action ('init', [
			$this,
			'StylesAll'
		]);
		add_action ('init', [
			$this,
			'ScriptsAll'
		]);

		//Frontend
		add_action ('wp_enqueue_scripts', [
			$this,
			'StylesFrontPage'
		]);
		add_action ('wp_enqueue_scripts', [
			$this,
			'ScriptsFrontPage'
		]);

		//Admin Area
		add_action ('admin_enqueue_scripts', [
			$this,
			'StylesAdmin'
		]);
		add_action ('admin_enqueue_scripts', [
			$this,
			'ScriptsAdmin'
		]);

	}
	#endregion

	#endregion

	#region CREATE MENU

	public function AddAdminMenu () {

		global $submenu;

		add_menu_page ('VABS Plugin Administration', 'VABS', 'manage_options', 'vabs', [$this, 'ShowSettingsForm'], 'dashicons-cloud');
		add_submenu_page ('vabs', 'VABS Settings', 'Settings', 'manage_options', 'vabs', [$this, 'ShowSettingsForm'],1);
		add_submenu_page ('vabs', 'VABS Generate Form-ShortCode', 'Shortcode', 'manage_options', 'shortcode', [$this, 'ShowShortCodeForm'],2);

		$submenu['members'][0][0] = 'Settings';

	}

	#endregion

	public function GetPluginName (): string {

		return $this->pluginName;
	}

	public function GetVersion (): string {

		return $this->version;
	}

	#region ADMIN AREA

	public function ShowSettingsForm () {

		$settings = $this->Settings->Load ();
		$this->HTMLHeader ();

		?>
		<h1>Settings</h1>
		<h5>Version <span class="badge-primary" style="color: red">1.3</span></h5>
		<form action="" class="form-inline" method="POST">
			<div class="form">
				<h3>API</h3>
				<div class="form-group row">
					<label for="api_url" class="col-sm-2 col-form-label">API-URL:</label>
					<div class="col-sm-10">
						<input type="text" class="border bg-light col-6 form-control" id="apiURL" value="<?php echo $settings['apiURL'] ?? ''; ?>">
					</div>
				</div>
				<div class="form-group row">
					<label for="api_token" class="col-sm-2 col-form-label">API TOKEN:</label>
					<div class="col-sm-4">
						<input type="text" class="border bg-light form-control" id="apiToken" value="<?php echo $settings['apiToken'] ?? ''; ?>">
					</div>
					<label for="client_id" class="col-sm-2 col-form-label">API CLIENT ID:</label>
					<div class="col-sm-4">
						<input type="text" class="border bg-light form-control" id="apiClientId" value="<?php echo $settings['apiClientId'] ?? ''; ?>">
					</div>
				</div>
				<h3>PayPal</h3>
				<div class="form-group row">
					<label for="payPal" class="col-sm-2 col-form-label">Einschalten</label>
					<div class="col-sm-4">
						<input type="checkbox" class="border bg-light form-control" id="payPal" value="1" <?php echo $settings['payPal'] == 1 ? "checked" : ""; ?>>
					</div>
					<label for="payPal" class="col-sm-2 col-form-label">Benutze PayPal SANDBOX</label>
					<div class="col-sm-4">
						<input type="checkbox" class="border bg-light form-control" id="payPalSandbox" value="1" <?php echo $settings['payPalSandbox'] == 1 ? "checked" : ""; ?>>
					</div>
					<label for="payPalClientId" class="col-sm-2 col-form-label">PayPal CLIENT ID:</label>
					<div class="col-sm-4">
						<input type="text" class="border bg-light form-control" id="payPalClientId" value="<?php echo $settings['payPalClientId'] ?? ''; ?>">
					</div>
					<label for="payPalClientSecret" class="col-sm-2 col-form-label">PayPal CLIENT SECRET:</label>
					<div class="col-sm-4">
						<input type="password" class="border bg-light form-control" id="payPalClientSecret" value="<?php echo $settings['payPalClientSecret'] ?? ''; ?>">
					</div>
				</div>
				<h3>Karte</h3>
				<div class="alert alert-info">
					Koordinaten kannst Du <a href="https://www.latlong.net/" target="_blank">hier</a> abrufen und dann unten im Formular eingeben
				</div>
				<div class="form-group row">
					<label for="latCenter" class="col-sm-2 col-form-label">Latitude:</label>
					<div class="col-sm-4">
						<input type="text" class="border bg-light form-control" id="latCenter" value="<?php echo $settings['latCenter'] ?? ''; ?>">
					</div>

					<label for="lonCenter" class="col-sm-2 col-form-label">Longitude:</label>
					<div class="col-sm-4">
						<input type="text" class="border bg-light form-control" id="lonCenter" value="<?php echo $settings['lonCenter'] ?? ''; ?>">
					</div>

					<label for="zoom" class="col-sm-2 col-form-label">Zoom:</label>
					<div class="col-sm-4">
						<input type="number" class="border bg-light form-control" id="zoom" value="<?php echo $settings['zoom'] ?: 15; ?>">
					</div>
				</div>
				<h3>Links</h3>
				<div class="form-group row">
					<label for="dsgvo" class="col-sm-2 col-form-label">DSGVO Link:</label>
					<div class="col-sm-4">
						<input type="text" class="border bg-light form-control" id="dsgvoLink" value="<?php echo $settings['dsgvoLink'] ?? ''; ?>">
					</div>

					<label for="agb" class="col-sm-2 col-form-label">AGB Link:</label>
					<div class="col-sm-4">
						<input type="text" class="border bg-light form-control" id="agbLink" value="<?php echo $settings['agbLink'] ?? ''; ?>">
					</div>
				</div>
				<h3>Zusatztexte</h3>
				<div class="form-group row">
					<label for="dsgvo" class="col-sm-2 col-form-label">Text vor Buchung:</label>
					<div class="col-sm-10">
						<input type="text" class="border bg-light form-control" id="textBeforeBooking" value="<?php echo $settings['textBeforeBooking'] ?? ''; ?>">
					</div>
				</div>
				<h3>Referrer URL (ID)</h3>
				<input type="hidden" id="settingsReferrerId" value="<?php echo $settings['referrerId'] ? (int)$settings['referrerId'] : 0; ?>">
				<div class="form-group row">
					<label for="referrerId" class="col-sm-2 col-form-label">Referrer:</label>
					<div class="col-sm-6">
						<select class="border bg-light col-3 form-control" id="referrerId"><!-- via AJAX --></select>
					</div>
				</div>
				<div class="form-group row">
					<label for="redirectLink" class="col-sm-2 col-form-label">Success Page:</label>
					<div class="col-sm-6">
						<input type="text" class="border bg-light form-control" id="redirectLink" value="<?php echo $settings['redirectLink'] ?? ''; ?>">
					</div>
				</div>
				<div class="form-row align-items-left">
					<div class="col-sm-6 my-2">
						<button type="button" class="button button-danger" id="btnSave">Save</button>

						<button type="button" class="button button-primary" id="btnLoadAGBS">Load AGBS-Link from VABS</button>

						<button type="button" class="button button-primary" id="btnLoadDSGVO">Load DSGVO-Link from VABS</button>
					</div>
					<span class="loading"></span>
				</div>
				<div class="form-group row">
					<p id="response"></p>
				</div>
				<div class="alert" id="backendErrorMessage" style="display: none;"></div>
			</div>
		</form>
		<?php
		$this->HTMLFooter ();

	}

	public function ShowShortCodeForm () {

		$this->HTMLHeader ();
		?>
		<div class="shortcodegenerator">
			<h2>Shortcode generieren</h2>

			<div class="shortcodegenerator-wrapper">
				<div class="shortcodegenerator-row">
					<p>Welche Art von Formular soll generiert werden?</p>
					<label for="formType1"> <input id="formType1" type="radio" name="formType" value='beachchair_booking' /> Strandkorb Buchungsformular</label>
				</div>

				<div class="shortcodegenerator-row">
					<button type="button" id="btnGenerateShortCode" class="button button-primary">Shortcode generieren</button>
				</div>

				<div class="shortcodegenerator-row">
					<strong>Kopiere den generierten ShortCode und füge ihn auf der Seite als ShortCode Widget ein, wo das entsprechende Formular erscheinen soll.</strong> <input type="text" id="shortCodeOutput" style="display: block; width: 100%; margin-top: .4rem" />
				</div>

			</div>

		</div>

		<?php
		$this->HTMLFooter ();

	}

	public function GenerateVABSForm ($attributes): string {

		$content = '';

		//$settings = $this->Settings->Load ();

		if ($attributes['type'] == 'beachchair_booking') {

			$token = $_GET['token'] ?: '';
			$PayerID = $_GET['PayerID'] ?: '';
			$salesHeaderId = $_SESSION['salesHeaderId'] ?: 0;
			$salesInvoiceId = $_SESSION['salesInvoiceId'] ?: 0;

			$_SESSION['payPalSuccessRedirectLink'] = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

			$Settings = new Settings();
			$settings = $Settings->Load ();
			$isSandBox = (int)$settings['payPalSandbox'] === 1;

			if(!empty($token) && !empty($PayerID)){

				if ($isSandBox) {
					$environment = new SandboxEnvironment($settings['payPalClientId'], $settings['payPalClientSecret']);
				} else {
					$environment = new ProductionEnvironment($settings['payPalClientId'], $settings['payPalClientSecret']);
				}

				try {

					$client = new PayPalHttpClient($environment);
					$request = new OrdersCaptureRequest($token);
					$request->prefer ('return=representation');

					// Call API with your client and get a response for your call
					$response = $client->execute ($request);

					$captureId = $response->result->purchase_units[0]->payments->captures[0]->id ?? '';

					$request  = new OrdersGetRequest($token);
					$response = $client->execute ($request);

					/*$response = $client->execute($request);*/
					try {
						$var = print_r ($response, true);
						Mailer::SendAdminMail ("File: ".__FILE__."<br>Place: Capture<br>Response: ".$var, Mailer::EMAIL_SUBJECT_DEBUG);
					} catch (Exception $e) {

						Mailer::SendAdminMail ("File: ".__FILE__."<br>Error: ".$e->getMessage (), Mailer::EMAIL_SUBJECT_EXCEPTION);
					}

				} catch (HttpException|IOException $e) {

					$statusCode = $e->statusCode;
					if ($statusCode == 422) {

						try {

							if (empty($client)) {
								$client = new PayPalHttpClient($environment);
							}
							$request  = new OrdersGetRequest($token);
							$response = $client->execute ($request);

							try {
								$var = print_r ($response, true);
								Mailer::SendAdminMail ("File: ".__FILE__."<br>Place: Already Captured<br>Response: ".$var, Mailer::EMAIL_SUBJECT_DEBUG);
							} catch (Exception $e) {
								Log::Log ($e->getMessage ());
							}

							$captureId = $response->result->purchase_units[0]->payments->captures[0]->id ?? '';

						} catch (Exception $e) {

							echo $e->getMessage ();

						}

					}

					//$captured = true;

				}



				$Settings = new Settings();
				$settings = $Settings->Load ();
				$API = new API();
				$API->PostPayment ($salesInvoiceId, $salesHeaderId, 4, $token, $PayerID, $captureId);
				$API->PutUpdateSalesInvoice ($salesHeaderId, $salesInvoiceId, 5);

				echo "<script type=\"text/javascript\">
    			window.location = '".$settings['redirectLink']."';
				</script>";

			}

			ob_start ();
			?>
			<div class="alert alert-success" role="alert" id="successMessage" style="display:none;">
				This is a success alert—check it out!
			</div>
			<div id="bookingContainer">

				<form id="form" class="row gx-3 gy-2 align-items-center">

					<div class="container" id="dateSelectContainer">
						<h5>Wähle einen oder mehrere Tag(e)</h5>
						<h3>Anreisetag anklicken und Abreisetag anklicken</h3>
						<input class="flatpickr flatpickr-input dateFrom p-3 border bg-light" placeholder="DD.MM.JJJJ" value="" type="text" readonly="readonly">
						<button type="button" id="btnRefresh" class="button button-success" style="margin-top: 1rem;">Laden</button>
						<div id="errorMessage">
							<!-- via booking Script -->
						</div>
					</div>

					<div class="container normal" id="locationSelectContainerNormal" style="display: none">
						<h5>Strandabschnitt wählen</h5>
						<p class="hint">Wählen Sie hier im Auswahlfeld einen Strandabschnitt oder klicken Sie einen in der Karte an!</p>
						<div class="locationSelect"><!-- via AJAX --></div>
					</div>

					<div class="container hopping" id="locationSelectContainerHopping" style="display: none">
						<h5>Strandabschnitt wählen</h5>
						<p class="hint">Wählen Sie hier im Auswahlfeld einen oder mehrere Strandabschnitte/Korbtypen, falls Sie nur bestimmte Abschnitte/Korbtypen buchen möchten. <b>Bitte bedenken Sie aber, dass es dann eventuell keinen Korb mehr über dem gesamten Zeitraum geben kann.</b></p>
						<div class="row g-3">
							<div class="col-sm-6">
								<div id="hoppingBeachLocationsSelectContainer"><!-- via AJAX --></div>
							</div>
							<div class="col-sm-6">
								<div id="hoppingBeachChairTypeSelectContainer"><!-- via AJAX --></div>
							</div>
						</div>
					</div>


					<div class="container normal" id="mapsContainer">
						<div id="leafLetMap"></div>
						<div id="flexMap">

							<div class="flexContainer">

								<div class="flexTopContainer">

									<h2 class="flexHeadline"><!-- via Ajax --></h2>
									<button class="flexBtnBack">X</button>

								</div>

								<div class="flexRowContainer">

									<div class="flexRows"><!-- via AJAX --></div>

								</div>

								<div id="chair-card" style="display:none;">
									<div class="chair-container">
										<button type="button" class="btn btn-secondary btnChairClose">X</button>
										<div class="chair-header" style="background-size: cover; background-repeat: no-repeat; background-position: center center;"></div>
										<div class="chair-body">
											<div>
												<strong style="display: block;">Strandkorb Nummer: <span id="chairCardName"></span></strong> <span style="display: block;">Modell: <span id="chairCardType"></span></span>
											</div>
											<button type="button" id="chairCardBtnAddToShoppingCart" class="btn btn-success" data-id="">Zur Buchung hinzufügen</button>
											<button type="button" id="chairCardBtnRemoveFromShoppingCart" class="btn btn-primary" data-id="">Aus Buchung entfernen</button>
										</div>
									</div>
								</div>

							</div>

						</div>
					</div>

					<div class="container" id="personalDataContainer" style="display: none">

						<h5>Vervollständige die persönlichen Daten</h5>

							<div class="row g-3">
								<div class="col-sm-3">
									<input name="firstName" class="border bg-light" type="text"placeholder="Vorname" required>
								</div>
								<div class="col-sm-3">
									<input name="lastName" class="border bg-light" type="text" placeholder="Nachname" required>
								</div>
								<div class="col-sm-3">
									<input name="email" class="border bg-light" type="email" placeholder="Emailadresse" data-parsley-trigger="change" required>
								</div>
								<div class="col-sm-3">
									<input name="tel" class="border bg-light" type="text" placeholder="Telefonnummer">
								</div>
							</div>
							<div class="row g-3">
								<div class="col-sm-2">
									<input name="postCode" class="border bg-light" type="text" placeholder="PLZ" required>
								</div>
								<div class="col-sm-4">
									<input name="city" class="border bg-light" type="text" placeholder="Ort" required>
								</div>
								<div class="col-sm-4">
									<input name="street" class="border bg-light" type="text" placeholder="Strasse" required>
								</div>
								<div class="col-sm-2">
									<input name="number" class="border bg-light" type="text" placeholder="Hausnummer" required>
								</div>
							</div>
							<div class="row g-3">
								<div class="col-12">
									<textarea name="comment" class="col-12 border bg-light" placeholder="Ihre Bemerkung" rows="1"></textarea>
								</div>
							</div>
					</div>

					<div class="container" id="shoppingCartContainerWrapper" style="display: none">
						<h5>Zusammenfassung &amp; Buchung</h5>
						<div id="shoppingCartContainer">
							<div id="shoppingCartHeader">
								<strong>Zeitraum: <span id="shoppingCartDateTimeRange"></span></strong>

							</div>
							<div id="shoppingCartList">
								<!-- via AJAX -->
							</div>
							<input type="checkbox" required name="confirm" value="1" />Hiermit bestätige ich die<a href="https://vabs-demo-beach.drechsler-development.de/" target="blank" style="margin: 0px 4px; text-decoration: underline;">AGB</a>und<a href="https://vabs-demo-beach.drechsler-development.de/" target="blank" style="margin: 0px 4px; text-decoration: underline;">Datenschutzvereinbarung</a>gelesen und verstanden zu haben und stimme diesen zu.<br>

							<div id="paymentSection">
								<div class="form-check-inline no-padding-left">Ich bezahle via:</div>
								<?php
								$Settings = new Settings();
								$settings = $Settings->Load ();
								if($settings['payPal'] == 1){
								?>

									<div class="form-check form-check-inline">
										<input class="form-check-input" type="radio" name="paymentMethodId" id="radioInvoice" value="1"> <label class="form-check-label" for="radioInvoice">Rechnung</label>
									</div>
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="radio" name="paymentMethodId" id="radioPayPal" value="2"> <label class="form-check-label" for="radioPayPal">PayPal</label>
									</div>

								<?php
								}else{
								?>
									Ich bezahle via:
									<div class="form-check form-check-inline">
										<input class="form-check-input" type="radio" name="paymentMethodId" id="radioInvoice" value="1"> <label class="form-check-label" for="radioInvoice">Rechnung</label>
									</div>

								<?php
								}
								?>
							</div>
							<?php
							if (!empty($settings['textBeforeBooking'])) {
								?>
								<div class="alert alert-warning" style="margin-top: 15px;"><strong>Bitte beachten Sie: </strong> <?php echo strip_tags ($settings['textBeforeBooking']); ?></div>
								<?php
							}
							?>
							<button type="button" id="btnOrderNow" class="button button-primary" style="margin-top: 1rem;">Jetzt kostenpflichtig bestellen!</button>
							<div class="alert" id="backendErrorMessage" style="display: none; margin-top: 15px"></div>
						</div>

					</div>

				</form>

			</div>

			<?php
			$content = ob_get_contents ();
			ob_end_clean ();

		}

		return $content;

	}

	#endregion

	public function HTMLHeader() {
	?>
	<div id="vabs-container">

		<div id="vabs-container-header">
			<div id="vabs-container-logo">
				<img src="<?php echo PLUGIN_FOLDER_NAME_WEB; ?>/assets/img/logo.png" alt="logo">
			</div>
			<div id="vabs-container-body">
				<!-- START MODULE CONTENT -->
	<?php
	}

	public function HTMLFooter(){

	?>
				<!-- END MODULE CONTENT -->
			</div>
		</div>

	</div>
	<?php
	}

	public function Run () {

		//Add Menu
		add_action ('admin_menu', [
				$this,
				'AddAdminMenu'
		]);

		//Register enqueued scripts
		$this->RegisterScripts ();

		register_activation_hook (__FILE__, [
				$this,
				'Activate'
		]);
		register_deactivation_hook (__FILE__, [
				$this,
				'DeActivate'
		]);

		//Add Shortcodes
		add_shortcode ('generate_vabs_form', [
				$this,
				'GenerateVABSForm'
		]);

	}

}

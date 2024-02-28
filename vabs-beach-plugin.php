<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://drechsler-development.de
 * @since             1.0.0
 * @package           vabs-wp-plugin
 *
 * @wordpress-plugin
 * Plugin Name:       VABS Form Generator
 * Plugin URI:        https://github.com/vabspro/vabs-beachchair-plugin-php
 * Description:       Provides forms to send data requests to the VABS API
 * Version:           2.0.4
 * Author:            Ronny Drechsler-Hildebrandt
 * Author URI:        https://drechsler-development.de
 * License:           MIT
 * License URI:       https://choosealicense.com/licenses/mit
 * Text Domain:       vabs-wp-plugin
 */

// If this file is called directly, abort.
if (!defined ('WPINC')) {
	die;
}

define ('VABS_PLUGIN_PATH', str_replace (ABSPATH, '/', __DIR__));

require_once 'vendor/autoload.php';
require_once 'config.php';

use VABS\Session;
use DD\Exceptions\ValidationException;
use DD\Helper\Date;
use DD\PayPal\Exception\AlreadyCapturedException;
use DD\PayPal\Process;
use DD\WordPress\VABS\VABSAPIWPSettings;
use VABS\API;

if (!defined ('ABSPATH')) {
	exit;
}

error_reporting (E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED ^ E_USER_DEPRECATED);

if (defined ('SHOW_ERRORS') && SHOW_ERRORS) {
	error_reporting (E_ALL);
	ini_set ('display_errors', 1);
	set_error_handler (['\DD\ErrorHandler', 'SetErrorHandler']);
	set_exception_handler (['\DD\ErrorHandler', 'SetExceptionHandler']);
}

if ($_GET['refrenceCode'] ?? 0) {
	Session::SetReferenceCode ((int)$_GET['refrenceCode']);
}

class VABSBeachPlugin {

	private string $jquery = 'jquery';
	//private string $jquery = 'jquery-360';

	private int $randomNumber;

	const SHORTCODE_BOOKING_FORM = 'vabs_booking_form';
	const SHORTCODE_SUCCESS_PAGE = 'vabs_success_page';
	const SHORTCODE_CANCEL_PAGE = 'vabs_cancel_page';

	private VABSAPIWPSettings $Settings;

	/**
	 * @throws ValidationException
	 */
	public function __construct () {

		$this->randomNumber = rand (1, 1000000);

		$this->InitHooks ();

		$this->LoadSettings ();

	}

	/**
	 * @throws ValidationException
	 * @throws Exception
	 */
	public function LoadSettings (): void {

		if (!defined ("DB_PASSWORD")) {
			throw new Exception("Kein Password für die Datanbank angegeben");
		} else {
			if (!defined ("DB_PASS")) {
				define ('DB_PASS', DB_PASSWORD);
			}
		}

		$this->Settings = new VABSAPIWPSettings(SETTINGS_TABLE);
		$this->Settings->Load ();
	}

	public static function ActivatePlugin (): void {

	}

	public function StylesAll (): void {

		wp_enqueue_style ('bootstrap', "https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css");
		wp_enqueue_style ('flatpickr', plugins_url ('/assets/css/flatpickr.css', __FILE__));



	}

	public function StylesFrontPage (): void {

		wp_enqueue_style ('public', plugins_url ('/assets/css/public.css', __FILE__));
		wp_enqueue_style ('leaflet', plugins_url ('/assets/css/leaflet.css', __FILE__));
		wp_enqueue_style ('select2', plugins_url ('/assets/css/select2.css', __FILE__));

	}

	public function StylesAdmin (): void {

		wp_enqueue_style ('admin', plugins_url ('/assets/css/admin.css', __FILE__));

	}

	public function ScriptsAll (): void {

		wp_enqueue_script ('bootstrap', "https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js", [], $this->randomNumber, true);
		wp_enqueue_script ('flatpickr', plugins_url ("/assets/js/flatpickr.js", __FILE__), [], $this->randomNumber, true);
		wp_enqueue_script ('flatpickr-de', plugins_url ("/assets/js/flatpickr-de.js", __FILE__), [],$this->randomNumber, true);
		wp_enqueue_script ('moment', plugins_url ('/assets/js/moment.js', __FILE__), [], $this->randomNumber, true);
	}

	public function ScriptsFrontPage (): void {

		wp_enqueue_script ('loadingOverlay', plugins_url ("/assets/js/loadingOverlay.js", __FILE__), [], $this->randomNumber, true);
		wp_enqueue_script ('leaflet', plugins_url ("/assets/js/leaflet.js", __FILE__), [], $this->randomNumber, true);
		wp_enqueue_script ('flatpickr-rangePlugin', plugins_url ("/assets/js/flatpickr-rangePlugin.js", __FILE__), [],$this->randomNumber, true);
		wp_enqueue_script ('select2', plugins_url ("/assets/js/select2.js", __FILE__), [], $this->randomNumber, true);
		wp_enqueue_script ('script', plugins_url ('/assets/js/script.js', __FILE__), [],$this->randomNumber, true);

	}

	public function ScriptsAdmin (): void {

		wp_enqueue_script ('admin', plugins_url ('/assets/js/admin.js', __FILE__), [], $this->randomNumber, true);

	}

	public function RegisterScripts (): void {

		//For all
		add_action ('wp_enqueue_scripts', [
			$this,
			'StylesAll'
		]);
		add_action ('wp_enqueue_scripts', [
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

		//For all
		add_action ('admin_enqueue_scripts', [
			$this,
			'StylesAll'
		]);
		add_action ('admin_enqueue_scripts', [
			$this,
			'ScriptsAll'
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

	public function AddAdminMenuPoint (): void {

		global $submenu;

		add_menu_page ('VABS Plugin Administration', 'VABS', 'manage_options', 'vabs', [$this, 'ShowSettingsForm'], 'dashicons-cloud');
		add_submenu_page ('vabs', 'VABS Settings', 'Settings', 'manage_options', 'vabs', [$this, 'ShowSettingsForm'], 1);
		add_submenu_page ('vabs', 'VABS Generate Form-ShortCode', 'Shortcode', 'manage_options', 'shortcode', [$this, 'ShowShortCodeForm'], 2);

		$submenu['members'][0][0] = 'Settings';

	}

	/**
	 * @return void
	 */
	public function InitHooks (): void {

		// Hook, to initialize the plugin
		add_action ('init', [$this, 'InitVBP']);

	}

	/**
	 * @return void
	 */
	public function InitVBP (): void {

		add_action ('admin_menu', [
			$this,
			'AddAdminMenuPoint'
		]);

		add_shortcode (self::SHORTCODE_BOOKING_FORM, [
			$this,
			'ShowBookingForm'
		]);

		add_shortcode (self::SHORTCODE_SUCCESS_PAGE, [
			$this,
			'HandleSuccessMessage'
		]);

		add_shortcode (self::SHORTCODE_CANCEL_PAGE, [
			$this,
			'HandleCancelMessage'
		]);

		$this->RegisterScripts ();
	}

	public function ShowSettingsForm (): void {

		try {

			$Settings = new VABSAPIWPSettings(SETTINGS_TABLE);
			$Settings->Load ();
			$this->HTMLHeader ();

			?>
			<h1>Settings</h1>			<h5>Version <span class="badge-primary" style="color: red"><?php
					echo $Settings->row->versionNumber; ?></span></h5>
			<form action="" class="form-inline" method="POST">

				<div class="form">

					<h3>API</h3>
					<div class="form-group row">

						<label for="api_url" class="col-sm-2 col-form-label">API-URL:</label>
						<div class="col-sm-10">
							<input type="text" class="border bg-light col-6 form-control" id="apiURL" value="<?php
							echo $Settings->row->apiURL ?? ''; ?>">
						</div>

					</div>
					<div class="form-group row">

						<label for="api_token" class="col-sm-2 col-form-label">API TOKEN:</label>
						<div class="col-sm-4">
							<input type="text" class="border bg-light form-control" id="apiToken" value="<?php
							echo $Settings->row->apiToken ?? ''; ?>">
						</div>

						<label for="client_id" class="col-sm-2 col-form-label">API CLIENT ID:</label>
						<div class="col-sm-4">
							<input type="text" class="border bg-light form-control" id="apiClientId" value="<?php
							echo $Settings->row->apiClientId ?? ''; ?>">
						</div>

					</div>

					<h3>Buchungsformular sperren</h3>
					<div class="form-group row">

						<div class="col-sm-10">
							<label for="blockBookingEnabled" class="col-sm-2 col-form-label">Sperren</label>
							<div class="col-sm-4">
								<input type="checkbox" class="border bg-light form-control" id="blockBookingEnabled" value="1" <?php
								echo $Settings->row->blockBookingEnabled == 1 ? "checked" : ""; ?>>
							</div>
						</div>

					</div>

					<div class="form-group row">

						<label for="blockBookingFrom" class="col-sm-2 col-form-label">Von:</label>
						<div class="col-sm-4">
							<input type="text" class="border bg-light form-control" id="blockBookingFrom" value="<?php
							echo $Settings->row->blockBookingFrom ? Date::FormatDateToFormat ($Settings->row->blockBookingFrom) : ''; ?>">
						</div>

						<label for="blockBookingTo" class="col-sm-2 col-form-label">Bis:</label>
						<div class="col-sm-4">
							<input type="text" class="border bg-light form-control" id="blockBookingTo" value="<?php
							echo $Settings->row->blockBookingTo ? Date::FormatDateToFormat ($Settings->row->blockBookingTo) : ''; ?>">
						</div>

						<label for="blockBookingText" class="col-sm-2 col-form-label">Grund/Text auf der Webseite:</label>
						<div class="col-sm-10">
							<textarea class="border bg-light form-control" id="blockBookingText"><?php
								echo $Settings->row->blockBookingText ?? ''; ?></textarea>
						</div>

					</div>

					<h3>Kalender Startdatum</h3>
					<div class="form-group row">

						<label for="additionalCalendarStartDays" class="col-sm-2 col-form-label">Anzahl +Tage:</label>
						<div class="col-sm-2">
							<input type="number" class="border bg-light form-control" id="additionalCalendarStartDays" value="<?php
							echo $Settings->row->additionalCalendarStartDays ?? 0; ?>">
						</div>

					</div>

					<div class="form-group row">

						<label for="additionalCalendarStartDaysText" class="col-sm-2 col-form-label">Text auf der Webseite:</label>
						<div class="col-sm-10">
							<textarea class="border bg-light form-control" id="additionalCalendarStartDaysText"><?php
								echo $Settings->row->additionalCalendarStartDaysText ?? ''; ?></textarea>
						</div>

					</div>

					<h3>PayPal (<?php
						echo $Settings->row->payPalSandbox == 1 ? "TEST" : "PROD"; ?>)</h3>
					<div class="form-group row">

						<label for="payPal" class="col-sm-2 col-form-label">Einschalten</label>
						<div class="col-sm-4">
							<input type="checkbox" class="border bg-light form-control" id="payPal" value="1" <?php
							echo $Settings->row->payPal == 1 ? "checked" : ""; ?>>
						</div>

						<label for="payPal" class="col-sm-2 col-form-label">Benutze PayPal SANDBOX</label>
						<div class="col-sm-4">
							<input type="checkbox" class="border bg-light form-control" id="payPalSandbox" value="1" <?php
							echo $Settings->row->payPalSandbox == 1 ? "checked" : ""; ?>>
						</div>

						<label for="payPalClientId" class="col-sm-2 col-form-label">PayPal CLIENT ID:</label>
						<div class="col-sm-4">
							<input type="text" class="border bg-light form-control" id="payPalClientId" value="<?php
							echo $Settings->row->payPalClientId ?? ''; ?>">
						</div>

						<label for="payPalClientSecret" class="col-sm-2 col-form-label">PayPal CLIENT SECRET:</label>
						<div class="col-sm-4">
							<input type="password" class="border bg-light form-control" id="payPalClientSecret" value="<?php
							echo $Settings->row->payPalClientSecret ?? ''; ?>">
						</div>
					</div>
					<h3>Links</h3>
					<div class="form-group row">

						<label for="dsgvo" class="col-sm-2 col-form-label">DSGVO Link:</label>
						<div class="col-sm-4">
							<input type="text" class="border bg-light form-control" id="dsgvoLink" value="<?php
							echo $Settings->row->dsgvoLink ?? ''; ?>">
						</div>

						<label for="agb" class="col-sm-2 col-form-label">AGB Link:</label>
						<div class="col-sm-4">
							<input type="text" class="border bg-light form-control" id="agbLink" value="<?php
							echo $Settings->row->agbLink ?? ''; ?>">
						</div>

					</div>
					<div class="form-group row">

						<label for="successPage" class="col-sm-2 col-form-label">Erfolgsseite:</label>
						<div class="col-sm-10">
							<input type="text" class="border bg-light form-control" id="successPage" value="<?php
							echo $Settings->row->successPage ?? ''; ?>">
						</div>

					</div>
					<div class="form-group row">

						<label for="cancelPage" class="col-sm-2 col-form-label">Abbruchseite:</label>
						<div class="col-sm-10">
							<input type="text" class="border bg-light form-control" id="cancelPage" value="<?php
							echo $Settings->row->cancelPage ?? ''; ?>">
						</div>

					</div>
					<h3>Zusatztexte</h3>
					<div class="form-group row">

						<label for="dsgvo" class="col-sm-2 col-form-label">Text vor Buchung:</label>
						<div class="col-sm-10">
							<input type="text" class="border bg-light form-control" id="textBeforeBooking" value="<?php
							echo $Settings->row->textBeforeBooking ?? ''; ?>">
						</div>

					</div>
					<h3>Referrer URL (ID)</h3>
					<input type="hidden" id="settingsReferrerId" value="<?php
					echo $Settings->row->referrerId; ?>">
					<div class="form-group row">

						<label for="referrerId" class="col-sm-2 col-form-label">Referrer:</label>
						<div class="col-sm-6">
							<select class="border bg-light col-3 form-control" id="referrerId"><!-- via AJAX --></select>
						</div>

					</div>

					<div class="form-row align-items-left">
						<div class="col-sm-12 my-2">
							<button type="button" class="button button-danger" id="btnSave">Speichern</button>

							<button type="button" class="button button-primary" id="btnLoadAGBS">Load AGBS-Link from VABS</button>

							<button type="button" class="button button-primary" id="btnLoadDSGVO">Load DSGVO-Link from VABS</button>
						</div>
						<span class="loading"></span>
					</div>

					<div class="form-group row">
						<p id="response"></p>
					</div>

					<div class="alert" id="vabs__backendErrorMessage" style="display: none;"></div>

				</div>

			</form>
			<?php
		} catch (Exception $e) {
			echo $e->getMessage ();
		}

		$this->HTMLFooter ();

	}

	public function ShowShortCodeForm (): void {

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

	public function ShowBookingForm (): string {

		$this->RegisterScripts ();

        ob_start ();
        ?>
        <div class="vabs__container">
            <?php
            try {

                $Settings = new VABSAPIWPSettings(SETTINGS_TABLE);
                $Settings->Load ();

                $isBlocked = !empty($Settings->row->blockBookingEnabled);
                $today     = new DateTime();
                $from      = new DateTime($Settings->row->blockBookingFrom . " 00:00:00");
                $to        = new DateTime($Settings->row->blockBookingTo . " 23:59:59");
                $inRange   = $today >= $from && $today <= $to;

                if ($isBlocked && $inRange) {
                    ?>
                    <div class="wp-block-columns">
                        <div class="wp-block-column">
                            <div class="alert alert-danger" role="alert" style="font-size: 1.2em !important">
                                <?php
                                echo str_replace ("#bis#", Date::FormatDateToFormat ($Settings->row->blockBookingTo), $Settings->row->blockBookingText);
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="wp-block-columns">
                                <div class="wp-block-column">
                                    <div class="alert alert-success" role="alert" id="successMessage" style="display:none;">
                                        Das hat geklappt.....
                                    </div>
                                    <div id="vabs__bookingContainer_loading"><img src="<?php
                                        echo PLUGIN_FOLDER_NAME_WEB; ?>/assets/img/loading.gif" alt="logo" height="100" width="100"> Daten werden geladen.
                                    </div>
                                    <div id="vabs__bookingContainer" style="display: none;">

                                        <form id="form" class="row gx-3 gy-2 align-items-center">

                                            <div class="vabs__container" id="vabs__dateSelectContainer">
                                                <h5>Wähle einen oder mehrere Tag(e)</h5>

                                                <?php
                                                $date                    = new DateTime();
                                                $additionalStartDays     = $Settings->row->additionalCalendarStartDays;
                                                $additionalStartDaysText = strip_tags ($Settings->row->additionalCalendarStartDaysText);
                                                if (!empty($additionalStartDays) && !empty($additionalStartDaysText)) {

                                                    $additionalStartDays = $additionalStartDays - 1;

                                                    if (str_contains ($additionalStartDaysText, '#datum2#')) {
                                                        $date = new DateTime();
                                                        $date->add (new DateInterval('P' . $additionalStartDays . 'D'));
                                                        $additionalStartDaysText = str_replace ('#datum2#', $date->format ('d.m.Y'), $additionalStartDaysText);
                                                    }

                                                    if (str_contains ($additionalStartDaysText, '#datum1#')) {
                                                        //Add another day when booking is available
                                                        $date->add (new DateInterval('P1D'));
                                                        $additionalStartDaysText = str_replace ('#datum1#', $date->format ('d.m.Y'), $additionalStartDaysText);
                                                    }
                                                    ?>
                                                    <h5 style="color: #C00;" id="additionalCalendarStartDaysHint"><?php
                                                        echo $additionalStartDaysText; ?></h5>
                                                    <?php
                                                }

                                                ?>

                                                <h3>An- und Abreisetag anklicken!</h3>
                                                <input class="flatpickr flatpickr-input dateFrom p-3 border bg-light" placeholder="DD.MM.JJJJ" value="" type="text" readonly="readonly">
                                                <button type="button" id="btnRefresh" class="button button-success" style="margin-top: 1rem;">Suchen</button>
                                                <div id="errorMessage">
                                                    <!-- via booking Script -->
                                                </div>
                                            </div>

                                            <div class="vabs__container normal" id="vabs__locationSelectContainerNormal" style="display: none">
                                                <h5>Strandabschnitt wählen</h5>
                                                <p class="hint">Wählen Sie hier im Auswahlfeld einen Strandabschnitt oder klicken Sie einen in der Karte an!</p>
                                                <div class="locationSelect"><!-- via AJAX --></div>
                                            </div>

                                            <div class="vabs__container hopping" id="vabs__locationSelectContainerHopping" style="display: none">
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

                                            <div class="vabs__container normal" id="vabs__mapsContainer">

                                                <div id="vabs__leafLetMap"></div>
                                                <div id="vabs__flexMap">

                                                    <div class="vabs__flexContainer">

                                                        <div class="vabs__flexTopContainer">

                                                            <h2 class="vabs__flexHeadline"><!-- via Ajax --></h2>
                                                            <button class="vabs__flexBtnBack">X</button>

                                                        </div>

                                                        <div class="vabs__flexRowContainer">

                                                            <div class="vabs__flexRows"><!-- via AJAX --></div>

                                                        </div>

                                                        <div id="vabs__chair-card" style="display:none;">
                                                            <div class="vabs__chair-container">
                                                                <button type="button" class="btn btn-secondary vabs__btnChairClose">X</button>
                                                                <div class="vabs__chair-header" style="background-size: cover; background-repeat: no-repeat; background-position: center center;"></div>
                                                                <div class="vabs__chair-body">
                                                                    <div>
                                                                        <strong style="display: block;">Strandkorb Nummer: <span id="vabs__chairCardName"></span></strong> <span style="display: block;">Modell: <span id="vabs__chairCardType"></span></span>
                                                                    </div>
                                                                    <button type="button" id="vabs__chairCardBtnAddToShoppingCart" class="btn btn-success" data-id="">Zur Buchung hinzufügen</button>
                                                                    <button type="button" id="vabs__chairCardBtnRemoveFromShoppingCart" class="btn btn-primary" data-id="">Aus Buchung entfernen</button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>

                                                </div>
                                            </div>

                                            <div class="vabs__container" id="vabs__personalDataContainer" style="display: none">

                                                <h5>Vervollständige die persönlichen Daten</h5>

                                                <div class="row g-3">
                                                    <div class="col-sm-3">
                                                        <input name="firstName" class="border bg-light form-control" type="text" placeholder="Vorname" required>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <input name="lastName" class="border bg-light form-control" type="text" placeholder="Nachname" required>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <input name="email" class="border bg-light form-control" type="email" placeholder="Emailadresse" data-parsley-trigger="change" required>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <input name="tel" class="border bg-light form-control" type="text" placeholder="Telefonnummer">
                                                    </div>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-sm-2">
                                                        <input name="postCode" class="border bg-light form-control" type="text" placeholder="PLZ" required>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <input name="city" class="border bg-light form-control" type="text" placeholder="Ort" required>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <input name="street" class="border bg-light form-control" type="text" placeholder="Strasse" required>
                                                    </div>
                                                    <div class="col-sm-2">
                                                        <input name="number" class="border bg-light form-control" type="text" placeholder="Hausnummer" required>
                                                    </div>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <textarea name="comment" class="col-12 border bg-light form-control" placeholder="Ihre Bemerkung" rows="1"></textarea>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="vabs__container" id="vabs__shoppingCartContainerWrapper" style="display: none">

                                                <h5>Zusammenfassung &amp; Buchung</h5>

                                                <div id="vabs__shoppingCartContainer">
                                                    <div id="vabs__shoppingCartHeader">
                                                        <strong>Zeitraum: <span id="shoppingCartDateTimeRange"></span></strong>

                                                    </div>
                                                    <div id="vabs__shoppingCartList">
                                                        <!-- via AJAX -->
                                                    </div>
                                                    <input type="checkbox" required name="confirm" value="1" />Hiermit bestätige ich die<a href="<?php
                                                    echo $Settings->row->agbLink; ?>" target="blank" style="margin: 0 4px; text-decoration: underline;">AGB</a>und<a href="<?php
                                                    echo $Settings->row->dsgvoLink; ?>" target="blank" style="margin: 0 4px; text-decoration: underline;">Datenschutzvereinbarung</a> gelesen und verstanden zu haben und stimme diesen zu.<br>

                                                    <div id="vabs__paymentSection">
                                                        <div class="form-check-inline no-padding-left">Ich bezahle via:</div>
                                                        <?php

                                                        if ($Settings->row->payPal == 1) {
                                                            ?>

                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="paymentMethodId" id="radioInvoice" value="1"> <label class="form-check-label" for="radioInvoice">Rechnung</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="paymentMethodId" id="radioPayPal" value="2"> <label class="form-check-label" for="radioPayPal">PayPal</label>
                                                            </div>

                                                            <?php
                                                        } else {
                                                            ?>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="paymentMethodId" id="radioInvoice" value="1"> <label class="form-check-label" for="radioInvoice">Rechnung</label>
                                                            </div>

                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                    <?php
                                                    if (!empty($Settings->row->textBeforeBooking)) {
                                                        ?>
                                                        <div class="alert alert-warning" style="margin-top: 15px;"><strong>Bitte beachten Sie: </strong> <?php
                                                            echo strip_tags ($Settings->row->textBeforeBooking); ?></div>
                                                        <?php
                                                    }
                                                    ?>
                                                    <div class="alert" id="vabs__backendErrorMessage" style="display: none; margin-top: 15px"></div>
                                                    <button type="button" id="vabs__btnOrderNow" class="button button-primary" style="margin-top: 1rem;">Jetzt kostenpflichtig bestellen!</button>
                                                </div>

                                            </div>

                                        </form>

                                    </div>
                                </div>
                            </div>
                    <?php
                }

            } catch (Exception $e) {

                $message = $e->getMessage ();
                ?>
                <div class="alert alert-error" role="alert">
                    <?php
                    echo $message; ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
		$content = ob_get_contents ();
		ob_end_clean ();

		return $content;

	}

	/**
	 * @return string
	 */
	public function HandleSuccessMessage (): string {

        ob_start ();

		try {

			$this->Settings = new VABSAPIWPSettings(SETTINGS_TABLE);
			$this->Settings->Load ();

			$salesHeaderId  = $_SESSION['salesHeaderId'] ?? 0;
			$salesInvoiceId = $_SESSION['salesInvoiceId'] ?? 0;
			$orderId        = Session::GetOrderId ();

			$textOrderNumber = 'Deine Bestellnummer lautet: <br><br><b style="font-size: 60px; color: #C00;"># ' . $orderId . '</b><br>';

			#region Capture PayPal

			##############
			### PAYPAL ###
			##############

			$token   = $_GET['token'] ?? '';
			$PayerID = $_GET['PayerID'] ?? '';
			if (!empty($token) && !empty($PayerID)) {
                ?>
                <div class="vabs__container">
                <?php

				try {
					$PayPal    = new Process($this->Settings->row->payPalClientId, $this->Settings->row->payPalClientSecret, $this->Settings->row->payPalSandbox);
					$captureId = $PayPal->CaptureOrder ($token, false);

					if (!empty($captureId)) {
						$API = new API($this->Settings->row->apiURL, $this->Settings->row->apiToken, $this->Settings->row->apiClientId);
						$API->AddPayPalPayment ($salesInvoiceId, $salesHeaderId, 4, $token, $PayerID, $captureId);
						$API->SendInvoice ($salesHeaderId);
					}

					?>
                    <div id="success">
                        <h3>Das hat geklappt1</h3>
                        <p><?php
							echo $textOrderNumber; ?></p>
                        <p>Weitere Informationen findest Du in der Email, welche wir dir geschickt haben.<br>
                            Schaue bitte auch in Deinen Spam-Ordner nach!<br>
                            Eventuell ist sie dort gelandet.<br>
                            Jegliche Zahlungsaufforderungen kannst Du in der Email ignorieren, da du ja bereits per PayPal bezahlt hast.</p>
                    </div>
					<?php
				} catch (AlreadyCapturedException) {
					?>
                    <div id="success">
                        <h3>Ähhhmmm?</h3>
                        <p><?php
							echo $textOrderNumber; ?></p>
                        <p>
                            Du hast bestimmt diese Seite neu geladen. <br>
                            Deine Bestellung wurde bereits an uns übermittelt und bezahlt.<br>
                            Weitere Informationen findest Du in der Email, welche wir dir geschickt haben.<br>
                            Schaue bitte auch in Deinen Spam-Ordner nach. Eventuell ist sie dort gelandet.<br>
                            Jegliche Zahlungsaufforderungen kannst Du in der Email ignorieren, da du ja bereits per PayPal bezahlt hast. </p>
                    </div>
					<?php
				} catch (Exception $e) {
					?>
                    <div id="error">
                        <h3>Irgendwas ist schief gelaufen...</h3>
                        <p><?php
							echo $textOrderNumber; ?></p>
						<?php
						$errorMessage = self::ConvertJsonToPreArray ($e->getMessage ());
						?>
                        <p id="errorMessage"><?php
							echo $errorMessage; ?></p>
                    </div>
					<?php
				}

			}

			##############
			### STRIPE ###
			##############

			//Stripe will send in a positive response a get parameter called session_id
			if (!empty($_GET['session_id'])) {

				/*$apiKey = $this->Settings->row->stripeSandbox ? $this->Settings->row->stripeSecretTestKey : $this->Settings->row->stripeSecretProdKey;
				Stripe::setApiKey ($apiKey);
				$stripeSession = \Stripe\Checkout\Session::retrieve ($_GET['session_id']);

				if ($stripeSession['payment_intent'] != null) {
					$paymentIntent = $stripeSession['payment_intent'];
					$API           = new API($this->Settings->row->apiURL, $this->Settings->row->apiToken, $this->Settings->row->apiClientId);
					$response      = $API->AddStripePayment ($salesInvoiceId, $salesHeaderId, 8, $paymentIntent);
					ob_start ();
					*/?><!--
                    <div id="success">
                        <h3>Das hat geklappt</h3>
                        <p><?php
/*							echo $textOrderNumber; */?></p>
                        <p>Weitere Informationen findest Du in der Email, welche wir dir geschickt haben.<br>
                            Schaue bitte auch in Deinen Spam-Ordner nach!<br>
                            Eventuell ist sie dort gelandet.<br>
                            Jegliche Zahlungsaufforderungen kannst Du in der Email ignorieren, da du ja bereits per Stripe bezahlt hast.</p>
                    </div>
					--><?php
/*					$content = ob_get_clean ();
				}

				return $content;*/

			}

			################
			### RECHNUNG ###
			################

			?>
            <div id="success">
                <h3>Das hat geklappt1</h3>
                <p><?php
					echo $textOrderNumber; ?></p>
                <p>Weitere Informationen findest Du in der Email, welche wir dir geschickt haben. <br>
                    Schaue bitte auch in Deinen Spam-Ordner nach!<br>
                    Eventuell ist sie dort gelandet.<br>
                    Dort findest Du auch die Zahlungsaufforderungen. Wir bitten Dich, diese auch zeitnah nachzukommen.</p>
            </div>
			<?php

		} catch (Exception $e) {

			$message = $e->getMessage ();
			?>
            <div class="alert alert-error" role="alert">
				<?php
				echo $message; ?>
            </div>
			<?php

		}

		return ob_get_clean ();

	}

	/**
	 * @throws ValidationException
	 */
	public function HandleCancelMessage (): void {

		?>
        <div class="vabs__container">
			<?php

			$this->Settings->Load ();

			$token          = $_GET['token'] ?? '';
			$salesHeaderId  = $_SESSION['salesHeaderId'] ?? 0;
			$salesInvoiceId = $_SESSION['salesInvoiceId'] ?? 0;

			##############
			### PAYPAL ###
			##############

			if (!empty($token) && !empty($salesHeaderId) && !empty($salesInvoiceId)) {

				?>
                <div id="success">
                    <h3>Das hat (fast) geklappt</h3>
                    <p>Du hast deine Bezahlung bei PayPal abgebrochen. Setze Dich mit uns in Verbindung, um den Betrag auf eine andere Art und Weise durchzuführen.<br>
                        Weitere Informationen findest Du in der Email, welche wir dir geschickt haben. Schaue bitte auch in Deinen Spam-Ordner nach.
                        Eventuell ist sie dort gelandet. Dort findest Du auch die Bankverbindung, weölche Du alternativ als Zahlungsmethode benutzen kannst.</p>
                </div>
				<?php

			} else {
				?>
                <div id="success">
                    <h3>Keine Ahnung, warum du hier gelandet bist</h3>
                    <p>Diese Seite erscheint eigentlich nur, wenn Du einen Zahlungsprozess (wie PayPal oder Stripe) abgebrochen hast.</p>
                </div>
				<?php
			}
			?>
        </div>
		<?php
	}

	public function HTMLHeader (): void {
		?>
		<div id="vabs-container">

		<div id="vabs-container-header">
		<div id="vabs-container-logo">
			<img src="<?php
			echo PLUGIN_FOLDER_NAME_WEB; ?>/assets/img/logo.png" alt="logo">
		</div>		<div id="vabs-container-body">		<!-- START MODULE CONTENT -->
		<?php
	}

	public function HTMLFooter (): void {
		?>
		<!-- END MODULE CONTENT -->		</div>		</div>

		</div>
		<?php
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	private function ConvertJsonToPreArray (string $message): string {
		if (str_contains ($message, '{')) {
			$message = json_decode ($message, true);
			$message = "<pre>" . print_r ($message, true) . "</pre>";
		}

		return $message;
	}

}

$vabsBeachPlugin = new VABSBeachPlugin();

register_activation_hook (__FILE__, ['VABSBeachPlugin', 'ActivatePlugin']);

<?php

namespace VABS;

use DD\Database;
use Exception;
use PDO;
use PDOException;

class Settings
{

	const VERSION = "3.0.0";

	public string $apiToken     = '';
	public string $apiClientId  = '';
	public string $apiURL       = '';
	public int    $referrerId   = 0;
	public string $dsgvoLink    = '';
	public string $agbLink      = '';
	public string $redirectLink = '';
	public string $path         = PLUGIN_FOLDER_PATH.'/settings.php';

	public int    $payPal             = 0;
	public int    $payPalSandbox      = 0;
	public string $payPalClientId     = '';
	public string $payPalClientSecret = '';

	public string $textBeforeBooking  = '';

	//SMTP
	public string $smtpServer  = '';
	public string $smtpUser  = '';
	public string $smtpPass  = '';

	//Block Booking
	public int $blockBookingEnabled = 0;
	public string $blockBookingFrom = '';
	public string $blockBookingTo   = '';
	public string $blockBookingText   = '';

	//Calendar start date
	public int    $additionalCalendarStartDays     = 0;
	public string $additionalCalendarStartDaysText = '';

	public int $debug  = 0;
	/**
	 * @var mixed
	 */
	public        $row;
	public string $errorMessage = '';
	public string $versionNumber = '';

	public function __construct () {

		$this->CheckSettings();

	}

	//Loads the settings from a settings file

	/**
	 * @return bool
	 */
	public function Load() : bool {

		try {

			$conPDO = Database::getInstance ();

			$SQL = "SELECT  
						 
						IFNULL(apiToken,'') as apiToken,
						IFNULL(apiClientId,'') as apiClientId,
						IFNULL(apiURL,'') as apiURL,
						IFNULL(referrerId,0) as referrerId,
						IFNULL(dsgvoLink,'') as dsgvoLink,
						IFNULL(agbLink,'') as agbLink,
						IFNULL(redirectLink,'') as redirectLink,
						IFNULL(payPal,0) as payPal,
						IFNULL(payPalSandbox,0) as payPalSandbox,
						IFNULL(payPalClientId,'') as payPalClientId,
						IFNULL(payPalClientSecret,'') as payPalClientSecret,
						IFNULL(textBeforeBooking,'') as textBeforeBooking,
						IFNULL(smtpServer,'') as smtpServer,
						IFNULL(smtpUser,'') as smtpUser,
						IFNULL(smtpPass,'') as smtpPass,
						IFNULL(debug,0) as debug,
						IFNULL(versionNumber,'') as versionNumber,
						IFNULL(blockBookingEnabled,0) as blockBookingEnabled,
						IFNULL(blockBookingFrom,'') as blockBookingFrom,
						IFNULL(blockBookingTo,'') as blockBookingTo,
						IFNULL(blockBookingText,'') as blockBookingText,
						IFNULL(additionalCalendarStartDays,0) as additionalCalendarStartDays,
						IFNULL(additionalCalendarStartDaysText,'') as additionalCalendarStartDaysText
					FROM
						vabs_settings";
			$stm = $conPDO->prepare ($SQL);
			$stm->execute();

			$stm->setFetchMode (PDO::FETCH_CLASS, __CLASS__);
			$this->row = $stm->fetch ();

			return true;

		} catch (Exception $e){
			$this->errorMessage = $e->getMessage ();
		}

		return false;

	}

	/**
	 * Saves the settings into a settings file
	 * @return bool
	 * @throws Exception
	 */
	public function Save () : bool {

		try {

			$conPDO = Database::getInstance ();

			if (empty($this->apiURL)) {
				throw new Exception("API URL must not be empty");
			}

			if (empty($this->apiToken)) {
				throw new Exception("API TOKEN must not be empty");
			}

			if (empty($this->apiClientId)) {
				throw new Exception("API ClientId must not be empty");
			}

			$SQL = "UPDATE  
						vabs_settings 
					SET 
						apiToken = :apiToken,
						apiClientId = :apiClientId,
						apiURL = :apiURL,
						referrerId = :referrerId,
						dsgvoLink = :dsgvoLink,
						agbLink = :agbLink,
						redirectLink = :redirectLink,
						payPal = :payPal,
						payPalSandbox = :payPalSandbox,
						payPalClientId = :payPalClientId,
						payPalClientSecret = :payPalClientSecret,
						textBeforeBooking = :textBeforeBooking,
						smtpServer = :smtpServer,
						smtpUser = :smtpUser,
						smtpPass = :smtpPass,
						debug = :debug,
						blockBookingEnabled = :blockBookingEnabled,
						blockBookingFrom = :blockBookingFrom,
						blockBookingTo = :blockBookingTo,
						blockBookingText = :blockBookingText,
						additionalCalendarStartDays = :additionalCalendarStartDays,
						additionalCalendarStartDaysText = :additionalCalendarStartDaysText";
			$stm = $conPDO->prepare ($SQL);
			$stm->bindValue (':apiToken', $this->apiToken);
			$stm->bindValue (':apiClientId', $this->apiClientId);
			$stm->bindValue (':apiURL', $this->apiURL);
			$stm->bindValue (':referrerId', $this->referrerId, PDO::PARAM_INT);
			$stm->bindValue (':dsgvoLink', $this->dsgvoLink);
			$stm->bindValue (':agbLink', $this->agbLink);
			$stm->bindValue (':redirectLink', $this->redirectLink);
			$stm->bindValue (':payPal', $this->payPal, PDO::PARAM_INT);
			$stm->bindValue (':payPalSandbox', $this->payPalSandbox, PDO::PARAM_INT);
			$stm->bindValue (':payPalClientId', $this->payPalClientId);
			$stm->bindValue (':payPalClientSecret', $this->payPalClientSecret);
			$stm->bindValue (':textBeforeBooking', $this->textBeforeBooking);
			$stm->bindValue (':smtpServer', $this->smtpServer);
			$stm->bindValue (':smtpUser', $this->smtpUser);
			$stm->bindValue (':smtpPass', $this->smtpPass);
			$stm->bindValue (':debug', $this->debug, PDO::PARAM_INT);
			$stm->bindValue (':blockBookingEnabled', $this->blockBookingEnabled, PDO::PARAM_INT);
			$stm->bindValue (':blockBookingFrom', $this->blockBookingFrom);
			$stm->bindValue (':blockBookingTo', $this->blockBookingTo);
			$stm->bindValue (':blockBookingText', $this->blockBookingText);
			$stm->bindValue (':additionalCalendarStartDays', $this->additionalCalendarStartDays);
			$stm->bindValue (':additionalCalendarStartDaysText', $this->additionalCalendarStartDaysText);

			$stm->execute ();

			return true;

		} catch (Exception $e) {
			$this->errorMessage = $e->getMessage ();
		}

		return false;

	}

	/**
	 * @return void
	 */
	private function CheckSettings(): void {

		try {

			$conPDO = Database::getInstance ();

			$SQL = "SHOW TABLES LIKE 'vabs_settings'";
			$stm = $conPDO->prepare ($SQL);
			$stm->execute ();
			if($stm->rowCount () == 0){

				$SQL = "CREATE TABLE IF NOT EXISTS `vabs_settings` (
							`apiToken` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`apiClientId` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`apiURL` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`dsgvoLink` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`agbLink` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`redirectLink` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`textBeforeBooking` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`referrerId` SMALLINT(6) NULL DEFAULT NULL,
							`payPal` TINYINT(1) NULL DEFAULT 0,
							`payPalSandbox` TINYINT(1) NULL DEFAULT 1,
							`payPalClientId` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`payPalClientSecret` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`debug` TINYINT(1) NULL DEFAULT 0,
							`smtpServer` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`smtpUser` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`smtpPass` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`versionNumber` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`blockBookingEnabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
							`blockBookingFrom` DATE NULL,
							`blockBookingTo` DATE NULL,
							`blockBookingText` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci',
							`additionalCalendarStartDays` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
							`additionalCalendarStartDaysText` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci'
						) COLLATE='utf8_general_ci' ENGINE=InnoDB;";
				$stm = $conPDO->prepare ($SQL);
				$stm->execute ();

				$SQL = "INSERT INTO 
							vabs_settings 
						SET 
							apiToken = :apiToken,
							apiClientId = :apiClientId,
							apiURL = :apiURL,
							referrerId = :referrerId,
							dsgvoLink = :dsgvoLink,
							agbLink = :agbLink,
							redirectLink = :redirectLink,
							payPal = :payPal,
							payPalSandbox = :payPalSandbox,
							payPalClientId = :payPalClientId,
							payPalClientSecret = :payPalClientSecret,
							textBeforeBooking = :textBeforeBooking,
							smtpServer = :smtpServer,
							smtpUser = :smtpUser,
							smtpPass = :smtpPass,
							versionNumber = :versionNumber,
							debug = :debug,
							blockBookingEnabled = 0,
							blockBookingFrom = '',
							blockBookingTo = '',
							blockBookingText = '',
							additionalCalendarStartDays = 0,
							additionalCalendarStartDaysText = ''";


				$stm = $conPDO->prepare ($SQL);
				$stm->bindValue (':apiToken', '');
				$stm->bindValue (':apiClientId', '');
				$stm->bindValue (':apiURL', '');
				$stm->bindValue (':referrerId', 0, PDO::PARAM_INT);
				$stm->bindValue (':dsgvoLink', '');
				$stm->bindValue (':agbLink', '');
				$stm->bindValue (':redirectLink', '');
				$stm->bindValue (':payPal', 0, PDO::PARAM_INT);
				$stm->bindValue (':payPalSandbox', 1, PDO::PARAM_INT);
				$stm->bindValue (':payPalClientId', '');
				$stm->bindValue (':payPalClientSecret', '');
				$stm->bindValue (':textBeforeBooking', '');
				$stm->bindValue (':smtpServer', '');
				$stm->bindValue (':smtpUser', '');
				$stm->bindValue (':smtpPass', '');
				$stm->bindValue (':versionNumber', self::VERSION);
				$stm->bindValue (':additionalCalendarStartDays', 0, PDO::PARAM_INT);
				$stm->bindValue (':additionalCalendarStartDaysText', '');

				$stm->execute ();

			}

		} catch (PDOException|Exception $e) {
			$this->errorMessage = $e->getMessage ();
		}

	}

}

<?php

namespace VABS;

use DD\Mailer\Mailer;
use DD\Utils;
use Exception;
use PHPMailer\PHPMailer\Exception AS PHPMailerException;

class Email
{

	/**
	 * @param string $body
	 * @param string $subject
	 * @param string $filePath
	 * @param string $fileName
	 */
	public static function SendAdminMail (string $body, string $subject = "", string $filePath = "", string $fileName = ""): void {

		ob_start ();
		Utils::PrintStack (debug_backtrace ());
		$varBacktrace = ob_get_contents ();
		ob_end_clean ();

		try {

			//Eimail-Objekt erstellen
			$mail              = new Mailer(true);
			$mail->Port = 587;
			//$mail->SMTPDebug = 2;
			$mail->isAdminMail = true;

			//Header
			$mail->DDAddTo (EMAIL_DEVELOPER, false, false);
			//Betreff
			$subject = strlen (trim ($subject)) > 0 ? trim ($subject) : "Email für DD Admininistrator";
			$mail->DDSubject ($subject);
			//Body
			$body = str_replace ("::", "<br>", $body);
			$body = str_replace ("\n", "<br>", $body);
			$body = str_replace (" Fehler: ", "<br> Fehler: ", $body);
			$body .= '<br>'.$varBacktrace;

			if (!empty($_SESSION['login'])) {
				$body .= "<br>User: ".$_SESSION['login']['vorname']." ".$_SESSION['login']['nachname']." (".$_SESSION['login']['id'].")";
			}

			if (!empty($_SESSION['login']['company'])) {
				$body .= "<br>DD Customer: ".$_SESSION['login']['company'];
			}

			$mail->msgHTML ($body);

			if (!empty($filePath) && !empty($fileName) && file_exists ($filePath)) {
				$mail->addAttachment ($filePath, $fileName);
			}

			//Senden
			if (!$mail->DDSend ()) {
				throw new Exception($mail->ErrorInfo);
			}

		} catch (PHPMailerException|Exception $e) {

			Log::Log ($e->getMessage ());

		} // ENDE try {

	}
}

<?php

namespace VABS;

class Log
{

	const FOLDER = PLUGIN_FOLDER_PATH."/logs";

	/**
	 * @param string $message
	 */
	public static function Log (string $message = ""): void {


		if (!@file_exists (self::FOLDER)) {
			@mkdir (self::FOLDER, 0777, true);
		}

		$datum      = date ("Y-m-d");
		$zeit       = date ("H:i:s");
		$text       = "";
		$dateiname  = self::FOLDER."/".$datum.".log";
		$backTrace  = debug_backtrace ();
		$scriptPath = $backTrace[0]['file'];

		//Kopfdaten schreiben, wenn Datei noch nicht existiert und angelegt wwerden muss
		if (!@file_exists ($dateiname)) {
			$text .= "Datum\tZeit\tDatei\tFehler\r\n";
		} // ENDE

		//Text zusammenbauen
		$text .= $datum."\t".$zeit."\t".$scriptPath."\t".$message;

		//Datei erstellen und schreiben
		$filestream = @fopen ($dateiname, "a");
		@fwrite ($filestream, $text."\r\n");
		@fclose ($filestream);

	}

}

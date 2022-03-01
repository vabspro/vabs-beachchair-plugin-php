<?php

namespace VABS;

use Exception;

class Settings
{

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

	public int    $zoom      = 15;
	public string $latCenter = '';
	public string $lonCenter = '';

	public string $textBeforeBooking  = '';

	//LOads the settings from a settings file

	/**
	 * Loads settings from a file or create empty settings file if settings file doesn't exists
	 * @throws Exception
	 */
	public function Load() : array {

		//Create file if not exists
		if(!file_exists ($this->path)){
			$data = [
				'apiToken'           => '',
				'apiClientId'        => '',
				'apiURL'             => '',
				'referrerId'         => 0,
				'dsgvoLink'          => '',
				'agbLink'            => '',
				'redirectLink'       => '',
				'payPal'             => 0,
				'payPalSandbox'      => 0,
				'payPalClientId'     => '',
				'payPalClientSecret' => '',
				'textBeforeBooking' => '',
				'zoom' => 15,
				'latCenter' => '',
				'lonCenter' => '',
			];
			$write = file_put_contents ($this->path, '<?php $settings = '.var_export ($data, true).';');
			if($write === false){
				throw new Exception("File could not be written");
			}
		}

		include $this->path;
		if(!empty($settings)){
			return (array)$settings;
		}

		return [];
	}


	/**
	 * Saves the settings into a settings file
	 * @return bool
	 * @throws Exception
	 */
	public function Save () : bool {

		if (empty($this->apiURL)){
			throw new Exception("API URL must not be empty");
		}

		if (empty($this->apiToken)) {
			throw new Exception("API TOKEN must not be empty");
		}

		if (empty($this->apiClientId)) {
			throw new Exception("API ClientId must not be empty");
		}

		$data = [
			'apiToken'           => $this->apiToken,
			'apiClientId'        => $this->apiClientId,
			'apiURL'             => $this->apiURL,
			'referrerId'         => $this->referrerId ? : 0,
			'dsgvoLink'          => $this->dsgvoLink ? : null,
			'agbLink'            => $this->agbLink ? : null,
			'redirectLink'       => $this->redirectLink ? : null,
			'payPal'             => $this->payPal ?? 0,
			'payPalSandbox'      => $this->payPalSandbox ?? 1,
			'payPalClientId'     => $this->payPalClientId ? : '',
			'payPalClientSecret' => $this->payPalClientSecret ? : '',
			'textBeforeBooking'  => $this->textBeforeBooking ? strip_tags ($this->textBeforeBooking) : '',
			'zoom' => $this->zoom ?? 1,
			'latCenter' => $this->latCenter ?? '',
			'lonCenter' => $this->lonCenter ?? '',
		];

		$write = file_put_contents ($this->path, '<?php $settings = '.var_export ($data, true).';');

		return $write !== false;

	}

}

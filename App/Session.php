<?php

namespace VABS;

class Session {

	public static function SetReferenceCode (int $refrenceCode): void {

		$_SESSION['refrenceCode'] = $refrenceCode;

	}

	public static function GetReferenceCode (): int {

		return $_SESSION['refrenceCode'] ?? 0;

	}

	public static function Destroy (): void {

		unset($_SESSION['refrenceCode']);
		session_destroy ();

	}

	public static function SetOrderId (int $orderId): void {

		$_SESSION['orderId'] = $orderId;

	}

	public static function GetOrderId (): int {

		return $_SESSION['orderId'] ?? 0;

	}

}

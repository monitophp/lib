<?php
namespace MonitoLib\Utils;

class Ean
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-09-13
    * Initial release
    */

	public static function convertEan14ToEan1(string $ean) : string
    {
        $ean = substr($ean, 1, 12);
        return self::setCheckSum($ean);
    }
    public static function setCheckSum(string $ean) : string
	{
		$even = true;
		$sum  = 0;
		$len  = strlen($ean);

		for ($i = $len - 1; $i >= 0; $i--) {
			$sum += (int)$ean[$i] * ($even ? 3 : 1);
			$even = !$even;
		}

		return $ean . (10 - ($sum % 10)) % 10;
	}
}
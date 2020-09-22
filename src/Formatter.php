<?php
namespace MonitoLib;

class Formatter
{
	/**
	 * Formata CNPJ
	 * @param string $n
	 * @return string
	 */
	public static function cnpj($cnpj)
	{
		$n = Functions::removeNonNumbers($cnpj);

		if ($n != '') {
			return substr($n, 0, 2) . '.' . substr($n, 2, 3) . '.' . substr($n, 5, 3) . '/' . substr($n, 8, 4) . '-' . substr($n, 12, 2);
		}
	}
	/**
	 * Formata CPF
	 * @param string $n
	 * @return string
	 */
	public static function cpf($cpf)
	{
		$n = Functions::removeNonNumbers($cpf);

		if ($n != '') {
			return substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9, 2);
		}
	}
	/**
	 * Formata CPF/CNPJ
	 * @param string $n
	 * @return string
	 */
	public static function CpfCnpj($string)
	{
		$n = Functions::removeNonNumbers($string);
		$r = NULL;

		if (strlen($n) > 0) {
			if (strlen($n) <= 11) {
				$r = self::cpf($n);
			} else {
				$r = self::cnpj($n);
			}
		}

		return $r;
	}
	/**
	 * Formata Data
	 * @param string $value
	 * @return string
	 */
	static public function date($value)
	{
		return $value != '' ? date('d/m/Y', strtotime($value)) : '';
	}
	/**
	 * Formata Data
	 * @param string $value
	 * @return string
	 */
	static public function datetime($value)
	{
		return $value != '' ? date('d/m/Y H:i:s', strtotime($value)) : '';
	}
	/**
	 * Formata value
	 * @param float $value
	 * @param int $decimals Default: 2
	 * @return string
	 */
	static public function decimal($value, $decimals = 2)
	{
		if (is_numeric($value)) {
			$value = number_format($value, $decimals, ',', '.');
		}
		return $value;
	}
	static public function secondsToString($seconds)
	{
		//echo $seconds . "\n";
		$string = '';
		$year   = 60 * 60 * 24 * 365;
		$month  = 60 * 60 * 24 * 30;
		$day    = 60 * 60 * 24;
		$hour   = 60 * 60;
		$minute = 60;
		$second = 1;

		if ($seconds > $year) {
			$years   = (int)($seconds / $year);
			$seconds = $seconds - $year;
			$string  = $years . ' ano' . ($years > 1 ? 's' : '');
		}
		if ($seconds > $month) {
			$months   = (int)($seconds / $month);
			$seconds  = $seconds - $month;
			$string  .= ($string == '' ? '' : ' ') . $months . ' m' . ($months > 1 ? 'ês' : 'eses');
		}
		if ($seconds > $day) {
			$days     = (int)($seconds / $day);
			$seconds  = $seconds - $day;
			$string  .= ($string == '' ? '' : ' ') . $days . 'd' . ($days > 1 ? 's' : '');
		}
		if ($seconds > $hour) {
			$hours    = (int)($seconds / $hour);
			$seconds  = $seconds - $hour;
			$string  .= ($string == '' ? '' : ' ') . $hours . 'h' . ($hours > 1 ? 's' : '');
		}
		if ($seconds > $minute) {
			$minutes  = (int)($seconds / $minute);
			$seconds  = $seconds - $minute;
			$string  .= ($string == '' ? '' : ' ') . $minutes . 'min' . ($minutes > 1 ? 's' : '');
		}
		if ($seconds > $second) {
			$secondx  = (int)($seconds / $second);
			$seconds  = $seconds - $second;
			$string  .= $secondx;
		}
		if ($seconds > 0) {
			$seconds  = ceil(fmod($seconds, 1) * 1000);
			$string  .= ($string == '' ? '0.' : '.') . $seconds;
		}

		$string  .= 'seg';

		return $string;
	}
	/**
	 * Formata telefone
	 * @param string $value
	 * @return string
	 */
	static public function telefone($value)
	{
		$value = Functions::removeNonNumbers($value);

		if (substr($value, 0, 4) == '0800') {
			$len     = strlen($value);
			$pattern = '/(\d{4})(\d*)(\d{4})/';
			$value   = preg_replace($pattern, '$1-$2-$3', $value);
		} else {
			$len   = strlen(+$value);
			//
			//if ($len >= 7 and $len <= 9)
			//{
			//	$pattern = '/(\d*)(\d{4})/';
			//	$value   = preg_replace($pattern, '$1-$2', $value);
			//}
			//elseif ($len >= 10 and $len <= 11)
			//{
			//	$pattern = '/(\d{2})(\d*)(\d{4})/';
			//	$value   = preg_replace($pattern, '($1) $2-$3', $value);
			//}

			switch ($len) {
				case 7:
				case 8:
				case 9:
					$pattern = '/(\d*)(\d{4})/';
					$value   = preg_replace($pattern, '$1-$2', $value);
					break;
				case 10:
				case 11:
					$pattern = '/(\d{2})(\d*)(\d{4})/';
					$value   = preg_replace($pattern, '($1) $2-$3', $value);
					break;
			}
		}

		return $value;
	}
}
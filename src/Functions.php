<?php
namespace MonitoLib;

class Functions
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2019-04-17
    * first versioned
    */

	/**
	 * Merges an array recursively
	 * Mescla um array recursivamente
	 *
	 * @param array $a1 Default array
	 * @param array $a2 Input array
	 *
	 * @return array
	 */
	public static function arrayMergeRecursive ($a1, $a2)
	{
		$newArray = $a1;
		if (is_array($a2)) {
			foreach ($a2 as $k => $v) {
				if (isset($a1[$k])) {
					if (is_array($a1[$k])) {
						$newArray[$k] = self::arrayMergeRecursive($a1[$k], $a2[$k]);
					} else {
						$newArray[$k] = $a2[$k];
					}
				} else {
					$newArray[$k] = $a2[$k];
				}
			}
		}
		return $newArray;
	}
    public static function compareDto ($dtoSource, $dtoDestination)
    {
        $reflection = new \ReflectionClass(get_class($dtoSource));
        $properties = $reflection->getdefaultProperties();

        foreach ($properties as $property => $value) {
            $method = ucfirst($property);
            $get    = 'get' . $method;

            if (method_exists($dtoDestination, $get)) {
                if ($dtoSource->$get() !== $dtoDestination->$get()) {
                   return false;
                }
            }
        }

        return true;
    }
    public static function copyDto ($dtoSource, $dtoDestination)
    {
        $reflection = new \ReflectionClass(get_class($dtoSource));
        $properties = $reflection->getdefaultProperties();

        foreach ($properties as $property => $value) {
            $method = ucfirst($property);
            $get    = 'get' . $method;
            $set    = 'set' . $method;
            $value  = $dtoSource->$get();

            if (!is_null($value) && $value !== '') {
                if (method_exists($dtoDestination, $set)) {
                    if (is_null($dtoDestination->$get())) {
                       $dtoDestination->$set($value);
                    }
                }
            }
        }

        return $dtoDestination;
    }
	/**
	 * jLibhex_to_float
	 *
	 * @param string $hex
	 */
	public static function hexToFloat ($hex)
	{
		$hex   = str_replace('#', '', $hex);
		$color = [];
	
		if (strlen($hex) == 3) {
			$color[] = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1)) / 255;
			$color[] = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1)) / 255;
			$color[] = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1)) / 255;
		} elseif (strlen($hex) == 6) {
			$color[] = hexdec(substr($hex, 0, 2)) / 255;
			$color[] = hexdec(substr($hex, 2, 2)) / 255;
			$color[] = hexdec(substr($hex, 4, 2)) / 255;
		}
	
		return $color;
	}
	public static function intToDate ($int)
	{
		if (preg_match('/[0-9]{8}/', $int)) {
			return substr($int, 4, 4) . '-' . substr($int, 2, 2) . '-' . substr($int, 0, 2);
		}
	}
	public static function postValue ($variable, $default = null)
	{
		if (!isset($_POST[$variable])) {
			throw new \Exception("Post field '$variable' not found!");
		}

		$value = $_POST[$variable];

		if ($value == '') {
			return $default;	
		}

		return $value;
	}	
	/**
	* Função de redirecionamento de página
	*
	* @param $url Url para onde a página será redirecionada
	*
	* @return void
	*/
	public static function urlRedirect ($url)
	{
		if (!headers_sent()) {
			header("Location: $url");
		} else {
			echo "<meta HTTP-EQUIV='Refresh' CONTENT='0;URL=$url'>";
			echo "<script>top.document.location='$url';</script>";
		}
	
		exit;
	}
	/**
	 * Remove não números de uma string
	 * @param string $n
	 * @return string
	 */
	public static function removeNonNumbers ($str)
	{
		$er = '/[^\d]/';
		return preg_replace($er, '', $str);
	}
	public static function convertToUrl ($url)
	{
		$url = strtolower($url);
		$url = self::removeAccents($url);
		$url = preg_replace('/[^a-z0-9\-]/', '-', $url);
		$url = preg_replace('/-{2,}/', '-', $url);
		$url = preg_replace('/-$/', '', $url);
		
		return $url;
	}
	public static function friendlyDate ($time)
	{
		$diff = time() - $time;

		$r = 'há ';

		if ($diff <= 60) {
			$r = $diff . ' segundo' . ($diff > 1 ? 's' : '');
		} elseif ($diff > 60 && $diff <= 3600) {
			$diff = round($diff / 60);
			$r = $diff . ' minuto' . ($diff > 1 ? 's' : '');
		} elseif ($diff > 3600 && $diff <= 86400) {
			$diff = round($diff / 3600);
			$r = $diff . ' hora' . ($diff > 1 ? 's' : '');
		} elseif ($diff > 86400 && $diff <= 31536000) {
			$diff = round($diff / 86400);
			$r = $diff . ' dia' . ($diff > 1 ? 's' : '');
		} else {
			$diff = floor($diff / 31536000);
			$r = 'mais de ' . $diff . ' ano' . ($diff > 1 ? 's' : '');
		}

		return $r;
	}
	public static function post ($fieldName)
	{
		$value = null;

		if (isset($_POST[$fieldName])) {
			$value = $_POST[$fieldName];
		}

		return $value;
	}
	public static function removeAccents ($str)
	{
		$a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
		$b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'ss', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
		return str_replace($a, $b, $str);
	}
	public static function spaceRightPad ($string, $size)
	{
		return str_pad($string, $size, ' ', STR_PAD_RIGHT);
	}
	public static function stringToFloat ($value)
	{

		$value = str_replace('.', '', $value);
		$value = str_replace(',', '.', $value);

		if ($value == '') {
			return null;
		}

		return $value;
	}
	public static function toInt ($number)
	{
		if ($number != '') {
			return +$number;
		}
	}
	public static function toLowerCamelCase ($string)
	{
		$frag  = explode('_', strtolower($string));
		$count = count($frag);
		$newString = $frag[0];
		
		for ($i = 1; $i < $count; $i++) {
			$newString .= ucfirst($frag[$i]);
		}
		
		return $newString;
	}
	public static function toUpperCamelCase ($string)
	{
		return ucfirst(self::toLowerCamelCase($string));
	}
	public static function toSingular ($string)
	{
		if (strtolower($string) == 'status') {
			return $string;
		}
		if (preg_match('/ens$/', $string)) {
			$string = substr($string, 0, -3) . 'em';
		}
		if (preg_match('/ies$/', $string)) {
			$string = substr($string, 0, -3) . 'y';
		}
		if (preg_match('/oes$/', $string)) {
			$string = substr($string, 0, -3) . 'ao';
		}
		//if (preg_match('/res$/', $string))
		//{
		//	$string = substr($string, 0, -2);
		//}
		if (preg_match('/tchs$/', $string)) {
			$string = substr($string, 0, -1);
		}
		if (preg_match('/[acdeiouglmnprt]s$/', $string)) {
			$string = substr($string, 0, -1);
		}

		return $string;
	}
	static function upperSeparator ($string, $separator)
	{
		$len = strlen($string);
		$res = '';

		for ($i = 0; $i < $len; $i++) {
			$crt = $string[$i];
			$lwr = strtolower($crt);

			if ($crt === $lwr) {
				$res .= $crt;
			} else {
				$res .= $separator . $lwr;
			}
		}

		return $res;
	}
	public static function urlToMethod ($url)
	{
		$af = explode('-', $url);
		$ra = null;

		if (!empty($af)) {
			$ra = $af[0];
			array_shift($af);

			foreach ($af as $f) {
				$ra .= ucfirst($f);
			}
		}

		return $ra;
	}
	/**
	 * Preenche um número com zeros à esquerda
	 * @param int $n Número que será preenchido 
	 * @param int $t Tamanho do preenchimento
	 * @return string
	 */
	static function zeroLeftPad ($number, $size)
	{
		if (is_numeric($number)) {
			return str_pad($number, $size, '0', STR_PAD_LEFT);
		} else {
			return $number;
		}
	}
}
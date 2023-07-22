<?php
/**
 * Helpers.
 *
 * @version 1.0.0
 */

use MonitoLib\App;

function compare_dto($dtoSource, $dtoDestination)
{
    $reflection = new \ReflectionClass(get_class($dtoSource));
    $properties = $reflection->getdefaultProperties();

    foreach ($properties as $property => $value) {
        $method = ucfirst($property);
        $get = 'get' . $method;

        if (method_exists($dtoDestination, $get)) {
            if ($dtoSource->{$get}() !== $dtoDestination->{$get}()) {
                return false;
            }
        }
    }

    return true;
}

function convert_name(string $name): string
{
    $words = explode(' ', $name);
    $name = '';

    foreach ($words as $w) {
        $w = trim($w);
        $w = remove_accents($w);

        if (preg_match('/^[a-zA-Z]+$/', $w)) {
            $w = strtolower($w);
            $w = ucfirst($w);

            if (in_array($w, [
                'Da',
                'De',
                'Dos',
                'E',
            ])) {
                $w = strtolower($w);
            }
        }

        $name .= "{$w} ";
    }

    return trim($name);
}

function convert_to_url(string $string): string
{
    $url = mb_strtolower($string);
    $url = remove_accents($url);
    $url = preg_replace('/[^a-z0-9\-]/', '-', $url);
    $url = preg_replace('/-{2,}/', '-', $url);

    return preg_replace('/-$/', '', $url);
}

function copy_dto(object $dtoSource, object $dtoDestination): object
{
    $reflection = new \ReflectionClass(get_class($dtoSource));
    $properties = $reflection->getdefaultProperties();

    foreach ($properties as $property => $value) {
        $method = ucfirst($property);
        $get = 'get' . $method;
        $set = 'set' . $method;
        $value = $dtoSource->{$get}();

        if (!is_null($value) && '' !== $value) {
            if (method_exists($dtoDestination, $set)) {
                if (is_null($dtoDestination->{$get}())) {
                    $dtoDestination->{$set}($value);
                }
            }
        }
    }

    return $dtoDestination;
}

function env($config, $default = null)
{
    $value = $_ENV[$config] ?? $default;

    if (is_int($value)) {
        return intval($value);
    }

    if (is_float($value)) {
        return floatval($value);
    }

    return $value;
}

function delete_dir(string $directory)
{
    if (!is_dir($directory)) {
        throw new \InvalidArgumentException("Invalid directory: {$directory}");
    }

    $iterator = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }

    rmdir($directory);
}

function download_file(string $url, ?string $destinationFile): string
{
    if (is_null($destinationFile)) {
        $destinationFile = App::getTmpPath() . 'tmpfile';
    }

    $file = @fopen($url, 'rb');

    if (false === $file) {
        throw new \Exception("Error downloading {$url}");
    }

    $newf = fopen($destinationFile, 'wb');

    if ($newf) {
        while (!feof($file)) {
            fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
        }
    }

    if ($file) {
        fclose($file);
    }

    if ($newf) {
        fclose($newf);
    }

    return $destinationFile;
}

function friendly_date($time)
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

function hex_to_float($hex)
{
    $hex = str_replace('#', '', $hex);
    $color = [];

    if (strlen($hex) === 3) {
        $color[] = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1)) / 255;
        $color[] = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1)) / 255;
        $color[] = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1)) / 255;
    } elseif (6 == strlen($hex)) {
        $color[] = hexdec(substr($hex, 0, 2)) / 255;
        $color[] = hexdec(substr($hex, 2, 2)) / 255;
        $color[] = hexdec(substr($hex, 4, 2)) / 255;
    }

    return $color;
}

function int_to_date($int)
{
    if (preg_match('/[0-9]{8}/', $int)) {
        return substr($int, 4, 4) . '-' . substr($int, 2, 2) . '-' . substr($int, 0, 2);
    }
}

function is_cli(): bool
{
    return PHP_SAPI === 'cli';
}

function is_valid_json(string $string): bool
{
    $json = json_decode($string);

    return $json && $string != $json;
}

function ml_array_merge_recursive(array $defaultArray, array $arrayToMerge): array
{
    $newArray = $defaultArray;

    if (is_array($arrayToMerge)) {
        foreach ($arrayToMerge as $k => $v) {
            if (isset($defaultArray[$k])) {
                if (is_array($defaultArray[$k])) {
                    $newArray[$k] = ml_array_merge_recursive($defaultArray[$k], $arrayToMerge[$k]);
                } else {
                    $newArray[$k] = $arrayToMerge[$k];
                }
            } else {
                $newArray[$k] = $arrayToMerge[$k];
            }
        }
    }

    return $newArray;
}

function ml_decrypt(string $encrypted, string $key): string
{
    $decoded = base64_decode($encrypted);
    $key = str_repeat('Q', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

    if (false === $decoded) {
        throw new \Exception('Scream bloody murder, the encoding failed');
    }

    if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
        throw new \Exception('Scream bloody murder, the message was truncated');
    }

    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

    $plain = sodium_crypto_secretbox_open(
        $ciphertext,
        $nonce,
        $key
    );

    if (false === $plain) {
        throw new \Exception('the message was tampered with in transit');
    }

    sodium_memzero($ciphertext);
    sodium_memzero($key);

    return $plain;
}

function ml_encrypt(string $message, string $key): string
{
    $key = str_repeat('Q', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    $cipher = base64_encode(
        $nonce .
            sodium_crypto_secretbox(
                $message,
                $nonce,
                $key
            )
    );

    sodium_memzero($message);
    sodium_memzero($key);

    return $cipher;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function remove_non_numbers(?string $str): string
{
    if (is_null($str)) {
        return null;
    }

    $er = '/[^\d]/';

    return preg_replace($er, '', $str);
}

function remove_accents(?string $str)
{
    if (is_null($str)) {
        return $str;
    }

    $a = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή'];
    $b = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'ss', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η'];

    return str_replace($a, $b, $str);
}

function space_right_pad($string, $size)
{
    return str_pad($string, $size, ' ', STR_PAD_RIGHT);
}

function string_to_float(string $value): float
{
    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);

    if ('' == $value) {
        return null;
    }

    return $value;
}

function to_int(string $number): int
{
    if ('' != $number) {
        return +$number;
    }
}

function to_lower_camel_case(string $string): string
{
    $frag = explode('_', strtolower($string));
    $count = count($frag);
    $newString = $frag[0];

    for ($i = 1; $i < $count; ++$i) {
        $newString .= ucfirst($frag[$i]);
    }

    return $newString;
}

function parse_null(?string $value)
{
    return '' === $value ? null : $value;
}

function to_singular(string $string): string
{
    if ('status' == strtolower($string)) {
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

    if (preg_match('/tchs$/', $string)) {
        $string = substr($string, 0, -1);
    }

    if (preg_match('/[acdeiouglmnprt]s$/', $string)) {
        $string = substr($string, 0, -1);
    }

    return $string;
}

function to_upper_camel_case(string $string): string
{
    return ucfirst(to_lower_camel_case($string));
}

function today(): string
{
    return date('Y-m-d');
}

function upper_separator($string, $separator)
{
    $len = strlen($string);
    $res = '';

    for ($i = 0; $i < $len; ++$i) {
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

/**
 * Função de redirecionamento de página.
 *
 * @param $url Url para onde a página será redirecionada
 */
function url_redirect($url): void
{
    if (!headers_sent()) {
        header("Location: {$url}");
    } else {
        echo "<meta HTTP-EQUIV='Refresh' CONTENT='0;URL={$url}'>";
        echo "<script>top.document.location='{$url}';</script>";
    }

    exit;
}

function url_to_method($url)
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

function zero_left_pad($number, int $length): string
{
    if (is_numeric($number)) {
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }

    return $number;
}

<?php
/**
 * General validation.
 *
 * @version 1.1.0
 */

namespace MonitoLib;

use DateTime;

class Validator
{
    public static function cnpj($cnpj)
    {
        $cnpj = remove_non_numbers($cnpj);

        if (strlen($cnpj) === 14) {
            return false;
        }

        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // First checksum
        for ($i = 0, $j = 5, $soma = 0; $i < 12; ++$i) {
            $soma += $cnpj[$i] * $j;
            $j = (2 == $j) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        if ($cnpj[12] !== ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }

        // Second checksum
        for ($i = 0, $j = 6, $soma = 0; $i < 13; ++$i) {
            $soma += $cnpj[$i] * $j;
            $j = (2 == $j) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        return $cnpj[13] === ($resto < 2 ? 0 : 11 - $resto);
    }

    public static function cpf($cpf)
    {
        $cpf = remove_non_numbers($cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Checksum
        for ($t = 9; $t < 11; ++$t) {
            for ($d = 0, $c = 0; $c < $t; ++$c) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ($cpf[$c] !== $d) {
                return false;
            }
        }

        return true;
    }

    public static function date($date, string $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    public static function email($email)
    {
        return (bool) preg_match('/(\\w[-._\\w]*\\w@\\w[-._\\w]*\\w\\.\\w{2,3})/', $email);
    }
}

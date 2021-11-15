<?php

namespace MonitoLib\Database;

use \MonitoLib\Exception\InvalidModel;

class Validator
{
    const VERSION = '1.0.0';
    /**
     * 1.0.0 - 2021-07-20
     * Initial release
     */

    private function date($column, $value): array
    {
        $errors = [];
        $id     = $column->getId();
        // $format = $column->getFormat();


        if (!($value instanceof \MonitoLib\Type\DateTime)) {
            $errors[] = "Data/hora inválida para o campo {$id}: $value";
        }

        // if ($value === '') {
        //     $errors[] = "Campo inválida para o campo {$id}: $value";
        // } else {
        //     if (!is_null($value) && !\MonitoLib\Validator::date($value, $format)) {
        //         if ($format === 'Y-m-d') {
        //             $errors[] = "Data inválida para o campo {$id}: $value";
        //         } else {
        //             $errors[] = "Data/hora inválida para o campo {$id}: $value";
        //         }
        //     }
        // }

        return $errors;
    }
    private function number($column, $value): array
    {
        $errors    = [];
        $id        = $column->getId();
        $type      = $column->getType();
        $maxValue  = $column->getMaxValue();
        $minValue  = $column->getMinValue();
        $auto      = $column->getAuto();
        $maxLength = $column->getMaxLength();
        $minLength = $column->getMinLength();
        $vType     = gettype($value);
        $length    = mb_strlen($value);

        if ($type === 'int' && !is_numeric($value) && !$auto) {
            $errors[] = "O campo {$id} espera um número inteiro e {$vType} foi informado";
        }

        if ($type === 'float' && !is_float($value)) {
            $errors[] = "O campo {$id} espera um número decimal e {$vType} foi informado";
        }

        // if (is_numeric($value)) {
        // Verifica o valor máximo do campo
        if ($maxValue > 0 && $value > $maxValue) {
            $errors[] = "O valor máximo do campo {$id} é {$maxValue} mas {$value} foi informado";
        }

        // Verifica o tamanho mínimo do campo
        if ($minValue > 0 && $value > $minValue) {
            $errors[] = "O valor mínimo do campo {$id} é {$minValue} mas {$value} foi informado";
        }
        // }

        // Verifica o tamanho máximo do campo
        if ($maxLength > 0 && $length > $maxLength) {
            $errors[] = "O tamanho máximo do campo {$id} é {$maxLength} mas {$length} foi informado";
        }

        // Verifica o tamanho mínimo do campo
        if ($minLength > 0 && $length < $minLength) {
            $errors[] = "O tamanho mínimo do campo {$id} é {$minLength} mas {$length} foi informado";
        }

        return $errors;
    }
    private function string($column, $value): array
    {
        $errors = [];
        $id     = $column->getId();
        return $errors;
    }
    public function validate(object $dto, object $model): void
    {
        // \MonitoLib\Dev::pre($dto);
        $errors  = [];
        $columns = $model->getColumns();

        foreach ($columns as $column) {
            $id        = $column->getId();
            $auto      = $column->getAuto();
            $type      = $column->getType();
            // $format    = $column->getFormat();
            $required  = $column->getRequired();
            $default   = $column->getDefault();
            $maxLength = $column->getMaxLength();
            $minLength = $column->getMinLength();
            $maxValue  = $column->getMaxValue();
            $minValue  = $column->getMinValue();
            $get       = 'get' . ucfirst($id);
            $value     = $dto->$get();
            // $length    = mb_strlen($value);
            $vType     = gettype($value);
            $isNull    = is_null($value);
            $isEmpty   = $value === '';

            // Valida os campos requeridos
            if ($isNull && !$required) {
                continue;
            }

            if ($required && ($isNull || $isEmpty)) {
                if (!$auto && (($isNull || $isEmpty) && is_null($default))) {
                    $errors[] = "O campo {$id} é requerido";
                }
            }

            switch ($type) {
                case 'date':
                case 'datetime':
                case 'time':
                    $value = new \MonitoLib\Type\DateTime($value);
                    $e = $this->date($column, $value);
                    break;
                case 'double':
                case 'float':
                case 'int':
                    $e = $this->number($column, $value);
                    break;
                default:
                    $e = $this->string($column, $value);
            }

            if (!empty($e)) {
                // \MonitoLib\Dev::pre($e);
                $errors = array_merge($errors, $e);
            }

            // \MonitoLib\Dev::ee('validator');




            // if (is_null($value) || $value === '') {
            // } else {
            //     // Verifica se o campo é do tipo esperado
            //     if ($type === 'int' || $type === 'double') {
            //         if ($type === 'int' && !is_numeric($value) && !$auto) {
            //             $errors[] = "O campo {$id} espera um número inteiro e {$vType} foi informado";
            //         }

            //         if ($type === 'float' && !is_float($value)) {
            //             $errors[] = "O campo {$id} espera um número decimal e {$vType} foi informado";
            //         }

            //         if (is_numeric($value)) {
            //             // Verifica o valor máximo do campo
            //             if ($maxValue > 0 && $value > $maxValue) {
            //                 $errors[] = "O valor máximo do campo {$id} é {$maxValue} mas {$value} foi informado";
            //             }

            //             // Verifica o tamanho mínimo do campo
            //             if ($minValue > 0 && $value > $minValue) {
            //                 $errors[] = "O tamanho mínimo do campo {$id} é {$minValue} mas {$value} foi informado";
            //             }
            //         }
            //     }

            //     if (in_array($type, ['date', 'datetime']) && !Validator::date($value, $format)) {
            //         if ($format === 'Y-m-d') {
            //             $errors[] = "Data inválida para o campo {$id}: $value";
            //         } else {
            //             $errors[] = "Data/hora inválida para o campo {$id}: $value";
            //         }
            //     }

            //     // Verifica o tamanho máximo do campo
            //     if ($maxLength > 0 && $length > $maxLength) {
            //         $errors[] = "O tamanho máximo do campo {$id} é {$maxLength} mas {$length} foi informado";
            //     }

            //     // Verifica o tamanho mínimo do campo
            //     if ($minLength > 0 && $length < $minLength) {
            //         $errors[] = "O tamanho mínimo do campo {$id} é {$minLength} mas {$length} foi informado";
            //     }
            // }
        }

        // \MonitoLib\Dev::pre($errors);

        if (!empty($errors)) {
            throw new InvalidModel('Há erros na validação: ' . implode(' | ', $errors), $errors);
        }
    }
}

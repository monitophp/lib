<?php
/**
 * Logger.
 *
 * @version 1.0.1
 */

namespace MonitoLib;

use MonitoLib\Exception\InternalErrorException;

class Logger
{
    private $echoLog = false;
    private $outputFile;

    public function __construct($outputFile = null)
    {
        if (is_null($outputFile)) {
            $this->outputFile = App::getLogPath() . 'general.log';
        } else {
            if (is_dir($outputFile)) {
                throw new InternalErrorException("Invalid log file: {$outputFile}");
            }

            if (file_exists($outputFile)) {
                $this->outputFile = $outputFile;
            } else {
                if (preg_match('/[a-zA-Z0-9.-_]/', $outputFile)) {
                    $this->outputFile = App::getLogPath() . $outputFile;
                } else {
                    throw new InternalErrorException("Invalid log file: {$outputFile}");
                }
            }
        }
    }

    public function log($text, $echo = false, $breakLine = true, $timeStamp = true)
    {
        if ($timeStamp) {
            $text = now() . ': ' . $text;
        }

        if ($breakLine) {
            $text .= "\r\n";
        }

        if ($this->echoLog || $echo) {
            echo $text;
        }

        if (!error_log($text, 3, $this->outputFile)) {
            throw new InternalErrorException('Failed to log: ' . $this->outputFile . "\r\n");
        }
    }

    public function setOutputFile($outputFile)
    {
        $this->outputFile = $outputFile;

        return $this;
    }
}

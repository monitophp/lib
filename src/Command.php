<?php
/**
 * Command.
 *
 * @version 1.0.0
 */

namespace MonitoLib;

use MonitoLib\Exception\InternalErrorException;
use MonitoLib\Exception\LockedException;

class Command
{
    private $canUnlock = false;
    private $lockFile;
    private $lockTimeout = 3600;
    private $options = [];
    private $params = [];

    public function getOption($option)
    {
        return $this->options[$option] ?? null;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getParam($index)
    {
        return $this->params[$index] ?? null;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function lock(int $timeout = 0, string $suffix = ''): void
    {
        if ($timeout > 0) {
            $this->lockTimeout = $timeout;
        }

        $db = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

        $this->lockFile = App::getTmpPath()
            . preg_replace('/[^a-zA-Z]/', '-', substr($db[0]['file'], 0, -4))
            . '-'
            . $db[1]['function']
            . $suffix
            . '.lock';

        $tol = 0;

        if (file_exists($this->lockFile)) {
            $now = time();
            $fct = filectime($this->lockFile);
            $tol = $now - $fct;
            $min = floor($tol / 60);
            $sec = zero_left_pad($tol % 60, 2);
            $time = $min . 'min' . $sec . 'sec';

            if ($tol <= $this->lockTimeout) {
                throw new LockedException("Another instance is running for {$time}");
            }
        }

        if ($tol === 0 || $tol > $this->lockTimeout) {
            if (@!touch($this->lockFile)) {
                throw new InternalErrorException('Lock file cannot be created');
            }

            $this->canUnlock = true;
        }
    }

    public function run()
    {
        $this->parse();

        return $this;
    }

    public function unlock(): void
    {
        if ($this->canUnlock) {
            if (file_exists($this->lockFile)) {
                if (@!unlink($this->lockFile)) {
                    throw new InternalErrorException('Lock file ' . basename($this->lockFile) . ' cannot be deleted');
                }
            }
        }
    }

    private function parse()
    {
        $argv = array_slice($GLOBALS['argv'], 2);
        $params = [];
        $options = [];
        $obj = null;
        $val = null;

        foreach ($argv as $arg) {
            if (preg_match('/^\-/', $arg)) {
                $obj = 'option';
                $val = trim($arg, '-');
                $options[$val] = true;
            } else {
                if ('option' === $obj) {
                    $options[$val] = trim($arg, '\\');
                    $obj = 'argument';
                } else {
                    $params[] = $arg;
                    $obj = 'param';
                }
            }
        }

        $this->params = $params;
        $this->options = $options;
    }
}

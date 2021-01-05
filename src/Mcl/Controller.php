<?php
namespace MonitoLib\Mcl;

use \MonitoLib\App;
use \MonitoLib\Exception\Locked;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Controller
{
    const VERSION = '1.0.1';
    /**
    * 1.0.2 - 2021-01-05
    * new: Suffix in lock method
    *
    * 1.0.1 - 2020-08-06
    * new: Locked and InternalError exceptions
    *
    * 1.0.0 - 2020-03-09
    * initial release
    */

    protected $request;
    protected $response;

    private $canUnlock = false;
    private $lockFile;
    private $lockWarning = 600;
    private $lockTimeout = 3600;
    private $lockTime = 0;

    public function __construct()
    {
        $this->request  = \MonitoLib\Mcl\Request::getInstance();
        $this->response = \MonitoLib\Mcl\Response::getInstance();
    }
    public function input(string $text) : string
    {
        $return = '';
        while ($return === '') {
            $return = readline($text . ' ');
        }
        return $return;
    }
    public function lock(int $warning = 0, int $timeout = 0, $suffix = '') : void
    {
        if ($warning > 0) {
            $this->lockWarning = $warning;
        }

        if ($timeout > 0) {
            if ($warning >= $timeout) {
                throw new \Exception('O tempo de warning deve ser menor que o de timeout');
            }

            $this->lockTimeout = $timeout;
        }

        $db = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

        $search = [
            App::getDocumentRoot(),
            '/',
            '\\'
        ];

        $replace = [
            '',
            '-',
            '-',
        ];

        $this->lockFile = App::getTmpPath()
            . str_replace($search, $replace, substr($db[0]['file'], 0, -4))
            . '-'
            . $db[1]['function']
            . $suffix
            . '.lock';

        $tol = 0;

        if (file_exists($this->lockFile)) {
            $now = time();
            $fct = filectime($this->lockFile);
            $tol = $now - $fct;

            // if ($tol > $this->lockWarning) {
                // TODO: criar central de notificações pra lançar
            // }

            // TODO: criar função de formatação para horas
            $min  = floor($tol / 60);
            $sec  = Functions::zeroLeftPad($tol % 60, 2);
            $time = $min . 'min' . $sec . 'sec';

            if ($tol <= $this->lockTimeout) {
                throw new Locked("Já existe um processo sendo executado há $time");
            }
        }

        if ($tol === 0 || $tol > $this->lockTimeout) {
            if (@!touch($this->lockFile)) {
                throw new InternalError('Não foi possível criar o lock do arquivo. Verifique as permissões');
            }

            $this->canUnlock = true;
        }
    }
    public function question(string $question, bool $defaultAnswer = true)
    {
        $answered = false;
        $answer   = $defaultAnswer;

        while (!$answered) {
            $rl = strtolower(readline($question . ' '));

            if ($rl === '') {
                $answered = true;
            } else {
                $answered = true;

                switch ($rl) {
                    case 'n':
                    case 'nao':
                    case 'no':
                        $answer = false;
                        break;
                    case 's':
                    case 'sim':
                    case 'y':
                    case 'yes':
                        $answer = true;
                        break;
                    default:
                        $answer   = true;
                        $answered = false;
                }
            }
        }

        return $answer;
    }
    public function unlock() : void
    {
        if ($this->canUnlock) {
            if (file_exists($this->lockFile)) {
                if (@!unlink($this->lockFile)) {
                    throw new InternalError('Nao foi possível excluir o arquivo de lock ' . basename($this->lockFile));
                }
            }
        }
    }
}
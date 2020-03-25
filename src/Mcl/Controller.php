<?php
namespace MonitoLib\Mcl;

use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Controller
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2020-03-09
    * initial release
    */

    protected $request;
    protected $response;

    public function __construct ()
    {
        $this->request  = \MonitoLib\Mcl\Request::getInstance();
        $this->response = \MonitoLib\Mcl\Response::getInstance();
    }
    public function question(string $question)
    {
        return readline($question . ' ');
    }
}
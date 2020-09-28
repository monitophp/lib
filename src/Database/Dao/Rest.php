<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Rest // extends Base implements \MonitoLib\Database\Dao
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2020-09-28
    * new: decrypt pass
    *
    * 1.0.0 - 2020-06-09
    * initial release
    */

    protected $dbms = 3;
    private $curl;

    public function curl()
    {
        if (is_null($this->curl)) {
            // Cria uma instância do Curl
            $this->curl = new \MonitoLib\Curl();

            // Busca os dados de conexão
            $connection = \MonitoLib\Database\Connector::getInstance()->getConnection($this->connection);
            $pass = $connection['token'] ?? $connection['pass'];
            $this->curl->setAuthorization(Functions::decrypt($pass, $connection['name'] . $connection['env']));
            $this->curl->setHost($connection['host']);
        }

        if (!is_null($this->baseUrl)) {
            $this->curl->setBaseUrl($this->baseUrl);
        }

        return $this->curl;
    }
    /**
    * delete
    */
    public function delete($url)
    {
        return $this->curl()->delete($url);
    }
    /**
    * get
    */
    public function get($url)
    {
        return $this->curl()->get($url);
    }
    /**
    * post
    */
    public function post($url, $data = '')
    {
        return $this->curl()->post($url, $data);
    }
    /**
    * put
    */
    public function put($url, $data = '')
    {
        return $this->curl()->put($url, $data);
    }
}
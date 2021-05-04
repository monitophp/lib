<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Functions;

class Rest
{
    const VERSION = '1.1.1';
    /**
    * 1.1.1 - 2020-09-28
    * fix: fixes in curl()
    *
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
            $this->curl = new Curl();

            // Busca os dados de conexão
            $connection = \MonitoLib\Database\Connector::getInstance()->getConnection($this->connection);

            $this->curl->setHost($connection['host']);

            if (isset($connection['keys'])) {
                $keys = $connection['keys'];

                if (isset($keys) && is_array($keys) && !empty($keys)) {
                    foreach ($keys as $key => $value) {
                        $this->curl->addHeader($key, $value);
                    }
                }
            } else {
                $pass = $connection['token'] ?? $connection['pass'];
                $this->curl->setAuthorization(Functions::decrypt($pass, $connection['name'] . $connection['env']));
            }
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
    * patch
    */
    public function pacth($url, $data = '')
    {
        return $this->curl()->patch($url, $data);
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
    /**
    * setDebug
    */
    public function setDebug(bool $debug) : object
    {
        $this->curl()->setDebug($debug);
        return $this->curl;
    }
}
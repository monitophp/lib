<?php
/**
 * Curl
 *
 * Curl client
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2020
 *
 * @package \MonitoLib
 */
namespace MonitoLib;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\Conflict;
use \MonitoLib\Exception\Forbidden;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Exception\Locked;
use \MonitoLib\Exception\NotFound;

class Curl
{
    const VERSION = '1.0.2';
    /**
    * 1.0.2 - 2021-02-19
    * fix: scaped url
    *
    * 1.0.1 - 2020-09-28
    * fix: remove fixed header host
    *
    * 1.0.0 - 2020-06-05
    * initial release
    */

    private $baseUrl = '';
    private $curl;
    private $debug = true;
    private $header = [];
    private $host = '';
    private $token;

    public function __construct()
    {
        $this->curl = curl_init();

        curl_setopt_array($this->curl, [
            CURLINFO_HEADER_OUT     => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => 'gzip, deflate, br',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_2_0,
            CURLOPT_SSL_VERIFYHOST  => 0,
            CURLOPT_SSL_VERIFYPEER  => 0,
            CURLOPT_SSH_COMPRESSION => true,
        ]);
    }
    public function addHeader(string $key, string $value)
    {
        $this->header[$key] = $value;
    }
    public function close()
    {
        curl_close($this->curl);
    }
    public function delete(string $url)
    {
        // curl_setopt($this->curl, CURLOPT_URL, $this->host . $this->baseUrl . $url);
        $this->setUrl($url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        return $this->exec('DELETE', $url);
    }
    public function exec(string $method, string $url)
    {
        $this->header = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        if (!is_null($this->token)) {
            $this->header[] = "Authorization: " . $this->token;
        }

        if ($method === 'GET') {
            $this->header[] = 'Content-Length: 0';
        }

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->header);

        $response = curl_exec($this->curl);
        $info     = curl_getinfo($this->curl);
        $httpCode = $info['http_code'];

        // \MonitoLib\Dev::pr($info);
        // \MonitoLib\Dev::pr($response);

        $return = new \stdClass();
        $return->httpCode = $httpCode;
        $return->response = null;

        // TODO: tratar corretamente o debug em caso de erro
        if ($this->debug) {
            $return->debug = new \stdClass();
            $return->debug->info     = $info;
            $return->debug->response = $response;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $return->httpCode = $httpCode;
            $json = json_decode($response);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $json = $response;
            }

            // JSON_ERROR_NONE No error has occurred
            // JSON_ERROR_DEPTH    The maximum stack depth has been exceeded
            // JSON_ERROR_STATE_MISMATCH   Invalid or malformed JSON
            // JSON_ERROR_CTRL_CHAR    Control character error, possibly incorrectly encoded
            // JSON_ERROR_SYNTAX   Syntax error
            // JSON_ERROR_UTF8 Malformed UTF-8 characters, possibly incorrectly encoded    PHP 5.3.3
            // JSON_ERROR_RECURSION    One or more recursive references in the value to be encoded PHP 5.5.0
            // JSON_ERROR_INF_OR_NAN   One or more NAN or INF values in the value to be encoded    PHP 5.5.0
            // JSON_ERROR_UNSUPPORTED_TYPE A value of a type that cannot be encoded was given  PHP 5.5.0
            // JSON_ERROR_INVALID_PROPERTY_NAME    A property name that cannot be encoded was given    PHP 7.0.0
            // JSON_ERROR_UTF16

            $return->response = $json;
        } else {
            $error    = curl_error($this->curl);
            $response = $error === '' ? $response : $error;

            switch ($httpCode) {
                case 400:
                    throw new BadRequest($response);
                case 403:
                    throw new Forbidden($response);
                case 404:
                    throw new NotFound($response);
                case 409:
                    throw new Conflict($response);
                case 423:
                    throw new Locked($response);
                default:
                    throw new InternalError($response);
            }
        }

        return $return;
    }
    public function setAuthorization(string $token)
    {
        // $this->header[] = "Authorization: $token";
        $this->token = $token;
        return $this;
    }
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }
    // public function setHeader($header)
    // {
    //     curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
    //     return $this;
    // }
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
        return $this;
    }
    public function setHost(string $host)
    {
        $this->host = $host;
        return $this;
    }
    public function setUrl(string $url)
    {
        // \MonitoLib\Dev::pre($url);
        $url = str_replace(
            [
                ' ',
                '[',
                ']'
            ],
            [
                '%20',
                '%5B',
                '%5D'
            ],
        $url);

        $url = $this->host . $this->baseUrl . $url;

        curl_setopt($this->curl, CURLOPT_URL, $url);
        return $this;
    }
    public function get(string $url)
    {
        $this->setUrl($url);
        // curl_setopt($this->curl, CURLOPT_URL, $this->host . $this->baseUrl . $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        return $this->exec('GET', $url);
    }
    public function post(string $url, string $data = '')
    {
        $this->setUrl($url);
        // curl_setopt($this->curl, CURLOPT_URL, $this->host . $this->baseUrl . $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        return $this->exec('POST', $url);
    }
    public function put(string $url, string $data = '')
    {
        // $url = $this->host . $this->baseUrl . $url;

        $this->setUrl($url);
        // curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        return $this->exec('PUT', $url);
    }
}
<?php
/**
 * Curl client.
 *
 * @version 1.0.2
 */

namespace MonitoLib;

use MonitoLib\Exception\BadRequestException;
use MonitoLib\Exception\ConflictException;
use MonitoLib\Exception\ForbiddenException;
use MonitoLib\Exception\InternalErrorException;
use MonitoLib\Exception\LockedException;
use MonitoLib\Exception\NotFoundException;
use stdClass;

class Curl
{
    protected $baseUrl = '';
    protected $host;
    private $curl;
    private $debug = true;
    private $header = [];

    public function __construct()
    {
        $this->curl = curl_init();

        curl_setopt_array($this->curl, [
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'gzip, deflate, br',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSH_COMPRESSION => true,
        ]);
    }

    public function addHeader(array $header)
    {
        $this->header = array_merge($this->header, $header);
    }

    public function close()
    {
        curl_close($this->curl);
    }

    public function delete(string $url)
    {
        $this->setUrl($url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $this->exec('DELETE');
    }

    public function exec(string $method)
    {
        $header = $this->header;

        if ('GET' === $method) {
            unset($header['Content-Length']);
        }

        $header = array_map(function ($k, $v) {
            return "{$k}: {$v}";
        }, array_keys($header), $header);

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($this->curl);
        $info = curl_getinfo($this->curl);
        $httpCode = $info['http_code'];

        $return = new stdClass();
        $return->httpCode = $httpCode;
        $return->response = null;

        if ($this->debug) {
            $return->debug = new stdClass();
            $return->debug->info = $info;
            $return->debug->response = $response;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $return->httpCode = $httpCode;
            $json = json_decode($response);

            if (JSON_ERROR_NONE !== json_last_error()) {
                $json = $response;
            }

            $return->response = $json;
        } else {
            $error = curl_error($this->curl);
            $response = $error === '' ? $response : $error;

            switch ($httpCode) {
                case 400:
                    throw new BadRequestException($response);

                case 403:
                    throw new ForbiddenException($response);

                case 404:
                    throw new NotFoundException($response);

                case 409:
                    throw new ConflictException($response);

                case 423:
                    throw new LockedException($response);

                default:
                    throw new InternalErrorException($response);
            }
        }

        return $return;
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

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
        $url = str_replace(
            [' ', '[', ']'],
            ['%20', '%5B', '%5D'],
            $url
        );

        $url = $this->host . $this->baseUrl . $url;

        curl_setopt($this->curl, CURLOPT_URL, $url);

        return $this;
    }

    public function get(string $url)
    {
        $this->setUrl($url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');

        return $this->exec('GET');
    }

    public function patch(string $url, string $data = '')
    {
        $this->setUrl($url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        return $this->exec('PATCH');
    }

    public function post(string $url, string $data = '')
    {
        $this->setUrl($url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        return $this->exec('POST');
    }

    public function put(string $url, string $data = '')
    {
        $this->setUrl($url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        return $this->exec('PUT');
    }
}

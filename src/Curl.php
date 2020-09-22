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

class Curl
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2020-06-05
    * initial release
    */

    private $curl;
    private $baseUrl = '';
    private $host = '';
    private $header = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Host: www.ferimport.com.br'
    ];
    private $token;

    public function __construct()
    {
        $this->curl = curl_init();

        curl_setopt_array($this->curl, [
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => 'gzip, deflate, br',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);
    }
    public function close()
    {
        curl_close($this->curl);
    }
    public function delete($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->host . $this->baseUrl . $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        return $this->exec('DELETE', $url);
    }
    public function exec($method, $url)
    {
        // curl_setopt($this->curl, CURLOPT_HTTPHEADER, 0);

        $this->header = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Host: www.ferimport.com.br'
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

        // \MonitoLib\Dev::pr($info);
        // \MonitoLib\Dev::pr($response);

        $return           = new \stdClass();
        $return->httpCode = $info['http_code'];

        $data = null;

        if (curl_errno($this->curl)) {
            $error = curl_error($this->curl);
        } else {
            $json = json_decode($response);

            if (is_null($json)) {
                $error = 'Conteúdo de retorno inválido';
                $return->rawData = $response;
            } else {
                if ($return->httpCode >= 200 && $return->httpCode < 300) {
                    $data = $json;
                } else {
                    $error = $json;
                }
            }
        }

        if (is_null($data)) {
            $return->error = $error;
        } else {
            $return->data = $data;
        }

        return $return;
    }
    public function setAuthorization($token)
    {
        // $this->header[] = "Authorization: $token";
        $this->token = $token;
        return $this;
    }
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }
    // public function setHeader($header)
    // {
    //     curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
    //     return $this;
    // }
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }
    public function setUrl($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->host . $this->baseUrl . $url);
        return $this;
    }
    public function get($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->host . $this->baseUrl . $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        return $this->exec('GET', $url);
    }
    public function post($url, $data = '')
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->host . $this->baseUrl . $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        return $this->exec('POST', $url);
    }
    public function put($url, $data = '')
    {
        $url = $this->host . $this->baseUrl . $url;

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        return $this->exec('PUT', $url);
    }
}
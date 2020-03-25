<?php
/**
 * 1.0.0. - 2017-03-16
 * Inicial release
 */
namespace MonitoLib;

use \MonitoLib\App;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Exception\NotFound;
use \MonitoLib\Functions;

class Router
{
    const VERSION = '1.0.2';
    /**
    * 1.0.1 - 2019-09-20
    * fix: minor fixes
    *
    * 1.0.1 - 2019-05-02
    * fix: checks OPTIONS request method on check function
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    static private $routes = [];

    private static function add ($method, $url, $action, $secure = true)
    {
        $parts = explode('/', trim($url, '/'));

        $routes = [];
        $cf = null;
        $af = null;

        $len = count($parts) - 1;

        for ($i = $len; $i >= 0; $i--) {
            $index = $parts[$i];

            if ($i == $len) {
                $af[$index] = [
                    '@' => [
                        $method => $action . ($secure ? '+' : '')
                    ]
                ];
            } else {
                $af[$index] = $cf;
            }

            $cf = $af;
            $af = [];
        }

        self::$routes = Functions::arrayMergeRecursive(self::$routes, $cf);
    }
    public static function cli ($url, $action, $secure = true)
    {
        self::add('CLI', $url, $action, $secure);
    }
    public static function get ($url, $action, $secure = true)
    {
        self::add('GET', $url, $action, $secure);
    }
    public static function patch ($url, $action, $secure = true)
    {
        self::add('PATCH', $url, $action, $secure);
    }
    public static function post ($url, $action, $secure = true)
    {
        self::add('POST', $url, $action, $secure);
    }
    public static function put ($url, $action, $secure = true)
    {
        self::add('PUT', $url, $action, $secure);
    }
    public static function delete ($url, $action, $secure = true)
    {
        self::add('DELETE', $url, $action, $secure);
    }
    static private function error ($message)
    {
        return $json = [
            'code'    => '1',
            'message' => $message
            ];
    }
    static public function check ($request)
    {
        $cli = PHP_SAPI === 'cli';

        // Se a requisição for do tipo OPTIONS não verifica a rota
        if (!$cli && ($_SERVER['REQUEST_METHOD'] ?? 'OPTIONS') === 'OPTIONS') {
        // if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            // \MonitoLib\Dev::ee($_SERVER['REQUEST_METHOD'] ?? 'OPTIONS');
            exit;
        }

        if (PHP_SAPI == 'cli') {
            $requestMethod = 'CLI';
            $params = $request->getParam();
        } else {
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            $params = [];
        }

        $uri = trim($request->getRequestUri(), '/');

        // $file = str_replace('/', '.', $uri) . '.routes.php';

        // while (!file_exists($file)) {
        //     echo $file . '<br />';
        //     $file =
        // }

        // \MonitoLib\Dev::ee($file);

        $uriParts = explode('/', trim($request->getRequestUri(), '/'));
        $file     = App::getRoutesPath() . 'routes.php';
        $filename = App::getRoutesPath();

        foreach ($uriParts as $part) {
            $filename .= $part . '.';

            if (file_exists($filename . 'routes.php')) {
                $file = $filename . 'routes.php';
            } else {
                // Se o arquivo não existe, cancela a verificação e usa o último arquivo encontrado ou o base
                break;
            }
        }

        // \MonitoLib\Dev::ee($file);

        require_once $file;

        // Verifica se existe arquivo de rota específico

        // $dgrc = new \MonitoMkr;

        if ($cli) {
            // Verifica se existe o package MonitoMkr

            $ovcuc = 'MonitoMkr\cli\Import';

            if (class_exists('MonitoMkr\cli\Import')) {
                \MonitoLib\Dev::pre('isisti!');
            } else {
                \MonitoLib\Dev::pre('num isisti!');
            }

        }


        // \MonitoLib\Dev::pre(self::$routes);

        $currentArray = [];

        $action = true;

        if (!isset(self::$routes)) {
            throw new InternalError('Não há rotas configuradas!');
        }

        $ri = self::$routes;

        $cParts = count($uriParts);
        $i = 1;

        $matched = false;
        $xPart = '';

        foreach ($uriParts as $uriPart) {
            $xPart = $uriPart;
            // Verifica se a parte casa
            if (isset($ri[$uriPart])) {
                $matched = true;
            } else {
                foreach ($ri as $key => $value) {
                    if (preg_match('/:\{.*\}/', $key)) {
                        $key1 = substr($key, 2, -1);

                        if (preg_match("/$key1/", $uriPart)) {
                            $matched = true;
                            $xPart = $key;
                            $params[] = $uriPart;
                            // exit;
                            break;
                        }
                    }
                }
            }

            // Se a parte da URL não for a última, continua comparando
            if ($cParts !== $i) {
                $matched = false;

                if (!isset($ri[$xPart])) {
                    break;
                }

                $ri = $ri[$xPart];
                $i++;
                continue;
            }
        }

        // Se a url foi encontrada
        if (!$matched) {
            throw new NotFound('Rota não configurada!');
        }

        $xM = $requestMethod;

        if (!isset($ri[$xPart]['@'][$requestMethod])) {
            if (isset($ri[$xPart]['@']['*'])) {
                $xM = '*';
            } else {
                http_response_code(405);
                throw new \Exception('Método HTTP não permitido!', 405);
            }
        }

        if (isset($ri[$xPart]['@'][$xM])) {
            $action = $ri[$xPart]['@'][$xM];
            $parts  = explode('@', $action);
            $class  = $parts[0];
            $method = $parts[1];
            $secure = false;

            if (substr($method, -1) === '+') {
                $secure = true;
                $method = substr($method, 0, -1);
            }

            if (class_exists($class)) {
                if (is_callable([$class, $method])) {
                    $router = new \StdClass;
                    $router->class    = $class;
                    $router->method   = $method;
                    $router->params   = $params;
                    $router->isSecure = $secure;
                    return $router;
                } else {
                    throw new NotFound('Método do controller não encontrado!');
                }
            } else {
                throw new NotFound("Controller $class não encontrado!");
            }
        } else {
            throw new NotFound('Ação não encontrada!');
        }
    }
}
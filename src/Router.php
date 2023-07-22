<?php
/**
 * Router.
 *
 * @version 2.0.0
 */

namespace MonitoLib;

use Exception;
use MonitoLib\Exception\InternalErrorException;
use MonitoLib\Exception\NotFoundException;
use stdClass;

class Router
{
    private static $routes = [];

    public static function check()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $params = [];

        $uriParts = explode('/', trim(Request::getRequestUri(), '/'));
        $filename = implode('.', $uriParts);
        $filepath = App::getRoutesPath() . $filename . '.php';
        $count = count($uriParts);

        $i = 0;

        while (!file_exists($filepath) && $i < $count) {
            $filepath = App::getRoutesPath() . $filename . '.php';
            $filename = substr($filename, 0, strrpos($filename, '.'));
            $i++;
        }

        if (!file_exists($filepath)) {
            $filepath = App::getRoutesPath() . 'default.php';

            if (!file_exists($filepath)) {
                throw new InternalErrorException('Route file not found');
            }
        }

        require_once $filepath;

        $action = true;

        if (!isset(self::$routes)) {
            throw new InternalErrorException('Empty file routes');
        }

        $ri = self::$routes;

        $cParts = count($uriParts);
        $i = 1;

        $matched = false;
        $xPart = '';

        foreach ($uriParts as $uriPart) {
            $xPart = $uriPart;

            if (isset($ri[$uriPart])) {
                $matched = true;
            } else {
                foreach ($ri as $key => $value) {
                    if (preg_match('/:\{.*\}/', $key)) {
                        $key1 = substr($key, 2, -1);

                        if (preg_match("/{$key1}/", $uriPart)) {
                            $matched = true;
                            $xPart = $key;
                            $params[] = $uriPart;
                            break;
                        }
                    }
                }
            }

            if ($cParts !== $i) {
                $matched = false;

                if (!isset($ri[$xPart])) {
                    break;
                }

                $ri = $ri[$xPart];
                ++$i;

                continue;
            }
        }

        if (!$matched) {
            throw new NotFoundException('Not found route');
        }

        $xM = $requestMethod;

        if (!isset($ri[$xPart]['@'][$requestMethod])) {
            if (isset($ri[$xPart]['@']['*'])) {
                $xM = '*';
            } else {
                http_response_code(405);

                throw new Exception("Not allowed method: {$requestMethod}", 405);
            }
        }

        if (isset($ri[$xPart]['@'][$xM])) {
            $action = $ri[$xPart]['@'][$xM];
            $parts = explode('@', $action);
            $class = $parts[0];
            $method = $parts[1];
            $secure = false;

            if ('+' === substr($method, -1)) {
                $secure = true;
                $method = substr($method, 0, -1);
            }

            if (class_exists($class)) {
                $router = new stdClass();
                $router->class = $class;
                $router->method = $method;
                $router->params = $params;
                $router->isSecure = $secure;

                return $router;
            }

            throw new NotFoundException("Controller not found: {$class}");
        } else {
            throw new NotFoundException("Mehotd not found: {$action}");
        }
    }

    public static function delete($url, $className, $classMethod, $secure = true)
    {
        self::add('DELETE', $url, $className, $classMethod, $secure);
    }

    public static function get($url, $className, $classMethod, $secure = true)
    {
        self::add('GET', $url, $className, $classMethod, $secure);
    }

    public static function patch($url, $className, $classMethod, $secure = true)
    {
        self::add('PATCH', $url, $className, $classMethod, $secure);
    }

    public static function post($url, $className, $classMethod, $secure = true)
    {
        self::add('POST', $url, $className, $classMethod, $secure);
    }

    public static function put($url, $className, $classMethod, $secure = true)
    {
        self::add('PUT', $url, $className, $classMethod, $secure);
    }

    private static function add($method, $url, $className, $classMethod, $secure = true)
    {
        $parts = explode('/', trim($url, '/'));

        $cf = null;
        $af = null;

        $len = count($parts) - 1;

        for ($i = $len; $i >= 0; --$i) {
            $index = $parts[$i];

            if ($i == $len) {
                $af[$index] = [
                    '@' => [
                        $method => $className . '@' . $classMethod . ($secure ? '+' : ''),
                    ],
                ];
            } else {
                $af[$index] = $cf;
            }

            $cf = $af;
            $af = [];
        }

        self::$routes = ml_array_merge_recursive(self::$routes, $cf);
    }
}

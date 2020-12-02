<?php
namespace MonitoLib;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;
use \MonitoLib\Request;
use \MonitoLib\Response;

class App
{
    const VERSION = '1.3.0';
    /**
    * 1.3.0 - 2020-09-18
    * new: env properties and methods
    * new: removed __construct(), getInstance()
    *
    * 1.2.2 - 2020-03-02
    * new: env properties and methods
    *
    * 1.2.1 - 2020-03-02
    * new: getRoutesPath
    *
    * 1.2.0 - 2019-09-12
    * new: today
    *
    * 1.1.0 - 2019-06-05
    * new: createPath, getDocumentRoot
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    const DS = DIRECTORY_SEPARATOR;

    static private $cachePath;
    static private $configPath;
    static private $debug = 0;
    static private $env = 'dev';
    static private $hasPrivileges = false;
    static private $isLoggedIn = false;
    static private $logPath;
    static private $routesPath;
    static private $storagePath;
    static private $tmpPath;
    static private $userId;
    static private $username;

    public static function createPath($path)
    {
        if (!file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                throw new InternalError("Erro ao criar o diretório $path");
            }
        }

        return $path;
    }
    public static function getDebug()
    {
        return self::$debug;
    }
    public static function getDocumentRoot()
    {
        if (PHP_SAPI === 'cli') {
            $dr = substr(__DIR__, 0, strripos(__DIR__, 'vendor') - 1);
        } else {
            $dr = $_SERVER['DOCUMENT_ROOT'];
        }

        return $dr . self::DS;
    }
    public static function getEnv()
    {
        return self::$env;
    }
    public static function getCachePath($relativePath = null)
    {
        return self::getPath('cache', $relativePath);
    }
    public static function getConfigPath($relativePath = null)
    {
        return self::getPath('config', $relativePath);
    }
    public static function getLogPath($relativePath = null)
    {
        return self::getPath('log', $relativePath);
    }
    private static function getPath($directory, $relativePath = null)
    {
        $directoryPath = $directory . 'Path';

        if (is_null(self::$$directoryPath)) {
            $path = MONITOLIB_ROOT_PATH . $directory . DIRECTORY_SEPARATOR;

            if (!file_exists($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new InternalError("Erro ao criar o diretório $path");
                }
            }

            self::$$directoryPath = $path;
        }

        if (!is_null($relativePath)) {
            $relativePath = self::$$directoryPath . $relativePath . DIRECTORY_SEPARATOR;

            if (!file_exists($relativePath)) {
                if (!mkdir($relativePath, 0755, true)) {
                    throw new InternalError("Erro ao criar o diretório $relativePath");
                }
            }

            return $relativePath;
        }

        return self::$$directoryPath;
    }
    public static function getRoutesPath($relativePath = null)
    {
        return self::getPath('routes', $relativePath);
    }
    public static function getStoragePath($relativePath = null)
    {
        return self::getPath('storage', $relativePath);
    }
    public static function getTmpPath($relativePath = null)
    {
        return self::getPath('tmp', $relativePath);
    }
    public static function getUserId()
    {
        if (is_null(self::$userId)) {
            throw new BadRequest('Usuário não logado na aplicação');
        }

        return self::$userId;
    }
    public static function getUsername()
    {
        if (is_null(self::$userId)) {
            throw new BadRequest('Usuário não logado na aplicação');
        }

        return self::$username;
    }
    public static function now()
    {
        return date('Y-m-d H:i:s');
    }
    public static function run()
    {
        try {
            $uri = $_SERVER['REQUEST_URI'];

            // Removes site from url
            if (substr($uri, 0, strlen(MONITOLIB_ROOT_URL)) == MONITOLIB_ROOT_URL) {
                $uri = substr($uri, strlen(MONITOLIB_ROOT_URL));
            }

            // Splits query string from url
            $uri = explode('?', $uri);

            Request::setRequestUri($uri[0]);

            // Config file
            if (file_exists($config = self::getConfigPath() . 'config.php')) {
                require $config;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                exit;
            }

            // Requires an app init file, if exists
            if (file_exists($init = self::getConfigPath() . 'init.php')) {
                require $init;
            }

            if (isset($uri[1])) {
                Request::setQueryString($uri[1]);
            }

            $return = [];
            $router = \MonitoLib\Router::check();

            if ($router->isSecure) {
                if (!self::$isLoggedIn) {
                    http_response_code(401);
                    throw new \Exception('Usuário não logado!', 401);
                }

                if (!self::$hasPrivileges) {
                    http_response_code(403);
                    throw new \Exception('Usuário sem permissão para acessar o recurso!', 403);
                }
            }

            http_response_code(500);

            $class  = $router->class;
            $method = $router->method;
            $class  = new $class;
            $return = $class->$method(...$router->params);

            if (!is_null($return)) {
                if (!($return instanceof \stdClass)) {
                    $return = Response::toArray($return);
                }
            }

            // $httpCode = Response::getHttpResponseCode() ?? 200;
            $httpCode = 200;
            $erro = [];
            // $httpCode = 500;
            // \MonitoLib\Dev::pr($e);
            // $error['message'] = $e->getMessage();
            // $error['debug']['errors'] = $e->getErrors();
        } catch (\MonitoLib\Exception\DatabaseError $e) {
            $httpCode = $e->getCode();
            $return['message'] = $e->getMessage();

            if (self::getDebug() > 1) {
                if (method_exists($e, 'getErrors') && !empty($e->getErrors())) {
                    $return['errors'] = $e->getErrors();
                }
            }
        } catch (\Exception | \ThrowAble $e) {
            $httpCode = $e->getCode();
            $return['message'] = $e->getMessage();

            if (method_exists($e, 'getErrors') && !empty($e->getErrors())) {
                $return['errors'] = $e->getErrors();
            }
        } finally {
            if (empty($error)) {
                $buffer = Response::render();

                if ($buffer === '') {
                    http_response_code(204);
                } else {
                    echo $buffer;
                }
            } else {
                $return['message'] = $error['message'];

                if (self::getDebug() > 0) {
                    $return['debug']['method'] = $_SERVER['REQUEST_METHOD'];
                    $return['debug']['url']    = Request::getRequestUri();
                    $return['debug']['file'] = $e->getFile();
                    $return['debug']['line'] = $e->getLine();
                }
                if (self::getDebug() > 1) {
                    $return['debug']['trace'] = $e->getTrace();
                }
            }

            header(Response::getContentType());
            http_response_code($httpCode < 100 || $httpCode > 599 ? 500 : $httpCode);

            if (!is_null($return)) {
                try {
                    echo json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR);
                } catch (\Exception | \ThrowAble $e) {
                    echo json_encode(['message' => 'Erro ao codificar o JSON']);
                }
            }
        }
    }
    public static function setDebug($debug)
    {
        if (!is_integer($debug) || $debug < 0 || $debug > 2) {
            throw new InternalError('O nível de debug deve ser 0, 1 ou 2!');
        }

        if ($debug > 0) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        self::$debug = $debug;
    }
    public static function setEnv($env)
    {
        self::$env = $env;
    }
    public static function setHasPrivileges($hasPrivileges)
    {
        self::$hasPrivileges = $hasPrivileges;
    }
    public static function setIsLoggedIn($isLoggedIn)
    {
        self::$isLoggedIn = $isLoggedIn;
    }
    public static function setUserId($userId)
    {
        self::$userId = $userId;
    }
    public static function setUsername($username)
    {
        self::$username = $username;
    }
    public static function today()
    {
        return date('Y-m-d');
    }
}
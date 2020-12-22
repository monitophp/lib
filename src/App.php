<?php
namespace MonitoLib;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use MonitoLib\Exception\NotFound;
use \MonitoLib\Request;
use \MonitoLib\Response;

class App
{
    const VERSION = '1.4.0';
    /**
    * 1.4.0 - 2020-12-21
    * new: $create param in getPath
    * new: typed methods
    * new: refactored run()
    *
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

    public static function createPath(string $path) : string
    {
        if (!file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                throw new InternalError("Erro ao criar o diretório $path");
            }
        }

        return $path;
    }
    public static function getDebug() : int
    {
        return self::$debug;
    }
    public static function getDocumentRoot() : string
    {
        if (PHP_SAPI === 'cli') {
            $dr = substr(__DIR__, 0, strripos(__DIR__, 'vendor') - 1);
        } else {
            $dr = $_SERVER['DOCUMENT_ROOT'];
        }

        return $dr . self::DS;
    }
    public static function getEnv() : string
    {
        return self::$env;
    }
    public static function getCachePath(string $relativePath = null, ?bool $create = true) : string
    {
        return self::getPath('cache', $relativePath, $create);
    }
    public static function getConfigPath(string $relativePath = null, ?bool $create = true) : string
    {
        return self::getPath('config', $relativePath, $create);
    }
    public static function getLogPath(string $relativePath = null, ?bool $create = true) : string
    {
        return self::getPath('log', $relativePath, $create);
    }
    private static function getPath(string $directory, ?string $relativePath = null, ?bool $create = true) : ?string
    {
        $directoryPath = $directory . 'Path';

        if (is_null(self::$$directoryPath)) {
            $path = MONITOLIB_ROOT_PATH . $directory . DIRECTORY_SEPARATOR;

            if (!file_exists($path)) {
                if ($create) {
                    self::createPath($path);
                } else {
                    throw new NotFound("Diretório $path não existe");
                }
            }

            self::$$directoryPath = $path;
        }

        if (!is_null($relativePath)) {
            $relativePath = self::$$directoryPath . $relativePath . DIRECTORY_SEPARATOR;

            if (!file_exists($relativePath)) {
                if ($create) {
                    self::createPath($relativePath);
                } else {
                    throw new NotFound("Diretório $relativePath não existe");
                }
            }

            return $relativePath;
        }

        return self::$$directoryPath;
    }
    public static function getRoutesPath(string $relativePath = null, ?bool $create = true) : string
    {
        return self::getPath('routes', $relativePath, $create);
    }
    public static function getStoragePath(string $relativePath = null, ?bool $create = true) : string
    {
        return self::getPath('storage', $relativePath, $create);
    }
    public static function getTmpPath(string $relativePath = null, ?bool $create = true) : string
    {
        return self::getPath('tmp', $relativePath, $create);
    }
    public static function getUserId()
    {
        if (is_null(self::$userId)) {
            throw new BadRequest('Usuário não logado na aplicação');
        }

        return self::$userId;
    }
    public static function getUsername() : string
    {
        if (is_null(self::$userId)) {
            throw new BadRequest('Usuário não logado na aplicação');
        }

        return self::$username;
    }
    public static function now() : string
    {
        return date('Y-m-d H:i:s');
    }
    public static function run() : void
    {
        try {
            http_response_code(500);
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

            // Requires an app init file, if exists
            if (file_exists($init = self::getConfigPath() . 'init.php')) {
                require $init;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                exit;
            }

            if (isset($uri[1])) {
                Request::setQueryString($uri[1]);
            }

            $router = \MonitoLib\Router::check();
            $debug = [];

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

            $class  = $router->class;
            $method = $router->method;
            $class  = new $class();
            $return = $class->$method(...$router->params);

            if (is_null(Response::getHttpResponseCode())) {
                Response::setHttpResponseCode(200);
            }
        } catch (\Exception | \ThrowAble $e) {
            $httpCode = $e->getCode();
            $return   = $e->getMessage();
            Response::setHttpResponseCode($e->getCode());

            if (method_exists($e, 'getErrors') && !empty($e->getErrors())) {
                $debug['errors'] = $e->getErrors();
            }

            if (self::getDebug() > 1) {
                $debug['file'] = $e->getFile();
                $debug['line'] = $e->getLine();
            }
        } finally {
            if (self::getDebug() > 0) {
                $debug['method'] = $_SERVER['REQUEST_METHOD'];
                $debug['url']    = Request::getRequestUri();
            }

            // Aplica o debug na mensagem, se não estiver vazio
            if (!empty($debug)) {
                Response::setDebug($debug);
            }

            Response::parse($return);
            Response::render();
        }
    }
    public static function setDebug(int $debug) : void
    {
        if ($debug < 0 || $debug > 2) {
            throw new InternalError('O nível de debug deve ser 0, 1 ou 2');
        }

        if ($debug > 0) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        self::$debug = $debug;
    }
    public static function setEnv(string $env) : void
    {
        self::$env = $env;
    }
    public static function setHasPrivileges(bool $hasPrivileges) : void
    {
        self::$hasPrivileges = $hasPrivileges;
    }
    public static function setIsLoggedIn(bool $isLoggedIn) : void
    {
        self::$isLoggedIn = $isLoggedIn;
    }
    public static function setUserId($userId) : void
    {
        self::$userId = $userId;
    }
    public static function setUsername(string $username) : void
    {
        self::$username = $username;
    }
    public static function today() : string
    {
        return date('Y-m-d');
    }
}
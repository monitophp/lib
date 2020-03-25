<?php
namespace MonitoLib;

use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class App
{
    const VERSION = '1.2.1';
    /**
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

    static private $debug = 0;
    static private $instance;
    static private $cachePath;
    static private $configPath;
    static private $hasPrivileges = false;
    static private $isLoggedIn = false;
    static private $logPath;
    static private $routesPath;
    static private $storagePath;
    static private $tmpPath;

    private function __construct ()
    {
    }
    public static function createPath ($path)
    {
        if (!file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                throw new InternalError("Erro ao criar o diretório $path");
            }
        }

        return $path;
    }
    public static function getDebug ()
    {
        return self::$debug;
    }
    public static function getDocumentRoot ()
    {
        if (PHP_SAPI === 'cli') {
            $dr = substr(__DIR__, 0, strripos(__DIR__, 'vendor'));
        } else {
            $dr = $_SERVER['DOCUMENT_ROOT'];
        }

        return $dr . self::DS;
    }
    public static function getInstance ()
    {
        if (!isset(self::$instance)) {
            self::$instance = new \MonitoLib\App;
        }

        return self::$instance;
    }
    private static function getPath ($directory, $relativePath = null)
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
    public static function getCachePath ($relativePath = null)
    {
        return self::getPath('cache', $relativePath);
    }
    public static function getConfigPath ($relativePath = null)
    {
        return self::getPath('config', $relativePath);
    }
    public static function getLogPath ($relativePath = null)
    {
        return self::getPath('log', $relativePath);
    }
    public static function getRoutesPath ($relativePath = null)
    {
        return self::getPath('routes', $relativePath);
    }
    public static function getStoragePath ($relativePath = null)
    {
        return self::getPath('storage', $relativePath);
    }
    public static function getTmpPath ($relativePath = null)
    {
        return self::getPath('tmp', $relativePath);
    }
    public static function now ()
    {
        return date('Y-m-d H:i:s');
    }
    public static function run ()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
            http_response_code(500);
        }

        try {
            // Busca os arquivos de rota
            // foreach (glob(self::getConfigPath() . '*routes.php') as $filename) {
            //     require_once $filename;
            // }

            $uri = $_SERVER['REQUEST_URI'];

            // Removes site from url
            if (substr($uri, 0, strlen(MONITOLIB_ROOT_URL)) == MONITOLIB_ROOT_URL) {
                $uri = substr($uri, strlen(MONITOLIB_ROOT_URL));
            }

            // Splits query string from url
            $uri = explode('?', $uri);

            $request = \MonitoLib\Request::getInstance();
            $request->setRequestUri($uri[0]);

            if (isset($uri[1])) {
                $request->setQueryString($uri[1]);
            }

            // Requires an app init file, if exists
            require self::getConfigPath() . 'init.php';

            $return = [];

            if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
                $router = \MonitoLib\Router::check($request);

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

                $class    = $router->class;
                $method   = $router->method;
                $class    = new $class;
                $return   = $class->$method(...$router->params);
                $response = \MonitoLib\Response::getInstance();
            }
        } catch (\MonitoLib\Exception\DatabaseError $e) {
            $return['error'] = $e->getMessage();
            if (self::getDebug() > 1) {
                if (!empty($e->getErrors())) {
                    $return['debug']['errors'] = $e->getErrors();
                }
            }
        } catch (\ThrowAble $e) {
            $return['error'] = $e->getMessage();
            if (method_exists($e, 'getErrors') && !empty($e->getErrors())) {
                $return['errors'] = $e->getErrors();
            }
        } finally {
            header('Content-Type: application/json');

            if (empty($return['error'])) {
                $response = \MonitoLib\Response::getInstance();
                $buffer = $response->__toString();
                if ($buffer === '') {
                    http_response_code(204);
                } else {
                    echo $buffer;
                }
            } else {
                if (self::getDebug() > 0) {
                    $return['debug']['method'] = $_SERVER['REQUEST_METHOD'];
                    $return['debug']['url']    = $request->getRequestUri();
                }

                if (self::getDebug() > 1) {
                    $return['debug']['file'] = $e->getFile();
                    $return['debug']['line'] = $e->getLine();
                }

                echo json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
    }
    public static function setHasPrivileges ($hasPrivileges)
    {
        self::$hasPrivileges = $hasPrivileges;
    }
    public static function setIsLoggedIn ($isLoggedIn)
    {
        self::$isLoggedIn = $isLoggedIn;
    }
    public static function setDebug ($debug)
    {
        if (!is_integer($debug) || $debug < 0 || $debug > 2) {
            throw new InternalError('O nível de debug deve ser 0, 1 ou 2!');
        }

        self::$debug = $debug;
    }
    public static function today ()
    {
        return date('Y-m-d');
    }
}
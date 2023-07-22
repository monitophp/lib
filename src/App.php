<?php
/**
 * App.
 *
 * @version 1.6.0
 */

namespace MonitoLib;

use MonitoLib\Exception\ForbiddenException;
use MonitoLib\Exception\InternalErrorException;
use MonitoLib\Exception\NotFoundException;
use MonitoLib\Exception\UnauthorizedException;
use Throwable;

class App
{
    public const DS = DIRECTORY_SEPARATOR;

    private static $cachePath;
    private static $configPath;
    private static $debug = 0;
    private static $env = 'dev';
    private static $hasPrivileges = false;
    private static $isLoggedIn = false;
    private static $logPath;
    private static $routesPath;
    private static $storagePath;
    private static $srcPath;
    private static $tmpPath;
    private static $userDto;
    private static $userId;
    private static $username;
    private static $started;

    public static function createPath(string $path): string
    {
        if (!file_exists($path)) {
            if (!@mkdir($path, 0755, true)) {
                throw new InternalErrorException("Could not create directory: {$path}");
            }
        }

        return $path;
    }

    public static function getDebug(): int
    {
        return self::$debug;
    }

    public static function getDocumentRoot(): string
    {
        if (is_cli()) {
            $dr = substr(__DIR__, 0, strripos(__DIR__, 'vendor') - 1);
        } else {
            $dr = $_SERVER['DOCUMENT_ROOT'];
        }

        return $dr . self::DS;
    }

    public static function getEnv(): string
    {
        return self::$env;
    }

    public static function getCachePath(?string $relativePath = null, ?bool $create = true): string
    {
        return self::getPath('tmp', 'cache/' . $relativePath, $create);
    }

    public static function getConfigPath(?string $relativePath = null, ?bool $create = true): string
    {
        return self::getPath('config', $relativePath, $create);
    }

    public static function getLogPath(?string $relativePath = null, ?bool $create = true): string
    {
        return self::getPath('log', $relativePath, $create);
    }

    public static function getProcessTime(): int
    {
        return intval((hrtime(true) - self::$started) / 1e6);
    }

    public static function getRoutesPath(?string $relativePath = null, ?bool $create = true): string
    {
        return self::getPath('routes', $relativePath, $create);
    }

    public static function getSrcPath(?string $relativePath = null, ?bool $create = true): string
    {
        return self::getPath('src', $relativePath, $create);
    }

    public static function getStoragePath(?string $relativePath = null, ?bool $create = true): string
    {
        return self::getPath('storage', $relativePath, $create);
    }

    public static function getTmpPath(?string $relativePath = null, ?bool $create = true): string
    {
        return self::getPath('tmp', $relativePath, $create);
    }

    public static function getUserDto(): App\Dto\User
    {
        self::auth();

        return self::$userDto;
    }

    public static function getUserId()
    {
        self::auth();

        return self::$userId;
    }

    public static function getUsername(): string
    {
        self::auth();

        return self::$username;
    }

    public static function run(): void
    {
        try {
            $debug = [];
            self::$started = hrtime(true);

            http_response_code(500);
            $uri = $_SERVER['REQUEST_URI'];

            if (substr($uri, 0, strlen(MONITOLIB_ROOT_URL)) === MONITOLIB_ROOT_URL) {
                $uri = substr($uri, strlen(MONITOLIB_ROOT_URL));
            }

            $uri = explode('?', $uri);

            Request::setRequestUri($uri[0]);

            if (file_exists($config = self::getConfigPath() . 'config.php')) {
                require $config;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);

                exit;
            }

            // Requires an app init file, if exists
            if (file_exists($init = self::getConfigPath() . 'init.php')) {
                require $init;
            }

            if (isset($uri[1])) {
                Request::setQueryString($uri[1]);
            }

            $router = \MonitoLib\Router::check();

            if ($router->isSecure) {
                if (!self::$isLoggedIn) {
                    http_response_code(401);

                    throw new UnauthorizedException('User not authenticated');
                }

                if (!self::$hasPrivileges) {
                    http_response_code(403);

                    throw new ForbiddenException('Resource not allowed');
                }
            }

            $class = $router->class;
            $method = $router->method;
            $class = new $class();
            $return = $class->{$method}(...$router->params);

            if (is_null(Response::getHttpResponseCode())) {
                Response::setHttpResponseCode(200);
            }
        } catch (Throwable $e) {
            Response::setHttpResponseCode($e->getCode());

            $return = [
                'message' => $e->getMessage(),
            ];

            if (method_exists($e, 'getErrors')) {
                $debug['errors'][0] = $e->{'getErrors'}() ?? [];
            }

            if (self::getDebug() > 1) {
                $debug = array_merge_recursive($debug, [
                    [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                    ...array_map(
                        fn ($e) => [
                            'file' => $e['file'],
                            'line' => $e['line'],
                            'function' => $e['function'],
                        ],
                        $e->getTrace()
                    ),
                ]);
            }
        } finally {
            if (!empty($debug)) {
                Response::setDebug($debug);
            }

            Response::parse($return);
            Response::render();
        }
    }

    public static function runCommand()
    {
        $argv = $GLOBALS['argv'];
        $mac = array_map(fn ($e) => ucfirst(url_to_method($e)), explode(':', $argv[1]));
        $class = "{$mac[0]}\\Commands\\{$mac[1]}";

        // Config file
        if (file_exists($config = self::getConfigPath() . 'config.php')) {
            require $config;
        }

        // Requires an app init file, if exists
        if (file_exists($init = self::getConfigPath() . 'init.php')) {
            require $init;
        }

        try {
            $debug = [];
            $instance = new $class();
            $instance->run()->handler();
        } catch (Throwable $e) {
            print_r($e);
            echo $e->getMessage() . "\n";

            if (self::getDebug() > 1) {
                if (method_exists($e, 'getErrors')) {
                    $debug['errors'][0] = $e->{'getErrors'}() ?? [];
                }

                $debug = ml_array_merge_recursive($debug, [
                    [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                    ...array_map(
                        fn ($e) => [
                            'file' => $e['file'],
                            'line' => $e['line'],
                            'function' => $e['function'],
                        ],
                        $e->getTrace()
                    ),
                ]);

                print_r($debug);
            }
        }
    }

    public static function setDebug(int $debug): void
    {
        if ($debug < 0 || $debug > 2) {
            throw new InternalErrorException('Valid debug levels: 0, 1 or 2');
        }

        if ($debug > 0) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        self::$debug = $debug;
    }

    public static function setConsoleUser(int $userId, string $username)
    {
        $userDto = new \MonitoLib\App\Dto\User();
        $userDto->setUserId($userId)->setUsername($username);

        self::setUserDto($userDto);
        self::setIsLoggedIn(true);
        self::setHasPrivileges(true);
        self::setUserId(348);
    }

    public static function setEnv(string $env): void
    {
        self::$env = $env;
    }

    public static function setHasPrivileges(bool $hasPrivileges): void
    {
        self::$hasPrivileges = $hasPrivileges;
    }

    public static function setIsLoggedIn(bool $isLoggedIn): void
    {
        self::$isLoggedIn = $isLoggedIn;
    }

    public static function setUserDto(App\Dto\User $userDto): void
    {
        self::$userDto = $userDto;
    }

    public static function setUserId($userId): void
    {
        self::$userId = $userId;
    }

    public static function setUsername(string $username): void
    {
        self::$username = $username;
    }

    private static function auth(): void
    {
        if (is_null(self::$userDto) || is_null(self::$userId)) {
            throw new UnauthorizedException('User not authenticated');
        }
    }

    private static function getPath(string $directory, ?string $relativePath = null, ?bool $create = true): ?string
    {
        $directoryPath = $directory . 'Path';

        if (is_null(self::${$directoryPath})) {
            $path = MONITOLIB_ROOT_PATH . $directory . DIRECTORY_SEPARATOR;

            if (!file_exists($path)) {
                if ($create) {
                    self::createPath($path);
                } else {
                    throw new NotFoundException("Directory not found: {$path}");
                }
            }

            self::${$directoryPath} = $path;
        }

        if (!is_null($relativePath)) {
            $relativePath = self::${$directoryPath} . $relativePath . DIRECTORY_SEPARATOR;

            if (!file_exists($relativePath)) {
                if ($create) {
                    self::createPath($relativePath);
                } else {
                    throw new NotFoundException("Directory not found: {$relativePath}");
                }
            }

            return $relativePath;
        }

        return self::${$directoryPath};
    }
}

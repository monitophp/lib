<?php
/**
 * Database\Connector.
 *
 * @version 3.0.0
 */

namespace MonitoLib\Database;

use MonitoLib\Exception\InternalErrorException;

class Connector
{
    private static $active = [];
    private static $configured = [];
    private static $instance;
    private static $params = [];

    public static function addParam(string $connectionName, string $param)
    {
        self::$params[$connectionName] = $param;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new \MonitoLib\Database\Connector();
        }

        return self::$instance;
    }

    public static function getConnection($connectionName = null)
    {
        $name = strtoupper($connectionName ?? 'default');
        $conn = $name === 'DEFAULT' ? '' : "{$name}_" . 'DB_';
        $type = env("{$conn}TYPE");
        $host = env("{$conn}HOST");
        $user = env("{$conn}USER");
        $database = env("{$conn}DATABASE");
        $pass = env("{$conn}PASS");
        $charset = env("{$conn}CHARSET");

        $class = '\MonitoLib\Database\Connector\\' . $type;

        if (!class_exists($class)) {
            throw new InternalErrorException("Invalid connection type: {$type}");
        }

        if (is_null($charset)) {
            $charsets = [
                'Oracle' => 'AL32UTF8',
                'MySQL' => 'UTF8',
            ];

            $charset = $charsets[$type] ?? null;
        }

        if (is_null($type)) {
            throw new InternalErrorException('Database connection not defined: ' . $name);
        }

        if (isset(self::$active[$name])) {
            return self::$active[$name];
        }

        return self::$active[$name] = new $class([
            'type' => $type,
            'host' => $host,
            'user' => $user,
            'database' => $database,
            'pass' => $pass,
            'charset' => $charset,
        ]);
    }

    public static function getConnectionsList(): array
    {
        return self::$configured;
    }

    public static function setConnectionName(string $connectionName): void
    {
        self::$default = $connectionName;
    }

    public static function setConnections(array $connections): void
    {
        self::$configured = $connections;
    }
}

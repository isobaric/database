<?php

namespace Isobaric\Database\Action;

use PDO;

final class Connector
{
    private static array $pool = [];

    private string $domainNameServer = '';

    private string $driver;

    private array $config;

    private string $host;

    private string $port;

    private string $username;

    private string $password;

    private string $database;

    private string $charset;

    private array $options;

    private array $defaultOptions = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        ksort($config);
        $this->config = $config;
        $this->driver = $config['driver'] ?? '';
        $this->host = $config['host'] ?? '';
        $this->port = $config['port'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->database = $config['database'] ?? '';
        $this->charset = $config['charset'] ?? '';
        $this->options = $config['options'] ?? [];
        $this->domainNameServer();
    }

    /**
     * 获取PDO对象
     *
     * @return PDO
     */
    public function connection(): PDO
    {
        $key = $this->uniqueName();
        if (isset(self::$pool[$key])) {
            return self::$pool[$key];
        }

        $connection = $this->createConnection();
        self::$pool[$key] = $connection;
        return $connection;
    }

    /**
     * @return PDO
     */
    private function createConnection(): PDO
    {
        return new PDO($this->domainNameServer, $this->username, $this->password, $this->options());
    }

    /**
     * @return string
     */
    private function uniqueName(): string
    {
        ksort($this->config);
        return md5(json_encode($this->config));
    }

    /**
     * @return void
     */
    private function domainNameServer(): void
    {
        switch ($this->driver) {
            case 'pdo_mysql':
            case 'mysql':
                $this->domainNameServer = $this->mysqlDomainServer();
                $this->defaultOptions = [
                    PDO::ATTR_CASE => PDO::CASE_NATURAL,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                return;
            case 'pdo_sqlsrv':
            case 'sqlserver':
            case 'sqlsrv':
                $this->domainNameServer = $this->sqlserverDomainServer();
                $this->defaultOptions = [
                    PDO::ATTR_CASE => PDO::CASE_NATURAL,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
                    PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true,
                ];
                return;
            case 'pdo_dblib':
            case 'dblib':
                $this->domainNameServer = $this->dblibDomainServer();
            return;
            default:
        }
    }

    /**
     * mysql
     * @return string
     */
    private function mysqlDomainServer(): string
    {
        return 'mysql:host=' . $this->host . ';dbname=' . $this->database . ';port=' . $this->port
            . $this->charset();
    }

    /**
     * sqlserver
     * @return string
     */
    private function sqlserverDomainServer(): string
    {
        return 'sqlsrv:Server=' . $this->host . ',' . $this->port . ';Database=' . $this->database;
    }

    /**
     * dblib
     * @return string
     */
    private function dblibDomainServer(): string
    {
        return 'dblib:host=' . $this->host . ':' . $this->port . ';dbname=' . $this->database
            . $this->charset();
    }

    /**
     * @return string
     */
    private function charset(): string
    {
        return !empty($this->charset) ? ';charset=' . $this->charset : '';
    }

    /**
     * @return array
     */
    private function options(): array
    {
        $options = [];
        if (!empty($this->options)) {
            $options = $this->options;
        }
        return array_diff_key($this->defaultOptions, $options) + $options;
    }
}

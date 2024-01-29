<?php

namespace Isobaric\Database;

use Closure;
use Isobaric\Database\Action\Connector;

final class Database
{
    /**
     * 数据存储
     *
     * @var array
     */
    private static array $store = [];

    /**
     * 数据库连接
     *
     * @var null|\PDO
     */
    private static ?\PDO $connection = null;

    /**
     * 事务的数据库连接
     *
     * @var \PDO
     */
    private static \PDO $transaction;

    /**
     * @param array $connection
     * @return \PDO
     */
    public static function connection(array $connection): \PDO
    {
        if (is_null(self::$connection)) {
            return self::$connection = (new Connector($connection))->connection();
        }
        return self::$connection;
    }

    /**
     * @return void
     */
    public static function beginTransaction(): void
    {
        self::$store['transaction'] = true;
    }

    /**
     * @return bool
     */
    public static function commit(): bool
    {
        if (self::$transaction->inTransaction()) {
            self::$store['transaction'] = false;
            return self::$transaction->commit();
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function rollBack(): bool
    {
        if (self::$transaction->inTransaction()) {
            self::$store['transaction'] = false;
            return self::$transaction->rollBack();
        }
        return false;
    }

    /**
     *  打印SQL语句 不影响程序中的SQL执行
     * @return void
     */
    public static function print(): void
    {
        self::$store['print'] = true;
    }

    /**
     * 在SQL执行前 执行闭包程序
     * 闭包接受三个参数 string $sql, string $prepare ,array $bindings
     * 闭包中不应该有终止程序的操作 如:die exit
     * $model->listenSql(function ($sql, $prepare, $bindings) {
     *      print_r($sql);
     *      echo PHP_EOL;
     *      print_r($prepare);
     *      echo PHP_EOL;
     *      print_r($bindings);
     * });
     * @param Closure $closure
     * @return void
     */
    public static function listen(Closure $closure): void
    {
        self::$store['listen'] = $closure;
    }

    /**
     * 获取设定的值
     *
     * @param string $key
     * @return mixed
     */
    public static function store(string $key): mixed
    {
        return self::$store[$key] ?? null;
    }

    /**
     * @param \PDO $connection
     * @return void
     */
    public static function setTransaction(\PDO $connection): void
    {
        self::$transaction = $connection;
    }
}

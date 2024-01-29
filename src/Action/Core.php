<?php

namespace Isobaric\Database\Action;

use Isobaric\Database\Database;

abstract class Core
{
    protected Connector $resource;

    protected string $table;

    protected array $connection;

    protected bool $printSql = false;

    protected bool $toSql = false;

    protected bool $toCompleteSql = false;

    protected ?\Closure $listen = null;

    // 统计查询的方法名称
    protected array $aggregate = ['count', 'min', 'max', 'sum', 'avg'];

    // 查询全部数据的方法
    protected array $fetchAll = ['fetchAll', 'distinct', 'jsonArrayAgg', 'jsonObjectAgg'];

    // 获取影响行数的方法
    private array $rowExecute = ['insert', 'delete', 'update', 'replace', 'truncate'];

    /**
     * SQL字段加入标识符
     *
     * @param string $column
     * @return string
     */
    protected abstract function fieldHandle(string $column): string;

    /**
     * 执行SQL操作
     *
     * @param string $function
     * @param string $prepare
     * @param array $bindings
     * @return mixed
     */
    protected function execute(string $function, string $prepare, array $bindings): mixed
    {
        // 全局的SQL监听
        if (Database::store('listen')) {
            Database::store('listen')($this->getCompleteSql($prepare, $bindings), $prepare, $bindings);
        }

        // 当前的SQL监听
        if ($this->listen) {
            $closure = $this->listen;
            $closure($this->getCompleteSql($prepare, $bindings), $prepare, $bindings);
        }

        if ($this->printSql || Database::store('print')) {
            print_r($this->getCompleteSql($prepare, $bindings));
            echo PHP_EOL;
        }

        if ($this->toSql) {
            return $prepare;
        }

        if ($this->toCompleteSql) {
            return $this->getCompleteSql($prepare, $bindings);
        }

        return $this->databasesTake($function, $prepare, $bindings);
    }

    /**
     * Grab
     * @param string $function
     * @param string $prepare
     * @param array $bindings
     * @return mixed
     */
    private function databasesTake(string $function, string $prepare, array $bindings): mixed
    {
        $connection = $this->resource->connection();

        // 判断并开启事务
        if (Database::store('transaction') && !$connection->inTransaction()) {
            $connection->beginTransaction();
            Database::setTransaction($connection);
        }

        // SQL预处理
        $stmt = $connection->prepare($prepare);
        if ($stmt == false) {
            throw new \PDOException($stmt->errorInfo()[2]);
        }

        // SQL参数绑定
        foreach ($bindings as $index => $binding) {
            if ($stmt->bindValue($index + 1, $binding) == false) {
                throw new \PDOException($stmt->errorInfo()[2]);
            }
        }

        if ($stmt->execute() == false) {
            throw new \PDOException($stmt->errorInfo()[2]);
        }

        // 返回受影响的行
        if (in_array($function, $this->rowExecute)) {
            return $stmt->rowCount();
        }

        // 返回最后写入的ID
        if ($function == 'insertGetId' || $function == 'replaceGetId') {
            return $connection->lastInsertId();
        }

        // 获取结果集
        if (in_array($function, $this->fetchAll)) {
            $fetch = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $fetch = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        if ($fetch === false) {
            return [];
        }

        // 返回结果集
        return $fetch;
    }

    /**
     * @param string $prepare
     * @param array $bindings
     * @return string
     */
    protected function getCompleteSql(string $prepare, array $bindings): string
    {
        $list = explode('?', $prepare);
        $string = '';
        foreach ($list as $key => $pre) {
            if (isset($bindings[$key])) {
                if (is_string($bindings[$key])) {
                    $bindings[$key] = "'$bindings[$key]'";
                }
                $string .= $pre . $bindings[$key];
            } else {
                $string .= $pre;
            }
        }
        return $string;
    }

    /**
     * @param string $str
     * @param string $head
     * @param string $tail
     * @return string
     */
    protected function strDress(string $str, string $head = '(', string $tail = ')'): string
    {
        return $head . $str . $tail;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function strDistance(string $string): string
    {
        return ' ' . $string . ' ';
    }

    /**
     * @param int $total
     * @param int $page
     * @param int $per
     * @return bool
     */
    public static function hasNextPage(int $total, int $page, int $per): bool
    {
        if ($total == 0) {
            return false;
        }
        if ($page > ceil($total / $per)) {
            return false;
        }
        return true;
    }
}

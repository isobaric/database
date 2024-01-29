<?php

namespace Isobaric\Database;

use Isobaric\Database\Action\Connector;
use Isobaric\Database\Action\Core;
use Isobaric\Database\Drawer\Analyzer;
use Isobaric\Database\Drawer\Continuous;
use Isobaric\Database\Drawer\Execute;
use Isobaric\Database\Drawer\MySQL\Section;
use Isobaric\Database\Drawer\Scope;

abstract class MySQL extends Core
{
    use Execute,
        Continuous,
        Analyzer,
        Scope,
        Section;

    public function __construct()
    {
        $this->resource = new Connector($this->connection);
    }

    /**
     * 返回聚合为JSON数组的查询结果
     *
     * @param string $column
     *  用于聚合的字段名，仅支持单个字段聚合
     *
     * @return array|string
     *  <p>array: 返回查询结果集</p>
     *  <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *  ->groupBy('a_column')->select('a_column')->jsonArrayAgg('b_column');
     */
    public function jsonArrayAgg(string $column): array|string
    {
        $this->setJsonArrayColumn($column);

        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * 返回聚合为JSON对象的查询结果
     *
     * @param string $key
     *  作为返回值对象key的字段名
     *
     * @param string $value
     *  作为返回值对象value的字段名
     *
     * @return array|string
     *  <p>array: 返回查询结果</p>
     *  <p>string: 当使用 toSql() / toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *   ->groupBy('a_column')->select('a_column')->jsonArrayAgg('b_column');
     */
    public function jsonObjectAgg(string $key, string $value): array|string
    {
        $this->setJsonObjectColumn($key, $value);

        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * limit 条件
     *
     * @param int $limit
     *  查询的记录数
     *
     * @return $this
     *
     * @example
     *  where(['state' => 1])->limit(1);
     *  SQL: where state = 1 limit 1;
     */
    public function limit(int $limit): static
    {
        $this->setScopeIndie('limit', $limit);
        return $this;
    }

    /**
     * offset 条件，必须和 limit() 方法一起使用
     *
     * @param int $offset
     *  跳过的记录数
     *
     * @return $this
     *
     * @example
     *  where(['state' => 1])->limit(1)->offset(2);
     *  SQL: where state = 1 limit 1 offset 2;
     */
    public function offset(int $offset): static
    {
        $this->setScopeIndie('offset', $offset);
        return $this;
    }

    /**
     * 分页条件
     *
     * @param int $page
     *  页码数
     *
     * @param int $per
     *  每页查询的数据数量
     *
     * @return $this
     *
     * @example
     *  where(['state' => 1])->page(2, 2)->fetchAll();
     *  SQL: where state = 1 limit 2 offset 2;
     */
    public function page(int $page, int $per): static
    {
        $this->limit($page);
        $this->offset(($page - 1) * $per);
        return $this;
    }

    /**
     * 向数据库写入一条或多条数据，如果主键ID相同，那么会更新当前一条记录而不是写入
     *
     * @param array $data
     *  <p>向数据库插入的数据：</p>
     *  <p>如果是一维数组：那么将数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *  <p>如果是二维数组：那么将二维数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *
     * @return int|string
     *  <p>int: 返回受影响的行数/字段数</p>
     *  <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *   ->replace(['id' => 1, 'title' => 'new title']);
     *   SQL: replace into table (id,title) values (1,'new title');
     *
     *   ->replace([['id' => 1, 'title' => 'title_1'], ['id' => 2, 'title' => 'title_2']]);
     *   SQL: replace into table (title) values (1, 'title_1'),(2, 'title_2');
     */
    public function replace(array $data): int|string
    {
        $this->insertAnalyzer($data);

        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * 向数据库写入一条或多条数据并返回最近一次写入的主键ID，如果主键ID相同，那么会更新当前一条记录而不是写入
     *
     * @param array $data
     *  <p>向数据库插入的数据：</p>
     *  <p>如果是一维数组：那么将数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *  <p>如果是二维数组：那么将二维数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *
     * @return int|string
     *  <p>int: 返回受影响的行数/字段数</p>
     *  <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *    ->replace(['id' => 1, 'title' => 'new title']);
     *    SQL: replace into table (id,title) values (1,'new title');
     *
     *    ->replace([['id' => 1, 'title' => 'title_1'], ['id' => 2, 'title' => 'title_2']]);
     *    SQL: replace into table (title) values (1, 'title_1'),(2, 'title_2');
     */
    public function replaceGetId(array $data): int|string
    {
        $this->insertAnalyzer($data);

        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * SQL字段名加区分字符
     *
     * @param string $column
     * @return string
     */
    protected function fieldHandle(string $column): string
    {
        return '`' . $column . '`';
    }
}

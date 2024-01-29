<?php

namespace Isobaric\Database\Drawer;

/**
 * @method fetchMin(string $column): mixed
 * @method fetchMax(string $column): mixed
 * @method fetchSum(string $column): null|int|float|string
 * @method fetchAvg(string $column): null|int|float|string
 * @method fetchCount(string $column = '*'): int|string
 * @method fetchDistinct(string $column): array
 */
trait Execute
{
    /**
     * 使用 __call 实现的方法名称
     *
     * @var array|string[]
     */
    private array $callAggregate = [
        'fetchMin', 'fetchMax', 'fetchSum', 'fetchAvg', 'fetchCount', 'fetchDistinct'
    ];

    /**
     * 查询一条数据
     *
     * @return array|string
     *
     * @example
     *  ->select(['id', 'title'])->where(['id' => 1])->fetch();
     *  SQL: select id,title from table where id = 1 limit 1;
     */
    public function fetch(): array|string
    {
        $this->limit(1);
        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * 查询全部数据
     *
     * @return array|string
     *
     * @example
     *  ->select(['id', 'title'])->where(['id', '>', 1])->fetchAll();
     *  SQL: select id,title from table where id > 1;
     */
    public function fetchAll(): array|string
    {
        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * 查询分页数据
     *
     * @param int $page
     *  页码数
     *
     * @param int $per
     *  每页查询的数据数量
     *
     * @return array
     *  <p>返回字段:</p>
     *  <p>total: int 总记录数</p>
     *  <p>list: array 分页列表</p>
     *
     * @example
     *  ->select(['id', 'title'])->where(['id', '>', 1])->paginator(2, 2);
     *  SQL: select id,title from table where id > 1 limit 2 offset 2;
     */
    public function paginator(int $page, int $per): array
    {
        $columns = $this->getScopeStringValue('columns');

        $total = $this->fetchCount();

        if ($columns == '') {
            $columns = '*';
        }
        $this->setScopeIndie('columns', $columns);

        $list = $this->hasNextPage($total, $page, $per) ? $this->page($page, $per)->fetchAll() : [];

        return compact('total', 'list');
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!in_array($name, $this->callAggregate, true)) {
            throw new \PDOException('Unsupported Method: ' . $name);
        }
        $function = lcfirst(ltrim($name, 'fetch'));

        if (empty($arguments)) {
            $column = '*';
        } else {
            $column = $this->fieldHandle($arguments[0]);
        }
        $column = $this->strDress($column);

        $this->setScopeIndie('columns', $function . $column);

        $result = $this->execute($function, $this->getExpression($function), $this->getBindings());

        if ($function != 'distinct') {
            return current($result);
        }
        return $result;
    }

    /**
     * 向数据库写入一条或多条数据
     *
     * @param array $data
     *   <p>向数据库插入的数据：</p>
     *   <p>如果是一维数组：那么将数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *   <p>如果是二维数组：那么将二维数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *
     * @return int|string
     *  <p>int: 返回受影响的行数</p>
     *  <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *  ->insert(['title' => 'new title']);
     *  SQL: insert into table (title) values ('new title');
     *
     *  ->insert([['title' => 'title_1'], ['title' => 'title_2']]);
     *  SQL: insert into table (title) values ('title_1'),('title_2');
     */
    public function insert(array $data): int|string
    {
        $this->insertAnalyzer($data);

        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * 向数据库写入一条或多条数据并返回最近一次写入的主键ID
     *
     * @param array $data
     *  <p>向数据库插入的数据：</p>
     *  <p>如果是一维数组：那么将数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *  <p>如果是二维数组：那么将二维数组中的key作为写入数据表的字段名，key对应的value作为写入字段名的值</p>
     *
     * @return int|string
     *   <p>int: 返回最近一次写入的主键ID</p>
     *   <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *   ->insertGetId(['title' => 'new title']);
     *   SQL: insert into table (title) values ('new title');
     *
     *   ->insertGetId([['title' => 'title_1'], ['title' => 'title_2']]);
     *   SQL: insert into table (title) values ('title_1'),('title_2');
     */
    public function insertGetId(array $data): int|string
    {
        $this->insertAnalyzer($data);

        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * 删除数据
     *
     * @return int|string
     *  <p>int: 返回受影响的行数</p>
     *  <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *  ->where(['id' => 1])->delete();
     *  SQL: delete from table where id = 1;
     */
    public function delete(): int|string
    {
        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * truncate
     *
     * @return int|false|string
     */
    public function truncate(): int|false|string
    {
        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), []);
    }

    /**
     * 更新数据
     *
     * @param array $update
     *  <p>更新的数据，格式为key:value的一维数组</p>
     *  <p>数组中的key作为对应为数据表的字段名，key对应的value作为更新字段名的值</p>
     *
     * @return int|string
     *  <p>int: 返回受影响的行数</p>
     *  <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *  ->where(['id' => 1])->update(['a'=> 'ABC']);
     *  SQL: update table set a = 'ABC' where id = 1;
     */
    public function update(array $update): int|string
    {
        $this->setAnalyzer($update);

        return $this->execute(__FUNCTION__, $this->getExpression(__FUNCTION__), $this->getBindings());
    }

    /**
     * 更新数据
     *
     * @param string $setExpr
     *  set语句表达式
     *
     * @return int|string
     *  <p>int: 返回受影响的行数</p>
     *  <p>string: 当使用 toSql()/toCompleteSql() 时返回当前一次操作的SQL，且不执行数据库操作</p>
     *
     * @example
     *   ->where(['id' => 1])->update("a = 'ABC'");
     *   SQL: update table set a = 'ABC' where id = 1;
     */
    public function updateRaw(string $setExpr): int|string
    {
        $this->setAnalyzer($setExpr);

        return $this->execute('update', $this->getExpression('update'), $this->getBindings());
    }

    /**
     * 获取当前对象的数据表名称
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * 获取当前对象使用的数据库配置信息
     *
     * @return array
     */
    public function getConnection(): array
    {
        return $this->connection;
    }

    /**
     * 打印SQL语句 不影响程序中的SQL执行
     *
     * @return void
     */
    public function print(): void
    {
        $this->printSql = true;
    }

    /**
     * 将执行操作 转为预处理SQL语句输出，SQL不执行
     *
     * @return $this
     */
    public function toSql(): static
    {
        $this->toSql = true;
        return $this;
    }

    /**
     * 将执行操作 转为完整SQL语句输出，SQL不执行
     *
     * @return $this
     */
    public function toCompleteSql(): static
    {
        $this->toCompleteSql = true;
        return $this;
    }

    /**
     * 在SQL执行前执行闭包程序
     *
     * @param \Closure $closure
     * @return void
     *
     * @example
     *  <p>闭包接受三个参数 string $sql, string $prepare ,array $bindings</p>
     *  <p>闭包中不应该有终止程序的操作 如:die exit</p>
     *  $model->listen(function ($sql, $prepare, $bindings) {
     *       print_r($sql);
     *       echo PHP_EOL;
     *       print_r($prepare);
     *       echo PHP_EOL;
     *       print_r($bindings);
     *  });
     */
    public function listen(\Closure $closure): void
    {
        $this->listen = $closure;
    }
}

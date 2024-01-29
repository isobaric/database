<?php

namespace Isobaric\Database;

use Isobaric\Database\Action\Connector;
use Isobaric\Database\Action\Core;
use Isobaric\Database\Drawer\Analyzer;
use Isobaric\Database\Drawer\Continuous;
use Isobaric\Database\Drawer\Execute;
use Isobaric\Database\Drawer\Scope;
use Isobaric\Database\Drawer\SQLServer\Section;

abstract class SQLServer extends Core
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
     * top 条件
     *
     * @param int $top
     *  查询的记录数
     *
     * @return $this
     *
     * @example
     *  ->top(1)->fetchAll();
     *  SQL:select top 1 * from [table]
     */
    public function top(int $top): static
    {
        $this->setScopeIndie('top', $top);
        return $this;
    }

    /**
     * top 条件
     *
     * @param int $limit
     *  查询的记录数
     * @return $this
     *
     * @example
     *  参考top()方法
     */
    public function limit(int $limit): static
    {
        $this->top($limit);
        return $this;
    }

    /**
     * next 条件，必须和 offset() 方法一起使用
     *
     * @param int $next
     *  查询的记录数
     *
     * @return $this
     *
     * @example
     *   参考top()方法
     */
    public function next(int $next): static
    {
        $this->top($next);
        return $this;
    }

    /**
     * offset 条件，必须和 limit()/top()/next() 方法一起使用
     *
     * @param int $offset
     *  跳过的记录数
     *
     * @return $this
     *
     * @example
     *  where(['state' => 1])->orderBy('id')->offset(2)->next(2)->fetchAll();
     *  SQL: where [state] = 1 order by [id] offset 2 rows fetch next 2 rows only;
     *
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
     *  where(['state' => 1])->orderBy('id')->page(2, 2)->fetchAll();
     *  SQL: where `state` = 1 order by [id] offset 2 rows fetch next 2 rows only;
     */
    public function page(int $page, int $per): static
    {
        $this->top($per);
        $this->offset(($page - 1) * $per);
        return $this;
    }

    /**
     * @param string $column
     * @return string
     */
    protected function fieldHandle(string $column): string
    {
        return '[' . $column . ']';
    }
}

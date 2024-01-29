<?php

namespace Isobaric\Database\Drawer;

use Isobaric\Database\MySQL;
use Isobaric\Database\SQLServer;

trait Continuous
{
    /**
     * where条件
     *
     * @param array $where
     *  where条件，可以是一维数组或二维数组
     *
     * @return $this
     *
     * @example
     *  <p>一维关联数组 ['id' => 1, 'state' => 1]: key是查询的字段名，value是字段的值，默认两者的关系是 '等于'</p>
     *  <p>MySQL: where id = 1 and state = 1;</p>
     *
     *  <p>一维list数组 ['id', 1] 数组中是两个元素；第一个是字段名，第二个是字段的值，默认两者的关系是 '等于'</p>
     *  <p>MySQL: where id = 1;</p>
     *
     *  <p>一维list数组 ['id', '=', Mysql]（子查询）：数组中有三个元素；第一个是字段名，第二个是符号；第三个Mysql类，且该类必须使用 subColumn()方法设置子查询的字段</p>
     *  <p>MySQL: where id = (select sub_column_name from table where ...);</p>
     *
     *  <p>一维list数组 ['id', '>', 1] 数组中是三个元素；第一个是字段名，第二个是符号，第三个是字段的值</p>
     *  <p>MySQL: where id > 1;</p>
     *
     *  <p>一维list数组 ['state', 'is', 'null'] 数组中是三个元素；第一个是字段名，第二个是is，第三个是is允许的值；例：true / true / not null / null</p>
     *  <p>MySQL: where state is null;</p>
     *
     *  <p>二维list数组 [['id', '>', 1],['code', '=', 'ABC'], ['state' => 1]] 二维数组的格式与一维数组一致，数组之间是and关系</p>
     *  <p>MySQL: where (id > 1 and code = 'ABC' and state = 1);</p>
     */
    public function where(array $where): static
    {
        $this->setScopeWhere($where, [], 'and');
        return $this;
    }

    /**
     * where 条件
     *
     * @param string $column
     *  字段名
     *
     * @param string $symbol
     *  符号；如果只有两个参数，那么把当前参数作为字段值并且把$symbol赋值为等号'='
     *
     * @param string|int $value
     *  字段值
     *
     * @return $this
     *
     * @example
     *  ->whereCase('type');
     *  MySQL: where type = '';
     *
     *  ->whereCase('type', 1);
     *  MySQL: where type = 1;
     *
     *  ->whereCase('type', '>', 1);
     *  MySQL: where type > 1;
     */
    public function whereCase(string $column, string $symbol = '=', string|int $value = ''): static
    {
        $condition = array_slice(func_get_args(), 0, 3);
        if (!empty($condition)) {
            if (!isset($condition[1])) {
                $condition[1] = '=';
                $condition[2] = '';
            } else if (!isset($condition[2])) {
                $condition[2] = $condition[1];
                $condition[1] = '=';
            }
            $this->where($condition);
        }
        return $this;
    }

    /**
     * where表达式
     *
     * @param string $expr
     *  <p>where表达式</p>
     *  <p>使用'?'作为占位符</p>
     *
     * @param array $bindings
     *  where表达式中占位符绑定的值
     *
     * @return $this
     *
     * @example
     *  ->select(['id', 'title'])->whereRaw('id = 1 and state > 2')->fetchAll();
     *  MySQL: select id,title from table where id = 1 and state > 2;
     *
     *  ->select(['id', 'title'])->whereRaw('id = ? and state > ?', [1, 2])->fetchAll();
     *   MySQL: select id,title from table where id = 1 and state > 2;
     */
    public function whereRaw(string $expr, array $bindings = []): static
    {
        $this->setScopeWhere($expr, $bindings, 'and');
        return $this;
    }

    /**
     * 子查询
     *
     * @param string $column
     *  主SQL的字段名
     *
     * @param string $symbol
     *  符号：in / = / <> 等
     *
     * @param MySQL|SQLServer $mysql
     *  构建子SQL的类
     *
     * @param string $subColumn
     *  子SQL的查询字段
     *
     * @return $this
     *
     * @example
     *  ->where('master_column', 'in', $mysql->where(['state' => 1]), 'sub_column');
     *  MySQL: where master_column = (select sub_column from table where state = 1);
     */
    public function whereSub(string $column, string $symbol, MySQL|SQLServer $mysql, string $subColumn): static
    {
        $mysql->subColumn($subColumn);
        $this->where([$column, $symbol, $mysql]);
        return $this;
    }

    /**
     * where的 or 条件
     *
     * @param array $where
     *  使用方法参考where()方法
     *
     * @return $this
     *
     * @example
     *  ->select('id,title')->whereOr(['state' => 1])->fetchALl();
     *  MySQL: select id,title from where state = 1;
     *
     *  ->select('id,title')->where(['type' => 2])->whereOr(['state' => 1])->fetchALl();
     *   MySQL: select id,title from where type = 2 or state = 1;
     */
    public function whereOr(array $where): static
    {
        $this->setScopeWhere($where, [], 'or');
        return $this;
    }

    /**
     * where表达式
     *
     * @param string $expr
     *  <p>where表达式</p>
     *  <p>使用'?'作为占位符</p>
     *
     * @param array $bindings
     *  where表达式中占位符绑定的值
     *
     * @return $this
     *
     * @example
     *  ->select('id,title')->whereOrRaw('state = 1')->fetchALl();
     *   MySQL: select id,title from where state = 1;
     *
     *   ->select('id,title')->where(['type' => 2])->whereOrRaw('state = 1')->fetchALl();
     *   ->select('id,title')->where(['type' => 2])->whereOrRaw('state = ?', [1])->fetchALl();
     *    MySQL: select id,title from where type = 2 or state = 1;
     */
    public function whereOrRaw(string $expr, array $bindings = []): static
    {
        $this->setScopeWhere($expr, $bindings, 'or');
        return $this;
    }

    /**
     * where字段的 Null 条件
     *
     * @param string $column
     *  字段名
     *
     * @return $this
     *
     * @example
     *  ->whereNull('type');
     *  MySQL: where type is null;
     */
    public function whereNull(string $column): static
    {
        $this->where([$column, 'is', 'null']);
        return $this;
    }

    /**
     * where字段的 Not Null 条件
     *
     * @param string $column
     *  字段名
     *
     * @return $this
     *
     * @example
     *   ->whereNotNull('type');
     *   MySQL: where type is not null;
     */
    public function whereNotNull(string $column): static
    {
        $this->where([$column, 'is', 'not null']);
        return $this;
    }

    /**
     * where的 in 条件
     *
     * @param string $column
     *  字段名
     *
     * @param array $value
     *  字段值
     *
     * @return $this
     *
     * @example
     *  ->whereIn('type', [1, 2, 3]);
     *  MySQL: where type in (1, 2, 3);
     */
    public function whereIn(string $column, array $value): static
    {
        $this->where([$column, 'in', $value]);
        return $this;
    }

    /**
     * where的 not in 条件
     *
     * @param string $column
     *  字段名
     *
     * @param array $value
     *  字段值
     *
     * @return $this
     *
     * @example
     *   ->whereNotIn('type', [1, 2, 3]);
     *   MySQL: where type not in (1, 2, 3);
     */
    public function whereNotIn(string $column, array $value): static
    {
        $this->where([$column, 'not in', $value]);
        return $this;
    }

    /**
     * where的 like 条件
     *
     * @param string $column
     *  字段名
     *
     * @param string $value
     *  字段值
     *
     * @return $this
     *
     * @example
     *  ->whereLike('username', 'Tom');
     *  MySQL: where username like 'Tom';
     *
     *  ->whereLike('username', '%Tom%');
     *  MySQL: where username like '%Tom%';
     */
    public function whereLike(string $column, string $value): static
    {
        $this->where([$column, 'like', $value]);
        return $this;
    }

    /**
     * where的 not like 条件
     *
     * @param string $column
     *  字段名
     *
     * @param string $value
     *  字段值
     *
     * @return $this
     *
     * @example
     *  ->whereNotLike('username', 'Tom');
     *  MySQL: where username not like 'Tom';
     *
     *  ->whereNotLike('username', '%Tom%');
     *  MySQL: where username not like '%Tom%';
     */
    public function whereNotLike(string $column, string $value): static
    {
        $this->where([$column, 'not like', $value]);
        return $this;
    }

    /**
     * where的 between 条件
     *
     * @param string $column
     *  字段名
     *
     * @param array $value
     *  字段值
     *
     * @return $this
     *
     * @example
     *  ->whereBetween('type', [1, 5]);
     *  MySQL: where type between 1 and 5;
     */
    public function whereBetween(string $column, array $value): static
    {
        $this->where([$column, 'between', $value]);
        return $this;
    }

    /**
     * where的 not between 条件
     *
     * @param string $column
     *  字段名
     *
     * @param array $value
     *  字段值
     *
     * @return $this
     *
     * @example
     *  ->whereNotBetween('type', [1, 5]);
     *  MySQL: where type not between 1 and 5;
     */
    public function whereNotBetween(string $column, array $value): static
    {
        $this->where([$column, 'not between', $value]);
        return $this;
    }

    /**
     * having条件
     *
     * @param array $having
     * @return $this
     *
     * @example
     *  参数和示例参考 where();
     */
    public function having(array $having): static
    {
        $this->setScopeHaving($having, [], 'and');
        return $this;
    }

    /**
     * having的 between 条件
     *
     * @param string $column
     * @param array $value
     * @return $this
     *
     * @example
     *  参数和示例参考 whereBetween();
     */
    public function havingBetween(string $column, array $value): static
    {
        $this->having([$column, 'between', $value]);
        return $this;
    }

    /**
     * having的 not between 条件
     *
     * @param string $column
     * @param array $value
     * @return $this
     *
     * @example
     *   参数和示例参考 whereNotBetween();
     */
    public function havingNotBetween(string $column, array $value): static
    {
        $this->having([$column, 'not between', $value]);
        return $this;
    }

    /**
     * having的 null 条件
     *
     * @param string $columns
     * @return $this
     *
     * @example
     *    参数和示例参考 whereNull();
     */
    public function havingNull(string $columns): static
    {
        $this->having([$columns, 'is', 'null']);
        return $this;
    }

    /**
     * having的 not null 条件
     *
     * @param string $columns
     * @return $this
     *
     * @example
     *    参数和示例参考 whereNotNull();
     */
    public function havingNotNull(string $columns): static
    {
        $this->having([$columns, 'is', 'not null']);
        return $this;
    }

    /**
     * having表达式
     *
     * @param string $having
     * @param array $bindings
     * @return $this
     *
     * @example
     *     参数和示例参考 whereRaw();
     */
    public function havingRaw(string $having, array $bindings = []): static
    {
        $this->setScopeHaving($having, $bindings, 'and');
        return $this;
    }

    /**
     * group by 条件
     *
     * @param string $column
     *  字段名
     *
     * @return $this
     *
     * @example
     *  where(['state' => 1])->groupBy('type')->fetchAll();
     *  MySQL: where state = 1 group by `type`;
     *
     *  where(['state' => 1])->groupBy('type,state')->fetchAll();
     *  MySQL: where state = 1 group by `type`,`state`;
     */
    public function groupBy(string $column): static
    {
        $this->setScopeStringAppend('group by', $this->fieldDecode($column));
        return $this;
    }

    /**
     * group by 条件
     *
     * @param string $expr
     *  group by 表达式
     *
     * @return $this
     *
     * @example
     *  where(['state' => 1])->groupBy('type')->fetchAll();
     *  MySQL: where state = 1 group by type;
     *
     *  where(['state' => 1])->groupBy('type,state')->fetchAll();
     *  MySQL: where state = 1 group by type,state;
     */
    public function groupByRaw(string $expr): static
    {
        $this->setScopeStringAppend('group by', $expr);
        return $this;
    }

    /**
     * order by 条件
     *
     * @param string $expr
     *  order by 表达式
     *
     * @return $this
     *
     * @example
     *   where(['state' => 1])->orderBy('type')->fetchAll();
     *   MySQL: where state = 1 order by type;
     *
     *   where(['state' => 1])->orderBy('type,state')->fetchAll();
     *   MySQL: where state = 1 order by type,state;
     */
    public function orderByRaw(string $expr): static
    {
        $this->setScopeStringAppend('order by', $expr);
        return $this;
    }

    /**
     * order by 条件
     *
     * @param string $column
     *  字段名
     *
     * @return $this
     *
     * @example
     *    where(['state' => 1])->orderBy('type')->fetchAll();
     *    MySQL: where state = 1 order by `type`;
     *
     *    where(['state' => 1])->orderBy('type,state')->fetchAll();
     *    MySQL: where state = 1 order by `type`,`state`;
     */
    public function orderBy(string $column): static
    {
        $this->setScopeStringAppend('order by', $this->fieldDecode($column));
        return $this;
    }

    /**
     * 倒叙的 order by 条件
     *
     * @param string $column
     *  排序的字段
     *
     * @return $this
     *
     * @example
     *  where(['state' => 1])->orderByDesc('type')->fetchAll();
     *  MySQL: where state = 1 order by `type` desc;
     *
     *  where(['state' => 1])->orderByDesc('type')->orderByDesc('state')->fetchAll();
     *  MySQL: where state = 1 order by `type` desc,`state` desc;
     */
    public function orderByDesc(string $column): static
    {
        $this->setScopeStringAppend('order by', $this->fieldDecode($column) . ' desc');
        return $this;
    }

    /**
     * 设置 table 别名
     *
     * @param string $alias
     * @return $this
     *
     * @example
     *  ->select('id')->alias('alias_table')->fetch();
     *  MySQL: select id from table as alias_table limit 1;
     */
    public function alias(string $alias): static
    {
        $this->setScopeIndie('as', $this->fieldHandle($alias));
        return $this;
    }

    /**
     * join 条件
     *
     * @param string $table
     *  join的表名称
     *
     * @param string $onExpr
     *  自定义 on 表达式
     *
     * @param string $as
     *  join的表名称的别名
     *
     * @param string $join
     *  join的类型：'join', 'cross join', 'inner join', 'left join', 'right join', 'left outer join', 'right outer join',
     * 'full outer join', 'straight_join', 'natural join', 'natural left join', 'natural right join',
     * 'natural inner join'
     *
     * @return $this
     *
     * @example
     *  ->alias('table_a')->select('table_a.id,table_b.title')->joinRaw('table_next', 'table_a.id = table_b.a_id', 'table_b');
     *  MySQL: select table_a.id,table_b.title from table as table_a join table_next as table_b on table_a.id = table_b.a_id
     */
    public function joinRaw(string $table, string $onExpr = '', string $as = '', string $join = 'join'): static
    {
        if (!empty($table)) {
            $this->setScopeJoin($join, $table, $onExpr, $as);
        }
        return $this;
    }

    /**
     * cross join 条件
     *
     * @param string $table
     *  join的表名称
     *
     * @param array $on
     *  <p>on 条件，格式为一维关联数组</p>
     *  <p>key为左表字段value为右表字段,多条件之间是and关系</p>
     *
     * @param string $as
     *  join的表名称的别名
     *
     * @return $this
     *
     * @example
     *  ->alias('table_a')->select('table_a.id,table_b.title')->joinRaw('table_next', ['table_a.id' => 'table_b.a_id'], 'table_b');
     *  MySQL: select table_a.id,table_b.title from table as table_a cross join table_next as table_b on table_a.id = table_b.a_id
     */
    public function join(string $table, array $on = [], string $as = ''): static
    {
        if (!empty($table)) {
            $this->setScopeJoin('join', $table, $on, $as);
        }
        return $this;
    }

    /**
     * inner join 条件
     *
     * @param string $table
     * @param array $on
     * @param string $as
     * @return $this
     *
     * @example
     *  功能和示例参考 join() 方法；
     */
    public function innerJoin(string $table, array $on = [], string $as = ''): static
    {
        if (!empty($table)) {
            $this->setScopeJoin('inner join', $table, $on, $as);
        }
        return $this;
    }

    /**
     * left join 条件
     *
     * @param string $table
     * @param array $on
     * @param string $as
     * @return $this
     *
     * @example
     *   功能和示例参考 join() 方法；
     */
    public function leftJoin(string $table, array $on = [], string $as = ''): static
    {
        if (!empty($table)) {
            $this->setScopeJoin('left join', $table, $on, $as);
        }
        return $this;
    }

    /**
     * right join 条件
     *
     * @param string $table
     * @param array $on
     * @param string $as
     * @return $this
     *
     * @example
     *   功能和示例参考 join() 方法；
     */
    public function rightJoin(string $table, array $on = [], string $as = ''): static
    {
        if (!empty($table)) {
            $this->setScopeJoin('right join', $table, $on, $as);
        }
        return $this;
    }

    /**
     * 设置子查询的字段名
     *
     * @param string $column
     * @return $this
     */
    public function subColumn(string $column): static
    {
        $this->setScopeIndie('scope_sub_field', $column);
        return $this;
    }

    /**
     * 查询的数据库字段
     *
     * @param string|array $columns
     *  <p>string: 查询字段的表达式，不支持json格式或函数格式的表达式</p>
     *  <p>array: 格式为一维的list格式，一个数组元素为一个数据表字段</p>
     *
     * @return $this
     *
     * @example
     *  ->select('id,title')->fetchAll();
     *  ->select(['id', 'title'])->fetchAll();
     *  MySQL: select `id`,`title` from table;
     *
     *  ->alias('alias_table')->select('alias_table.id,alias_table.title')->fetchAll();
     *  ->alias('alias_table')->select(['alias_table.id', 'alias_table.title'])->fetchAll();
     *  MySQL: select `alias_table`.`id`,'alias_table'.`title` from table as alias_table;
     */
    public function select(string|array $columns = '*'): static
    {
        $this->setColumns($this->getColumnsExpr($columns));
        return $this;
    }

    /**
     * 查询的数据库字段
     *
     * @param string $expr
     *  表达式
     *
     * @return $this
     *
     * @example
     *  ->selectRaw('id,title')->fetchAll();
     *  MySQL: select id,title from table;
     */
    public function selectRaw(string $expr = '*'): static
    {
        $this->setColumns($expr);
        return $this;
    }

    /**
     * 查询条件中的 min() 函数
     *
     * @param string $column
     *  字段名，仅支持单个字段名称
     *
     * @param string $as
     *  min() 函数的别名，如果为设置别名，则用 $column 作为别名
     *
     * @return $this
     *
     * @example
     *  ->min('id')->fetch();
     *  MySQL: select min(`id`) as `id` limit 1;
     *
     *  ->min('id', 'min_id')->fetch();
     *  MySQL: select min(`id`) as `min_id` limit 1;
     */
    public function min(string $column, string $as = ''): static
    {
        $this->setAggregateAlias(__FUNCTION__, $column, $as);
        return $this;
    }

    /**
     * 查询条件中的 max() 函数
     *
     * @param string $column
     *  字段名，仅支持单个字段名称
     *
     * @param string $as
     *  max() 函数的别名，如果为设置别名，则用 $column 作为别名
     *
     * @return $this
     *
     * @example
     *  ->max('id')->fetch();
     *  MySQL: select max(`id`) as `id` limit 1;
     *
     *  ->max('id', 'max_id')->fetch();
     *  MySQL: select max(`id`) as `max_id` limit 1;
     */
    public function max(string $column, string $as = ''): static
    {
        $this->setAggregateAlias(__FUNCTION__, $column, $as);
        return $this;
    }

    /**
     * 查询条件中的 sum() 函数
     *
     * @param string $column
     *  字段名，仅支持单个字段名称
     *
     * @param string $as
     *  sum() 函数的别名，如果为设置别名，则用 $column 作为别名
     *
     * @return $this
     *
     * @example
     *  ->sum('id')->fetch();
     *  MySQL: select sum(`id`) as `id` limit 1;
     *
     *  ->sum('id', 'sum_id')->fetch();
     *  MySQL: select sum(`id`) as `sum_id` limit 1;
     */
    public function sum(string $column, string $as = ''): static
    {
        $this->setAggregateAlias(__FUNCTION__, $column, $as);
        return $this;
    }

    /**
     * 查询条件中的 avg() 函数
     *
     * @param string $column
     *  字段名，仅支持单个字段名称
     *
     * @param string $as
     *  avg() 函数的别名，如果为设置别名，则用 $column 作为别名
     *
     * @return $this
     *
     * @example
     *  ->avg('id')->fetch();
     *  MySQL: select avg(`id`) as `id` limit 1;
     *
     *  ->avg('id', 'avg_id')->fetch();
     *  MySQL: select avg(`id`) as `avg_id` limit 1;
     */
    public function avg(string $column, string $as = ''): static
    {
        $this->setAggregateAlias(__FUNCTION__, $column, $as);
        return $this;
    }

    /**
     * 查询条件中的 count() 函数
     *
     * @param string $column
     *  字段名，仅支持单个字段名称
     *
     * @param string $as
     *  count() 函数的别名，如果为设置别名，则用 $column 作为别名
     *
     * @return $this
     *
     * @example
     *  ->count('id')->fetch();
     *  MySQL: select count(`id`) as `aggregate` limit 1;
     *
     *  ->count('id', 'count_id')->fetch();
     *  MySQL: select count(`id`) as `count_id` limit 1;
     */
    public function count(string $column = '*', string $as = 'aggregate'): static
    {
        $this->setAggregateAlias(__FUNCTION__, $column, $as);
        return $this;
    }

    /**
     * distinct 条件
     *
     * @param string $column
     *  <p>字段名，仅支持单个字段名称</p>
     *  <p>如果使用select()方法，那么select()方法必须在distinct()方法之前使用</p>
     *
     * @return $this
     *
     * @example
     *  ->distinct('type')->fetchAll();
     *  MySQL: select distinct(`type`) from table;
     *
     *  ->select('state')->distinct('type')->fetchAll();
     *   MySQL: select `state`,distinct(`type`) from table;
     */
    public function distinct(string $column): static
    {
        $column = $this->strDress($this->fieldHandle($column));
        $this->setColumns(__FUNCTION__ . $column);
        return $this;
    }

    /**
     * select ... for update
     *
     * @return $this
     *
     * @example
     *  ->select('id')->where(['id' => 1])->lockForUpdate()->fetch();
     *  MySQL: select id from table where id = 1 limit 1 for update;
     */
    public function lockForUpdate(): static
    {
        $this->setScopeIndie('lock', 'for update');
        return $this;
    }

    /**
     * lock in share mode
     *
     * @return $this
     *
     * @example
     *  ->select('id')->where(['id' => 1])->lockInShareMode()->fetch();
     *  MySQL: select id from table where id = 1 limit 1 lock in share mode;
     */
    public function lockInShareMode(): static
    {
        $this->setScopeIndie('lock', 'lock in share mode');
        return $this;
    }
}

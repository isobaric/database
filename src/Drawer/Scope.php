<?php

namespace Isobaric\Database\Drawer;

trait Scope
{
    // select 查询的数据库方法名称
    private array $scopeSelect = ['fetch', 'fetchAll', 'distinct', 'jsonArrayAgg', 'jsonObjectAgg'];

    /**
     * 支持的join操作
     */
    private array $join = [
        'join', 'cross join', 'inner join', 'left join', 'right join', 'left outer join', 'right outer join',
        'full outer join', 'straight_join', 'natural join', 'natural left join', 'natural right join',
        'natural inner join'
    ];

    /**
     * 临时存储的数据
     * @var array
     */
    private array $scope = [];

    /**
     * @var array
     */
    private array $prepareBindings = [];

    /**
     * 重置scope
     *
     * @return void
     */
    public function reset(): void
    {
        $this->scope = [];
        $this->prepareBindings = [];
    }

    /**
     * 获取预处理SQL的绑定值
     *
     * @return array
     */
    public function getPrepareBindings(): array
    {
        return $this->prepareBindings;
    }

    /**
     * @param string $function
     * @return string
     */
    protected function getExpression(string $function): string
    {
        $prepareHead = $this->prepareHeader($function);
        $scopeKeys = $this->expressionKeywords($function);

        $compiler = '';
        foreach ($scopeKeys as $keyword) {
            $expression = $this->expressionBuild($keyword);
            if ($expression == '') {
                continue;
            }
            $compiler .= $expression;
        }

        return $prepareHead . $compiler;
    }

    /**
     * @param string $keyword
     * @return string
     */
    protected function expressionBuild(string $keyword): string
    {
        if (!array_key_exists($keyword, $this->scope)) {
            if ($keyword == 'columns') {
                $this->scope[$keyword] = '*';
            } else {
                return '';
            }
        }
        switch ($keyword) {
            case 'columns':
                return $this->getScopeStringValue('columns') . $this->strDistance('from') . $this->fieldHandle($this->table);
            case 'as':
            case 'set':
            case 'values':
            case 'group by':
            case 'order by':
                return $this->getKeywordsExpression($keyword, $this->getScopeStringValue($keyword));
            case 'top':
            case 'limit':
                return $this->getLimit();
            case 'offset':
                return $this->getOffset();
            case 'join':
                return $this->getJoin();
            case 'where':
            case 'having':
                $scopeList = $this->getScopeArrayValue($keyword);
                $expr = $this->whereConditionAnalyzer($scopeList);
                return $this->getKeywordsExpression($keyword, $expr);
            case 'lock':
            case 'insert':
                return $this->getKeywordsExpression('', $this->getScopeStringValue($keyword));
            default:
                return '';
        }
    }

    /**
     * SQL头处理
     *
     * @param string $function
     * @return string
     */
    private function prepareHeader(string $function): string
    {
        $selectScope = function ($function) {
            return in_array($function, $this->scopeSelect) || in_array($function, $this->aggregate) ? $function : false;
        };

        return match ($function) {
            $selectScope($function) => 'select ',

            'update' => 'update ' . $this->fieldHandle($this->table),

            'delete' => 'delete from ' . $this->fieldHandle($this->table),

            'insert', 'insertGetId' => 'insert into ' . $this->fieldHandle($this->table),

            'replace', 'replaceGetId' => 'replace into ' . $this->fieldHandle($this->table),

            'truncate' => $this->truncateHeader(),

            default => throw new \RuntimeException('Unsupported Function: ' . $function),
        };
    }

    /**
     * 设置SQL参数
     *
     * @param array $bindings
     * @param bool $isReverse
     * @return void
     */
    protected function setBindings(array $bindings, bool $isReverse = false): void
    {
        if (isset($this->scope['bindings'])) {
            if ($isReverse) {
                $this->scope['bindings'] = array_merge($bindings, $this->scope['bindings']);
            } else {
                $this->scope['bindings'] = array_merge($this->scope['bindings'], $bindings);
            }
        } else {
            $this->scope['bindings'] = $bindings;
        }
    }

    /**
     * 获取绑定的参数
     *
     * @return array
     */
    protected function getBindings(): array
    {
        if (array_key_exists('bindings', $this->scope)) {
            $bindings = $this->scope['bindings'];
            $this->scope['bindings'] = [];
        } else {
            $bindings = [];
        }
        $this->prepareBindings = $bindings;
        return $bindings;
    }

    /**
     * 记录 where
     *
     * @param string|array $condition
     * @param array        $bindings
     * @param string       $factor
     * @return void
     */
    protected function setScopeWhere(string|array $condition, array $bindings, string $factor): void
    {
        if (empty($condition)) {
            return;
        }
        $this->setScopeArrayAppend('where', compact('condition', 'bindings', 'factor'));
    }

    /**
     * 记录 having
     *
     * @param string|array $condition
     * @param array        $bindings
     * @param string       $factor
     * @return void
     */
    protected function setScopeHaving(string|array $condition, array $bindings, string $factor): void
    {
        if (empty($condition)) {
            return;
        }
        $this->setScopeArrayAppend('having', compact('condition', 'bindings', 'factor'));
    }

    /**
     * 记录 join
     *
     * @param string $type
     * @param string $table
     * @param string|array $on
     * @param string $as
     * @return void
     */
    protected function setScopeJoin(string $type, string $table, string|array $on, string $as): void
    {
        $type = strtolower($type);
        if (!in_array($type, $this->join)) {
            throw new \PDOException('Unsupported Join: ' . $type);
        }

        $this->setScopeIndie('join', $type);
        $this->setScopeArrayAppend('scope_join', compact('type', 'table', 'on', 'as'));
    }

    /**
     * @param string $function
     * @param string $column
     * @param string $alias
     * @return void
     */
    protected function setAggregateAlias(string $function, string $column, string $alias): void
    {
        if ($alias == '') {
            $alias = $column;
        }
        if ($column != '*') {
            $column = $this->fieldHandle($column);
        }
        $this->setColumns($function . $this->strDress($column) . ' as ' . $this->fieldHandle($alias));
    }

    /**
     * @param string $column
     * @return void
     */
    protected function setJsonArrayColumn(string $column): void
    {
        $columnBuild = $this->strDress($this->fieldHandle($column));
        $this->setColumns('json_arrayagg' . $columnBuild . ' as ' . $this->fieldHandle($column));
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function setJsonObjectColumn(string $key, string $value): void
    {
        $columnBuild = $this->strDress($this->fieldHandle($key) . ',' . $this->fieldHandle($value));
        $this->setColumns('json_objectagg' . $columnBuild . ' as ' . $this->fieldHandle($key));
    }

    /**
     * @param string $columns
     * @return void
     */
    protected function setColumns(string $columns): void
    {
        $this->setScopeStringAppend('columns', $columns);
    }

    /**
     * 为scope直接赋值
     * @param string $scopeName
     * @param mixed $scopeValue
     * @return void
     */
    private function setScopeIndie(string $scopeName, mixed $scopeValue): void
    {
        $this->scope[$scopeName] = $scopeValue;
    }

    /**
     * @param string $scopeName
     * @param string $scopeValue
     * @param string $separator
     * @return void
     */
    private function setScopeStringAppend(string $scopeName, string $scopeValue, string $separator = ','): void
    {
        if (array_key_exists($scopeName, $this->scope)) {
            $this->scope[$scopeName] .= $separator . $scopeValue;
        } else {
            $this->scope[$scopeName] = $scopeValue;
        }
    }

    /**
     * 获取value值为string类型的scope内容
     * @param string $scopeName
     * @return string|int
     */
    protected function getScopeStringValue(string $scopeName): string|int
    {
        return $this->scope[$scopeName] ?? '';
    }

    /**
     * @param string $scopeName
     * @param mixed $scopeValue
     * @return void
     */
    private function setScopeArrayAppend(string $scopeName, mixed $scopeValue): void
    {
        $this->scope[$scopeName][] = $scopeValue;
    }

    /**
     * 获取value值为array类型的scope内容
     *
     * @param string $scopeName
     * @return array
     */
    protected function getScopeArrayValue(string $scopeName): array
    {
        return $this->scope[$scopeName] ?? [];
    }
}

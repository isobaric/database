<?php

namespace Isobaric\Database\Drawer;

use Isobaric\Database\MySQL;
use Isobaric\Database\SQLServer;

trait Analyzer
{
    private array $symbol = [
        '=', '>', '<', '>=', '<=', 'in', 'not in', 'between', 'not between', 'is', 'like', 'not like'
    ];

    /**
     * @var array|string[]
     */
    private array $isValues = ['null', 'not null', 'false', 'true'];

    /**
     * @param string $keywords
     * @param int|string $expr
     * @return string
     */
    protected function getKeywordsExpression(string $keywords, int|string $expr): string
    {
        if ($keywords == '') {
            $keywords = ' ';
        } else {
            $keywords = $this->strDistance($keywords);
        }
        return $expr ? $keywords . $expr : '';
    }

    /**
     * @return string
     */
    protected function getJoin(): string
    {
        $scopeJoin = $this->getScopeArrayValue('scope_join');
        if (empty($scopeJoin)) {
            return '';
        }

        // 左表别名
        $tableAlias = $this->getScopeStringValue('as');
        if ($tableAlias == '') {
            $tableAlias = $this->fieldHandle($this->table);
        }

        $joinStr = '';
        foreach ($scopeJoin as $join) {

            $joinStr .= $this->strDistance($join['type']) . $this->fieldHandle($join['table']);

            // 右表别名
            if ($join['as'] == '') {
                $join['as'] = $join['table'];
            }
            $join['as'] = $this->fieldHandle($join['as']);
            $joinStr .= $this->strDistance('as') . $join['as'];

            if (empty($join['on'])) {
                continue;
            }

            if (is_array($join['on'])) {

                $onList = [];
                foreach ($join['on'] as $leftColumn => $rightColumn) {
                    $leftColumn = $this->fieldDecode(trim($leftColumn), $tableAlias);
                    $rightColumn = $this->fieldDecode(trim($rightColumn), $join['as']);
                    $onList[] = $leftColumn . $this->strDistance('=') . $rightColumn;
                }

                $join['on'] = implode(' and ', $onList);
            }
            $joinStr .= $this->strDistance('on') . $this->strDress($join['on']);
        }
        return $joinStr;
    }

    /**
     * @param array $scopeData
     * @return string
     */
    protected function whereConditionAnalyzer(array $scopeData): string
    {
        if (empty($scopeData)) {
            return '';
        }

        $where = [];
        foreach ($scopeData as $index => $package) {
            if (is_array($package['condition'])) {
                $analyzer = $this->whereListAnalyzer($package);
            } else {
                $analyzer = $this->whereStringAnalyzer($package, 0);
            }

            if ($index == 0) {
                $where[] = $analyzer;
            } else {
                $where[] = $package['factor'] . ' ' . $analyzer;
            }
        }
        return implode(' ', $where);
    }

    /**
     * @param string|array $update
     * @return void
     */
    protected function setAnalyzer(string|array $update): void
    {
        $bind = [];
        $prepare = '';
        if (is_string($update)) {
            $updateList = explode(',', $update);
            foreach ($updateList as $item) {
                $setList = explode('=', $item);
                $prepare .= $this->fieldDecode(trim(current($setList))) . ' = ' . '?, ';
                $bind[] = trim(next($setList));
            }
        } else {
            foreach ($update as $field => $value) {
                $bind[] = $value;
                $prepare .= $this->fieldDecode(trim($field)) . ' = ?, ';
            }
        }

        $this->setScopeIndie('set', rtrim($prepare, ', '));

        $this->setBindings($bind, true);
    }

    /**
     * @param array $data
     * @return void
     */
    protected function insertAnalyzer(array $data): void
    {
        $isSimple = true;
        foreach ($data as $item) {
            if (!is_array($item)) {
                break;
            }
            $isSimple = false;
            $this->insertBuildAnalyzer($item);
        }
        if ($isSimple) {
            $this->insertBuildAnalyzer($data);
        }
    }

    /**
     * @param array|string $columns
     * @return string
     */
    protected function getColumnsExpr(array|string $columns): string
    {
        if (is_string($columns)) {
            if ($columns == '*') {
                return '*';
            }
            $columns = explode(',', $columns);
        } else {
            if (count($columns) == 1 && current($columns) == '*') {
                return '*';
            }
        }

        $fieldList = [];
        foreach ($columns as $column) {
            $column = trim($column);

            // as别名
            if (str_contains($column, ' as ')) {
                $symbol = ' as ';
                $columnList = explode(' as ', $column);
            }
            // 空格别名
            else if (str_contains($column, ' ')) {
                $symbol = ' ';
                $columnList = explode(' ', $column);
            }
            // 无别名
            else {
                $symbol = '';
                $columnList = [$column];
            }

            $baseColumn = current($columnList);
            $aliasColumn = next($columnList);
            if ($aliasColumn !== false) {
                $aliasColumn = $this->fieldHandle($aliasColumn);
            }
            $fieldList[] = $this->fieldDecode($baseColumn) . $symbol . $aliasColumn;
        }
        return implode(',', $fieldList);
    }

    /**
     * @param array $data
     * @return void
     */
    private function insertBuildAnalyzer(array $data): void
    {
        $fieldStr = '';
        $valueStr = '';
        $binding = [];
        foreach ($data as $field => $value) {
            $fieldStr .= $this->fieldHandle($field) . ', ';
            $valueStr .= '?,';
            $binding[] = $value;
        }

        // insert字段拼接
        $this->setScopeIndie('insert', $this->strDress(rtrim($fieldStr, ', ')));

        // value值拼接
        $this->setScopeStringAppend('values', $this->strDress(rtrim($valueStr, ',')));

        // 参数绑定
        $this->setBindings($binding);
    }

    /**
     * @param array $package
     * @return string
     */
    private function whereListAnalyzer(array $package): string
    {
        if (is_array(current($package['condition']))) {
            $string = [];
            foreach ($package['condition'] as $key => $value) {
                $string[] = $this->whereStringAnalyzer($this->packageListAnalyzer($value, true), $key);
            }
            return $this->strDress(implode(' ', $string));
        }

        return $this->whereStringAnalyzer($this->packageListAnalyzer($package, false), 0);
    }

    /**
     * @param array $package
     * @param int   $index
     * @return string
     */
    private function whereStringAnalyzer(array $package, int $index): string
    {
        extract($package);

        if ($index == 0) {
            $factor = '';
        } else {
            $factor = $factor . ' ';
        }
        $this->setBindings($bindings);

        return $factor . $condition;
    }

    /**
     * @param array $package
     * @param bool  $isList
     * @return array
     */
    private function packageListAnalyzer(array $package, bool $isList): array
    {
        if ($isList) {
            return array_merge($this->fieldValueAnalyzer($package), ['factor' => 'and']);
        }

        return array_merge($package, $this->fieldValueAnalyzer($package['condition']));
    }

    /**
     * @param array $where
     * @return array
     */
    private function fieldValueAnalyzer(array $where): array
    {
        if (!array_is_list($where)) {
            $string = '';
            $bindings = [];
            foreach ($where as $col => $colVal) {
                $string .= $this->fieldDecode($col) . ' = ? and ';
                $bindings[] = $colVal;
            }
            return [
                'condition' => rtrim($string, ' and'),
                'bindings' => $bindings,
            ];
        }

        extract($this->symbolConvert($where));

        return [
            'condition' => $field,
            'bindings' => $bindings
        ];
    }

    /**
     * @param array $where
     * @return array
     */
    private function symbolConvert(array $where): array
    {
        $field = $this->fieldDecode(current($where));
        if (count($where) == 2) {
            $value = next($where);
            $symbol = is_array($value) ? 'in' : '=';
        } else {
            $symbol = strtolower(trim(next($where)));
            $value = next($where);
        }

        if (!in_array($symbol, $this->symbol)) {
            throw new \PDOException('Unsupported Symbol: ' . $symbol);
        }

        if ($value instanceof MySQL || $value instanceof SQLServer) {
            return $this->selectObjectValueAnalyzer($value, $symbol, $field);
        }

        switch ($symbol) {
            case 'is':
                $value = strtolower($value);
                if (!in_array($value, $this->isValues, true)) {
                    throw new \PDOException('Unsupported Is Value: ' . $value);
                }
                $field .= $this->strDistance($symbol) . $value;
                $bindings = [];
                break;
            case 'between':
            case 'not between':
                $field .= $this->strDistance($symbol) . '? and ?';
                $bindings = $value;
                break;
            case 'in':
            case 'not in':
                $field .= $this->strDistance($symbol) . $this->strDress(rtrim(str_repeat('?,', count($value)), ','));
                $bindings = $value;
                break;
            default:
                $field .= $this->strDistance($symbol) . '?';
                $bindings = [$value];
        }
        return [
            'field' => $field,
            'bindings' => $bindings
        ];
    }

    /**
     * @param MySQL|SQLServer $object
     * @param string $symbol
     * @param string $field
     * @return array
     */
    private function selectObjectValueAnalyzer(MySQL|SQLServer $object, string $symbol, string $field): array
    {
        $subField = $object->getScopeStringValue('scope_sub_field');
        $rowExpr = $object->getColumnsExpr($subField);
        $object->setColumns($rowExpr);

        $prepare = $object->getExpression('fetchAll');

        $field .= ' ' . $symbol . ' ' . $this->strDress($prepare);

        return [
            'field' => $field,
            'bindings' => $object->getBindings()
        ];
    }

    /**
     * 包装字段名
     *  处理含有.的字段名
     *  如果是JSON字段(c->"$.id")应该使用Raw方法
     *
     *  如果 $table != '' 则使用$table作为字段的引用表
     *
     * @param string $field
     * @param string $table
     * @return string
     */
    protected function fieldDecode(string $field, string $table = ''): string
    {
        if (str_contains($field, '.')) {
            $asFields = explode('.', $field);
            return $this->fieldHandle(current($asFields)) . '.' . $this->fieldHandle(next($asFields));
        }
        
        if ($table != '') {
            return $table . '.' . $this->fieldHandle($field);
        }

        return $this->fieldHandle($field);
    }
}

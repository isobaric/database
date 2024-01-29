<?php

namespace Isobaric\Database\Drawer\SQLServer;

trait Section
{
    /**
     * @param string $function
     * @return array|string[]
     */
    protected function expressionKeywords(string $function): array
    {
        return match ($function) {
            in_array($function, $this->scopeSelect) ? $function : false => [
                'top' ,'columns', 'as', 'join', 'where', 'group by', 'having', 'order by', 'offset'
            ],
            in_array($function, $this->aggregate) ? $function : false => [
                'columns', 'as', 'join', 'where', 'group by', 'having'
            ],
            'insert', 'insertGetId' => [
                'insert', 'values'
            ],
            'update' => [
                'top' ,'as', 'set', 'where', 'order by'
            ],
            'delete' => [
                'top' ,'as', 'where', 'order by'
            ],
            'truncate' => [],
            default => throw new \PDOException('missing scope keys'),
        };
    }

    /**
     * @return string
     */
    protected function getLimit(): string
    {
        $top = $this->getScopeStringValue('top');
        $offset = $this->getScopeStringValue('offset');
        if ($top === '' || $offset !== '') {
            return '';
        }
        return 'top ' . $top . ' ';
    }

    /**
     * @return string
     */
    protected function getOffset(): string
    {
        $top = $this->getScopeStringValue('top');
        $offset = $this->getScopeStringValue('offset');
        if ($offset === '') {
            return '';
        }
        if ($top === '') {
            $top = 0;
        }
        return ' offset ' . $offset . ' rows fetch next ' . $top . ' rows only';
    }

    /**
     * @return string
     */
    protected function truncateHeader(): string
    {
        return 'truncate table ' . $this->fieldHandle($this->table);
    }
}

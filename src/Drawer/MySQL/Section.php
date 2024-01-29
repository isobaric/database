<?php

namespace Isobaric\Database\Drawer\MySQL;

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
                'columns', 'as', 'join', 'where', 'group by', 'having', 'order by', 'limit', 'offset', 'lock'
            ],
            in_array($function, $this->aggregate) ? $function : false => [
                'columns', 'as', 'join', 'where', 'group by', 'having', 'lock'
            ],
            'insert', 'insertGetId', 'replace', 'replaceGetId' => [
                'insert', 'values'
            ],
            'update' => [
                'as', 'set', 'where', 'order by', 'limit'
            ],
            'delete' => [
                'as', 'where', 'order by', 'limit'
            ],
            'truncate' => [],
            default => throw new \PDOException('missing scope keys'),
        };
    }

    /**
     * @return int|string
     */
    protected function getLimit(): int|string
    {
        return $this->getKeywordsExpression('limit', $this->getScopeStringValue('limit'));
    }

    /**
     * @return int|string
     */
    protected function getOffset(): int|string
    {
        return $this->getKeywordsExpression('offset', $this->getScopeStringValue('offset'));
    }

    /**
     * @return string
     */
    protected function truncateHeader(): string
    {
        return 'truncate ' . $this->fieldHandle($this->table);
    }
}

<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class QueryBuilderService
{
    protected array $operators = [
        '$eq' => '=',
        '$ne' => '!=',
        '$lt' => '<',
        '$lte' => '<=',
        '$gt' => '>',
        '$gte' => '>=',
        '$contains' => 'LIKE',
        '$notContains' => 'NOT LIKE',
        '$in' => 'IN',
        '$notIn' => 'NOT IN',
        '$null' => 'IS NULL',
        '$notNull' => 'IS NOT NULL',
    ];

    public function apply(Builder $query, array $params): Builder
    {
        if (isset($params['filters'])) {
            $this->applyFilters($query, $params['filters']);
        }

        if (isset($params['sort'])) {
            $this->applySort($query, $params['sort']);
        }

        if (isset($params['fields'])) {
            $this->applyFields($query, $params['fields']);
        }

        if (isset($params['populate'])) {
            $this->applyPopulate($query, $params['populate']);
        }

        return $query;
    }

    protected function applyFilters(Builder $query, array $filters)
    {
        foreach ($filters as $field => $conditions) {
            // Handle field nesting for JSON or relations if needed (simplified for now)
            if (is_array($conditions)) {
                foreach ($conditions as $operator => $value) {
                    $this->applyCondition($query, $field, $operator, $value);
                }
            } else {
                // Default to $eq if no operator provided
                $this->applyCondition($query, $field, '$eq', $conditions);
            }
        }
    }

    protected function applyCondition(Builder $query, string $field, string $operator, $value)
    {
        if (! isset($this->operators[$operator])) {
            return;
        }

        $sqlOperator = $this->operators[$operator];

        switch ($operator) {
            case '$contains':
            case '$notContains':
                $value = '%'.$value.'%';
                $query->where($field, $sqlOperator, $value);
                break;
            case '$in':
            case '$notIn':
                $query->whereIn($field, (array) $value, 'and', $operator === '$notIn');
                break;
            case '$null':
            case '$notNull':
                if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                    if ($operator === '$null') {
                        $query->whereNull($field);
                    } else {
                        $query->whereNotNull($field);
                    }
                } else {
                    if ($operator === '$null') {
                        $query->whereNotNull($field);
                    } else {
                        $query->whereNull($field);
                    }
                }
                break;
            default:
                $query->where($field, $sqlOperator, $value);
        }
    }

    protected function applySort(Builder $query, $sort)
    {
        // sort=title:asc or sort[0]=title:asc
        $sortParams = is_array($sort) ? $sort : explode(',', $sort);

        foreach ($sortParams as $param) {
            if (str_contains($param, ':')) {
                [$field, $direction] = explode(':', $param);
            } else {
                $field = $param;
                $direction = 'asc';
            }
            $query->orderBy($field, $direction);
        }
    }

    protected function applyFields(Builder $query, array $fields)
    {
        // We always need ID for internals, so ensure it's there.
        if (! in_array('id', $fields)) {
            array_unshift($fields, 'id');
        }
        $query->select($fields);
    }

    protected function applyPopulate(Builder $query, $populate)
    {
        if ($populate === '*') {
            return;
        }

        $relations = is_array($populate) ? $populate : explode(',', $populate);
        $query->with($relations);
    }
}

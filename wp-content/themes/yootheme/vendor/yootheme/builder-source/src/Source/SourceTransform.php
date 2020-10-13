<?php

namespace YOOtheme\Builder\Source;

use function YOOtheme\app;
use YOOtheme\Arr;
use YOOtheme\Builder\Source;
use YOOtheme\Str;

class SourceTransform
{
    /**
     * @var array
     */
    public $filters;

    /**
     * Constructor.
     *
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = array_merge([
            'date' => [$this, 'applyDate'],
            'limit' => [$this, 'applyLimit'],
            'search' => [$this, 'applySearch'],
            'before' => [$this, 'applyBefore'],
            'after' => [$this, 'applyAfter'],
            'condition' => [$this, 'applyCondition'],
        ], $filters);
    }

    /**
     * Adds a filter.
     *
     * @param string   $name
     * @param callable $filter
     * @param int      $offset
     */
    public function addFilter($name, callable $filter, $offset = null)
    {
        Arr::splice($this->filters, $offset, 0, [$name => $filter]);
    }

    /**
     * Transform callback.
     *
     * @param object $node
     * @param array  $params
     *
     * @return bool|void
     */
    public function __invoke($node, array $params)
    {
        if (empty($node->source->query->name) || empty($node->source->props)) {
            return;
        }

        // query source data
        $data = app(Source::class)->querySource($node->source, $params);

        // map source properties
        if ($data && empty($data[0])) {
            return $this->mapSource($node, $params + compact('data'));
        }

        // map source array
        if ($data) {
            return $this->repeatSource($node, $params + compact('data'));
        }

        return false;
    }

    /**
     * Map source properties.
     *
     * @param object $node
     * @param array  $params
     *
     * @return object|bool
     */
    public function mapSource($node, array $params)
    {
        foreach ((array) @$node->source->props as $name => $prop) {

            $value = trim((string) Arr::get($params, "data.{$prop->name}"));
            $filters = isset($prop->filters) ? (array) $prop->filters : [];

            // apply value filters
            foreach (array_intersect_key($this->filters, $filters) as $key => $filter) {
                $value = $filter($value, $filters[$key], $filters, $params);
            }

            // check condition value
            if ($name === '_condition' && $value === false) {
                return false;
            }

            // set filtered value
            $node->props[$name] = $value;
        }

        return $node;
    }

    /**
     * Repeat node for each source item.
     *
     * @param object $node
     * @param array  $params
     *
     * @return bool
     */
    public function repeatSource($node, array $params)
    {
        $nodes = [];

        // clone node for each item
        foreach ($params['data'] as $data) {

            $clone = clone $node;
            $clone->transient = true;
            $clone->source = (object) [
                'props' => $node->source->props,
            ];

            if ($this->mapSource($clone, compact('data') + $params)) {
                $nodes[] = $clone;
            }
        }

        // insert all cloned nodes after current node
        if ($nodes) {
            array_splice($params['parent']->children, $params['i'] + 1, 0, $nodes);
        }

        return false;
    }

    /**
     * Apply "before" filter.
     *
     * @param mixed $value
     * @param mixed $before
     *
     * @return string
     */
    public function applyBefore($value, $before)
    {
        return $value ? ($before . $value) : $value;
    }

    /**
     * Apply "after" filter.
     *
     * @param mixed $value
     * @param mixed $after
     *
     * @return string
     */
    public function applyAfter($value, $after)
    {
        return $value ? ($value . $after) : $value;
    }

    /**
     * Apply "limit" filter.
     *
     * @param mixed $value
     * @param mixed $limit
     *
     * @return string
     */
    public function applyLimit($value, $limit)
    {
        return $limit ? Str::limit(strip_tags($value), intval($limit)) : $value;
    }

    /**
     * Apply "date" filter.
     *
     * @param mixed $value
     * @param mixed $format
     *
     * @return false|string
     */
    public function applyDate($value, $format)
    {
        if (is_string($value)) {
            $value = strtotime($value);
        }

        return date($format ?: 'd/m/Y', intval($value) ?: time());
    }

    /**
     * Apply "search" filter.
     *
     * @param mixed $value
     * @param mixed $search
     * @param array $filters
     *
     * @return false|string
     */
    public function applySearch($value, $search, array $filters)
    {
        $replace = isset($filters['replace']) ? $filters['replace'] : '';

        if ($search && $search[0] === '/') {
            return @preg_replace($search, $replace, $value);
        }

        return str_replace($search, $replace, $value);
    }

    /**
     * Apply "condition" filter.
     *
     * @param mixed $value
     * @param mixed $operator
     * @param array $filters
     *
     * @return false|string
     */
    public function applyCondition($value, $operator, array $filters)
    {
        $val = isset($filters['condition_value']) ? $filters['condition_value'] : '';

        if ($operator === '!') {
            return empty($value);
        }

        if ($operator === '!!') {
            return !empty($value);
        }

        if ($operator === '=') {
            return $value == $val;
        }

        if ($operator === '!=') {
            return $value != $val;
        }

        if ($operator === '<') {
            return $value < $val;
        }

        if ($operator === '>') {
            return $value > $val;
        }

        if ($operator === '~=') {
            return Str::contains($value, $val);
        }

        if ($operator === '!~=') {
            return !Str::contains($value, $val);
        }

        return $value;
    }
}

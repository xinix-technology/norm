<?php
namespace Norm\Schema;

use Norm\Model;
use Norm\Session;
use Norm\FilterContext;

abstract class NField
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param array
     */
    protected $filters = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function execFilter(Model $row, Session $session, bool $partial = false)
    {
        if ($partial && !array_key_exists($this->name, $row)) {
            return;
        }

        $value = $this->prepare(@$row[$this->name]);

        $context = new FilterContext($session, $row, $this);
        foreach ($this->filters as $filter) {
            $value = $filter($value, $context);
        }

        $row[$this->name] = $value;
    }

    public function prepare($value)
    {
        // when value is string, trim first before filtering
        if (is_string($value)) {
            $value = trim($value);
        }

        return $this->execPrepare($value);
    }

    abstract protected function execPrepare($value);
}

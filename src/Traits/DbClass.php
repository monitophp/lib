<?php

namespace MonitoLib\Traits;

trait DbClass
{
    private $daoClassName;
    private $dtoClassName;
    private $modelClassName;

    public function getDbClass(): void
    {
        $classParts = explode('\\', get_class($this));
        $namespace  = join('\\', array_slice($classParts, 0, -2)) . '\\';
        $className  = end($classParts);

        $this->daoClassName   = $namespace . 'Dao\\' . $className;
        $this->dtoClassName   = $namespace . 'Dto\\' . $className;
        $this->modelClassName = $namespace . 'Model\\' . $className;
    }
}

<?php

namespace Tochka\Promises\Support;

trait Database
{
    /** @var int */
    private $id;

    public function save()
    {

    }
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}

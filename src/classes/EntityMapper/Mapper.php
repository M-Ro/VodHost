<?php
namespace VodHost\EntityMapper;

abstract class Mapper
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }
}

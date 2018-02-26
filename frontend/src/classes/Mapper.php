<?php
namespace App\Frontend;

abstract class Mapper
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }
}

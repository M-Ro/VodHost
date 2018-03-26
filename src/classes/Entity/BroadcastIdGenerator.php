<?php
namespace VodHost\Entity;

use Doctrine\ORM\Id\AbstractIdGenerator;

class BroadcastIdGenerator extends AbstractIdGenerator
{
    /**
     * Generates a 10 character random alphanumeric string.
     *
     * @return string $str - generated string
     */
    public function generate(\Doctrine\ORM\EntityManager $em, $entity)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        $str = '';
        for ($i = 0; $i < 10; $i++) {
            $str .= $characters[rand(0, $charactersLength - 1)];
        }

        return $str;
    }
}

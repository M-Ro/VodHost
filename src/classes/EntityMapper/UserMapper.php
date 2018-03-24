<?php
namespace VodHost\EntityMapper;

use VodHost\Entity\UserEntity as UserEntity;

class UserMapper extends Mapper
{
    /**
     * Return all users in database
     *
     * @return array[UserEntity] Array of Users
     */
    public function getUsers()
    {
        $records = $this->em->getRepository(UserEntity::class)->findAll();
        return $records;
    }

    /**
     * Get user by ID
     *
     * @param int $user_id The ID of the user
     * @return UserEntity  The User
     */
    public function getUserById($user_id)
    {
        $result = $this->em->getRepository(UserEntity::class)->findOneBy(['id' => $user_id]);
        return $result;
    }

    /**
     * Get user by Email
     *
     * @param str $user_email The email address of the user
     * @return UserEntity  The User
     */
    public function getUserByEmail($user_email)
    {
        $result = $this->em->getRepository(UserEntity::class)->findOneBy(['email' => $user_email]);
        return $result;
    }

    /**
     * Get user by Username
     *
     * @param str $user_name The username of the user
     * @return UserEntity  The User
     */
    public function getUserByUsername($user_name)
    {
        $result = $this->em->getRepository(UserEntity::class)->findOneBy(['username' => $user_name]);
        return $result;
    }

    /**
     * Serialize a UserEntity to Database
     *
     * @param UserEntity $user The UserEntity object
     */
    public function save(UserEntity $user)
    {
        $this->em->persist($user);
        $this->em->flush();

        /* We make the first user an admin */
        if ($user->getId() == 1) {
            $user->setAdmin(true);
            $user = $this->em->merge($user);
            $this->em->flush();
        }
    }

    /**
     * Remove a UserEntity from the database
     *
     * @param $id - id of the user
     */
    public function delete($id)
    {
        $this->em->createQueryBuilder()
            ->delete(UserEntity::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}

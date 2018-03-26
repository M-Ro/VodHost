<?php
namespace VodHost\EntityMapper;

use VodHost\Entity\BroadcastEntity as BroadcastEntity;

class BroadcastMapper extends Mapper
{
    /** Return all broadcasts in database
     *  @return array[BroadcastEntity] Array of Broadcasts
     */
    public function getBroadcasts()
    {
        $records = $this->em->getRepository(BroadcastEntity::class)->findAll();
        return $records;
    }

    /** Return the recent broadcasts that are public
     *
     *  @param $limit - Maximum number of results to return
     *  @return array[BroadcastEntity] Array of Broadcasts
     */
    public function getRecentBroadcasts(int $limit = 30)
    {
        $result = $this->em->getRepository(BroadcastEntity::class)->findBy(
          ['visibility' => true],
          ['upload_date' => 'DESC'],
          $limit,
          null // offset
        );

        return $result;
    }

    /**
     * Get broadcast by ID
     *
     * @param int $broadcast_id The ID of the broadcast
     * @return BroadcastEntity  The Broadcast
     */
    public function getBroadcastById($broadcast_id)
    {
        $result = $this->em->getRepository(BroadcastEntity::class)->findOneBy(['id' => $broadcast_id]);
        return $result;
    }

    public function getBroadcastsByUserId($user_id)
    {
        $result = $this->em->getRepository(BroadcastEntity::class)->findBy(['user_id' => $user_id]);
        return $result;
    }

    /**
     * Serialize a BroadcastEntity to database
     *
     * @param BroadcastEntity $broadcast The BroadcastEntity object
     */
    public function save(BroadcastEntity $broadcast)
    {
        $this->em->persist($broadcast);
        $this->em->flush();
    }

    /**
     * Update a BroadcastEntity with new object properties
     *
     * @param BroadcastEntity $broadcast The BroadcastEntity object
     */
    public function update(BroadcastEntity $broadcast)
    {
        $this->em->merge($broadcast);
        $this->em->flush();
    }

    /**
     * Delete a BroadcastEntity from the database
     *
     * @param BroadcastEntity $broadcast The BroadcastEntity object
     */
    public function delete(BroadcastEntity $broadcast)
    {
        $this->em->remove($broadcast);
        $this->em->flush();
    }

    /**
     * Update the visibility status of a broadcast
     *
     * @param $id - id of the broadcast
     * @param $vis - new visibility state
     */
    public function changeVisibility($id, $vis)
    {
        $fetch = $this->em->getRepository(BroadcastEntity::class)->findBy(['id' => $id]);

        foreach ($fetch as $object) {
            $object->visibility = $vis;
            $this->em->flush();
        }
    }

    /**
     * Increment the number of views on a broadcast
     *
     * @param $id - id of the broadcast
     */
    public function incrementBroadcastViews($id)
    {
        $query = $this->em->createQuery('
            UPDATE VodHost\Entity\BroadcastEntity t
            SET t.views = t.views + 1 WHERE t.id = ?1');
        $query->setParameter(1, $id);
        $query->execute();
    }

    /**
     * Remove a BroadcastEntity from the database
     *
     * @param $id - id of the broadcast
     */
    public function deleteById($id)
    {
        $this->em->createQueryBuilder()
            ->delete(BroadcastEntity::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Generates a 10 character random alphanumeric string.
     *
     * @return string $str - generated string
     */
    public function generateUniqueID()
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

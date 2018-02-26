<?php
namespace App\Frontend;

use App\Frontend\Entity\BroadcastEntity as BroadcastEntity;

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
     * Remove a BroadcastEntity from the database
     *
     * @param $id - id of the broadcast
     */
    public function delete($id)
    {
        $this->em->createQueryBuilder()
            ->delete(BroadcastEntity::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }
}

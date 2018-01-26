<?php
namespace App\Backend;

class BroadcastMapper extends Mapper
{
	/* Initial migration */
	public function createBroadcastsTable()
	{
        $sql = "CREATE TABLE IF NOT EXISTS broadcasts (
	        id INTEGER PRIMARY KEY,
            user_id INTEGER,
	        title TEXT NOT NULL,
	        length INTEGER NOT NULL,
	        visibility INTEGER NOT NULL,
            FOREIGN KEY(user_id) REFERENCES user(id));";

	    $this->db->exec($sql);
	}	

	/** Return all broadcasts in database
	 *  @return array[BroadcastEntity] Array of Broadcasts
	 */
	public function getBroadcasts()
	{
		$sql = "SELECT id, user_id, title, length, visibility
            from broadcasts;";

        $stmt = $this->db->query($sql);
        
        $results = [];
        while($row = $stmt->fetch()) {
            $results[] = new BroadcastEntity($row);
        }

        return $results;
	}

	/**
     * Get broadcast by ID
     *
     * @param int $broadcast_id The ID of the broadcast
     * @return BroadcastEntity  The Broadcast
     */
	public function getBroadcastById($broadcast_id) {
        $sql = "SELECT id, user_id, title, length, visibility from broadcasts
                    where id = :broadcast_id";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(["broadcast_id" => $broadcast_id]);

        if($result) {
            return new BroadcastEntity($stmt->fetch());
        }
    }

    /**
     * Serialize a BroadcastEntity to Database
     *
     * @param BroadcastEntity $broadcast The BroadcastEntity object
     */
    public function save(BroadcastEntity $broadcast) {
        $sql = "insert into broadcasts (user_id, title, length, visibility) values
            (:user_id, :title, :length, :visibility)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            "user_id" => $broadcast->getUserId(),
            "title" => $broadcast->getTitle(),
            "length" => $broadcast->getLength(),
            "visibility" => $broadcast->getVisibility()
        ]);

        if(!$result) {
            throw new Exception("could not save record");
        }
    }
}

?>
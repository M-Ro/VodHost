<?php
namespace App\Backend;

class UserMapper extends Mapper
{
	/* Initial migration */
	public function createUsersTable()
	{
        $sql = "CREATE TABLE IF NOT EXISTS users (
	        id INTEGER PRIMARY KEY,
	        username TEXT UNIQUE,
	        email TEXT UNIQUE,
	        password TEXT NOT NULL);";

	    $this->db->exec($sql);
	}	

	/** Return all users in database
	 *  @return array[UserEntity] Array of Users
	 */
	public function getUsers()
	{
		$sql = "SELECT id, username, email, password
            from users;";

        $stmt = $this->db->query($sql);
        
        $results = [];
        while($row = $stmt->fetch()) {
            $results[] = new UserEntity($row);
        }

        return $results;
	}

	/**
     * Get user by ID
     *
     * @param int $user_id The ID of the user
     * @return UserEntity  The User
     */
	public function getUserById($user_id) {
        $sql = "SELECT id, username, email, password from users
                    where id = :user_id";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(["user_id" => $user_id]);

        if($result) {
            return new UserEntity($stmt->fetch(\PDO::FETCH_ASSOC));
        }
    }

    /**
     * Get user by Email
     *
     * @param str $user_email The email address of the user
     * @return UserEntity  The User
     */
    public function getUserByEmail($user_email) {
        $sql = "SELECT id, username, email, password from users
                    where email = :user_email";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(["user_email" => $user_email]);

        if($result) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if($row)
                return new UserEntity($row);
        }

        return null;
    }

    /**
     * Serialize a UserEntity to Database
     *
     * @param UserEntity $user The UserEntity object
     */
    public function save(UserEntity $user) {
        $sql = "insert into users (username, email, password) values
            (:username, :email, :password)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            "email" => $user->getEmail(),
            "username" => $user->getUsername(),
            "password" => $user->getPassword(),
        ]);

        if(!$result) {
            throw new Exception("could not save record");
        }
    }
}

?>
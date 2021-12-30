<?php
class User
{

    // database connection and table name
    private $conn;
    private $table_name = 'users';

    // object properties
    public $name;
    public $email;
    public $password;


    // constructor with $db as database connection
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // read products
    function read()
    {

        // select all query
        $query = "SELECT
                name, email, password, role
            FROM
                " . $this->table_name;

        // prepare query statement
        $stmt = $this->conn->prepare($query);

        // execute query
        $stmt->execute();

        return $stmt;
    }

    // create merchant in user table
    function create()
    {

        //query to insert record
        $query = "INSERT INTO
                " . $this->table_name . "
            SET
                name=:name, email=:email, password=:password, remaining_email=30, role='MERCHANT' ,pkg_id=1";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));

        //Statement to check error in your query
        // if (!$stmt->execute()) {
        //     print_r($stmt->errorInfo());
        // }

        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);


        // execute query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // create merchant in user table
    function createUsers()
    {

        //query to insert record
        $query = "INSERT INTO
                " . $this->table_name . "
            SET
                name=:name, email=:email, password=:password, role='S_USERS'";

        // prepare query
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));

        //Statement to check error in your query
        // if (!$stmt->execute()) {
        //     print_r($stmt->errorInfo());
        // }

        // bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);


        // execute query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
}

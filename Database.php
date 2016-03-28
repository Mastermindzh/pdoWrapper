<?php
/**
 * Created by PhpStorm.
 * User: mastermindzh
 * Date: 3/13/16
 * Time: 7:45 PM
 */
class Database{

    private $conn;
    private $error;
    private $stmt;

    /**
     *  Constructor. Initiates database connection
     * @param String $servername The host servername that you want to connect with
     * @param String $username   A username to access the $servername
     * @param String $password   The password belonging to $username
     * @param String $database	 A databaseunder $servername
     * @see sqlsrv_connect
     */
    function __construct($servername, $username, $password, $database, $type = "mysql"){
        $dsn = $this->getHost($type). $servername . ';dbname=' . $database;
        $options = array(
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );
        try {
            $this->conn = new PDO($dsn, $username, $password, $options);
        }// Catch any errors
        catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * return a valid host string
     * @param $str which db connection string to return / entire host string
     * @return string
     */
    private function getHost($str){
        switch(strtolower($str)){
            case "mysql":
                return "mysql:host=";
            case "mssql":
                return "dblib:host=";
            case "postgre":
                return "pgsql:host=";
            default:
                return $str;
        }
    }

    /**
     * prepares a sql statement
     * @param $sql statement to be prepared
     */
    public function prepare($sql){
        $this->stmt = $this->conn->prepare($sql);
    }

    /**
     * bind variables to prepared statement
     * @param $param  placeholder value that we will be using in our SQL statement, example :name.
     * @param $value actual value that we want to bind to the placeholder, example “John Smith”.
     * @param null $type the datatype of the parameter, example string.
     */
    public function bind($param, $value, $type = null){
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);

    }

    /**
     * executes the prepared statement
     * @return mixed
     */
    public function execute(){
        return $this->stmt->execute();
    }

    /**
     * returns an array with values from the database
     * @type Return type. http://php.net/manual/en/pdostatement.fetch.php
     * @return mixed
     */
    public function getResultset($type = PDO::FETCH_ASSOC){
        $this->execute();
        return $this->stmt->fetchAll($type);
    }

    /**
     * run a query
     * @param $sql query to run
     * @param array ...$args Paramaters to fill ? in $sql with
     * @return bool|Sqlsrv_stmt return true if there are results. false if there are non.
     */
    public function query($sql, ...$args ) {
        $this->prepare($sql);

        for($i = 0; $i < count($args); $i++){
            $this->bind($i+1,$args[$i]);
        }

        if(strrpos(strtolower($sql),'select', -strlen(strtolower($sql))) !== false){
            $arr = $this->getResultset();
            if(empty($arr)){
                return false;
            }else{
                return $arr;
            }
        }else{
            $this->execute();
            return true;
        }
    }
    
    /**
     * run a (prepared) query and returns a single row
     * @param $sql query to run
     * @param array ...$args Paramaters to fill ? in $sql with
     * @return bool|Sqlsrv_stmt return true if there are results. false if there are non.
     */
    public function singleQuery($sql, ...$args ){
		$this->prepare($sql);

        for($i = 0; $i < count($args); $i++){
            $this->bind($i+1,$args[$i]);
        }
        
        return $this->getSingle();
	}

    /**
     * Run a batch of inserts
     * @param $sql SQL statement
     * @param array ...$args Arrays with values
     * @return bool true if succeeded.
     * @throws Exception if there is ANY error at all throw an exception
     */
    public function batch($sql, ...$args ) {
        $this->prepare($sql);

        foreach($args as $arg){
            for($i = 0; $i < count($arg); $i++){
                $this->bind($i+1,$arg[$i]);
            }
            try{
                $this->execute();
            }catch (Exception $e){
                //we want it to keep running
            }
        }
        return true;
    }

    /**
     * Run a batch of inserts with a transaction.
     * All will fail if 1 insert fails
     * @param $sql SQL statement
     * @param array ...$args Arrays with values
     * @return bool true if succeeded.
     * @throws Exception if there is ANY error at all throw an exception
     */
    public function safeBatch($sql, ...$args ) {
        $this->transactionBegin();
        try{
            $this->prepare($sql);

            foreach($args as $arg){
                for($i = 0; $i < count($arg); $i++){
                    $this->bind($i+1,$arg[$i]);
                }
                $this->execute();
            }
            $this->transactionCommit();
        }catch (Exception $e){
            $this->transactionRollback();
            throw new Exception($e->getMessage());
        }
        return true;
    }

    /**** Transactions ****/
    /**
     * Initiates a transaction
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function transactionBegin(){
        return $this->conn->beginTransaction();
    }

    /**
     * Commits a transaction
     * @link http://php.net/manual/en/pdo.commit.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function transactionCommit(){
        return $this->conn->commit();
    }

    /**
     * Rolls back a transaction
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function transactionRollback(){
        return $this->conn->rollBack();
    }
	
	/**
     * closes the DB connection
     * @link http://php.net/manual/en/pdo.connections.php
     */
	public function close(){
		$this->conn = null;
	}
    /**
     * Set an attribute
     * @link http://php.net/manual/en/pdo.setattribute.php
     * @param int $attribute
     * @param mixed $value
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure
     */
    public function setAttribute($attribute, $value){
        return $this->getConn()->setAttribute($attribute,$value);
    }
    /**
     * returns a single row from the database
     * @return mixed
     */
    public function getSingle(){
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * returns the numnber of rows in the current statement
     * @return mixed
     */
    public function getRowCount(){
        return $this->stmt->rowCount();
    }

    /**
     * Dump an SQL prepared command
     * @link http://php.net/manual/en/pdostatement.debugdumpparams.php
     * @return bool No value is returned.
     */
    public function getDebug(){
        return $this->stmt->debugDumpParams();
    }

    /**
     * returns the ID of the last succesful insert
     * @return string
     */
    public function getLastInsertId(){
        return $this->conn->lastInsertId();
    }

    /**
     * return the connection error as string
     * @return string
     */
    public function getError(){
        return $this->error;
    }

    /**
     * return the database handler
     * @return PDO
     */
    private function getConn(){
        return $this->conn;
    }

}

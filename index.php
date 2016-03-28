<?php
/**
 * Created by PhpStorm.
 * User: mastermindzh
 * Date: 3/13/16
 * Time: 10:06 PM
 */
/**
 * @param $arr array to be presented nicely
 */
function read_r($arr){
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

/**
 * @param $msg message to print encapsulated with h1
 */
function heading($msg){
    echo '<h1>'.$msg.'</h1>';
}

include('Database.php');

//the password is obv. NOT a public password ;)
$database = new Database("localhost","dev", "T83VDoTPQ9VzZJ8o" , "test");

if(!empty($database->getError())){
    heading("Database connection error:");
    read_r($database->getError());
}

heading("Manual PDO with ?");
$database->prepare("select FName,LName,Age,Gender from mytable where FName = ? AND LName = ?");
$database->bind(1,"John");
$database->bind(2,"Smith");
$row = $database->getResultset();
echo 'Number of rows found: '.$database->getRowCount();
read_r($row);

heading("Manual PDO with :name");
$database->prepare('SELECT FName, LName, Age, Gender FROM mytable WHERE FName = :fname AND LName = :lname');
$database->bind(':fname', 'John');
$database->bind(':lname', 'Smith');
$row = $database->getResultset();
echo 'Number of rows found: '.$database->getRowCount();
read_r($row);


heading("If statement which fails (empty resultset) *uses query*");
$sql = "select FName,LName,Age,Gender from mytable where FName = ? AND LName = ?";
if($row = $database->query($sql,"sdfg","tsdgsdg")){
    read_r($row);
}else{
    echo 'Resultset is empty';
}

heading("If statement which passes *uses query*");
$sql = "select FName,LName,Age,Gender from mytable where FName = ? AND LName = ?";
if($row = $database->query($sql,"John","Smith")){
    read_r($row);
}else{
    echo 'Resultset is empty';
}

heading("If statement which passes *uses singleQuery*");
$sql = "select FName,LName,Age,Gender from mytable where FName = ? AND LName = ?";
if($row = $database->singleQuery($sql,"John","Smith")){
    read_r($row);
}else{
    echo 'Resultset is empty';
}

heading("Insert with query method");
try{
    $row = $database->query("INSERT INTO mytable (FName, LName, Age, Gender) VALUES (?,?,?,?)","querytest","querytest",35,"male");
    if($row){
        echo 'Insert succesful with ID: '.$database->getLastInsertId();
    }
}catch(Exception $e){
    read_r($e->getMessage());
}

heading("Batch");
try{
    $row = $database->batch("INSERT INTO mytable (FName, LName, Age, Gender) VALUES (?,?,?,?)"
        ,array("batch1","batch1",35,"male")
        ,array("batch2","batch2",35,"male")
        ,array("batch3","batch3",35,"male")
    );
    if($row){
        echo 'Insert succesful! Last ID: '.$database->getLastInsertId();
    }
}catch(Exception $e){
    read_r($e->getMessage());
}

heading("Batch with transaction");
try{
    $row = $database->safeBatch("INSERT INTO mytable (FName, LName, Age, Gender) VALUES (?,?,?,?)"
        ,array("batch1","batch1",35,"male")
        ,array("batch2","batch2",35,"test")
        ,array("batch3","batch3",35,5)
    );
    if($row){
        echo 'Insert succesful!';
    }
}catch(Exception $e){
    read_r($e->getMessage());
}

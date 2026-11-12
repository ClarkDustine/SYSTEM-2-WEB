<?php
session_start();

require 'dbcon.php';

// input field validation 
function validate($inputData){

     global $conn;
     $validated_data = mysqli_real_escape_string($conn, $inputData);
     return trim ($validated_data);
}

// redirect from 1 page to another page with a message (status)
function redirect($url, $status){
    $_SESSION['status'] = $status;
    header('Location: ' . $url);
    exit(0);
}

// Display message or status after any process.
function alertMessage(){

     if(isset($_SESSION['status'])){
         echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
               <h6> '. $_SESSION['status'] .' </h6>
               <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        unset($_SESSION['status']);
     }
}

// insert record using this function
function insert($tablename, $data)
{
  global $conn;

 $table = validate($tablename);

    $columns = array_keys($data);
    $values = array_values($data);

    $finalColumns = implode(', ', $columns);
    $finalValues = "'".implode("', '", $values)."'";

    $query = "INSERT INTO $table ($finalColumns) VALUES ($finalValues)";
    $result = mysqli_query($conn, $query);
    return $result;
}

// Update data using this function
function update($tableName, $id, $data){

     global $conn;

     $table = validate($tableName);
     $id = validate($id);

     $updateDataString = "";

    foreach($data as $column => $value){
        $updateDataString .= $column. '='."'$value', ";
    }

    $finalUpdateData = substr(trim($updateDataString),0, -1);
    $query = "UPDATE $table SET $finalUpdateData WHERE id='$id'";
    $result = mysqli_query($conn, $query);
    return $result;
}

function getALL($tableName, $status = NULL){

        global $conn;

        $table = validate($tableName);
        $status = validate($status);

        if($status == 'status')
            {
                $query = "SELECT * FROM $table WHERE status='0'";
            }

            {
                $query = "SELECT * FROM $table";
            }
        return mysqli_query($conn, $query);

}

function getById($tableName, $id){

    global $conn;

    $table = validate($tableName);
    $id = validate($id);

    $query = "SELECT * FROM $table WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if($result){

        if(mysqli_num_rows($result) == 1){  

        $row = mysqli_fetch_assoc($result);
        $response = [
            'status' => 200,
            'data' => $row,
            'message' => 'Record found'
        ];
        return $response;

    }else{
               $response = [
            'status' => 404,
            'message' => 'No data found'
        ];
        return $response;
        }

    }else{
        $response = [
            'status' => 500,
            'message' => 'Something Went Wrong'
        ];
        return $response;
    }
}

// Delete data from database using id
function delete($tableName, $id){

    global $conn;

    $table = validate($tableName);
    $id = validate($id);

    $query = "DELETE FROM $table WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    return $result;
}

function checkParamId($type)
{
 if(isset($_GET[$type])) {
    if($_GET[$type] != ''){
        return $_GET[$type];
    }else{
        echo '<h5>No id found</h5>';
        return false;
    }

 }else{
   return '<h5>No Id Given</h5>';
 }
}

function logoutSession(){

    unset($_SESSION['logged_in']);
    unset($_SESSION['loggedInUser']);
    session_destroy();
}

function jsonResponse($status, $status_type, $message){
     
  $response = [
         'status' => $status,
         'status_type' => $status_type,
         'message' => $message
       ];
       echo json_encode($response);
       return;

}

// Log activity in the system
function logActivity($action, $details = ''){
    global $conn;
    
    $admin_id = isset($_SESSION['loggedInUser']['id']) ? $_SESSION['loggedInUser']['id'] : NULL;
    $admin_name = isset($_SESSION['loggedInUser']['name']) ? $_SESSION['loggedInUser']['name'] : 'System';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    
    $data = [
        'admin_id' => $admin_id,
        'admin_name' => $admin_name,
        'action' => validate($action),
        'details' => validate($details),
        'ip_address' => $ip_address,
        'timestamp' => $timestamp
    ];
    
    return insert('activity_logs', $data);
}

?>
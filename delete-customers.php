<?php

require '../config/function.php';

$paraResultId = checkParamId('id');
 if(is_numeric($paraResultId)){

          $customerId = validate($paraResultId);
          
          $customer = getById('customers', $customerId);
          if($customer['status'] == 200)
          {
               $response = delete('customers', $customerId);
               if($response)
               {
                   logActivity('DELETE', 'Customer deleted: ' . $customer['data']['name']);
                   redirect('customers.php', 'Customer deleted successfully.');
               }
               else
               {
                   redirect('customers.php', 'Something went wrong while deleting!');
               }
          }
          else
          {
            redirect('customers.php',$customer['message']);

          }
 }
 else
 {
    redirect('customers.php', 'Something went wrong! Invalid parameter.');

 }

?>
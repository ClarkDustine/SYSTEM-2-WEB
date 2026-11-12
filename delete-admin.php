<?php

require '../config/function.php';

$paraResultId = checkParamId('id');
 if(is_numeric($paraResultId)){

          $adminId = validate($paraResultId);
          
          $admin = getById('admins', $adminId);
          if($admin['status'] == 200)
          {
               $adminDeleteRes = delete('admins', $adminId);
               if($adminDeleteRes)
               {
                   logActivity('DELETE', 'Admin deleted: ' . $admin['data']['name']);
                   redirect('admin.php', 'Admin deleted successfully.');
               }
               else
               {
                   redirect('admin.php', 'Something went wrong while deleting!');
               }
          }
          else
          {
            redirect('admin.php',$admin['message']);

          }
          //echo $adminId;

 }
 else
 {
    redirect('admin.php', 'Something went wrong! Invalid parameter.');

 }

?>
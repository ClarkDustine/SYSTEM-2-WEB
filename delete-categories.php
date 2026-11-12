<?php

require '../config/function.php';

$paraResultId = checkParamId('id');
 if(is_numeric($paraResultId)){

          $categoryId = validate($paraResultId);
          
          $category = getById('categories', $categoryId);
          if($category['status'] == 200)
          {
               $response = delete('categories', $categoryId);
               if($response)
               {
                   logActivity('DELETE', 'Category deleted: ' . $category['data']['name']);
                   redirect('categories.php', 'Category deleted successfully.');
               }
               else
               {
                   redirect('categories.php', 'Something went wrong while deleting!');
               }
          }
          else
          {
            redirect('categories.php',$category['message']);

          }
 }
 else
 {
    redirect('categories.php', 'Something went wrong! Invalid parameter.');

 }

?>
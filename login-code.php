<?php

require 'config/function.php';

if(isset($_POST['loginBtn']))
{
    $email = validate($_POST['email']);
    $password = validate($_POST['password']);
    
    if($email != '' && $password != '')
    {
        $query = "SELECT * FROM admins WHERE email='$email' LIMIT 1";
        $result = mysqli_query($conn, $query);
        if($result)
            {
                if(mysqli_num_rows($result) == 1)
                    {
                        $row = mysqli_fetch_assoc($result);
                        $hashed_password = $row['password'];

                        if(!password_verify($password,$hashed_password))
                            {
                                redirect ('login.php', 'Invalid Password!');
                            }
                            
                            if($row['is_ban'] == 1)
                                {
                                    redirect ('login.php', 'Your account has been banned!');
                                }

                                $_SESSION['logged_in'] = true;
                                $_SESSION['loggedInUser'] = [
                                    'id' => $row['id'],
                                    'name' => $row['name'],
                                    'email' => $row['email'],
                                    'phone' => $row['phone'],

                                ];

                                 logActivity('LOGIN', 'Admin logged in: ' . $row['name']);
                                 redirect ('admin/index.php', 'Logged in Successfully!');

                    }
                    else
                    {
                        redirect ('login.php', 'Invalid Email Address!');
                    }

            }else
            {
                  redirect ('login.php', 'Something Went Wrong!');
            }
    }
    else
    {
        redirect ('login.php', 'All Fields Are Mandatory!');
    }
}

?>
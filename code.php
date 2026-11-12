<?php include('../config/function.php');

if (isset($_POST['saveAdmin'])) {

    $name = validate($_POST['name']);
    $email = validate($_POST['email']);
    $password = validate($_POST['password']);
    $phone = validate($_POST['phone']);
    $is_ban = isset($_POST['is_ban']) == true ? 1 : 0;

    if ($name != '' && $email != '' && $password != '') {

        $emailCheck = mysqli_query($conn, "SELECT * FROM admins WHERE email='$email'");
        if ($emailCheck) {
            if (mysqli_num_rows($emailCheck) > 0) {
                redirect('admins-create.php', 'Email already exists.');
            }
        }

        $bcrypt_password = password_hash($password, PASSWORD_BCRYPT);

        $data = [
            'name' => $name,
            'email' => $email,
            'password' => $bcrypt_password,
            'phone' => $phone,
            'is_ban' => $is_ban
        ];
        $result = insert('admins', $data);

        if ($result) {
            logActivity('CREATE', 'Admin created: ' . $name);
            redirect('admin.php', 'Admin created successfully.');
        } else {
            redirect('admins-create.php', 'Something went wrong!');
        }

    } else {
        redirect('admins-create.php', 'Please fill required fields.');
    }

}

// Update admin
if (isset($_POST['updateAdmin'])) {
    $adminId = validate($_POST['adminId']);
    $name = validate($_POST['name']);
    $email = validate($_POST['email']);
    $password = isset($_POST['password']) ? validate($_POST['password']) : '';
    $phone = validate($_POST['phone']);
    $is_ban = isset($_POST['is_ban']) == true ? 1 : 0;

    if ($name != '' && $email != '') {
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'is_ban' => $is_ban
        ];

        if ($password != '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $result = update('admins', $adminId, $data);

        if ($result) {
            logActivity('UPDATE', 'Admin updated: ' . $name);
            redirect('admin.php', 'Admin updated successfully.');
        } else {
            redirect('edit-admin.php?id=' . $adminId, 'Something went wrong while updating!');
        }

    } else {
        redirect('edit-admin.php?id=' . $adminId, 'Please fill required fields.');
    }

}



if (isset($_POST['saveCategory'])) {
    $name = validate($_POST['name']);
    $description = validate($_POST['description']);
    $status = isset($_POST['status']) == true ? 1 : 0;

    $data = [
        'name' => $name,
        'description' => $description,
        'status' => $status
    ];
    $result = insert('categories', $data);

    if ($result) {
        logActivity('CREATE', 'Category created: ' . $name);
        redirect('categories.php', 'Category created successfully');
    } else {
        redirect('categories-create.php', 'Something went wrong!');
    }
}

if (isset($_POST['updateCategory'])) {
    $categoryId = validate($_POST['categoryId']);
    $name = validate($_POST['name']);
    $description = validate($_POST['description']);
    $status = isset($_POST['status']) == true ? 1 : 0;

    $data = [
        'name' => $name,
        'description' => $description,
        'status' => $status
    ];
    $result = update('categories', $categoryId, $data);

    if ($result) {
        logActivity('UPDATE', 'Category updated: ' . $name);
        redirect('edit-categories.php?id=' . $categoryId, 'Category Updated Successfully.');
    } else {
        redirect('edit-categories.php?id=' . $categoryId, 'Something Went Wrong!');
    }
}


if (isset($_POST['saveProduct'])) {
    $category_id = validate($_POST['category_id']);
    $name = validate($_POST['name']);
    $description = validate($_POST['description']);

    $price = validate($_POST['price']);
    $quantity = validate($_POST['quantity']);
    $status = isset($_POST['status']) == true ? 1 : 0;

    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $path = "../assets/uploads/products";
        // ensure upload directory exists
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $image_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '.' . strtolower($image_ext);

        // use the correct tmp_name key
        move_uploaded_file($_FILES['image']['tmp_name'], $path . DIRECTORY_SEPARATOR . $filename);

        $finalImage = "assets/uploads/products/" . $filename;
    } else {
        $finalImage = '';
    }

    $data = [
        'category_id' => $category_id,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'quantity' => $quantity,
        'image' => $finalImage,
        'status' => $status
    ];

    $result = insert('products', $data);

    if ($result) {
        logActivity('CREATE', 'Product created: ' . $name);
        redirect('products.php', 'Products Created Successfully.');
    } else {
        redirect('products-create.php', 'Something Went Wrong!');
    }
}

if (isset($_POST['updateProduct'])) {
    $product_id = validate($_POST['product_id']);

    $productData = getById('products', $product_id);
    if (!$productData) {
        redirect('products.php', 'No such product found');
    }

    $category_id = validate($_POST['category_id']);
    $name = validate($_POST['name']);
    $description = validate($_POST['description']);

    $price = validate($_POST['price']);
    $quantity = validate($_POST['quantity']);
    $status = isset($_POST['status']) == true ? 1 : 0;

    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $path = "../assets/uploads/products";
        // ensure upload directory exists
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $image_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = time() . '.' . strtolower($image_ext);

        // use the correct tmp_name key
        move_uploaded_file($_FILES['image']['tmp_name'], $path . DIRECTORY_SEPARATOR . $filename);

        $finalImage = "assets/uploads/products/" . $filename;

        $deleteImage = "../" . $productData['data']['image'];
        if (file_exists($deleteImage)) {
            unlink($deleteImage);
        }
    } else {
        $finalImage = $productData['data']['image'];
    }

    $data = [
        'category_id' => $category_id,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'quantity' => $quantity,
        'image' => $finalImage,
        'status' => $status
    ];

    $result = update('products', $product_id, $data);

    if ($result) {
        logActivity('UPDATE', 'Product updated: ' . $name);
        redirect('edit-products.php?id=' . $product_id, 'Products Updated Successfully.');
    } else {
        redirect('edit-products.php?id=' . $product_id, 'Something Went Wrong!');
    }
}

if (isset($_POST['saveCustomer'])) {
    $name = validate($_POST['name']);
    $email = validate($_POST['email']);
    $phone = validate($_POST['phone']);
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name != '') {
        $emailCheck = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email'");
        if ($emailCheck) {
            if (mysqli_num_rows($emailCheck) > 0) {

                redirect('customers.php', 'Email already used by another user.');
            }
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'status' => $status
        ];

        $result = insert('customers', $data);
        if ($result) {
            logActivity('CREATE', 'Customer created: ' . $name);
            redirect('customers.php', 'Customer Created Successfully.');
        } else {
            redirect('customers.php', 'Something Went Wrong.');
        }
    } else {
        redirect('customers.php', 'Please fill required fields.');
    }
}

if (isset($_POST['updateCustomer'])) {
    $customerId = validate($_POST['customerId']);

    $name = validate($_POST['name']);
    $email = validate($_POST['email']);
    $phone = validate($_POST['phone']);
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name != '') {
        $emailCheck = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' AND id != '$customerId'");
        if ($emailCheck) {
            if (mysqli_num_rows($emailCheck) > 0) {
                redirect('edit-customers.php?id='.$customerId, 'Email already used by another user.');
            }
        }

        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'status' => $status
        ];

        $result = update('customers', $customerId, $data);
        if ($result) {
            logActivity('UPDATE', 'Customer updated: ' . $name);
            redirect('edit-customers.php?id='.$customerId, 'Customer Updated Successfully.');
        } else {
            redirect('edit-customers.php?id='.$customerId, 'Something Went Wrong.');
        }
    } else {
        redirect('edit-customers.php?id='.$customerId, 'Please fill required fields.');
    }
}

?>
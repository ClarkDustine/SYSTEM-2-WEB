<?php include('assets/includes/header.php'); ?>

<div class="container-fluid px-4">
    <div class= "card mt-4 shadow small">
        <div class="card-header">
            <h4 class="mb-0">Admins/Staff
                <a href="admins-create.php" class="btn btn-primary float-end">Add Admin</a>
            </h4>
        </div>
       <div class="card-body">
            <?php alertMessage(); ?>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $admins = getALL('admins');
                        if(mysqli_num_rows($admins) > 0){
                            while($admin = mysqli_fetch_assoc($admins)){
                        ?>
                        <tr>
                            <td><?= $admin['id'] ?></td>
                            <td><?= $admin['name'] ?></td>
                            <td><?= $admin['email'] ?></td>
                            <td>
                                <a href="edit-admin.php?id=<?= $admin['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="delete-admin.php?id=<?= $admin['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php 
                            }
                        }else{
                            echo '<tr><td colspan="4" class="text-center">No admins found</td></tr>';
                        }
                        ?>


                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include('assets/includes/footer.php'); ?>
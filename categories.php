<?php include('assets/includes/header.php'); ?>

<div class="container-fluid px-4">
    <div class= "card mt-4 shadow small">
        <div class="card-header">
            <h4 class="mb-0">Categories
                <a href="categories-create.php" class="btn btn-primary float-end">Add Category</a>
            </h4>
        </div>
       <div class="card-body">
            <?php alertMessage(); ?>

              <?php 
                        $categories = getALL('categories');
                        if(!$categories){
                            echo '<h4> Something went wrong!</h4>';
                            return false;
                        }
                                if(mysqli_num_rows($categories) > 0)
                            {
                     ?>
                      <div class="table-responsive">
                      <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $item) :?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><?= $item['name'] ?></td>
                            <td>
                              <?php 
                                if($item['status'] == 1){
                                    echo '<span class= "badge bg-danger">Hidden</span>';
                                }else{
                                    echo'<span class= "badge bg-primary">Visible</span>';
                                }
                                ?>
                            </td>
                            </td>
                            <td>
                                <a href="edit-categories.php?id=<?= $item['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="delete-categories.php?id=<?= $item['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            }
            else
            {
                ?>
                <h4 class="mb-0">No record found</h4>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<?php include('assets/includes/footer.php'); ?>
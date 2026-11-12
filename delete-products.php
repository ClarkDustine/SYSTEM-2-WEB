<?php

require '../config/function.php';

$productId = checkParamId('id');

if (!$productId || !is_numeric($productId)) {
    redirect('products.php', 'Something went wrong! Invalid parameter.');
}

$productId = validate($productId);
$product = getById('products', $productId);

if ($product['status'] !== 200) {
    redirect('products.php', $product['message']);
}

$response = delete('products', $productId);

if ($response) {
    logActivity('DELETE', 'Product deleted: ' . $product['data']['name']);
    $imagePath = isset($product['data']['image']) && $product['data']['image'] !== ''
        ? '../' . $product['data']['image']
        : '';

    if ($imagePath && file_exists($imagePath)) {
        unlink($imagePath);
    }

    redirect('products.php', 'Product deleted successfully.');
}

redirect('products.php', 'Something went wrong while deleting!');

?>
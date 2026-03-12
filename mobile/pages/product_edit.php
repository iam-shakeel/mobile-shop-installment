<?php
$pageTitle = "Edit Product";
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    redirect("index.php?page=products");
}

$product = db_fetch_one(db_query("SELECT * FROM products WHERE id = ?", "i", [$id]));
if (!$product) {
    set_flash("danger", "Product not found.");
    redirect("index.php?page=products");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $brand = trim($_POST["brand"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = (int)round((float)($_POST["price"] ?? 0));
    $costPrice = (int)round((float)($_POST["cost_price"] ?? 0));
    $specifications = trim($_POST["specifications"] ?? "");
    $stockQuantity = (int)($_POST["stock_quantity"] ?? 0);
    $imageUrl = trim($_POST["image_url"] ?? "");

    if ($name === "") {
        set_flash("danger", "Product name is required.");
    } else {
        db_query(
            "UPDATE products SET name = ?, brand = ?, model = ?, description = ?, price = ?, cost_price = ?, specifications = ?, stock_quantity = ?, image_url = ? WHERE id = ?",
            "ssssdssisi",
            [$name, $brand, $model, $description, $price, $costPrice, $specifications, $stockQuantity, $imageUrl, $id]
        );
        set_flash("success", "Product updated.");
    }
    redirect("index.php?page=products");
}
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-pencil-square"></i>Catalog</span>
      <h1 class="display-6 mb-2">Edit Product</h1>
      <p class="lead mb-0">Refine pricing, stock, and product details.</p>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-hero" href="index.php?page=products">Back to Products</a>
    </div>
  </div>
</div>

<div class="card glass-card">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Name</label>
          <input class="form-control" name="name" value="<?php echo h($product["name"]); ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Brand</label>
          <input class="form-control" name="brand" value="<?php echo h($product["brand"]); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Model</label>
          <input class="form-control" name="model" value="<?php echo h($product["model"]); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Price</label>
          <input class="form-control" name="price" type="number" step="1" min="0" value="<?php echo h((string)$product["price"]); ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Cost Price</label>
          <input class="form-control" name="cost_price" type="number" step="1" min="0" value="<?php echo h((string)$product["cost_price"]); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Stock Quantity</label>
          <input class="form-control" name="stock_quantity" type="number" value="<?php echo h((string)$product["stock_quantity"]); ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Image URL</label>
          <input class="form-control" name="image_url" value="<?php echo h($product["image_url"]); ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Specifications</label>
          <textarea class="form-control" name="specifications" rows="2"><?php echo h($product["specifications"]); ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="2"><?php echo h($product["description"]); ?></textarea>
        </div>
      </div>
      <button class="btn btn-primary mt-3"><i class="bi bi-check-circle"></i>Update Product</button>
    </form>
  </div>
</div>

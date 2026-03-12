<?php
$pageTitle = "Products";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "create") {
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
                "INSERT INTO products (name, brand, model, description, price, cost_price, specifications, stock_quantity, image_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "ssssdssis",
                [$name, $brand, $model, $description, $price, $costPrice, $specifications, $stockQuantity, $imageUrl]
            );
            set_flash("success", "Product added.");
        }
        redirect("index.php?page=products");
    }

    if ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            db_query("DELETE FROM products WHERE id = ?", "i", [$id]);
            set_flash("success", "Product deleted.");
        }
        redirect("index.php?page=products");
    }
}

$products = db_fetch_all(db_query("SELECT * FROM products ORDER BY created_at DESC"));
$productCount = count($products);
$stockTotal = 0;
$inventoryValue = 0.0;
foreach ($products as $product) {
    $stockTotal += (int)$product["stock_quantity"];
    $inventoryValue += ((float)$product["price"]) * (int)$product["stock_quantity"];
}
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-grid-3x3-gap"></i>Inventory Control</span>
      <h1 class="display-6 mb-2">Products</h1>
      <p class="lead mb-0">Track your catalog, pricing, and stock at a glance.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-hero" data-bs-toggle="modal" data-bs-target="#productModal"><i class="bi bi-plus-circle"></i>Add Product</button>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-box"></i></span>Total Products
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$productCount); ?>"><?php echo h((string)$productCount); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-stack"></i></span>Units In Stock
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$stockTotal); ?>"><?php echo h((string)$stockTotal); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-cash-stack"></i></span>Inventory Value
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)(int)round($inventoryValue)); ?>" data-prefix="PKR ">
          <?php echo h(format_currency($inventoryValue)); ?>
        </h3>
      </div>
    </div>
  </div>
</div>

<div class="card glass-card data-table-card">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h6 class="mb-0 section-title-icon"><i class="bi bi-list-check"></i>Product List</h6>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="table-products">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="table-products"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="table-products">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-tag"></i></span>Name</th>
          <th><span class="th-icon"><i class="bi bi-award"></i></span>Brand</th>
          <th><span class="th-icon"><i class="bi bi-cpu"></i></span>Model</th>
          <th><span class="th-icon"><i class="bi bi-cash"></i></span>Price</th>
          <th><span class="th-icon"><i class="bi bi-boxes"></i></span>Stock</th>
          <th><span class="th-icon"><i class="bi bi-gear"></i></span>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$products): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-5">
              No products yet. Add your first item to get started.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($products as $product): ?>
            <tr>
              <td><?php echo h($product["name"]); ?></td>
              <td><?php echo h($product["brand"]); ?></td>
              <td><?php echo h($product["model"]); ?></td>
              <td><?php echo h(format_currency($product["price"])); ?></td>
              <td><?php echo h((string)$product["stock_quantity"]); ?></td>
              <td>
                <div class="table-actions">
                  <a class="btn btn-sm btn-outline-secondary btn-edit" href="index.php?page=product_edit&id=<?php echo h((string)$product["id"]); ?>"><i class="bi bi-pencil"></i>Edit</a>
                  <form method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo h((string)$product["id"]); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')"><i class="bi bi-trash"></i>Delete</button>
                  </form>
                  <button class="btn btn-sm btn-outline-secondary btn-print no-print" type="button" data-print-row><i class="bi bi-printer"></i>Print</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <p class="text-uppercase text-muted small mb-1">Add New</p>
          <h5 class="modal-title" id="productModalLabel">Product</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post">
          <input type="hidden" name="action" value="create">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Brand</label>
              <input class="form-control" name="brand">
            </div>
            <div class="col-md-4">
              <label class="form-label">Model</label>
              <input class="form-control" name="model">
            </div>
            <div class="col-md-6">
              <label class="form-label">Price</label>
              <input class="form-control" name="price" type="number" step="1" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Cost Price</label>
              <input class="form-control" name="cost_price" type="number" step="1" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Stock Quantity</label>
              <input class="form-control" name="stock_quantity" type="number" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Image URL</label>
              <input class="form-control" name="image_url">
            </div>
            <div class="col-12">
              <label class="form-label">Specifications (JSON or text)</label>
              <textarea class="form-control" name="specifications" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
          </div>
          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i>Cancel</button>
            <button class="btn btn-primary"><i class="bi bi-check-circle"></i>Save Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

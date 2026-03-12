<?php
$pageTitle = "Customers";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "create") {
        $name = trim($_POST["name"] ?? "");
        $phone = trim($_POST["phone_number"] ?? "");
        $cnic = trim($_POST["cnic"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $email = trim($_POST["email"] ?? "");

        if ($name === "") {
            set_flash("danger", "Customer name is required.");
        } else {
            db_query(
                "INSERT INTO customers (name, phone_number, cnic, address, email) VALUES (?, ?, ?, ?, ?)",
                "sssss",
                [$name, $phone, $cnic, $address, $email]
            );
            set_flash("success", "Customer added.");
        }
        redirect("index.php?page=customers");
    }

    if ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            db_query("DELETE FROM customers WHERE id = ?", "i", [$id]);
            set_flash("success", "Customer deleted.");
        }
        redirect("index.php?page=customers");
    }
}

$customers = db_fetch_all(db_query("SELECT * FROM customers ORDER BY created_at DESC"));
$customerCount = count($customers);
$withPhone = 0;
$withEmail = 0;
foreach ($customers as $customer) {
    if (trim((string)$customer["phone_number"]) !== "") {
        $withPhone++;
    }
    if (trim((string)$customer["email"]) !== "") {
        $withEmail++;
    }
}
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-person-heart"></i>Client Hub</span>
      <h1 class="display-6 mb-2">Customers</h1>
      <p class="lead mb-0">Build relationships and keep contact details tidy.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-hero" data-bs-toggle="modal" data-bs-target="#customerModal"><i class="bi bi-person-plus"></i>Add Customer</button>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-people"></i></span>Total Customers
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$customerCount); ?>"><?php echo h((string)$customerCount); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-telephone"></i></span>With Phone
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$withPhone); ?>"><?php echo h((string)$withPhone); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-envelope"></i></span>With Email
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$withEmail); ?>"><?php echo h((string)$withEmail); ?></h3>
      </div>
    </div>
  </div>
</div>

<div class="card glass-card data-table-card">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h6 class="mb-0 section-title-icon"><i class="bi bi-card-list"></i>Customer List</h6>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="table-customers">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="table-customers"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="table-customers">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-person"></i></span>Name</th>
          <th><span class="th-icon"><i class="bi bi-telephone"></i></span>Phone</th>
          <th><span class="th-icon"><i class="bi bi-credit-card-2-front"></i></span>CNIC</th>
          <th><span class="th-icon"><i class="bi bi-envelope"></i></span>Email</th>
          <th><span class="th-icon"><i class="bi bi-gear"></i></span>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$customers): ?>
          <tr><td colspan="5" class="text-center text-muted py-5">No customers yet. Add your first client.</td></tr>
        <?php else: ?>
          <?php foreach ($customers as $customer): ?>
            <tr>
              <td><?php echo h($customer["name"]); ?></td>
              <td><?php echo h($customer["phone_number"]); ?></td>
              <td><?php echo h($customer["cnic"]); ?></td>
              <td><?php echo h($customer["email"]); ?></td>
              <td>
                <div class="table-actions">
                  <a class="btn btn-sm btn-outline-secondary btn-edit" href="index.php?page=customer_edit&id=<?php echo h((string)$customer["id"]); ?>"><i class="bi bi-pencil"></i>Edit</a>
                  <form method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo h((string)$customer["id"]); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this customer?')"><i class="bi bi-trash"></i>Delete</button>
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

<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <p class="text-uppercase text-muted small mb-1">Add New</p>
          <h5 class="modal-title" id="customerModalLabel">Customer</h5>
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
              <label class="form-label">Phone</label>
              <input class="form-control" name="phone_number">
            </div>
            <div class="col-md-4">
              <label class="form-label">CNIC</label>
              <input class="form-control" name="cnic">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" name="email" type="email">
            </div>
            <div class="col-md-6">
              <label class="form-label">Address</label>
              <input class="form-control" name="address">
            </div>
          </div>
          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i>Cancel</button>
            <button class="btn btn-primary"><i class="bi bi-check-circle"></i>Save Customer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

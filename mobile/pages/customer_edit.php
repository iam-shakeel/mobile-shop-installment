<?php
$pageTitle = "Edit Customer";
$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    redirect("index.php?page=customers");
}

$customer = db_fetch_one(db_query("SELECT * FROM customers WHERE id = ?", "i", [$id]));
if (!$customer) {
    set_flash("danger", "Customer not found.");
    redirect("index.php?page=customers");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone_number"] ?? "");
    $cnic = trim($_POST["cnic"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($name === "") {
        set_flash("danger", "Customer name is required.");
    } else {
        db_query(
            "UPDATE customers SET name = ?, phone_number = ?, cnic = ?, address = ?, email = ? WHERE id = ?",
            "sssssi",
            [$name, $phone, $cnic, $address, $email, $id]
        );
        set_flash("success", "Customer updated.");
    }
    redirect("index.php?page=customers");
}
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-person-lines-fill"></i>Client Hub</span>
      <h1 class="display-6 mb-2">Edit Customer</h1>
      <p class="lead mb-0">Keep profiles accurate and up to date.</p>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-hero" href="index.php?page=customers">Back to Customers</a>
    </div>
  </div>
</div>

<div class="card glass-card">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Name</label>
          <input class="form-control" name="name" value="<?php echo h($customer["name"]); ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Phone</label>
          <input class="form-control" name="phone_number" value="<?php echo h($customer["phone_number"]); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">CNIC</label>
          <input class="form-control" name="cnic" value="<?php echo h($customer["cnic"]); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" type="email" value="<?php echo h($customer["email"]); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Address</label>
          <input class="form-control" name="address" value="<?php echo h($customer["address"]); ?>">
        </div>
      </div>
      <button class="btn btn-primary mt-3"><i class="bi bi-check-circle"></i>Update Customer</button>
    </form>
  </div>
</div>

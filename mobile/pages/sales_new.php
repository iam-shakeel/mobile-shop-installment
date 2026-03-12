<?php
$pageTitle = "Sales";

$customers = db_fetch_all(db_query("SELECT id, name FROM customers ORDER BY name ASC"));
$products = db_fetch_all(db_query("SELECT id, name, price, stock_quantity FROM products ORDER BY name ASC"));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "delete_sale") {
        $saleId = (int)($_POST["sale_id"] ?? 0);
        if ($saleId > 0) {
            db()->begin_transaction();
            try {
                $items = db_fetch_all(db_query(
                    "SELECT product_id, quantity FROM sale_items WHERE sale_id = ?",
                    "i",
                    [$saleId]
                ));
                foreach ($items as $item) {
                    db_query(
                        "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                        "ii",
                        [(int)$item["quantity"], (int)$item["product_id"]]
                    );
                }

                db_query("DELETE FROM sales WHERE id = ?", "i", [$saleId]);
                db()->commit();
                set_flash("success", "Sale deleted.");
            } catch (Throwable $e) {
                db()->rollback();
                set_flash("danger", "Failed to delete sale.");
            }
        }
        redirect("index.php?page=sales_new");
    }

    $customerId = (int)($_POST["customer_id"] ?? 0);
    $paymentMethod = trim($_POST["payment_method"] ?? "cash");
    $paidAmount = (int)round((float)($_POST["paid_amount"] ?? 0));
    $notes = trim($_POST["notes"] ?? "");

    $productIds = $_POST["product_id"] ?? [];
    $quantities = $_POST["quantity"] ?? [];
    $unitPrices = $_POST["unit_price"] ?? [];

    $items = [];
    $totalAmount = 0;

    for ($i = 0; $i < count($productIds); $i++) {
        $pid = (int)$productIds[$i];
        $qty = (int)$quantities[$i];
        $lineTotal = (int)round((float)$unitPrices[$i]);

        if ($pid > 0 && $qty > 0) {
            $unitPrice = $lineTotal > 0 ? (int)round($lineTotal / $qty) : 0;
            $totalAmount += $lineTotal;
            $items[] = [
                "product_id" => $pid,
                "quantity" => $qty,
                "unit_price" => $unitPrice,
                "total_price" => $lineTotal
            ];
        }
    }

    if ($customerId <= 0 || !$items) {
        set_flash("danger", "Customer and at least one product are required.");
        redirect("index.php?page=sales_new");
    }

    $status = "processing";
    if ($paymentMethod === "installment") {
        $status = "installment";
    } elseif ($paidAmount >= $totalAmount) {
        $status = "completed";
    }

    db()->begin_transaction();
    try {
        db_query(
            "INSERT INTO sales (customer_id, total_amount, paid_amount, status, payment_method, notes)
             VALUES (?, ?, ?, ?, ?, ?)",
            "iddsss",
            [$customerId, $totalAmount, $paidAmount, $status, $paymentMethod, $notes]
        );
        $saleId = db()->insert_id;

        foreach ($items as $item) {
            db_query(
                "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?)",
                "iiidd",
                [$saleId, $item["product_id"], $item["quantity"], $item["unit_price"], $item["total_price"]]
            );
            db_query(
                "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
                "ii",
                [$item["quantity"], $item["product_id"]]
            );
        }

        if ($paymentMethod === "installment") {
            $downPayment = (int)round((float)($_POST["down_payment"] ?? 0));
            $durationMonths = (int)($_POST["duration_months"] ?? 1);
            $monthlyAmount = 0;
            $startDate = $_POST["start_date"] ?? date("Y-m-d");
            if ($durationMonths < 1) {
                $durationMonths = 1;
            }

            $monthlyAmount = (int)round(max(0, ($totalAmount - $downPayment) / $durationMonths));

            $remainingAmount = max(0, $totalAmount - $downPayment);
            $nextDueDate = date("Y-m-d", strtotime("+1 month", strtotime($startDate)));
            db_query(
                "INSERT INTO installment_plans (sale_id, down_payment, total_amount, remaining_amount, duration_months, monthly_amount, start_date, next_due_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                "idddidss",
                [$saleId, $downPayment, $totalAmount, $remainingAmount, $durationMonths, $monthlyAmount, $startDate, $nextDueDate]
            );
        }

        db()->commit();
        set_flash("success", "Sale created.");
    } catch (Throwable $e) {
        db()->rollback();
        set_flash("danger", "Failed to create sale.");
    }

    redirect("index.php?page=sales_new");
}

$totalSales = db_fetch_one(db_query("SELECT COUNT(*) AS total FROM sales"))["total"] ?? 0;
$todaySalesRow = db_fetch_one(db_query(
    "SELECT COUNT(*) AS total_sales, COALESCE(SUM(total_amount), 0) AS total_amount FROM sales WHERE DATE(created_at) = CURDATE()"
));
$todaySalesCount = $todaySalesRow["total_sales"] ?? 0;
$todaySalesAmount = $todaySalesRow["total_amount"] ?? 0;
$activeInstallments = db_fetch_one(db_query("SELECT COUNT(*) AS total FROM installment_plans WHERE status = 'active'"))["total"] ?? 0;
$recentSales = db_fetch_all(db_query(
    "SELECT s.id, s.total_amount, s.payment_method, s.status, s.created_at, c.name AS customer_name
     FROM sales s
     JOIN customers c ON c.id = s.customer_id
     ORDER BY s.created_at DESC
     LIMIT 10"
));
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-receipt-cutoff"></i>Point Of Sale</span>
      <h1 class="display-6 mb-2">Sales</h1>
      <p class="lead mb-0">Create invoices, apply installments, and update stock in one flow.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-hero" data-bs-toggle="modal" data-bs-target="#saleModal"><i class="bi bi-plus-circle"></i>Create Sale</button>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-bag-check"></i></span>Total Sales
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$totalSales); ?>"><?php echo h((string)$totalSales); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-calendar2-day"></i></span>Today Sales
        </p>
        <h3 class="mb-1" data-count="<?php echo h((string)$todaySalesCount); ?>"><?php echo h((string)$todaySalesCount); ?></h3>
        <div class="text-muted" data-count="<?php echo h((string)(int)round($todaySalesAmount)); ?>" data-prefix="PKR ">
          <?php echo h(format_currency($todaySalesAmount)); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-clock-history"></i></span>Active Installments
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$activeInstallments); ?>"><?php echo h((string)$activeInstallments); ?></h3>
      </div>
    </div>
  </div>
</div>

<div class="card glass-card data-table-card">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h6 class="mb-0 section-title-icon"><i class="bi bi-list-check"></i>Recent Sales</h6>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="table-sales-recent">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="table-sales-recent"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="table-sales-recent">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-hash"></i></span>ID</th>
          <th><span class="th-icon"><i class="bi bi-person"></i></span>Customer</th>
          <th><span class="th-icon"><i class="bi bi-cash"></i></span>Amount</th>
          <th><span class="th-icon"><i class="bi bi-credit-card"></i></span>Method</th>
          <th><span class="th-icon"><i class="bi bi-check2-circle"></i></span>Status</th>
          <th><span class="th-icon"><i class="bi bi-calendar-event"></i></span>Date</th>
          <th><span class="th-icon"><i class="bi bi-gear"></i></span>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recentSales): ?>
          <tr><td colspan="7" class="text-center text-muted py-5">No sales yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentSales as $sale): ?>
            <tr>
              <td><?php echo h((string)$sale["id"]); ?></td>
              <td><?php echo h($sale["customer_name"]); ?></td>
              <td><?php echo h(format_currency($sale["total_amount"])); ?></td>
              <td><?php echo h(ucwords(str_replace("_", " ", $sale["payment_method"]))); ?></td>
              <td><span class="badge badge-soft"><?php echo h(ucfirst($sale["status"])); ?></span></td>
              <td><?php echo h(date("Y-m-d", strtotime($sale["created_at"]))); ?></td>
              <td>
                <div class="table-actions">
                  <button class="btn btn-sm btn-outline-secondary btn-print no-print" type="button" data-print-row><i class="bi bi-printer"></i>Print</button>
                  <form method="post">
                    <input type="hidden" name="action" value="delete_sale">
                    <input type="hidden" name="sale_id" value="<?php echo h((string)$sale["id"]); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this sale?')"><i class="bi bi-trash"></i>Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="saleModal" tabindex="-1" aria-labelledby="saleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <p class="text-uppercase text-muted small mb-1">Create</p>
          <h5 class="modal-title" id="saleModalLabel">New Sale</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post">
          <div class="card glass-card mb-4">
            <div class="card-header bg-transparent">
              <h6 class="mb-0">Sale Details</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Customer</label>
                  <select class="form-select" name="customer_id" required>
                    <option value="">Select customer</option>
                    <?php foreach ($customers as $customer): ?>
                      <option value="<?php echo h((string)$customer["id"]); ?>"><?php echo h($customer["name"]); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Payment Method</label>
                  <select class="form-select" name="payment_method" data-payment-method>
                    <option value="cash">Cash</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="installment">Installment</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Paid Amount</label>
                  <input class="form-control" name="paid_amount" type="number" step="1" min="0">
                </div>
                <div class="col-12">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="card glass-card mb-4 installment-panel d-none" data-installment-section>
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
              <h6 class="mb-0">Sale Items</h6>
              <button class="btn btn-sm btn-outline-primary" type="button" data-add-sale-item><i class="bi bi-plus-circle"></i>Add Item</button>
            </div>
            <div class="card-body">
              <div id="sale-items" class="d-flex flex-column gap-3">
                <div class="row g-2 align-items-end" data-sale-item-row>
                  <div class="col-md-5">
                    <label class="form-label">Product</label>
                    <select class="form-select" name="product_id[]">
                      <option value="">Select product</option>
                      <?php foreach ($products as $product): ?>
                        <option value="<?php echo h((string)$product["id"]); ?>" data-price="<?php echo h((string)$product["price"]); ?>">
                          <?php echo h($product["name"]); ?> (Stock: <?php echo h((string)$product["stock_quantity"]); ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Qty</label>
                    <input class="form-control" name="quantity[]" type="number" value="1" min="1">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Line Total</label>
                    <input class="form-control" name="unit_price[]" type="number" step="1" min="0" readonly>
                  </div>
                  <div class="col-md-2 d-flex justify-content-between align-items-center">
                    <div>
                      <label class="form-label">Total</label>
                      <div class="fw-bold" data-line-total>0.00</div>
                    </div>
                    <button class="btn btn-outline-danger btn-sm" type="button" data-remove-sale-item><i class="bi bi-x-circle"></i>Remove</button>
                  </div>
                </div>
              </div>
              <div class="text-end mt-3">
                <strong>Total: PKR <span data-sale-total>0.00</span></strong>
                <input type="hidden" name="total_amount" value="0">
              </div>
            </div>
          </div>

          <div class="card glass-card mb-4">
            <div class="card-header bg-transparent">
              <h6 class="mb-0">Installment Details</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Down Payment</label>
                  <input class="form-control" name="down_payment" type="number" step="1" min="0">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Duration (Months)</label>
                  <input class="form-control" name="duration_months" type="number" min="1" value="6">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Monthly Amount</label>
                  <input class="form-control" name="monthly_amount" type="number" step="1" min="0" readonly data-monthly-amount>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Start Date</label>
                  <input class="form-control" name="start_date" type="date" value="<?php echo h(date('Y-m-d')); ?>">
                </div>
              </div>
              <p class="text-muted mt-2 mb-0">Installment fields are used only when payment method is set to Installment.</p>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i>Cancel</button>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check-circle"></i>Create Sale</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<template id="sale-item-template">
  <div class="row g-2 align-items-end" data-sale-item-row>
    <div class="col-md-5">
      <label class="form-label">Product</label>
      <select class="form-select" name="product_id[]">
        <option value="">Select product</option>
        <?php foreach ($products as $product): ?>
          <option value="<?php echo h((string)$product["id"]); ?>" data-price="<?php echo h((string)$product["price"]); ?>">
            <?php echo h($product["name"]); ?> (Stock: <?php echo h((string)$product["stock_quantity"]); ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Qty</label>
      <input class="form-control" name="quantity[]" type="number" value="1" min="1">
    </div>
    <div class="col-md-3">
      <label class="form-label">Line Total</label>
      <input class="form-control" name="unit_price[]" type="number" step="1" min="0" readonly>
    </div>
    <div class="col-md-2 d-flex justify-content-between align-items-center">
      <div>
        <label class="form-label">Total</label>
        <div class="fw-bold" data-line-total>0.00</div>
      </div>
      <button class="btn btn-outline-danger btn-sm" type="button" data-remove-sale-item><i class="bi bi-x-circle"></i>Remove</button>
    </div>
  </div>
</template>

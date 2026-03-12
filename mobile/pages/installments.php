<?php
$pageTitle = "Installments";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "record_payment") {
        $planId = (int)($_POST["plan_id"] ?? 0);
        $amount = (int)round((float)($_POST["amount"] ?? 0));
        $paymentDate = $_POST["payment_date"] ?? date("Y-m-d");

        if ($planId > 0 && $amount > 0) {
            db()->begin_transaction();
            try {
                db_query(
                    "INSERT INTO installment_payments (installment_plan_id, amount, payment_date)
                     VALUES (?, ?, ?)",
                    "ids",
                    [$planId, $amount, $paymentDate]
                );

                $plan = db_fetch_one(db_query("SELECT * FROM installment_plans WHERE id = ?", "i", [$planId]));
                if ($plan) {
                    $currentRemaining = (float)$plan["remaining_amount"];
                    $newRemaining = max(0, $currentRemaining - $amount);
                    $status = $newRemaining <= 0 ? "completed" : "active";
                    $nextDueDate = $status === "completed"
                        ? $plan["next_due_date"]
                        : date("Y-m-d", strtotime("+1 month", strtotime($plan["next_due_date"])));

                    db_query(
                        "UPDATE installment_plans SET remaining_amount = ?, status = ?, next_due_date = ? WHERE id = ?",
                        "dssi",
                        [$newRemaining, $status, $nextDueDate, $planId]
                    );
                }

                db()->commit();
                set_flash("success", "Payment recorded.");
            } catch (Throwable $e) {
                db()->rollback();
                set_flash("danger", "Failed to record payment.");
            }
        }

        redirect("index.php?page=installments");
    }

    if ($action === "delete_plan") {
        $planId = (int)($_POST["plan_id"] ?? 0);
        if ($planId > 0) {
            try {
                db_query("DELETE FROM installment_plans WHERE id = ?", "i", [$planId]);
                set_flash("success", "Installment plan deleted.");
            } catch (Throwable $e) {
                set_flash("danger", "Failed to delete installment plan.");
            }
        }
        redirect("index.php?page=installments");
    }
}

$plans = db_fetch_all(db_query(
    "SELECT ip.*,
            s.total_amount AS sale_total,
            c.name AS customer_name,
            (
              SELECT GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ')
              FROM sale_items si
              JOIN products p ON p.id = si.product_id
              WHERE si.sale_id = s.id
            ) AS product_names
     FROM installment_plans ip
     JOIN sales s ON s.id = ip.sale_id
     JOIN customers c ON c.id = s.customer_id
     ORDER BY ip.next_due_date ASC"
));
$planCount = count($plans);
$activeCount = 0;
$dueSoon = 0;
$today = new DateTimeImmutable("today");
$dueLimit = $today->modify("+7 days");
foreach ($plans as $plan) {
    if ($plan["status"] === "active") {
        $activeCount++;
        try {
            $nextDate = new DateTimeImmutable($plan["next_due_date"]);
            if ($nextDate <= $dueLimit) {
                $dueSoon++;
            }
        } catch (Throwable $e) {
        }
    }
}
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-calendar-check"></i>Payment Tracker</span>
      <h1 class="display-6 mb-2">Installments</h1>
      <p class="lead mb-0">Monitor upcoming dues and keep plans on track.</p>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-list-ol"></i></span>Total Plans
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$planCount); ?>"><?php echo h((string)$planCount); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-check-circle"></i></span>Active Plans
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$activeCount); ?>"><?php echo h((string)$activeCount); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-hourglass-split"></i></span>Due In 7 Days
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$dueSoon); ?>"><?php echo h((string)$dueSoon); ?></h3>
      </div>
    </div>
  </div>
</div>

<div class="card glass-card data-table-card">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h6 class="mb-0 section-title-icon"><i class="bi bi-journal-check"></i>Installment Plans</h6>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="table-installments">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="table-installments"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="table-installments">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-person"></i></span>Customer</th>
          <th><span class="th-icon"><i class="bi bi-phone"></i></span>Product</th>
          <th><span class="th-icon"><i class="bi bi-cash"></i></span>Total Amount</th>
          <th><span class="th-icon"><i class="bi bi-calendar2-week"></i></span>Monthly</th>
          <th><span class="th-icon"><i class="bi bi-wallet2"></i></span>Remaining</th>
          <th><span class="th-icon"><i class="bi bi-list-ol"></i></span>Installments Left</th>
          <th><span class="th-icon"><i class="bi bi-calendar-event"></i></span>Next Due</th>
          <th><span class="th-icon"><i class="bi bi-info-circle"></i></span>Status</th>
          <th><span class="th-icon"><i class="bi bi-gear"></i></span>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$plans): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">No installment plans.</td></tr>
        <?php else: ?>
          <?php foreach ($plans as $plan): ?>
            <?php
              $remaining = (float)$plan["remaining_amount"];
              $productNames = $plan["product_names"] ?: "-";
              $monthlyAmount = (float)$plan["monthly_amount"];
              $installmentsLeft = $monthlyAmount > 0 ? (int)ceil($remaining / $monthlyAmount) : 0;
            ?>
            <tr>
              <td><?php echo h($plan["customer_name"]); ?></td>
              <td><?php echo h($productNames); ?></td>
              <td><?php echo h(format_currency($plan["total_amount"])); ?></td>
              <td><?php echo h(format_currency($plan["monthly_amount"])); ?></td>
              <td><?php echo h(format_currency($remaining)); ?></td>
              <td><?php echo h((string)$installmentsLeft); ?></td>
              <td><?php echo h(date("Y-m-d", strtotime($plan["next_due_date"]))); ?></td>
              <td><span class="badge badge-soft"><?php echo h(ucfirst($plan["status"])); ?></span></td>
              <td>
                <div class="record-payment">
                  <form method="post" class="record-payment-form">
                    <input type="hidden" name="action" value="record_payment">
                    <input type="hidden" name="plan_id" value="<?php echo h((string)$plan["id"]); ?>">
                    <div class="record-payment-fields">
                      <input class="form-control form-control-sm" name="amount" type="number" step="1" min="0" value="<?php echo h((string)$plan["monthly_amount"]); ?>" placeholder="Amount">
                      <input class="form-control form-control-sm" name="payment_date" type="date" value="<?php echo h(date('Y-m-d')); ?>">
                      <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-circle"></i>Pay</button>
                    </div>
                  </form>
                  <div class="table-actions">
                    <button class="btn btn-sm btn-outline-secondary btn-print no-print" type="button" data-print-row><i class="bi bi-printer"></i>Print</button>
                    <form method="post">
                      <input type="hidden" name="action" value="delete_plan">
                      <input type="hidden" name="plan_id" value="<?php echo h((string)$plan["id"]); ?>">
                      <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this installment plan?')"><i class="bi bi-trash"></i>Delete</button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

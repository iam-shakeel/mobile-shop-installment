<?php
$pageTitle = "Reports";

$startDate = $_GET["start_date"] ?? "";
$endDate = $_GET["end_date"] ?? "";
$statusFilter = $_GET["status"] ?? "";

$salesQuery = "SELECT s.*, c.name AS customer_name FROM sales s JOIN customers c ON c.id = s.customer_id WHERE 1=1";
$types = "";
$params = [];

if ($startDate) {
    $salesQuery .= " AND DATE(s.created_at) >= ?";
    $types .= "s";
    $params[] = $startDate;
}
if ($endDate) {
    $salesQuery .= " AND DATE(s.created_at) <= ?";
    $types .= "s";
    $params[] = $endDate;
}

$salesQuery .= " ORDER BY s.created_at DESC";
$salesStmt = db_query($salesQuery, $types, $params);
$sales = db_fetch_all($salesStmt);

$totalSales = count($sales);
$totalAmount = array_sum(array_column($sales, "total_amount"));
$methodTotals = [];
foreach ($sales as $sale) {
    $method = $sale["payment_method"];
    $methodTotals[$method] = ($methodTotals[$method] ?? 0) + (float)$sale["total_amount"];
}

$installmentsQuery = "SELECT ip.*, c.name AS customer_name FROM installment_plans ip
                      JOIN sales s ON s.id = ip.sale_id
                      JOIN customers c ON c.id = s.customer_id WHERE 1=1";
$types = "";
$params = [];

if ($statusFilter) {
    $installmentsQuery .= " AND ip.status = ?";
    $types .= "s";
    $params[] = $statusFilter;
}

$installmentsQuery .= " ORDER BY ip.next_due_date ASC";
$installments = db_fetch_all(db_query($installmentsQuery, $types, $params));

$totalPlans = count($installments);
$totalInstallmentAmount = array_sum(array_column($installments, "total_amount"));
$totalReceivedRow = db_fetch_one(db_query("SELECT COALESCE(SUM(amount), 0) AS total_received FROM installment_payments"));
$totalReceived = (float)($totalReceivedRow["total_received"] ?? 0);
$totalPending = max(0, $totalInstallmentAmount - $totalReceived);

$ipdCustomer = trim($_GET["ipd_customer"] ?? "");
$ipdStartDate = $_GET["ipd_start_date"] ?? "";
$ipdEndDate = $_GET["ipd_end_date"] ?? "";

$installmentSummary = db_fetch_all(db_query(
    "SELECT c.id AS customer_id,
            c.name AS customer_name,
            COALESCE(SUM(pp.total_paid), 0) AS total_paid,
            COALESCE(SUM(ip.total_amount), 0) AS total_amount,
            COALESCE(SUM(ip.remaining_amount), 0) AS remaining_amount,
            COALESCE(SUM(pp.payment_count), 0) AS payments_count,
            MAX(pp.last_payment_date) AS last_payment_date
     FROM customers c
     JOIN sales s ON s.customer_id = c.id
     JOIN installment_plans ip ON ip.sale_id = s.id
     LEFT JOIN (
       SELECT installment_plan_id,
              COALESCE(SUM(amount), 0) AS total_paid,
              COUNT(*) AS payment_count,
              MAX(payment_date) AS last_payment_date
       FROM installment_payments
       GROUP BY installment_plan_id
     ) pp ON pp.installment_plan_id = ip.id
     GROUP BY c.id
     HAVING total_paid > 0
     ORDER BY total_paid DESC, c.name ASC"
));

$ipdQuery = "SELECT c.name AS customer_name,
            ip.total_amount,
            GREATEST(0, ip.total_amount - COALESCE(rp.cumulative_paid, 0)) AS remaining_amount,
            COALESCE(rp.cumulative_paid, 0) AS total_paid,
            pay.amount,
            pay.payment_date,
            (
              SELECT GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ')
              FROM sale_items si
              JOIN products p ON p.id = si.product_id
              WHERE si.sale_id = s.id
            ) AS product_names
     FROM installment_payments pay
     JOIN installment_plans ip ON ip.id = pay.installment_plan_id
     JOIN sales s ON s.id = ip.sale_id
     JOIN customers c ON c.id = s.customer_id
     LEFT JOIN (
       SELECT installment_plan_id,
              id AS payment_id,
              SUM(amount) OVER (PARTITION BY installment_plan_id ORDER BY payment_date, id) AS cumulative_paid
       FROM installment_payments
     ) rp ON rp.installment_plan_id = ip.id AND rp.payment_id = pay.id
     WHERE 1=1";
$ipdTypes = "";
$ipdParams = [];

if ($ipdCustomer !== "") {
    $ipdQuery .= " AND c.name LIKE ?";
    $ipdTypes .= "s";
    $ipdParams[] = "%" . $ipdCustomer . "%";
}
if ($ipdStartDate) {
    $ipdQuery .= " AND pay.payment_date >= ?";
    $ipdTypes .= "s";
    $ipdParams[] = $ipdStartDate;
}
if ($ipdEndDate) {
    $ipdQuery .= " AND pay.payment_date <= ?";
    $ipdTypes .= "s";
    $ipdParams[] = $ipdEndDate;
}

$ipdQuery .= " ORDER BY pay.payment_date DESC, pay.id DESC";
$installmentPayments = db_fetch_all(db_query($ipdQuery, $ipdTypes, $ipdParams));
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-graph-up-arrow"></i>Insights</span>
      <h1 class="display-6 mb-2">Reports</h1>
      <p class="lead mb-0">Filter revenue and installment performance in one view.</p>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card glass-card">
      <div class="card-header bg-transparent">
        <h6 class="mb-0 section-title-icon"><i class="bi bi-bar-chart-line"></i>Sales Report</h6>
      </div>
      <div class="card-body">
        <form class="row g-2 mb-3" method="get">
          <input type="hidden" name="page" value="reports">
          <div class="col-md-4">
            <input class="form-control" type="date" name="start_date" value="<?php echo h($startDate); ?>" placeholder="Start">
          </div>
          <div class="col-md-4">
            <input class="form-control" type="date" name="end_date" value="<?php echo h($endDate); ?>" placeholder="End">
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i>Filter</button>
          </div>
        </form>
        <div class="row text-center mb-3">
          <div class="col">
            <div class="text-muted">Total Sales</div>
            <div class="fs-5 fw-bold" data-count="<?php echo h((string)$totalSales); ?>"><?php echo h((string)$totalSales); ?></div>
          </div>
          <div class="col">
            <div class="text-muted">Total Amount</div>
            <div class="fs-5 fw-bold" data-count="<?php echo h((string)(int)round($totalAmount)); ?>" data-prefix="PKR ">
              <?php echo h(format_currency($totalAmount)); ?>
            </div>
          </div>
        </div>
        <ul class="list-group">
          <?php foreach ($methodTotals as $method => $amount): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?php echo h(ucwords(str_replace("_", " ", $method))); ?>
              <span><?php echo h(format_currency($amount)); ?></span>
            </li>
          <?php endforeach; ?>
          <?php if (!$methodTotals): ?>
            <li class="list-group-item text-muted text-center">No sales data.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card glass-card">
      <div class="card-header bg-transparent">
        <h6 class="mb-0 section-title-icon"><i class="bi bi-calendar2-range"></i>Installment Report</h6>
      </div>
      <div class="card-body">
        <form class="row g-2 mb-3" method="get">
          <input type="hidden" name="page" value="reports">
          <div class="col-md-8">
            <select class="form-select" name="status">
              <option value="">All Statuses</option>
              <option value="active" <?php echo $statusFilter === "active" ? "selected" : ""; ?>>Active</option>
              <option value="completed" <?php echo $statusFilter === "completed" ? "selected" : ""; ?>>Completed</option>
            </select>
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i>Filter</button>
          </div>
        </form>
        <div class="row text-center mb-3">
          <div class="col">
            <div class="text-muted">Plans</div>
            <div class="fs-5 fw-bold" data-count="<?php echo h((string)$totalPlans); ?>"><?php echo h((string)$totalPlans); ?></div>
          </div>
          <div class="col">
            <div class="text-muted">Total Amount</div>
            <div class="fs-5 fw-bold" data-count="<?php echo h((string)(int)round($totalInstallmentAmount)); ?>" data-prefix="PKR ">
              <?php echo h(format_currency($totalInstallmentAmount)); ?>
            </div>
          </div>
          <div class="col">
            <div class="text-muted">Pending</div>
            <div class="fs-5 fw-bold" data-count="<?php echo h((string)(int)round($totalPending)); ?>" data-prefix="PKR ">
              <?php echo h(format_currency($totalPending)); ?>
            </div>
          </div>
        </div>
        <ul class="list-group">
          <?php foreach ($installments as $plan): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?php echo h($plan["customer_name"]); ?> (<?php echo h(ucfirst($plan["status"])); ?>)
              <span><?php echo h(format_currency($plan["total_amount"])); ?></span>
            </li>
          <?php endforeach; ?>
          <?php if (!$installments): ?>
            <li class="list-group-item text-muted text-center">No installment data.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="card glass-card data-table-card mt-4">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h6 class="mb-0 section-title-icon"><i class="bi bi-receipt"></i>Sales Details</h6>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="table-sales-details">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="table-sales-details"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="table-sales-details">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-hash"></i></span>ID</th>
          <th><span class="th-icon"><i class="bi bi-person"></i></span>Customer</th>
          <th><span class="th-icon"><i class="bi bi-cash"></i></span>Amount</th>
          <th><span class="th-icon"><i class="bi bi-credit-card"></i></span>Method</th>
          <th><span class="th-icon"><i class="bi bi-check2-circle"></i></span>Status</th>
          <th><span class="th-icon"><i class="bi bi-calendar-event"></i></span>Date</th>
          <th><span class="th-icon"><i class="bi bi-printer"></i></span>Print</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$sales): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No sales found.</td></tr>
        <?php else: ?>
          <?php foreach ($sales as $sale): ?>
            <tr>
              <td><?php echo h((string)$sale["id"]); ?></td>
              <td><?php echo h($sale["customer_name"]); ?></td>
              <td><?php echo h(format_currency($sale["total_amount"])); ?></td>
              <td><?php echo h(ucwords(str_replace("_", " ", $sale["payment_method"]))); ?></td>
              <td><?php echo h(ucfirst($sale["status"])); ?></td>
              <td><?php echo h(date("Y-m-d", strtotime($sale["created_at"]))); ?></td>
              <td><button class="btn btn-sm btn-outline-secondary btn-print no-print" type="button" data-print-row><i class="bi bi-printer"></i>Print</button></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card glass-card data-table-card mt-4">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h6 class="mb-0 section-title-icon"><i class="bi bi-people"></i>Installment Payments By Customer</h6>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="table-installments-summary">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="table-installments-summary"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="table-installments-summary">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-person"></i></span>Customer</th>
          <th><span class="th-icon"><i class="bi bi-cash"></i></span>Total Amount</th>
          <th><span class="th-icon"><i class="bi bi-cash-stack"></i></span>Total Paid</th>
          <th><span class="th-icon"><i class="bi bi-wallet2"></i></span>Remaining</th>
          <th><span class="th-icon"><i class="bi bi-list-ol"></i></span>Payments</th>
          <th><span class="th-icon"><i class="bi bi-calendar-event"></i></span>Last Payment</th>
          <th><span class="th-icon"><i class="bi bi-printer"></i></span>Print</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$installmentSummary): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No installment payments recorded.</td></tr>
        <?php else: ?>
          <?php foreach ($installmentSummary as $row): ?>
            <tr>
              <td><?php echo h($row["customer_name"]); ?></td>
              <td><?php echo h(format_currency($row["total_amount"])); ?></td>
              <td><?php echo h(format_currency($row["total_paid"])); ?></td>
              <td><?php echo h(format_currency($row["remaining_amount"])); ?></td>
              <td><?php echo h((string)$row["payments_count"]); ?></td>
              <td><?php echo h($row["last_payment_date"] ? date("Y-m-d", strtotime($row["last_payment_date"])) : "-"); ?></td>
              <td><button class="btn btn-sm btn-outline-secondary btn-print no-print" type="button" data-print-row><i class="bi bi-printer"></i>Print</button></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card glass-card data-table-card mt-4">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h6 class="mb-0 section-title-icon"><i class="bi bi-credit-card-2-front"></i>Installment Payment Details</h6>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="installment-payment-details">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="installment-payment-details"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="card-body">
    <form class="row g-2" method="get">
      <input type="hidden" name="page" value="reports">
      <input type="hidden" name="start_date" value="<?php echo h($startDate); ?>">
      <input type="hidden" name="end_date" value="<?php echo h($endDate); ?>">
      <input type="hidden" name="status" value="<?php echo h($statusFilter); ?>">
      <div class="col-md-4">
        <input class="form-control" name="ipd_customer" placeholder="Customer name" value="<?php echo h($ipdCustomer); ?>">
      </div>
      <div class="col-md-3">
        <input class="form-control" type="date" name="ipd_start_date" value="<?php echo h($ipdStartDate); ?>">
      </div>
      <div class="col-md-3">
        <input class="form-control" type="date" name="ipd_end_date" value="<?php echo h($ipdEndDate); ?>">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </form>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="installment-payment-details">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-person"></i></span>Customer</th>
          <th><span class="th-icon"><i class="bi bi-phone"></i></span>Products</th>
          <th><span class="th-icon"><i class="bi bi-cash"></i></span>Total Amount</th>
          <th><span class="th-icon"><i class="bi bi-cash-stack"></i></span>Total Paid</th>
          <th><span class="th-icon"><i class="bi bi-wallet2"></i></span>Remaining</th>
          <th><span class="th-icon"><i class="bi bi-coin"></i></span>Amount</th>
          <th><span class="th-icon"><i class="bi bi-calendar-event"></i></span>Payment Date</th>
          <th><span class="th-icon"><i class="bi bi-printer"></i></span>Print</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$installmentPayments): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No installment payments recorded.</td></tr>
        <?php else: ?>
          <?php foreach ($installmentPayments as $payment): ?>
            <tr>
              <td><?php echo h($payment["customer_name"]); ?></td>
              <td><?php echo h($payment["product_names"] ?: "-"); ?></td>
              <td><?php echo h(format_currency($payment["total_amount"])); ?></td>
              <td><?php echo h(format_currency($payment["total_paid"])); ?></td>
              <td><?php echo h(format_currency($payment["remaining_amount"])); ?></td>
              <td><?php echo h(format_currency($payment["amount"])); ?></td>
              <td><?php echo h(date("Y-m-d", strtotime($payment["payment_date"]))); ?></td>
              <td><button class="btn btn-sm btn-outline-secondary btn-print no-print" type="button" data-print-row><i class="bi bi-printer"></i>Print</button></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

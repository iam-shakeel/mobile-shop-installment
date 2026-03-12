<?php
$pageTitle = "Dashboard";

$productCount = db_fetch_one(db_query("SELECT COUNT(*) AS total FROM products"))["total"] ?? 0;
$customerCount = db_fetch_one(db_query("SELECT COUNT(*) AS total FROM customers"))["total"] ?? 0;

$todaySalesRow = db_fetch_one(db_query(
    "SELECT COUNT(*) AS total_sales, COALESCE(SUM(total_amount), 0) AS total_amount FROM sales WHERE DATE(created_at) = CURDATE()"
));
$todaySalesCount = $todaySalesRow["total_sales"] ?? 0;
$todaySalesAmount = $todaySalesRow["total_amount"] ?? 0;

$dueInstallments = db_fetch_one(db_query(
    "SELECT COUNT(*) AS total FROM installment_plans WHERE status = 'active' AND next_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
))["total"] ?? 0;

$recentSales = db_fetch_all(db_query(
    "SELECT s.id, s.total_amount, s.payment_method, s.created_at, c.name AS customer_name
     FROM sales s
     JOIN customers c ON c.id = s.customer_id
     ORDER BY s.created_at DESC
     LIMIT 5"
));
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-activity"></i>Store Pulse</span>
      <h1 class="display-6 mb-2">Dashboard</h1>
      <p class="lead mb-0">Live snapshot of sales, stock, and upcoming dues.</p>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-hero" href="index.php?page=sales_new"><i class="bi bi-receipt-cutoff"></i>New Sale</a>
      <a class="btn btn-outline-light" href="index.php?page=products"><i class="bi bi-box-seam"></i>Manage Products</a>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-box-seam"></i></span>Products
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$productCount); ?>"><?php echo h((string)$productCount); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-people"></i></span>Customers
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$customerCount); ?>"><?php echo h((string)$customerCount); ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-receipt"></i></span>Today Sales
        </p>
        <h3 class="mb-1" data-count="<?php echo h((string)$todaySalesCount); ?>"><?php echo h((string)$todaySalesCount); ?></h3>
        <div class="text-muted" data-count="<?php echo h((string)(int)round($todaySalesAmount)); ?>" data-prefix="PKR ">
          <?php echo h(format_currency($todaySalesAmount)); ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card h-100" data-skeleton>
      <div class="card-body">
        <p class="text-uppercase text-muted small mb-1 label-icon">
          <span class="icon-badge"><i class="bi bi-calendar2-week"></i></span>Due Installments
        </p>
        <h3 class="mb-0" data-count="<?php echo h((string)$dueInstallments); ?>"><?php echo h((string)$dueInstallments); ?></h3>
        <div class="text-muted">Next 7 days</div>
      </div>
    </div>
  </div>
</div>

<div class="card glass-card data-table-card">
  <div class="card-header bg-transparent">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <h5 class="mb-0 section-title-icon"><i class="bi bi-clock-history"></i>Recent Sales</h5>
      <div class="table-tools">
        <input class="form-control quick-search" placeholder="Search" data-table-filter data-target-table="table-recent-sales">
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-refresh-page><i class="bi bi-arrow-clockwise"></i></button>
        <button class="btn btn-sm btn-outline-secondary btn-icon" type="button" data-export-csv data-target-table="table-recent-sales"><i class="bi bi-download"></i></button>
        
      </div>
    </div>
  </div>
  <div class="table-responsive" data-skeleton>
    <table class="table table-borderless align-middle mb-0 table-fit table-modern" id="table-recent-sales">
      <thead>
        <tr>
          <th><span class="th-icon"><i class="bi bi-hash"></i></span>ID</th>
          <th><span class="th-icon"><i class="bi bi-person"></i></span>Customer</th>
          <th><span class="th-icon"><i class="bi bi-cash"></i></span>Amount</th>
          <th><span class="th-icon"><i class="bi bi-credit-card"></i></span>Method</th>
          <th><span class="th-icon"><i class="bi bi-calendar-event"></i></span>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recentSales): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No sales yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentSales as $sale): ?>
            <tr>
              <td><?php echo h((string)$sale["id"]); ?></td>
              <td><?php echo h($sale["customer_name"]); ?></td>
              <td><?php echo h(format_currency($sale["total_amount"])); ?></td>
              <td><?php echo h(ucwords(str_replace("_", " ", $sale["payment_method"]))); ?></td>
              <td><?php echo h(date("Y-m-d", strtotime($sale["created_at"]))); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

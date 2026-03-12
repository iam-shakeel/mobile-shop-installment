<?php
$current = $_GET["page"] ?? "dashboard";
$appSettings = $appSettings ?? get_app_settings();
$brandName = $appSettings["business_name"] ?? "Mobile Shop";
$logoUrl = $appSettings["logo_url"] ?? "";
$contactNumber = trim($appSettings["contact"] ?? "");
$whatsappLink = $contactNumber ? "https://wa.me/" . preg_replace("/\D+/", "", $contactNumber) : "";
$siteHref = "index.php?page=dashboard";
$siteTarget = "_self";
$whatsappHref = $whatsappLink ?: "index.php?page=settings";
$whatsappTarget = $whatsappLink ? "_blank" : "_self";
$initials = strtoupper(substr(preg_replace("/\s+/", "", $brandName), 0, 2));
?>
<aside class="col-lg-2 col-md-3 bg-light sidebar py-4 d-flex flex-column">
  <div class="sidebar-brand px-3 mb-4">
    <?php if (!empty($logoUrl)): ?>
      <img src="<?php echo h($logoUrl); ?>" alt="Logo" class="sidebar-logo">
    <?php else: ?>
      <div class="sidebar-logo sidebar-logo-fallback"><?php echo h($initials ?: "MS"); ?></div>
    <?php endif; ?>
    <div>
      <div class="sidebar-title"><?php echo h($brandName); ?></div>
      <div class="sidebar-subtitle">Business Console</div>
    </div>
  </div>
  <div class="list-group list-group-flush">
    <a class="list-group-item list-group-item-action <?php echo $current === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
      <i class="bi bi-speedometer2 nav-icon"></i>Dashboard
    </a>
    <a class="list-group-item list-group-item-action <?php echo $current === 'products' ? 'active' : ''; ?>" href="index.php?page=products">
      <i class="bi bi-box-seam nav-icon"></i>Products
    </a>
    <a class="list-group-item list-group-item-action <?php echo $current === 'customers' ? 'active' : ''; ?>" href="index.php?page=customers">
      <i class="bi bi-people nav-icon"></i>Customers
    </a>
    <a class="list-group-item list-group-item-action <?php echo $current === 'sales_new' ? 'active' : ''; ?>" href="index.php?page=sales_new">
      <i class="bi bi-receipt-cutoff nav-icon"></i>New Sale
    </a>
    <a class="list-group-item list-group-item-action <?php echo $current === 'installments' ? 'active' : ''; ?>" href="index.php?page=installments">
      <i class="bi bi-calendar2-check nav-icon"></i>Installments
    </a>
    <a class="list-group-item list-group-item-action <?php echo $current === 'reports' ? 'active' : ''; ?>" href="index.php?page=reports">
      <i class="bi bi-graph-up nav-icon"></i>Reports
    </a>
    <a class="list-group-item list-group-item-action <?php echo $current === 'settings' ? 'active' : ''; ?>" href="index.php?page=settings">
      <i class="bi bi-sliders2 nav-icon"></i>Settings
    </a>
  </div>
  <div class="sidebar-footer mt-auto px-0 pt-2">
    <div class="sidebar-footer-card">
      <div class="sidebar-footer-text">Build by Shakeel Ahmad Jan</div>
      <div class="sidebar-footer-actions mt-3">
        <a class="btn btn-sm footer-btn" href="<?php echo h($siteHref); ?>" target="<?php echo h($siteTarget); ?>" rel="noopener">
          <i class="bi bi-globe2"></i><span>Site</span>
        </a>
        <a class="btn btn-sm footer-btn footer-btn-primary" href="<?php echo h($whatsappHref); ?>" target="<?php echo h($whatsappTarget); ?>" rel="noopener">
          <i class="bi bi-whatsapp"></i><span>WhatsApp</span>
        </a>
      </div>
    </div>
  </div>
</aside>
<main class="col-lg-10 col-md-9 py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash["message"]); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

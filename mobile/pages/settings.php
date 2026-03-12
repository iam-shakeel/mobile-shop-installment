<?php
$pageTitle = "Settings";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $businessName = trim($_POST["business_name"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $contact = trim($_POST["contact"] ?? "");
    $settings = get_app_settings();
    $logoUrl = $settings["logo_url"] ?? "";

    if (!empty($_FILES["logo_file"]["name"])) {
        if ($_FILES["logo_file"]["error"] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/../assets/uploads";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $tmpName = $_FILES["logo_file"]["tmp_name"];
            $imageInfo = @getimagesize($tmpName);
            if ($imageInfo === false) {
                set_flash("danger", "Logo must be an image file.");
                redirect("index.php?page=settings");
            }

            $extension = image_type_to_extension($imageInfo[2], false);
            if (!$extension) {
                $extension = pathinfo($_FILES["logo_file"]["name"], PATHINFO_EXTENSION);
            }
            $extension = strtolower($extension ?: "png");
            $fileName = "logo_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $extension;
            $destination = $uploadDir . "/" . $fileName;

            if (!move_uploaded_file($tmpName, $destination)) {
                set_flash("danger", "Failed to upload logo.");
                redirect("index.php?page=settings");
            }

            $logoUrl = "assets/uploads/" . $fileName;
        } else {
            set_flash("danger", "Logo upload failed.");
            redirect("index.php?page=settings");
        }
    }

    if ($businessName === "") {
        set_flash("danger", "Business name is required.");
    } else {
        db_query(
            "UPDATE settings SET business_name = ?, logo_url = ?, address = ?, contact = ? WHERE id = 1",
            "ssss",
            [$businessName, $logoUrl, $address, $contact]
        );
        set_flash("success", "Settings updated.");
    }
    redirect("index.php?page=settings");
}

$settings = get_app_settings();
?>

<div class="page-hero mb-4">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
    <div>
      <span class="eyebrow"><i class="bi bi-sliders2"></i>Configuration</span>
      <h1 class="display-6 mb-2">Print Settings</h1>
      <p class="lead mb-0">Update business details used on invoice printouts.</p>
    </div>
  </div>
</div>

<div class="card glass-card">
  <div class="card-header bg-transparent">
    <h6 class="mb-0 section-title-icon"><i class="bi bi-building"></i>Business Profile</h6>
  </div>
  <div class="card-body">
    <form method="post" class="row g-3" enctype="multipart/form-data">
      <div class="col-md-6">
        <label class="form-label">Business Name</label>
        <input class="form-control" name="business_name" value="<?php echo h($settings["business_name"] ?? ""); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Logo Upload</label>
        <input class="form-control" name="logo_file" type="file" accept="image/*">
        <?php if (!empty($settings["logo_url"])): ?>
          <div class="mt-2">
            <img src="<?php echo h($settings["logo_url"]); ?>" alt="Logo" style="height:48px;width:48px;border-radius:12px;object-fit:cover;">
          </div>
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Address</label>
        <input class="form-control" name="address" value="<?php echo h($settings["address"] ?? ""); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Contact</label>
        <input class="form-control" name="contact" value="<?php echo h($settings["contact"] ?? ""); ?>">
      </div>
      <div class="col-12 d-flex justify-content-end">
        <button class="btn btn-primary"><i class="bi bi-check-circle"></i>Save Settings</button>
      </div>
    </form>
  </div>
</div>

if (document.body) {
  document.body.classList.add("is-loading");
}

const themeKey = "theme";
const themeRoot = document.documentElement;
const savedTheme = localStorage.getItem(themeKey) || "light";
themeRoot.setAttribute("data-theme", savedTheme);

function updateThemeToggle() {
  const button = document.querySelector("[data-theme-toggle]");
  if (!button) return;
  const label = button.querySelector("[data-theme-label]");
  const icon = button.querySelector("i");
  const isDark = themeRoot.getAttribute("data-theme") === "dark";
  button.setAttribute("aria-pressed", isDark ? "true" : "false");
  if (label) label.textContent = isDark ? "Light Mode" : "Dark Mode";
  if (icon) icon.className = isDark ? "bi bi-sun" : "bi bi-moon-stars";
}

updateThemeToggle();

function recalcSaleTotals() {
  const rows = document.querySelectorAll("[data-sale-item-row]");
  let total = 0;
  rows.forEach((row) => {
    updateRowLineTotal(row);
    const price = parseFloat(row.querySelector("[name='unit_price[]']").value || "0");
    const lineTotal = price;
    row.querySelector("[data-line-total]").textContent = lineTotal.toFixed(0);
    total += lineTotal;
  });

  const totalEl = document.querySelector("[data-sale-total]");
  if (totalEl) totalEl.textContent = total.toFixed(0);

  const totalInput = document.querySelector("[name='total_amount']");
  if (totalInput) totalInput.value = total.toFixed(0);

  updateInstallmentFromSale();
}

function addSaleItemRow() {
  const template = document.querySelector("#sale-item-template");
  const container = document.querySelector("#sale-items");
  if (!template || !container) return;

  const clone = template.content.cloneNode(true);
  container.appendChild(clone);
  recalcSaleTotals();
}

function updateRowLineTotal(row) {
  if (!row) return;
  const qtyInput = row.querySelector("[name='quantity[]']");
  const priceInput = row.querySelector("[name='unit_price[]']");
  const select = row.querySelector("[name='product_id[]']");
  if (!qtyInput || !priceInput || !select) return;

  const qty = Math.max(0, parseFloat(qtyInput.value || "0"));
  const option = select.options[select.selectedIndex];
  const basePrice = option ? parseFloat(option.getAttribute("data-price") || "0") : 0;
  const lineTotal = Math.max(0, Math.round(basePrice * qty));
  priceInput.value = String(lineTotal);
}
function updateInstallmentFromSale() {
  const section = document.querySelector("[data-installment-section]");
  if (!section) return;
  const form = section.closest("form");
  if (!form) return;
  const methodSelect = form.querySelector("[data-payment-method]");
  if (!methodSelect || !section) return;
  if (methodSelect.value !== "installment") return;

  const totalInput = form.querySelector("[name='total_amount']");
  const downInput = form.querySelector("[name='down_payment']");
  const durationInput = form.querySelector("[name='duration_months']");
  const monthlyInput = form.querySelector("[name='monthly_amount']");
  if (!totalInput || !downInput || !durationInput || !monthlyInput) return;

  const total = parseFloat(totalInput.value || "0");
  const down = parseFloat(downInput.value || "0");
  const duration = Math.max(1, parseInt(durationInput.value || "1", 10));

  const monthly = Math.max(0, Math.round((total - down) / duration));
  monthlyInput.value = String(monthly);
}

document.addEventListener("input", (event) => {
  if (event.target && event.target.closest("[data-sale-item-row]")) {
    recalcSaleTotals();
  }
});

document.addEventListener("change", (event) => {
  if (event.target && event.target.matches("[name='product_id[]']")) {
    recalcSaleTotals();
  }
});

document.addEventListener("click", (event) => {
  const target = event.target instanceof Element ? event.target : event.target.parentElement;
  if (!target) return;

  if (target.closest("[data-theme-toggle]")) {
    event.preventDefault();
    const nextTheme = themeRoot.getAttribute("data-theme") === "dark" ? "light" : "dark";
    themeRoot.setAttribute("data-theme", nextTheme);
    localStorage.setItem(themeKey, nextTheme);
    updateThemeToggle();
  }

  if (target.closest("[data-refresh-page]")) {
    event.preventDefault();
    window.location.reload();
  }

  if (target.closest("[data-export-csv]")) {
    event.preventDefault();
    const button = target.closest("[data-export-csv]");
    const tableId = button.getAttribute("data-target-table");
    let table = tableId ? document.getElementById(tableId) : null;
    if (!table) {
      const card = button.closest(".card");
      table = card ? card.querySelector("table") : null;
    }
    if (!table) return;

    const headers = Array.from(table.querySelectorAll("thead th")).map((th) =>
      th.textContent.trim()
    );
    const skipIndexes = headers
      .map((h, i) => (/actions|print/i.test(h) ? i : -1))
      .filter((i) => i >= 0);

    const rows = [];
    rows.push(
      headers.filter((_, i) => !skipIndexes.includes(i)).join(",")
    );

    table.querySelectorAll("tbody tr").forEach((row) => {
      if (row.style.display === "none") return;
      const cols = Array.from(row.querySelectorAll("td"))
        .map((td) => td.textContent.replace(/\s+/g, " ").trim())
        .filter((_, i) => !skipIndexes.includes(i))
        .map((val) => `"${val.replace(/"/g, '""')}"`);
      rows.push(cols.join(","));
    });

    const blob = new Blob([rows.join("\n")], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "export.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }
  if (target.closest("[data-print-page]")) {
    event.preventDefault();
    window.print();
  }

  if (target.closest("[data-print-row]")) {
    event.preventDefault();
    const button = target.closest("[data-print-row]");
    const row = button.closest("tr");
    const table = row ? row.closest("table") : null;
    if (!row || !table) return;

    const headers = Array.from(table.querySelectorAll("thead th")).map((th) =>
      th.textContent.trim()
    );
    const cells = Array.from(row.querySelectorAll("td")).map((td) =>
      td.textContent.trim()
    );

    const details = [];
    headers.forEach((label, idx) => {
      const value = cells[idx] || "";
      if (!label || /actions|print/i.test(label)) return;
      if (!value) return;
      details.push({ label, value });
    });

    const recipient = details.length ? details[0].value : "Customer";
    const settings = window.APP_SETTINGS || {};
    const businessName = escapeHtml(settings.business_name || "Business Name");
    const businessAddress = escapeHtml(settings.address || "Office Address");
    const businessContact = escapeHtml(settings.contact || "+92 123 456 7890");
    const logoUrl = settings.logo_url ? escapeHtml(settings.logo_url) : "";

    const dateStr = new Date().toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    const detailRows = details
      .map(
        (item) => `
          <tr>
            <td>${item.label}</td>
            <td>${item.value}</td>
          </tr>`
      )
      .join("");

    const printWindow = window.open("", "_blank", "width=900,height=700");
    if (!printWindow) return;

    printWindow.document.write(`<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
      :root { --ink:#111827; --muted:#6b7280; --accent:#5b4ae3; --border:#e5e7eb; }
      * { box-sizing: border-box; }
      body {
        font-family: "Manrope", Arial, sans-serif;
        color: var(--ink);
        background: #f3f4f6;
        padding: 32px;
      }
      .invoice {
        max-width: 820px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 18px;
        padding: 32px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.2);
      }
      .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 20px;
      }
      .brand {
        display: flex;
        gap: 12px;
        align-items: center;
      }
      .logo {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: radial-gradient(circle at 30% 30%, #a5b4fc, #5b4ae3);
      }
      .brand h1 {
        font-family: "Space Grotesk", Arial, sans-serif;
        font-size: 20px;
        margin: 0;
      }
      .brand p {
        margin: 2px 0 0;
        color: var(--muted);
        font-size: 12px;
      }
      .invoice-meta {
        text-align: right;
      }
      .invoice-meta h2 {
        font-family: "Space Grotesk", Arial, sans-serif;
        margin: 0;
        font-size: 24px;
        letter-spacing: 0.08em;
        color: var(--accent);
      }
      .invoice-meta p {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 12px;
      }
      .section {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        padding: 22px 0;
      }
      .section h4 {
        margin: 0 0 6px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
      }
      .section p {
        margin: 0;
        font-size: 13px;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
      }
      thead th {
        background: #f4f5ff;
        color: #3f3f46;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 10px 12px;
        text-align: left;
      }
      tbody td {
        padding: 12px;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
      }
      .totals {
        margin-top: 18px;
        display: flex;
        justify-content: flex-end;
      }
      .totals-box {
        min-width: 220px;
        background: #f4f5ff;
        border-radius: 12px;
        padding: 12px 16px;
      }
      .totals-box div {
        display: flex;
        justify-content: space-between;
        margin: 6px 0;
        font-size: 12px;
        color: var(--muted);
      }
      .totals-box strong {
        color: var(--ink);
      }
      .footer {
        margin-top: 24px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        font-size: 11px;
        color: var(--muted);
      }
      .accent {
        color: var(--accent);
        font-weight: 600;
      }
    </style>
  </head>
  <body>
    <div class="invoice">
      <div class="header">
        <div class="brand">
          ${
            logoUrl
              ? `<img src="${logoUrl}" alt="Logo" style="width:56px;height:56px;border-radius:0;object-fit:contain;background:transparent;">`
              : `<div class="logo"></div>`
          }
          <div>
            <h1>${businessName}</h1>
            <p>${businessAddress}</p>
            <p>${businessContact}</p>
          </div>
        </div>
        <div class="invoice-meta">
          <h2>INVOICE</h2>
          <p>${dateStr}</p>
        </div>
      </div>

      <div class="section">
        <div>
          <h4>To</h4>
          <p>${recipient}</p>
          <p class="accent">Thank you for your business</p>
        </div>
        <div>
          <h4>Details</h4>
          <p>Generated from system record</p>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Item Description</th>
            <th style="width: 35%;">Value</th>
          </tr>
        </thead>
        <tbody>
          ${detailRows}
        </tbody>
      </table>

      <div style="height: 8px;"></div>
    </div>
    <script>window.print();<\/script>
  </body>
</html>`);
    printWindow.document.close();
  }

  if (target.matches("[data-add-sale-item]")) {
    event.preventDefault();
    addSaleItemRow();
  }

  if (target.matches("[data-remove-sale-item]")) {
    event.preventDefault();
    const row = target.closest("[data-sale-item-row]");
    if (row) row.remove();
    recalcSaleTotals();
  }
});

function applyStackedTables() {
  const tables = document.querySelectorAll(".table-fit");
  tables.forEach((table) => {
    const headers = Array.from(table.querySelectorAll("thead th")).map((th) =>
      th.textContent.trim()
    );
    if (!headers.length) return;
    table.querySelectorAll("tbody tr").forEach((row) => {
      Array.from(row.children).forEach((cell, idx) => {
        if (cell.tagName !== "TD") return;
        if (!cell.getAttribute("data-label") && headers[idx]) {
          cell.setAttribute("data-label", headers[idx]);
        }
      });
    });
  });
}

document.addEventListener("DOMContentLoaded", () => {
  applyStackedTables();
  initInstallmentToggle();
  recalcSaleTotals();
  initReveals();
  initCounters();
  // Parallax removed for performance.
  setTimeout(() => {
    if (document.body) document.body.classList.remove("is-loading");
  }, 120);
});

function initReveals() {
  const candidates = document.querySelectorAll(
    ".page-hero, .stat-card, .glass-card, .table-responsive, .modal-content"
  );
  candidates.forEach((el, index) => {
    el.classList.add("reveal");
    el.style.transitionDelay = `${(index % 10) * 60}ms`;
  });

  if (!("IntersectionObserver" in window)) {
    candidates.forEach((el) => el.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 }
  );

  candidates.forEach((el) => observer.observe(el));
}

function initCounters() {
  const elements = document.querySelectorAll("[data-count]");
  if (!elements.length) return;

  const prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (prefersReduced) {
    elements.forEach((el) => {
      const target = parseInt(el.getAttribute("data-count") || "0", 10);
      const prefix = el.getAttribute("data-prefix") || "";
      const suffix = el.getAttribute("data-suffix") || "";
      el.textContent = `${prefix}${formatNumber(target)}${suffix}`;
    });
    return;
  }

  const animate = (el) => {
    if (el.getAttribute("data-counted") === "true") return;
    el.setAttribute("data-counted", "true");
    const target = parseInt(el.getAttribute("data-count") || "0", 10);
    const prefix = el.getAttribute("data-prefix") || "";
    const suffix = el.getAttribute("data-suffix") || "";
    const duration = 1200;
    const start = performance.now();

    const tick = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      const value = Math.round(target * eased);
      el.textContent = `${prefix}${formatNumber(value)}${suffix}`;
      if (progress < 1) {
        requestAnimationFrame(tick);
      }
    };
    requestAnimationFrame(tick);
  };

  if (!("IntersectionObserver" in window)) {
    elements.forEach((el) => animate(el));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animate(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.3 }
  );

  elements.forEach((el) => observer.observe(el));
}

function formatNumber(value) {
  return Number(value || 0).toLocaleString("en-US");
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function initHeroParallax() {
  const heroes = document.querySelectorAll(".page-hero");
  if (!heroes.length) return;
  const prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (prefersReduced) return;

  const onScroll = () => {
    const offset = window.scrollY * 0.1;
    heroes.forEach((hero) => {
      hero.style.backgroundPosition = `center calc(50% + ${offset}px)`;
    });
  };

  let ticking = false;
  window.addEventListener("scroll", () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        onScroll();
        ticking = false;
      });
      ticking = true;
    }
  });

  heroes.forEach((hero) => {
    hero.addEventListener("mousemove", (event) => {
      const rect = hero.getBoundingClientRect();
      const x = ((event.clientX - rect.left) / rect.width - 0.5) * 6;
      const y = ((event.clientY - rect.top) / rect.height - 0.5) * 6;
      hero.style.backgroundPosition = `calc(50% + ${x}px) calc(50% + ${y}px)`;
    });
    hero.addEventListener("mouseleave", () => {
      hero.style.backgroundPosition = "center";
    });
  });
}

function initInstallmentToggle() {
  const methodSelects = document.querySelectorAll("[data-payment-method]");
  if (!methodSelects.length) return;

  const toggleSection = (selectEl) => {
    const form = selectEl.closest("form") || document;
    const section = form.querySelector("[data-installment-section]");
    if (!section) return;
    const isInstallment = selectEl.value === "installment";
    section.classList.toggle("d-none", !isInstallment);
    section.classList.toggle("is-open", isInstallment);

    section.querySelectorAll("input, select, textarea").forEach((field) => {
      field.disabled = !isInstallment;
    });

    if (!isInstallment) {
      section.querySelectorAll("input, select, textarea").forEach((field) => {
        if (field.tagName === "SELECT") {
          field.selectedIndex = 0;
        } else {
          field.value = "";
        }
      });
    }

    if (isInstallment) {
      updateInstallmentFromSale();
    }
  };

  methodSelects.forEach((selectEl) => {
    toggleSection(selectEl);
    selectEl.addEventListener("change", () => toggleSection(selectEl));
  });
}

document.addEventListener("input", (event) => {
  if (
    event.target &&
    (event.target.matches("[name='down_payment']") ||
      event.target.matches("[name='duration_months']"))
  ) {
    updateInstallmentFromSale();
  }

  if (event.target && event.target.matches("[data-table-filter]")) {
    const query = event.target.value.toLowerCase().trim();
    const tableId = event.target.getAttribute("data-target-table");
    if (!tableId) return;
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = table.querySelectorAll("tbody tr");
    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(query) ? "" : "none";
    });
  }
});

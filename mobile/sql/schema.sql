CREATE DATABASE IF NOT EXISTS mobile_shop;
USE mobile_shop;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  brand VARCHAR(255) DEFAULT NULL,
  model VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  specifications TEXT DEFAULT NULL,
  stock_quantity INT NOT NULL DEFAULT 0,
  image_url VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone_number VARCHAR(50) DEFAULT NULL,
  cnic VARCHAR(30) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'processing',
  payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS sale_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS installment_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NOT NULL,
  down_payment DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  remaining_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  duration_months INT NOT NULL DEFAULT 1,
  monthly_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  start_date DATE NOT NULL,
  next_due_date DATE NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  CONSTRAINT fk_installment_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS installment_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  installment_plan_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_date DATE NOT NULL,
  CONSTRAINT fk_payments_plan FOREIGN KEY (installment_plan_id) REFERENCES installment_plans(id) ON DELETE CASCADE
);

CREATE INDEX idx_sales_created_at ON sales(created_at);
CREATE INDEX idx_installment_due ON installment_plans(next_due_date);

CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY,
  business_name VARCHAR(255) NOT NULL DEFAULT 'Business Name',
  logo_url VARCHAR(500) DEFAULT NULL,
  address VARCHAR(255) DEFAULT 'Office Address',
  contact VARCHAR(100) DEFAULT '+92 123 456 7890',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO settings (id, business_name, address, contact)
VALUES (1, 'Business Name', 'Office Address', '+92 123 456 7890')
ON DUPLICATE KEY UPDATE id = id;

USE mobile_shop;

INSERT INTO products (name, brand, model, description, price, cost_price, specifications, stock_quantity)
VALUES
('iPhone 13 Pro', 'Apple', 'iPhone 13 Pro', 'Apple iPhone 13 Pro 128GB', 235000, 210000, '{"memory":"128GB","color":"Graphite","ram":"6GB"}', 15),
('Samsung Galaxy S22', 'Samsung', 'Galaxy S22', 'Samsung Galaxy S22 256GB', 186000, 165000, '{"memory":"256GB","color":"Black","ram":"8GB"}', 20),
('Xiaomi Redmi Note 11', 'Xiaomi', 'Redmi Note 11', 'Xiaomi Redmi Note 11 64GB', 42000, 34000, '{"memory":"64GB","color":"Blue","ram":"4GB"}', 30);

INSERT INTO customers (name, phone_number, cnic, address, email)
VALUES
('Ahmad Khan', '0300-1234567', '35202-1234567-8', 'Gulberg III, Lahore', 'ahmad@example.com'),
('Fatima Zaidi', '0321-9876543', '35202-8765432-1', 'DHA Phase 5, Karachi', 'fatima@example.com'),
('Imran Ali', '0333-5557777', '37405-1122334-5', 'Faisal Town, Islamabad', 'imran@example.com');

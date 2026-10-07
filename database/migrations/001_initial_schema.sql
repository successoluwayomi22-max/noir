CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id CHAR(26) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending','active','suspended','deleted') NOT NULL DEFAULT 'pending',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL UNIQUE, description VARCHAR(255) NULL, is_system BOOLEAN NOT NULL DEFAULT FALSE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE permissions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, slug VARCHAR(160) NOT NULL UNIQUE, resource VARCHAR(100) NULL, action VARCHAR(100) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE user_roles (user_id BIGINT UNSIGNED NOT NULL, role_id BIGINT UNSIGNED NOT NULL, assigned_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id, role_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE role_permissions (role_id BIGINT UNSIGNED NOT NULL, permission_id BIGINT UNSIGNED NOT NULL, effect ENUM('allow','deny') NOT NULL DEFAULT 'allow', PRIMARY KEY(role_id, permission_id), FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE, FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE access_rules (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, route_pattern VARCHAR(255) NOT NULL, http_method VARCHAR(12) NULL, permission_slug VARCHAR(160) NOT NULL, effect ENUM('allow','deny') NOT NULL DEFAULT 'allow', priority INT NOT NULL DEFAULT 0, is_active BOOLEAN NOT NULL DEFAULT TRUE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(route_pattern), INDEX(permission_slug)) ENGINE=InnoDB;
CREATE TABLE activity_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, event VARCHAR(160) NOT NULL, subject_type VARCHAR(100) NULL, subject_id BIGINT UNSIGNED NULL, ip_address VARCHAR(45) NULL, metadata JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(event), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB;

CREATE TABLE products (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(26) NOT NULL UNIQUE, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, status ENUM('draft','pending','published','archived') NOT NULL DEFAULT 'draft', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE orders (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_number VARCHAR(40) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NULL, status VARCHAR(40) NOT NULL DEFAULT 'pending', total DECIMAL(14,2) NOT NULL DEFAULT 0, currency CHAR(3) NOT NULL DEFAULT 'NGN', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB;

INSERT INTO roles (name, slug, description, is_system) VALUES
('Super Admin','super-admin','Unrestricted system access',TRUE),('Administrator','administrator','Administrative access',TRUE),('Store Manager','store-manager','Store operations',FALSE),('Vendor / Seller','vendor','Vendor operations',FALSE),('Inventory Manager','inventory-manager','Inventory operations',FALSE),('Warehouse Officer','warehouse-officer','Warehouse operations',FALSE),('Finance Manager','finance-manager','Finance operations',FALSE),('Customer Support','customer-support','Customer support operations',FALSE),('Marketing Manager','marketing-manager','Marketing operations',FALSE),('Delivery Manager','delivery-manager','Delivery operations',FALSE),('Content Manager','content-manager','Content operations',FALSE),('Affiliate Partner','affiliate-partner','Affiliate operations',FALSE),('Customer','customer','Customer account',FALSE),('Guest','guest','Unauthenticated visitor',FALSE);
INSERT INTO permissions (name, slug, resource, action) VALUES ('Full system access','*','system','*'),('Manage users','users.manage','users','manage'),('Manage roles','roles.manage','roles','manage'),('Manage products','products.manage','products','manage'),('Manage orders','orders.manage','orders','manage'),('View dashboard','dashboard.view','dashboard','view');
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id,p.id FROM roles r JOIN permissions p ON r.slug='super-admin' AND p.slug='*';

-- Development-only seed. Replace the password before using any non-local environment.
INSERT INTO users (public_id,name,email,password_hash,status,email_verified_at) VALUES ('01J00000000000000000000000','System Administrator','admin@noir.test','$2y$10$C1pKcYkKBcrraRL050ZR6.wRRxh3U3KSUcp8n1p./2uyEwHYcCbNy','active',NOW());
INSERT INTO user_roles (user_id,role_id) SELECT u.id,r.id FROM users u JOIN roles r ON u.email='admin@noir.test' AND r.slug='super-admin';

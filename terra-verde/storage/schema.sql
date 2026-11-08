CREATE DATABASE IF NOT EXISTS terra_verde CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE terra_verde;

CREATE USER IF NOT EXISTS 'terra_user'@'localhost' IDENTIFIED BY 'terra_pass_change_me';
GRANT SELECT, INSERT, UPDATE, DELETE ON terra_verde.* TO 'terra_user'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  skin_concern ENUM('clarify','glow','calm','nourish') NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_ip_time (email, ip, attempted_at),
  INDEX idx_ip_time (ip, attempted_at)
);

CREATE TABLE newsletter_subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  unsubscribed_at TIMESTAMP NULL
);

CREATE TABLE farms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  location VARCHAR(100) NOT NULL
);

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  farm_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  concern ENUM('clarify','glow','calm','nourish') NOT NULL,
  concern_label VARCHAR(120) NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  card_color VARCHAR(30) NOT NULL DEFAULT 'var(--peach)',
  icon_svg TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE SET NULL
);

INSERT INTO farms (name, location) VALUES
 ('Sandy Ridge Farm','Sandy, Oregon'),
 ('Bend Clay Quarry','Bend, Oregon'),
 ('Studio Garden','Portland, Oregon');

INSERT INTO products (farm_id,name,concern,concern_label,price,card_color,icon_svg) VALUES
 (1,'Eucalyptus Clarifying Mist','clarify','For clarifying + oil control',28,'var(--eucalyptus)','<circle cx="26" cy="26" r="24" fill="#A9B387"/>'),
 (3,'Peach Nectar Glow Serum','glow','For dullness + uneven tone',42,'var(--peach)','<path d="M6 30 Q26 2 46 30 Q26 50 6 30Z" fill="#DCA278"/>'),
 (1,'Ivory Oat Cream','nourish','For dryness + barrier repair',34,'var(--ivory)','<rect x="8" y="8" width="36" height="36" rx="14" fill="#E8ECCC"/>'),
 (3,'Pistachio Renewal Mask','nourish','For dullness + dehydration',36,'var(--pistachio)','<polygon points="26,4 46,26 26,48 6,26" fill="#A9B387"/>'),
 (2,'Clay Detox Bar','clarify','For congestion + breakouts',22,'var(--clay)','<rect x="6" y="14" width="40" height="24" rx="8" fill="#8b5a34"/>'),
 (3,'Sage Calm Balm','calm','For sensitivity + redness',30,'var(--pistachio)','<ellipse cx="26" cy="26" rx="22" ry="16" fill="#CDD4B1"/>'),
 (3,'Rosehip Midnight Oil','nourish','For fine lines + overnight repair',38,'var(--peach)','<path d="M26 4C26 4 8 24 8 34A18 18 0 0 0 44 34C44 24 26 4 26 4Z" fill="#B87D51"/>'),
 (3,'Calendula Soothing Cleanser','calm','For gentle cleansing + redness',26,'var(--eucalyptus)','<circle cx="26" cy="26" r="8" fill="#B87D51"/><circle cx="26" cy="11" r="7" fill="#DCA278"/><circle cx="41" cy="26" r="7" fill="#DCA278"/><circle cx="26" cy="41" r="7" fill="#DCA278"/><circle cx="11" cy="26" r="7" fill="#DCA278"/>'),
 (3,'Chamomile Dew Toner','calm','For redness + all-day hydration',24,'var(--pistachio)','<circle cx="20" cy="26" r="16" fill="#A9B387"/><circle cx="32" cy="26" r="16" fill="#CDD4B1" opacity="0.85"/>'),
 (2,'Rose Clay Polish','clarify','For soft exfoliation + visible pores',29,'var(--clay)','<polygon points="26,4 45,15 45,37 26,48 7,37 7,15" fill="#B87D51"/>'),
 (3,'Elderflower Bright Eye Balm','glow','For puffiness + dark circles',36,'var(--ivory)','<path d="M4 26Q26 6 48 26Q26 46 4 26Z" fill="#E8ECCC"/><circle cx="26" cy="26" r="7" fill="#A9B387"/>'),
 (1,'Beeswax & Honey Lip Salve','nourish','For chapped lips + cold weather',14,'var(--peach)','<rect x="16" y="6" width="20" height="40" rx="10" fill="#DCA278"/>'),
 (3,'Turmeric Radiance Mask','glow','For dullness + post-breakout marks',40,'var(--ivory)','<circle cx="26" cy="26" r="22" fill="#DCA278"/><circle cx="26" cy="26" r="12" fill="#FFF9E2" opacity="0.85"/>'),
 (3,'Lavender Night Balm','calm','For restless skin + overnight calm',32,'var(--eucalyptus)','<rect x="22" y="4" width="8" height="44" rx="4" fill="#A9B387"/><circle cx="26" cy="12" r="6" fill="#B87D51"/><circle cx="26" cy="24" r="6" fill="#DCA278"/><circle cx="26" cy="36" r="6" fill="#B87D51"/>'),
 (1,'Oat & Honey Hand Cream','nourish','For dry hands + cuticles',18,'var(--pistachio)','<path d="M26 4C14 14 10 30 26 48C42 30 38 14 26 4Z" fill="#DCA278"/><path d="M26 14V40" stroke="#FFF9E2" stroke-width="2"/>');

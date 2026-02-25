CREATE TABLE IF NOT EXISTS plugins (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug VARCHAR(150) UNIQUE,
  name VARCHAR(255),
  version VARCHAR(20),
  active TINYINT DEFAULT 0,
  installed_at DATETIME
);

-- Example: Activate ContactForm plugin
INSERT OR IGNORE INTO plugins (slug, name, version, active, installed_at)
VALUES ('contact-form', 'Contact Form', '1.0.0', 1, CURRENT_TIMESTAMP);

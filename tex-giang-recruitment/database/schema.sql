-- ============================================================================
-- TEX-GIANG RECRUITMENT — CƠ SỞ DỮ LIỆU MySQL 8.0+
-- Charset utf8mb4 / InnoDB. Import file này trước, sau đó import seed.sql.
--   mysql -u <user> -p < tên_database < database/schema.sql
-- Lưu ý: file này DROP bảng cũ. Chỉ chạy lần đầu hoặc khi muốn tạo lại từ đầu.
-- ============================================================================

SET NAMES utf8mb4;

-- Trên cPanel: tạo database + user trong panel trước, KHÔNG cần chạy 3 dòng dưới.
-- CREATE DATABASE IF NOT EXISTS tex_giang_recruitment
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE tex_giang_recruitment;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS banners;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS post_categories;
DROP TABLE IF EXISTS application_history;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- 1. PHÂN QUYỀN & NGƯỜI DÙNG QUẢN TRỊ
-- ---------------------------------------------------------------------------
CREATE TABLE roles (
  id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(50)  NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id       TINYINT UNSIGNED NOT NULL,
  username      VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(150) NOT NULL,
  email         VARCHAR(190) NULL,
  phone         VARCHAR(30)  NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME     NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role (role_id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. DANH MỤC: KHU SẢN XUẤT / KHỐI PHÒNG BAN
-- ---------------------------------------------------------------------------
CREATE TABLE branches (
  id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug       VARCHAR(80)  NOT NULL,
  name       VARCHAR(120) NOT NULL,
  address    VARCHAR(255) NULL,
  phone      VARCHAR(30)  NULL,
  map_url    VARCHAR(500) NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_branches_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(80)  NOT NULL,
  name        VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  sort_order  SMALLINT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. TIN TUYỂN DỤNG
--    status : draft = bản nháp | published = đang đăng | paused = tạm dừng
--             closed = đã đóng (hết hạn / tuyển đủ)
--    salary_range dùng cho bộ lọc mức lương trên trang danh sách.
-- ---------------------------------------------------------------------------
CREATE TABLE jobs (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug             VARCHAR(220) NOT NULL,
  code             VARCHAR(20)  NULL,
  branch_id        SMALLINT UNSIGNED NOT NULL,
  category_id      SMALLINT UNSIGNED NULL,
  title            VARCHAR(220) NOT NULL,
  quantity         INT UNSIGNED NOT NULL DEFAULT 1,
  location         VARCHAR(255) NULL,
  employment_type  VARCHAR(100) NULL,
  experience       VARCHAR(150) NULL,
  salary_text      VARCHAR(150) NULL,
  salary_min       INT UNSIGNED NULL,
  salary_max       INT UNSIGNED NULL,
  salary_range     ENUM('under-8','8-12','12-18','over-18') NULL,
  description      MEDIUMTEXT NOT NULL,
  requirements     MEDIUMTEXT NULL,
  benefits         MEDIUMTEXT NULL,
  status           ENUM('draft','published','paused','closed') NOT NULL DEFAULT 'draft',
  is_urgent        TINYINT(1) NOT NULL DEFAULT 0,
  posted_at        DATETIME NULL,
  deadline         DATE NULL,
  meta_title       VARCHAR(220) NULL,
  meta_description VARCHAR(320) NULL,
  views            INT UNSIGNED NOT NULL DEFAULT 0,
  sort_order       INT NOT NULL DEFAULT 0,
  created_by       BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_jobs_slug (slug),
  UNIQUE KEY uq_jobs_code (code),
  KEY idx_jobs_status (status, posted_at),
  KEY idx_jobs_branch (branch_id),
  KEY idx_jobs_category (category_id),
  KEY idx_jobs_salary (salary_range),
  KEY idx_jobs_deadline (deadline),
  CONSTRAINT fk_jobs_branch FOREIGN KEY (branch_id) REFERENCES branches (id),
  CONSTRAINT fk_jobs_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_jobs_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. HỒ SƠ ỨNG TUYỂN
--    cv_* : chỉ lưu tên file sinh ngẫu nhiên trong storage/uploads/cv,
--    KHÔNG lưu file trong public. Tải CV đi qua controller có xác thực.
-- ---------------------------------------------------------------------------
CREATE TABLE applications (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id          BIGINT UNSIGNED NULL,
  full_name       VARCHAR(150) NOT NULL,
  phone           VARCHAR(30)  NOT NULL,
  email           VARCHAR(190) NULL,
  gender          ENUM('nu','nam','khac') NULL,
  birth_year      SMALLINT UNSIGNED NULL,
  address         VARCHAR(255) NULL,
  message         TEXT NULL,
  cv_original_name VARCHAR(255) NULL,
  cv_stored_name   VARCHAR(120) NULL,
  cv_mime          VARCHAR(100) NULL,
  cv_size          INT UNSIGNED NULL,
  status          ENUM('new','contacted','interview','hired','rejected') NOT NULL DEFAULT 'new',
  source          VARCHAR(60) NOT NULL DEFAULT 'Website',
  admin_note      TEXT NULL,
  appointment_at  DATETIME NULL,
  handled_by      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_applications_status (status, created_at),
  KEY idx_applications_job (job_id),
  KEY idx_applications_phone (phone),
  CONSTRAINT fk_applications_job FOREIGN KEY (job_id) REFERENCES jobs (id) ON DELETE SET NULL,
  CONSTRAINT fk_applications_user FOREIGN KEY (handled_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_history (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id BIGINT UNSIGNED NOT NULL,
  status         ENUM('new','contacted','interview','hired','rejected') NOT NULL,
  note           TEXT NULL,
  changed_by     BIGINT UNSIGNED NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_history_application (application_id, created_at),
  CONSTRAINT fk_history_application FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE,
  CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5. TIN TỨC / BÀI VIẾT
-- ---------------------------------------------------------------------------
CREATE TABLE post_categories (
  id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug       VARCHAR(80)  NOT NULL,
  name       VARCHAR(120) NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_post_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(220) NOT NULL,
  title         VARCHAR(220) NOT NULL,
  category_id   SMALLINT UNSIGNED NULL,
  excerpt       VARCHAR(400) NULL,
  body          MEDIUMTEXT NOT NULL,
  image_path    VARCHAR(255) NULL,
  image_alt     VARCHAR(255) NULL,
  image_caption VARCHAR(255) NULL,
  status        ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at  DATETIME NULL,
  views         INT UNSIGNED NOT NULL DEFAULT 0,
  created_by    BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_posts_slug (slug),
  KEY idx_posts_status (status, published_at),
  KEY idx_posts_category (category_id),
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES post_categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6. BANNER / THÔNG BÁO
--    hero   : slide ảnh đầu trang chủ
--    notice : dải thông báo trên cùng
--    popup  : thẻ nổi góc trái
--    ad     : dải CTA quảng cáo
-- ---------------------------------------------------------------------------
CREATE TABLE banners (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(120) NOT NULL,
  type            ENUM('hero','notice','popup','ad') NOT NULL DEFAULT 'hero',
  title           VARCHAR(220) NULL,
  subtitle        VARCHAR(255) NULL,
  description     VARCHAR(600) NULL,
  image_path      VARCHAR(255) NULL,
  image_path_small VARCHAR(255) NULL,
  image_alt       VARCHAR(255) NULL,
  focal_x         TINYINT UNSIGNED NOT NULL DEFAULT 50,
  focal_y         TINYINT UNSIGNED NOT NULL DEFAULT 50,
  link_url        VARCHAR(500) NULL,
  link_label      VARCHAR(120) NULL,
  cta_label       VARCHAR(120) NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  sort_order      INT NOT NULL DEFAULT 0,
  starts_at       DATETIME NULL,
  ends_at         DATETIME NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_banners_type (type, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 7. CẤU HÌNH & NỘI DUNG SOẠN THẢO
--    settings.group: general | contact | seo | content | social
--    Trang "Cài đặt" và "Nội dung" trong admin ghi vào bảng này.
-- ---------------------------------------------------------------------------
CREATE TABLE settings (
  `key`      VARCHAR(100) NOT NULL,
  `value`    MEDIUMTEXT NULL,
  `group`    VARCHAR(50) NOT NULL DEFAULT 'general',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`),
  KEY idx_settings_group (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 8. LIÊN HỆ TỪ WEBSITE
-- ---------------------------------------------------------------------------
CREATE TABLE contact_messages (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name  VARCHAR(150) NOT NULL,
  phone      VARCHAR(30)  NOT NULL,
  email      VARCHAR(190) NULL,
  subject    VARCHAR(220) NULL,
  message    TEXT NOT NULL,
  status     ENUM('new','read','replied') NOT NULL DEFAULT 'new',
  handled_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_contact_status (status, created_at),
  CONSTRAINT fk_contact_user FOREIGN KEY (handled_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9. NHẬT KÝ THAO TÁC QUẢN TRỊ
-- ---------------------------------------------------------------------------
CREATE TABLE audit_logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NULL,
  action      VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80)  NULL,
  entity_id   BIGINT UNSIGNED NULL,
  meta        JSON NULL,
  ip_address  VARCHAR(45) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id, created_at),
  KEY idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Chuẩn hoá tên nhà máy: "Cái Lậy" → "Cai Lậy" (bỏ dấu sắc ở chữ "Cái")
--
-- Bản an toàn: tự quét MỌI cột text đang có trong database rồi thay thế,
-- nên chạy được cả khi database thiếu bảng/cột (không báo "Unknown column"
-- hay "Table doesn't exist"). Idempotent: chạy lại nhiều lần vẫn an toàn.
--
-- CÁCH CHẠY:
--   - phpMyAdmin: chọn database → tab SQL → dán TOÀN BỘ file này → Go
--   - hoặc CLI: mysql -u USER -p DB_NAME < 2026-11-10-chuan-hoa-cai-lay.sql
-- ============================================================================

DROP PROCEDURE IF EXISTS `tmp_fix_cai_lay`;

DELIMITER $$
CREATE PROCEDURE `tmp_fix_cai_lay`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE t VARCHAR(64);
    DECLARE c VARCHAR(64);
    DECLARE cur CURSOR FOR
        SELECT TABLE_NAME, COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND DATA_TYPE IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext')
        ORDER BY TABLE_NAME, ORDINAL_POSITION;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO t, c;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SET @s = CONCAT(
            'UPDATE `', t, '` SET `', c, '` = REPLACE(`', c, '`, ''Cái Lậy'', ''Cai Lậy'') ',
            'WHERE `', c, '` LIKE ''%Cái Lậy%'''
        );
        PREPARE st FROM @s;
        EXECUTE st;
        DEALLOCATE PREPARE st;
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL `tmp_fix_cai_lay`();
DROP PROCEDURE IF EXISTS `tmp_fix_cai_lay`;

-- ============================================================================
-- (Tuỳ chọn) Bản thủ công nếu hosting không cho tạo PROCEDURE:
-- Chỉ chạy các dòng dưới đây sau khi đã CHẮC CHẮN database có đủ cột.
-- ============================================================================
-- UPDATE branches         SET name = REPLACE(name, 'Cái Lậy', 'Cai Lậy');
-- UPDATE branches         SET address = REPLACE(address, 'Cái Lậy', 'Cai Lậy');
-- UPDATE jobs             SET title = REPLACE(title, 'Cái Lậy', 'Cai Lậy');
-- UPDATE jobs             SET location = REPLACE(location, 'Cái Lậy', 'Cai Lậy');
-- UPDATE jobs             SET meta_description = REPLACE(meta_description, 'Cái Lậy', 'Cai Lậy');
-- UPDATE settings         SET `value` = REPLACE(`value`, 'Cái Lậy', 'Cai Lậy') WHERE `value` LIKE '%Cái Lậy%';
-- UPDATE about_milestones SET `text` = REPLACE(`text`, 'Cái Lậy', 'Cai Lậy');
-- UPDATE banners          SET name = REPLACE(name, 'Cái Lậy', 'Cai Lậy');
-- UPDATE banners          SET subtitle = REPLACE(subtitle, 'Cái Lậy', 'Cai Lậy');
-- UPDATE banners          SET image_alt = REPLACE(image_alt, 'Cái Lậy', 'Cai Lậy');
-- UPDATE benefit_groups   SET intro = REPLACE(intro, 'Cái Lậy', 'Cai Lậy');
-- UPDATE benefit_items    SET `text` = REPLACE(`text`, 'Cái Lậy', 'Cai Lậy');
-- UPDATE posts            SET title = REPLACE(title, 'Cái Lậy', 'Cai Lậy');
-- UPDATE posts            SET excerpt = REPLACE(excerpt, 'Cái Lậy', 'Cai Lậy');
-- UPDATE posts            SET body = REPLACE(body, 'Cái Lậy', 'Cai Lậy');

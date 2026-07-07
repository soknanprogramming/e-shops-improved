-- if database support if not exists, then use it to update database

-- ALTER TABLE product_comments
-- ADD COLUMN IF NOT EXISTS rating TINYINT(1) DEFAULT NULL AFTER comment;


ALTER TABLE product_comments
ADD COLUMN rating TINYINT(1) DEFAULT NULL AFTER comment;
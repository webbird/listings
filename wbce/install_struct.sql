-- Listings: settings, field set, entries and the links mailed to owners (one section = one directory)

CREATE TABLE IF NOT EXISTS `{TP}mod_listings_settings` (
    `section_id` INT NOT NULL DEFAULT 0,
    `name`       VARCHAR(64) NOT NULL DEFAULT '',
    `value`      TEXT NULL,
    PRIMARY KEY (`section_id`, `name`)
) {TABLE_ENGINE};

CREATE TABLE IF NOT EXISTS `{TP}mod_listings_fields` (
    `field_id`   INT NOT NULL AUTO_INCREMENT,
    `section_id` INT NOT NULL DEFAULT 0,
    `position`   INT NOT NULL DEFAULT 0,
    `name`       VARCHAR(40) NOT NULL DEFAULT '',
    `type`       VARCHAR(20) NOT NULL DEFAULT 'text',
    `label`      VARCHAR(255) NOT NULL DEFAULT '',
    `help`       VARCHAR(255) NOT NULL DEFAULT '',
    `required`   TINYINT NOT NULL DEFAULT 0,
    `visibility` VARCHAR(10) NOT NULL DEFAULT 'public',
    `in_list`    TINYINT NOT NULL DEFAULT 0,
    `editable`   TINYINT NOT NULL DEFAULT 1,
    `filterable` TINYINT NOT NULL DEFAULT 0,
    `options`    TEXT NULL,
    PRIMARY KEY (`field_id`),
    UNIQUE KEY `section_name` (`section_id`, `name`),
    KEY `section_position` (`section_id`, `position`)
) {TABLE_ENGINE};

CREATE TABLE IF NOT EXISTS `{TP}mod_listings_items` (
    `item_id`       INT NOT NULL AUTO_INCREMENT,
    `section_id`    INT NOT NULL DEFAULT 0,
    `page_id`       INT NOT NULL DEFAULT 0,
    `title`         VARCHAR(255) NOT NULL DEFAULT '',
    `slug`          VARCHAR(190) NOT NULL DEFAULT '',
    `status`        VARCHAR(20) NOT NULL DEFAULT 'draft',
    `owner_user_id` INT NOT NULL DEFAULT 0,
    `owner_email`   VARCHAR(255) NOT NULL DEFAULT '',
    `data`          TEXT NULL,
    `created_at`    INT NOT NULL DEFAULT 0,
    `updated_at`    INT NOT NULL DEFAULT 0,
    `published_at`  INT NOT NULL DEFAULT 0,
    `expires_at`    INT NOT NULL DEFAULT 0,
    `reminded_at`   INT NOT NULL DEFAULT 0,
    `modified_by`   INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`item_id`),
    UNIQUE KEY `section_slug` (`section_id`, `slug`),
    KEY `section_status` (`section_id`, `status`),
    KEY `owner_user` (`section_id`, `owner_user_id`)
) {TABLE_ENGINE};

-- confirmation and edit links; only the SHA-256 hash of a token is stored
CREATE TABLE IF NOT EXISTS `{TP}mod_listings_tokens` (
    `token_id`   INT NOT NULL AUTO_INCREMENT,
    `section_id` INT NOT NULL DEFAULT 0,
    `item_id`    INT NOT NULL DEFAULT 0,
    `purpose`    VARCHAR(20) NOT NULL DEFAULT '',
    `token_hash` CHAR(64) NOT NULL DEFAULT '',
    `email`      VARCHAR(255) NOT NULL DEFAULT '',
    `created_at` INT NOT NULL DEFAULT 0,
    `expires_at` INT NOT NULL DEFAULT 0,
    `used_at`    INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`token_id`),
    UNIQUE KEY `token_hash` (`token_hash`),
    KEY `section_email` (`section_id`, `email`),
    KEY `item_id` (`item_id`)
) {TABLE_ENGINE};

-- 0.4: a tree of categories and free tags per directory; an entry may have several of each
CREATE TABLE IF NOT EXISTS `{TP}mod_listings_categories` (
    `cat_id`      INT NOT NULL AUTO_INCREMENT,
    `section_id`  INT NOT NULL DEFAULT 0,
    `parent_id`   INT NOT NULL DEFAULT 0,
    `position`    INT NOT NULL DEFAULT 0,
    `name`        VARCHAR(255) NOT NULL DEFAULT '',
    `slug`        VARCHAR(190) NOT NULL DEFAULT '',
    `description` TEXT NULL,
    `visible`     TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (`cat_id`),
    UNIQUE KEY `section_slug` (`section_id`, `slug`),
    KEY `section_parent` (`section_id`, `parent_id`, `position`)
) {TABLE_ENGINE};

CREATE TABLE IF NOT EXISTS `{TP}mod_listings_item_categories` (
    `section_id` INT NOT NULL DEFAULT 0,
    `item_id`    INT NOT NULL DEFAULT 0,
    `cat_id`     INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`item_id`, `cat_id`),
    KEY `cat_id` (`cat_id`),
    KEY `section_id` (`section_id`)
) {TABLE_ENGINE};

CREATE TABLE IF NOT EXISTS `{TP}mod_listings_tags` (
    `tag_id`     INT NOT NULL AUTO_INCREMENT,
    `section_id` INT NOT NULL DEFAULT 0,
    `name`       VARCHAR(100) NOT NULL DEFAULT '',
    `slug`       VARCHAR(120) NOT NULL DEFAULT '',
    PRIMARY KEY (`tag_id`),
    UNIQUE KEY `section_slug` (`section_id`, `slug`)
) {TABLE_ENGINE};

CREATE TABLE IF NOT EXISTS `{TP}mod_listings_item_tags` (
    `section_id` INT NOT NULL DEFAULT 0,
    `item_id`    INT NOT NULL DEFAULT 0,
    `tag_id`     INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`item_id`, `tag_id`),
    KEY `tag_id` (`tag_id`),
    KEY `section_id` (`section_id`)
) {TABLE_ENGINE};

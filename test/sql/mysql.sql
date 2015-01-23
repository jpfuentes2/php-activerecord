# Set our `sql_mode` for strict testing
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION,STRICT_TRANS_TABLES';


CREATE TABLE `authors` (
  `author_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_author_id` int(11),
  `name` varchar(25) NOT NULL DEFAULT 'default_name',
  `updated_at` datetime,
  `created_at` datetime,
  some_Date date,
  `some_time` time,
  `some_text` text,
  `some_enum` enum('a','b','c'),
  `encrypted_password` varchar(50),
  `mixedCaseField` varchar(50),
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB;

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11),
  `secondary_author_id` int(11),
  `name` varchar(50),
  `numeric_test` varchar(10) DEFAULT '0',
  `special` numeric(10,2) DEFAULT 0,
  PRIMARY KEY (`book_id`)
);

CREATE TABLE `publishers` (
  `publisher_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL DEFAULT 'default_name',
  PRIMARY KEY (`publisher_id`)
) ENGINE=InnoDB;

CREATE TABLE `venues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50),
  `city` varchar(60),
  `state` char(2),
  `address` varchar(50),
  `phone` varchar(10) default NULL,
  PRIMARY KEY (`id`),
  UNIQUE(`name`,`address`)
);

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NULL,
  `host_id` int(11) NOT NULL,
  `title` varchar(60) NOT NULL,
  `description` varchar(50),
  PRIMARY KEY (`id`),
  `type` varchar(15) default NULL
);

CREATE TABLE `hosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25),
  PRIMARY KEY (`id`)
);

CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `nick_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `active` smallint(11) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE `rm-bldg`(
  `rm-id` int(11) NOT NULL,
  `rm-name` varchar(10) NOT NULL,
  `space out` varchar(1) NOT NULL
);

CREATE TABLE `awesome_people` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11),
  `is_awesome` int(11) default 1,
  PRIMARY KEY (`id`)
);

CREATE TABLE `amenities` (
  `amenity_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`amenity_id`)
);

CREATE TABLE `property` (
  `property_id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`property_id`)
);

CREATE TABLE `property_amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amenity_id` int(11) NOT NULL DEFAULT '0',
  `property_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
);

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `newsletters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `user_newsletters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `newsletter_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `valuestore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(20) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

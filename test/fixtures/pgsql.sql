-- CREATE USER test;
-- GRANT ALL PRIVILEGES ON test to test;

DROP TABLE IF EXISTS authors;
CREATE TABLE authors(
	author_id SERIAL PRIMARY KEY,
	parent_author_id INT,
	name VARCHAR(25) NOT NULL DEFAULT 'default_name',
	updated_at timestamp,
	created_at timestamp,
	some_date date
);

DROP TABLE IF EXISTS books;
CREATE TABLE books(
	book_id SERIAL PRIMARY KEY,
	author_id INT,
	secondary_author_id INT,
	name VARCHAR(50),
	numeric_test VARCHAR(10) DEFAULT '0',
	special NUMERIC(10,2) DEFAULT 0
);

DROP TABLE IF EXISTS venues;
CREATE TABLE venues (
	id SERIAL PRIMARY KEY,
	name varchar(50),
	city varchar(60),
	state char(2),
	address varchar(50),
	phone varchar(10) default NULL,
	UNIQUE(name,address)
);

DROP TABLE IF EXISTS events;
CREATE TABLE events (
	id SERIAL PRIMARY KEY,
	venue_id int NOT NULL,
	host_id int NOT NULL,
	title varchar(50) NOT NULL,
	description varchar(10),
	type varchar(15) default NULL
);

DROP TABLE IF EXISTS hosts;
CREATE TABLE hosts(
	id SERIAL PRIMARY KEY,
	name VARCHAR(10)
);

DROP TABLE IF EXISTS employees;
CREATE TABLE employees (
	id SERIAL PRIMARY KEY,
	first_name VARCHAR(255) NOT NULL,
	last_name VARCHAR(255) NOT NULL,
	nick_name VARCHAR(255) NOT NULL
);

DROP TABLE IF EXISTS positions;
CREATE TABLE positions (
	id SERIAL PRIMARY KEY,
	employee_id int NOT NULL,
	title VARCHAR(255) NOT NULL,
	active SMALLINT NOT NULL
);

DROP TABLE IF EXISTS "rm-bldg";
CREATE TABLE "rm-bldg"(
    "rm-id" SERIAL PRIMARY KEY,
    "rm-name" VARCHAR(10) NOT NULL,
    "space out" VARCHAR(1) NOT NULL
);

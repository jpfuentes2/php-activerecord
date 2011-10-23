CREATE TABLE authors(
	author_id int NOT NULL IDENTITY PRIMARY KEY,
	parent_author_id INT,
	name VARCHAR(25) NOT NULL DEFAULT 'default_name',
	updated_at datetime,
	created_at datetime,
	some_date datetime,
	some_time datetime,
	some_text text,
	encrypted_password varchar(50),
	mixedCaseField varchar(50)
);

CREATE TABLE books(
	book_id int NOT NULL IDENTITY PRIMARY KEY,
	Author_Id INT,
	secondary_author_id INT,
	name VARCHAR(50),
	numeric_test VARCHAR(10) DEFAULT '0',
	special DECIMAL(10,2) DEFAULT 0
);

CREATE TABLE venues (
	Id int NOT NULL IDENTITY PRIMARY KEY,
	name varchar(50),
	city varchar(60),
	state char(2),
	address varchar(50),
	phone varchar(10) default NULL
);
CREATE UNIQUE INDEX venues_name_address ON venues (name ASC, address ASC);

CREATE TABLE events (
	id int NOT NULL IDENTITY PRIMARY KEY,
	venue_id int NOT NULL,
	host_id int NOT NULL,
	title varchar(60) NOT NULL,
	description varchar(50),
	type varchar(15) default NULL
);

CREATE TABLE forms (
	id int NOT NULL IDENTITY PRIMARY KEY,
	data TEXT NOT NULL
);

CREATE TABLE hosts(
	id int NOT NULL IDENTITY PRIMARY KEY,
	name VARCHAR(25)
);

CREATE TABLE employees (
	id int NOT NULL IDENTITY PRIMARY KEY,
	first_name VARCHAR(255) NOT NULL,
	last_name VARCHAR(255) NOT NULL,
	nick_name VARCHAR(255) NOT NULL
);

CREATE TABLE positions (
	id int NOT NULL IDENTITY PRIMARY KEY,
	employee_id int NOT NULL,
	title VARCHAR(255) NOT NULL,
	active SMALLINT NOT NULL
);

CREATE TABLE [rm-bldg] (
    [rm-id] INT NOT NULL,
    [rm-name] VARCHAR(10) NOT NULL,
    [space out] VARCHAR(1) NOT NULL
);

CREATE TABLE awesome_people(
	id int NOT NULL IDENTITY PRIMARY KEY,
	author_id int,
	is_awesome int default 1
);

CREATE TABLE amenities(
  amenity_id int NOT NULL IDENTITY PRIMARY KEY,
  type varchar(40) NOT NULL DEFAULT ''
);

CREATE TABLE property(
  property_id int NOT NULL IDENTITY PRIMARY KEY
);

CREATE TABLE property_amenities(
  id int NOT NULL IDENTITY PRIMARY KEY,
  amenity_id int NOT NULL DEFAULT '0',
  property_id int NOT NULL DEFAULT '0'
);

CREATE TABLE users (
    id int NOT NULL IDENTITY PRIMARY KEY
);

CREATE TABLE newsletters (
    id int NOT NULL IDENTITY PRIMARY KEY
);

CREATE TABLE user_newsletters (
    id int NOT NULL IDENTITY PRIMARY KEY,
    user_id INT NOT NULL,
    newsletter_id INT NOT NULL
);

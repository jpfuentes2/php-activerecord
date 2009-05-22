INSERT INTO venues (id, name, city, state, address, phone) VALUES(1, 'Blender Theater at Gramercy', 'New York', 'NY', '127 East 23rd Street', '2127776800');
INSERT INTO venues (id, name, city, state, address, phone) VALUES(2, 'Warner Theatre', 'Washington', 'DC', '1299 Pennsylvania Ave NW', '2027834000');
INSERT INTO venues (id, name, city, state, address, phone) VALUES(6, 'The Note - West Chester', 'West Chester', 'PA', '142 E. Market St.', '0000000000');

INSERT INTO events (id, venue_id, host_id, title, description, type) VALUES(1, 1, 1,'Monday Night Music Club feat. The Shivers', '', 'Music');
INSERT INTO events (id, venue_id, host_id, title, description, type) VALUES(2, 2, 2, 'Yeah Yeah Yeahs', '', 'Music');
INSERT INTO events (id, venue_id, host_id, title, description, type) VALUES(3, 2, 3, 'Love Overboard', '', 'Music');
INSERT INTO events (id, venue_id, host_id, title, description, type) VALUES(5, 6, 4, '1320 Records Presents A ''Live PA Set'' By STS9 with', '', 'Music');
INSERT INTO events (id, venue_id, host_id, title, description, type) VALUES(6, 500, 4, 'Kla likes to dance to YMCA', '', 'Music');

INSERT INTO hosts (id, name) VALUES(1, 'David Letterman');
INSERT INTO hosts (id, name) VALUES(2, 'Billy Crystal');
INSERT INTO hosts (id, name) VALUES(3, 'Jon Stewart');
INSERT INTO hosts (id, name) VALUES(4, 'Funny Guy');

INSERT INTO employees (id, first_name, last_name, nick_name) VALUES(1, 'michio', 'kaku', 'kakz');
INSERT INTO employees (id, first_name, last_name, nick_name) VALUES(2, 'jacques', 'fuentes', 'jax');
INSERT INTO employees (id, first_name, last_name, nick_name) VALUES(3, 'kien', 'la', 'kla');

INSERT INTO positions (id, employee_id, title, active) VALUES(3, 1, 'physicist', 0);
INSERT INTO positions (id, employee_id, title, active) VALUES(2, 2, 'programmer', 1);
INSERT INTO positions (id, employee_id, title, active) VALUES(1, 3, 'programmer', 1);

INSERT INTO authors(author_id,parent_author_id,name,created_at,updated_at) VALUES(1,null,'Tito',null,null);
INSERT INTO authors(author_id,parent_author_id,name) VALUES(2,null,'George W. Bush');
INSERT INTO authors(author_id,parent_author_id,name) VALUES(3,null,'Bill Clinton');

INSERT INTO books(book_id,author_id,secondary_author_id,name,special) VALUES(1,1,2,'Ancient Art of Main Tanking',0);

-- this is for a test of manually setting the table name --
INSERT INTO `rm-bldg` VALUES(1,'name','x');


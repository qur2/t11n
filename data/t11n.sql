DROP TABLE IF EXISTS repo;
CREATE TABLE IF NOT EXISTS repo (
	name VARCHAR PRIMARY KEY,
	created DATETIME NOT NULL
);
INSERT INTO repo (name, created) VALUES ('cv', '2011-11-03');

DROP TABLE IF EXISTS dom_doc;
CREATE TABLE IF NOT EXISTS dom_doc (
	id INTEGER PRIMARY KEY,
	repo_name VARCHAR,
	name VARCHAR,
	created DATETIME NOT NULL,
	FOREIGN KEY(repo_name) REFERENCES repo(name)
);
INSERT INTO dom_doc (id, repo_name, name, created) VALUES (1, 'cv', 'cv.html', '2011-11-03');

DROP TABLE IF EXISTS mod_type;
CREATE TABLE IF NOT EXISTS mod_type (
	id INTEGER PRIMARY KEY,
	name VARCHAR
);
INSERT INTO mod_type (id, name) VALUES (1, 'text');

DROP TABLE IF EXISTS mod_set;
CREATE TABLE IF NOT EXISTS mod_set (
	id INTEGER PRIMARY KEY,
	dom_doc_id INTEGER,
	FOREIGN KEY(dom_doc_id) REFERENCES dom_doc(id)
);
INSERT INTO mod_set (id, dom_doc_id) VALUES (1, 1);

DROP TABLE IF EXISTS mod;
CREATE TABLE IF NOT EXISTS mod (
	id INTEGER PRIMARY KEY,
	mod_set_id INTEGER,
	xpath VARCHAR NOT NULL,
	value VARCHAR,
	mod_type_id INTEGER NOT NULL,
	FOREIGN KEY(mod_set_id) REFERENCES mod_set(id),
	FOREIGN KEY(mod_type_id) REFERENCES modt_ype(id)
);
INSERT INTO mod (id, mod_set_id, xpath, value, mod_type_id) VALUES (1, 1, '//div/p/text()[1]', 'rick', 1);
INSERT INTO mod (id, mod_set_id, xpath, value, mod_type_id) VALUES (2, 1, '//h2/text()[2]', 'roll', 1);

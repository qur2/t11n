DROP TABLE IF EXISTS dom_doc;
CREATE TABLE IF NOT EXISTS dom_doc (
	name VARCHAR PRIMARY KEY,
	created DATETIME NOT NULL
);
INSERT INTO dom_doc (name, created) VALUES ('cv.html', '2011-11-03');

DROP TABLE IF EXISTS mod_type;
CREATE TABLE IF NOT EXISTS mod_type (
	id INTEGER PRIMARY KEY,
	name VARCHAR
);
INSERT INTO mod_type (id, name) VALUES (1, 'text');

DROP TABLE IF EXISTS mod;
CREATE TABLE IF NOT EXISTS mod (
	id INTEGER PRIMARY KEY,
	dom_doc_name VARCHAR,
	xpath VARCHAR NOT NULL,
	value VARCHAR,
	mod_type_id INTEGER NOT NULL,
	FOREIGN KEY(dom_doc_name) REFERENCES domdoc(name),
	FOREIGN KEY(mod_type_id) REFERENCES modtype(id)
);
INSERT INTO mod (dom_doc_name, xpath, value, mod_type_id) VALUES ('cv.html', '//div/p/text()[1]', 'rick', 1);
INSERT INTO mod (dom_doc_name, xpath, value, mod_type_id) VALUES ('cv.html', '//h2/text()[2]', 'roll', 1);
CREATE TABLE IF NOT EXISTS d2draft_heroes (
  id int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  localized_name varchar(100) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS d2draft_matches (
  id int(11) NOT NULL auto_increment,
  read_key varchar(100) NOT NULL,
  edit_key varchar(100) NOT NULL,
  `type` varchar(32) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS d2draft_matchheroes (
  id int(11) NOT NULL auto_increment,
  match_id int(11) NOT NULL,
  hero_id int(11) NOT NULL,
  `status` varchar(32) NOT NULL,
  PRIMARY KEY (id)
);

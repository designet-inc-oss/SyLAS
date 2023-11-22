CREATE TABLE `hosts` (
  `host_id` bigint(20) unsigned NOT NULL auto_increment,
  `host_name` char(64) default NULL,
  PRIMARY KEY  (`host_id`),
  UNIQUE KEY `host_id` (`host_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

CREATE TABLE `loginfo` (
  `log_id` bigint(20) unsigned NOT NULL auto_increment,
  `log_name` char(64) default NULL,
  `facility_name` char(64) default NULL,
  `search_tab` char(64) default NULL,
  `log_type` char(64) default NULL,
  `app_name` char(64) default NULL,
  PRIMARY KEY  (`log_id`),
  UNIQUE KEY `log_id` (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

CREATE TABLE `loggroup` (
  `group_id` bigint(20) unsigned NOT NULL auto_increment,
  `group_name` char(64) default NULL,
  `log_id` bigint(20) unsigned default NULL,
  PRIMARY KEY  (`group_id`),
  UNIQUE KEY `group_id` (`group_id`),
  KEY `log_id` (`log_id`),
  CONSTRAINT `loggroup_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `loginfo` (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

CREATE TABLE `search_hosts` (
  `group_id` bigint(20) unsigned default NULL,
  `host_id` bigint(20) unsigned default NULL,
  KEY `group_id` (`group_id`),
  KEY `host_id` (`host_id`),
  CONSTRAINT `search_hosts_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `loggroup` (`group_id`),
  CONSTRAINT `search_hosts_ibfk_2` FOREIGN KEY (`host_id`) REFERENCES `hosts` (`host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

insert into hosts (host_id,host_name) values('1','ALL');

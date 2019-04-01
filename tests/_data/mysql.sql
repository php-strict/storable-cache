CREATE TABLE IF NOT EXISTS `cache` (
  `id` int(11) NOT NULL auto_increment,
  `key` varchar(255) NOT NULL default '',
  `value` text NOT NULL,
  `dtm` int(11) NOT NULL default '0',
  `created` int(11) NOT NULL default '0',
  `lifetime` int(11) NOT NULL default '0',
  `savetime` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
TRUNCATE TABLE `cache`; /* cleanup from previous tests */

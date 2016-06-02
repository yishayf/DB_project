-- Dumping structure for table DbMysql08.QuestionFormats
CREATE TABLE IF NOT EXISTS `QuestionFormats` (
  `table_name` varchar(20) NOT NULL,
  `question_format` varchar(100) NOT NULL,
  UNIQUE KEY `table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A table containing the questions formats and the names of their tables';


-- Dumping structure for table DbMysql08.Question_type1
CREATE TABLE IF NOT EXISTS `Question_type1` (
  `year` year(4) NOT NULL,
  `season` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='question in the form: "Where did the YEAR SEASON olympics take place?"';


-- Dumping structure for table DbMysql08.Question_type2
CREATE TABLE IF NOT EXISTS `Question_type2` (
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Questions in the form: "What is the olympic sport field of the athlete ATHLETE_NAME?"';

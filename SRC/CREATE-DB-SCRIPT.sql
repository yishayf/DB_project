CREATE TABLE `OlympicGame` (
 `game_id` int(11) NOT NULL AUTO_INCREMENT,
 `year` varchar(4) NOT NULL,
 `season` varchar(6) NOT NULL,
 `city` varchar(100) DEFAULT NULL,
 `comment` text,
 PRIMARY KEY (`game_id`),
 UNIQUE KEY `year` (`year`,`season`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1


CREATE TABLE `Athlete` (
 `athlete_id` int(11) NOT NULL AUTO_INCREMENT,
 `dbp_label` varchar(100) NOT NULL,
 `name` varchar(100) NOT NULL,
 `birth_date` date DEFAULT NULL,
 `comment` text,
 PRIMARY KEY (`athlete_id`),
 UNIQUE KEY `unique_label` (`dbp_label`),
 KEY `dbp_label` (`dbp_label`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `CompetitionType` (
 `competition_id` int(11) NOT NULL AUTO_INCREMENT,
 `competition_name` varchar(100) NOT NULL,
 PRIMARY KEY (`competition_id`),
 UNIQUE KEY `competition_name` (`competition_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `OlympicSportField` (
 `field_id` int(11) NOT NULL AUTO_INCREMENT,
 `field_name` varchar(40) NOT NULL,
 PRIMARY KEY (`field_id`),
 UNIQUE KEY `field_name` (`field_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;


CREATE TABLE `AthleteOlympicSportFields` (
 `athlete_id` int(11) NOT NULL,
 `field_id` int(11) NOT NULL,
 PRIMARY KEY (`athlete_id`,`field_id`),
 KEY `athlete_id` (`athlete_id`),
 KEY `field_id` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `AthleteGames` (
 `game_id` int(11) NOT NULL,
 `athlete_id` int(11) NOT NULL,
 `field_id` int(11) NOT NULL,
 PRIMARY KEY (`game_id`,`athlete_id`,`field_id`),
 KEY `game_id` (`game_id`),
 KEY `athlete_id` (`athlete_id`),
 KEY `field_id` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `AthleteMedals` (
 `athlete_id` int(11) NOT NULL,
 `game_id` int(11) NOT NULL,
 `competition_id` int(11) NOT NULL,
 `medal_color` varchar(6) NOT NULL,
 PRIMARY KEY (`athlete_id`,`game_id`,`competition_id`,`medal_color`),
 KEY `athlete_id` (`athlete_id`),
 KEY `game_id` (`game_id`),
 KEY `competition_id` (`competition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



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

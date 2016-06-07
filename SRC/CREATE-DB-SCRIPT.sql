-- DBPedia data tables

CREATE TABLE `OlympicGame` (
 `game_id` int(11) NOT NULL,
 `year` varchar(4) NOT NULL,
 `season` varchar(6) NOT NULL,
 `city` varchar(100) DEFAULT NULL,
 `comment` text,
 PRIMARY KEY (`game_id`),
 UNIQUE KEY `year` (`year`,`season`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `Athlete` (
 `athlete_id` int(11) NOT NULL,
 `dbp_label` varchar(100) NOT NULL,
 `birth_date` date DEFAULT NULL,
 `comment` text,
 `image_url` text,
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `AthleteOlympicSportFields` (
 `athlete_id` int(11) NOT NULL,
 `field_id` int(11) NOT NULL,
 PRIMARY KEY (`athlete_id`,`field_id`),
 KEY `athlete_id` (`athlete_id`),
 KEY `field_id` (`field_id`),
 CONSTRAINT `athleteidconst` FOREIGN KEY (`athlete_id`) REFERENCES `Athlete` (`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `fieldidconst` FOREIGN KEY (`field_id`) REFERENCES `OlympicSportField` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `AthleteGames` (
 `game_id` int(11) NOT NULL,
 `athlete_id` int(11) NOT NULL,
 `field_id` int(11) NOT NULL,
 PRIMARY KEY (`game_id`,`athlete_id`,`field_id`),
 KEY `game_id` (`game_id`),
 KEY `athlete_id` (`athlete_id`),
 KEY `field_id` (`field_id`),
 CONSTRAINT `ahtleteidconst1` FOREIGN KEY (`athlete_id`) REFERENCES `Athlete` (`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `fieldidconst1` FOREIGN KEY (`field_id`) REFERENCES `OlympicSportField` (`field_id`),
 CONSTRAINT `gameidconst` FOREIGN KEY (`game_id`) REFERENCES `OlympicGame` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `AthleteMedals` (
 `athlete_id` int(11) NOT NULL,
 `game_id` int(11) NOT NULL,
 `competition_id` int(11) NOT NULL,
 `medal_color` varchar(6) NOT NULL,
 PRIMARY KEY (`athlete_id`,`game_id`,`competition_id`,`medal_color`),
 KEY `athlete_id` (`athlete_id`),
 KEY `game_id` (`game_id`),
 KEY `competition_id` (`competition_id`),
 CONSTRAINT `athleteidconst2` FOREIGN KEY (`athlete_id`) REFERENCES `Athlete` (`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `compidconst` FOREIGN KEY (`competition_id`) REFERENCES `CompetitionType` (`competition_id`) ON DELETE CASCADE ON UPDATE CASCADE,
 CONSTRAINT `gameidconst1` FOREIGN KEY (`game_id`) REFERENCES `OlympicGame` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- Questions tables

CREATE TABLE `Question_type1` (
 `game_id` int(11) NOT NULL,
 `num_correct` int(11) NOT NULL DEFAULT '0',
 `num_wrong` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`game_id`),
 KEY `game_id` (`game_id`),
 CONSTRAINT `gameid_q1_const` FOREIGN KEY (`game_id`) REFERENCES `OlympicGame` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='question in the form: Where did the [YEAR] [SEASON] olympics take place?';


CREATE TABLE `Question_type2` (
 `athlete_id` int(11) NOT NULL,
 `num_correct` int(11) NOT NULL DEFAULT '0',
 `num_wrong` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`athlete_id`),
 CONSTRAINT `athleteid_q2_const` FOREIGN KEY (`athlete_id`) REFERENCES `Athlete` (`athlete_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='question in the form: How many Olympic games did [OLYMPIC ATHLETE] participate in?';


CREATE TABLE `Question_type3` (
 `field_id` int(11) NOT NULL,
 `num_correct` int(11) NOT NULL DEFAULT '0',
 `num_wrong` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`field_id`),
 CONSTRAINT `fieldid_q3_const` FOREIGN KEY (`field_id`) REFERENCES `OlympicSportField` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='question in the form: Which of the following was part of the [OLYMPIC SPORT FIELD] competitors at the Olympic games?';


CREATE TABLE `Question_type4` (
 `athlete_id` int(11) NOT NULL,
 `medal_color` varchar(6) NOT NULL,
 `num_correct` int(11) NOT NULL DEFAULT '0',
 `num_wrong` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`athlete_id`,`medal_color`),
 CONSTRAINT `athleteid_q4_const` FOREIGN KEY (`athlete_id`) REFERENCES `Athlete` (`athlete_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='question in the form: How many [MEDAL COLOR] medals did [OLYMPIC ATHLETE] win at the olympic games?';


CREATE TABLE `Question_type5` (
 `game_id` int(11) NOT NULL,
 `num_correct` int(11) NOT NULL DEFAULT '0',
 `num_wrong` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`game_id`),
 CONSTRAINT `gameid_q5_const` FOREIGN KEY (`game_id`) REFERENCES `OlympicGame` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='question in the form: Who won most medals at the [YEAR] [SEASON] olympic games?';


CREATE TABLE `Question_type6` (
 `athlete_id` int(11) NOT NULL,
 `num_correct` int(11) NOT NULL DEFAULT '0',
 `num_wrong` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`athlete_id`),
 KEY `athlete_id` (`athlete_id`),
 CONSTRAINT `gameid_q6_const` FOREIGN KEY (`athlete_id`) REFERENCES `Athlete` (`athlete_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='question in the form: In which of the following competition type did [OLYMPIC ATHLETE] win a medal?';

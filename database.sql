CREATE TABLE IF NOT EXISTS `mytable` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `FName` varchar(50) NOT NULL,
  `LName` varchar(50) NOT NULL,
  `Age` int(11) NOT NULL,
  `Gender` enum('male','female') NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=38 ;

--
-- Data to be inserted into the table
--

INSERT INTO `mytable` (`ID`, `FName`, `LName`, `Age`, `Gender`) VALUES
(1, 'Samantha', 'Green', 23, 'female'),
(2, 'John', 'Smith', 24, 'male'),
(3, 'John', 'Smith', 24, 'male');


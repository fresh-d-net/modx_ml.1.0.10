<?php
/**
 * Created by fedo
 * Date: 27.12.12
 * Time: 11:32
 * Класс для специфических фукций по работе с датой
 */
 
class Date {

	static function getTextDate($iUnixtime, $schema='%d %m %Y'){

			$iMonth = date("n", $iUnixtime); // номер месяца
			$iDay = date("j", $iUnixtime); // день
			$iYear = date("Y", $iUnixtime); // год

			$aMonth = array('', 'Январ', 'Феврал', 'Март', 'Апрел', 'Ма', 'Июн', 'Июл', 'Август', 'Сентябр', 'Октябр', 'Ноябр', 'Декабр');
			$sMonth = $aMonth[$iMonth];
			if ( ($iMonth == 3) OR ($iMonth == 8) ){$sEnd = "а";}
			else{$sEnd = "я";}

			$schema = str_replace('%m', $sMonth.$sEnd, $schema);

		    $s_date = strftime($schema, $iUnixtime);

			return $s_date;
	}
}

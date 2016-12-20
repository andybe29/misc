<?php
class jdays {

	public $year  = 0;
	public $month = 1;
	public $day   = 1;
	public $jday  = 0;

	public function __construct() {
	}

	public function __destruct() {
	}

	# Converts Unix timestamp to Julian Day
	# unixtojd
	public function date2jday($unixTimeStamp = 0) {
		if (!$unixTimeStamp) {
			$unixTimeStamp = time();
		}
		$data = getdate($unixTimeStamp);
		$this->year  = $data['year'];
		$this->month = $data['mon'];
		$this->day   = $data['mday'];
		return $this->gcal2jday($this->month, $this->day, $this->year);
	}

	# Converts a Gregorian Calendar date to Julian Day
	# GregorianToJD
	public function gcal2jday($month, $day, $year) {
		if (checkdate($month, $day, $year)) {
			$a = floor((14 - $month) / 12);
			$y = floor($year + 4800 - $a);
			$m = floor($month + 12 * $a - 3);
			return $this->jday = $day + floor((153 * $m + 2) / 5) + $y * 365 + floor($y / 4) - floor($y / 100) + floor($y / 400) - 32045;
		}

		return false;
	}

	# Converts Julian Day to Unix timestamp
	# jdtounix
	public function jday2date($jd) {
		$l = $jd + 68569;
		$n = $this->_aint((4 * $l) / 146097);
		$l = $l - $this->_aint((146097 * $n + 3) / 4);
		$i = $this->_aint((4000 * ($l + 1)) / 1461001);
		$l = $l - $this->_aint((1461 * $i) / 4) + 31;
		$j = $this->_aint((80 * $l) / 2447);
		$this->day = $l - $this->_aint((2447 * $j) / 80);
		$l = $this->_aint($j / 11);
		$this->month = $j + 2 - (12 * $l);
		$this->year = 100 * ($n - 49) + $i + $l;
		return mktime(0, 0, 0, $this->month, $this->day, $this->year);
	}

	private function _aint($value) {
		return ($value > 0) ? floor($value) : ceil($value);
	}
}
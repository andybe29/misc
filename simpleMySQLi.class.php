<?php
/**
 * @author andy.bezbozhny <andy.bezbozhny@gmail.com>
 */
class simpleMySQLi {

	/**
	 * @var string  $str строка запроса
	 * @var boolean $log логировать запросы?
	 */
	public $str = '';
	public $log = false;

	/**
	 * @var resource $conn_id соединение с БД
	 * @var string   $err     сообщение об ошибке
	 * @var int      $id      последнее добавленное значение auto_increment
	 * @var string   $path    каталог для логов
	 * @var int      $rows    кол-во строк при запросах select/update/delete
	 * @var resource $res     результат выполнения запроса
	 */
	private $conn_id = null;
	private $err     = '';
	private $id      = 0;
	private $path    = '';
	private $rows    = 0;
	private $res     = null;

	public function __construct($db, $root = false) {
		if ($this->conn_id = mysqli_connect($db['host'], $db['username'], $db['passwd'], $db['dbname'])) {
			mysqli_set_charset($this->conn_id, 'utf8');
			$this->path = realpath($root ? $root : $_SERVER['DOCUMENT_ROOT']) . '/logs';
			return true;
		}
		return false;
	}

	public function __destruct() {
		mysqli_close($this->conn_id);
	}

	public function __get($key) {
		return in_array($key, ['err', 'id', 'path', 'rows']) ? $this->$key : null;
	}

	public function __debugInfo() {
		return [
			'str'  => $this->str,
			'err'  => $this->err,
			'id'   => $this->id,
			'rows' => $this->rows
		];
	}

	/**
	 * выборка всех строк после select
	 * @param boolean $num флаг гладкого или ассоциированного массива
	 * @return array массив данных
	 */
	public function all($num = false) {
		if (function_exists('mysqli_fetch_all')) {
			$u = mysqli_fetch_all($this->res, $num ? MYSQLI_NUM : MYSQLI_ASSOC);
			$u = array_map(['self', 'strip'], $u);
		} else {
			$u = [];
			while ($r = $num ? $this->fetch() : $this->assoc()) {
				$u[] = $r;
			}
		}

		return $u;
	}

	/**
	 * результат запроса - в ассоциированный массив
	 * @return array массив данных
	 */
	public function assoc() {
		$r = mysqli_fetch_assoc($this->res);
		return self::strip($r);
	}

	/**
	 * экранирование строки
	 * @param string $str эранируемая строка
	 * @return string экранированная строка
	 */
	public function escape($str) {
		return mysqli_real_escape_string($this->conn_id, $str);
	}

	/**
	 * выполнение запроса
	 * @param boolean $log логировать данный запрос?
	 * @return boolean результат выполнения запроса
	 */
	public function execute($log = false) {
		$this->err  = null;
		$this->rows = $this->id = 0;

		$this->str = trim(is_array($this->str) ? implode(' ', $this->str) : $this->str);
		$this->res = mysqli_query($this->conn_id, $this->str);

		if ($err_no = mysqli_errno($this->conn_id)) {

			$this->err = $err_no . ': ' . mysqli_error($this->conn_id);

		} else if (($pos = mb_stripos($this->str, 'delete')) === 0) {

			$this->rows = $this->get_rows();

		} else if (($pos = mb_stripos($this->str, 'insert')) === 0) {

			$this->id   = $this->last();
			$this->rows = $this->get_rows();

		} else if (($pos = mb_stripos($this->str, 'replace')) === 0) {

			$this->rows = $this->get_rows();

		} else if (($pos = mb_stripos($this->str, 'select')) === 0) {

			$this->rows = mysqli_num_rows($this->res);

		} else if (($pos = mb_stripos($this->str, 'update')) === 0) {

			$this->id   = $this->last();
			$this->rows = $this->get_rows();

		}

		if ($this->log or $log or $this->err) {
			$l   = [PHP_EOL];
			$l[] = date('H:i:s');
			$l[] = $this->str;
			$l[] = $this->err;
			error_log(implode(PHP_EOL, $l), 3, $x = $this->path . '/mysql.' . date('Y.m.d') . '.log');
			chmod($x, 0644);
		}

		return $this->err ? false : true;
	}

	/**
	 * результат запроса в гладкий массив
	 * @return array массив данных
	 */
	public function fetch() {
		$r = mysqli_fetch_array($this->res, MYSQLI_NUM);
		return self::strip($r);
	}

	/**
	 * высвобождение результата запроса
	 */
	public function free() {
		if (is_object($this->res) or is_array($this->res)) {
			mysqli_free_result($this->res);
		}
	}

	/**
	 * кол-во изменённных строк (после delete/insert/replace/update)
	 * @return int кол-во строк
	 */
	public function get_rows() {
		return mysqli_affected_rows($this->conn_id);
	}

	/**
	 * добавление в таблицу
	 * @param string $table название таблицы
	 * @param array  $data  массив данных, где ключ - названия поля
	 * @return boolean результат выполнения операции
	 */
	public function insert($table = '', $data = []) {
		if (!$data or !$table) return false;

		$this->str   = [];
		$this->str[] = 'insert into ' . $table;
		$this->str[] = '(' . implode(', ', array_keys($data)) . ')';
		$this->str[] = 'values (' . implode(', ', $data) . ')';
		return $this->execute() ? ($this->id ? $this->id : true) : false;
	}

	/**
	 * последние значение auto_increment
	 * @return int значение insert_id
	 */
	public function last() {
		return mysqli_insert_id($this->conn_id);
	}

	/**
	 * строковое представление даты/времени
	 * @param boolean $full флаг полноты
	 * @return string результат
	 */
	public function now($full = true) {
		return $full ? date('Y-m-d H:i:s') : date('Y-m-d');
	}

	/**
	 * обновление строки
	 * @param string $table название таблицы
	 * @param array  $data  массив данных, где ключ - названия поля
	 * @param array  $where массив условий
	 * @param string $sep   оператор условия
	 * @return int|boolean кол-во изменённых строк либо false в случае ошибки
	 */
    public function update($table = '', $data = [], $where = [], $sep = 'and') {
        if (!$data or !$table) return false;

       	$this->str  = 'update ' . $table . ' set ' . implode(', ', self::_assoc2plain($data));
       	$this->str .= ($where) ? ' where ' . implode(' ' . $sep . ' ', $where) : '';

       	return $this->execute() ? $this->rows : false;
    }

	/**
	 * заключение строки в двойные кавычки
	 * @param string $str исходная строка
	 * @return string результат
	 */
	public function varchar($str = '') {
		return '"' . $this->escape($str) . '"';
	}

	/**
	 * очистка массива/объекта от слешей
	 * @param mixed $obj исходный массив/объект
	 * @return mixed результат
	 */
	static function strip($obj) {
		if (is_object($obj) or is_array($obj)) {
			foreach ($obj as $key => $val) {
				$obj[$key] = is_string($val) ? stripcslashes($val) : $val;
			}
		} else if (is_string($obj)) {
			$obj = stripcslashes($obj);
		}
		return $obj;
	}

	/**
	 * преобразование ассоциированного массива в гладкий
	 */
	static function _assoc2plain($u = []) {
		$func = function($key, $val) { return $key . '=' . $val; };
		return array_map($func, array_keys($u), $u);
	}

	static function _and($u = []) {
		return implode(' and ', $u);
	}

	static function _or($u = []) {
		return implode(' or ', $u);
	}
}
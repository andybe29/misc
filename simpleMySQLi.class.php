<?php
/**
 * @author andy.bezbozhny <andy.bezbozhny@gmail.com>
 */
class simpleMySQLi
{
    /**
     * @var string  $str строка запроса
     * @var boolean $log логировать запросы?
     */
    public $str = '';
    public $log = false;

    /**
     * @var resource $conn_id соединение с БД
     * @var mixed    $db      конфиг подключения к БД
     * @var string   $err     сообщение об ошибке
     * @var int      $id      последнее добавленное значение auto_increment
     * @var string   $path    каталог для логов
     * @var int      $rows    кол-во строк при запросах select/update/delete
     * @var resource $res     результат выполнения запроса
     */
    private $conn_id = null;
    private $db      = null;
    private $err     = null;
    private $id      = 0;
    private $path    = '';
    private $rows    = 0;
    private $res     = null;

    public function __construct($db, $logs = null)
    {
        $this->db   = $db;
        $this->path = $logs;

        return $this->_connect();
    }

    public function __destruct()
    {
        if ($this->conn_id) {
            @mysqli_close($this->conn_id);
        }
    }

    public function __get($key)
    {
        return in_array($key, ['err', 'id', 'path', 'rows']) ? $this->$key : null;
    }

    public function __set($key, $value)
    {
        if ($key == 'err' and empty($value)) $this->err = null;
    }

    public function __debugInfo()
    {
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
    public function all($num = false)
    {
        $data = mysqli_fetch_all($this->res, $num ? MYSQLI_NUM : MYSQLI_ASSOC);
        $data = array_map(['self', '_strip'], $data);

        return $data;
    }

    /**
     * результат запроса - в ассоциированный массив
     * @return array массив данных
     */
    public function assoc()
    {
        $data = mysqli_fetch_assoc($this->res);
        return self::_strip($data);
    }

    /**
     * экранирование строки
     * @param string $str эранируемая строка
     * @return string экранированная строка
     */
    public function escape($str)
    {
        return mysqli_real_escape_string($this->conn_id, $str);
    }

    /**
     * выполнение запроса
     * @param boolean $log логировать данный запрос?
     * @return boolean результат выполнения запроса
     */
    public function execute($log = false)
    {
        $this->err  = null;
        $this->rows = $this->id = 0;

        $this->str = is_array($this->str) ? implode(' ', $this->str) : $this->str;
        $this->str = trim($this->str);

        $this->res = mysqli_query($this->conn_id, $this->str);

        if ($err_no = mysqli_errno($this->conn_id)) {

            $this->err = $err_no . ': ' . mysqli_error($this->conn_id);

        } else if (($pos = mb_stripos($this->str, 'DELETE')) === 0) {

            $this->rows = $this->rows();

        } else if (($pos = mb_stripos($this->str, 'INSERT')) === 0) {

            $this->id   = $this->last();
            $this->rows = $this->rows();

        } else if (($pos = mb_stripos($this->str, 'REPLACE')) === 0) {

            $this->rows = $this->rows();

        } else if (($pos = mb_stripos($this->str, 'SELECT')) === 0) {

            $this->rows = mysqli_num_rows($this->res);

        } else if (($pos = mb_stripos($this->str, 'UPDATE')) === 0) {

            $this->id   = $this->last();
            $this->rows = $this->rows();

        }

        if ($this->log or $log or $this->err) {
            $this->_write2log();
        }

        return empty($this->err);
    }

    /**
     * результат запроса в гладкий массив
     * @return array массив данных
     */
    public function fetch()
    {
        $data = mysqli_fetch_array($this->res, MYSQLI_NUM);
        return self::_strip($data);
    }

    /**
     * высвобождение результата запроса
     */
    public function free()
    {
        if (is_object($this->res) or is_array($this->res)) {
            mysqli_free_result($this->res);
        }
    }

    /**
     * кол-во изменённных строк (после DELETE/INSERT/REPLACE/UPDATE)
     * @return int кол-во строк
     */
    public function rows()
    {
        return mysqli_affected_rows($this->conn_id);
    }

    /**
     * добавление в таблицу
     * @param string $table название таблицы
     * @param array  $data  массив данных, где ключ - названия поля
     * @return boolean результат выполнения операции
     */
    public function insert($table = '', $data = [])
    {
        if (!$data or !$table) return false;

        $this->str   = [];
        $this->str[] = 'INSERT INTO ' . $table;
        $this->str[] = '(' . implode(', ', array_keys($data)) . ')';
        $this->str[] = 'VALUES (' . implode(', ', $data) . ')';

        return $this->execute() ? ($this->id ? $this->id : true) : false;
    }

    /**
     * последние значение auto_increment
     * @return int значение insert_id
     */
    public function last()
    {
        return mysqli_insert_id($this->conn_id);
    }

    /**
     * проверка соединения
     */
    public function ping()
    {
        return ($this->conn_id and mysqli_ping($this->conn_id)) ? true : $this->_connect();
    }

    /**
     * добавление с заменой в таблицу
     * @param string $table название таблицы
     * @param array  $data  массив данных, где ключ - названия поля
     * @return boolean результат выполнения операции
     */
    public function replace($table = '', $data = [])
    {
        if (!$data or !$table) return false;

        $this->str   = [];
        $this->str[] = 'REPLACE INTO ' . $table;
        $this->str[] = '(' . implode(', ', array_keys($data)) . ')';
        $this->str[] = 'VALUES (' . implode(', ', $data) . ')';

        return $this->execute() ? $this->rows : false;
    }

    /**
     * возврат первого элемента массива результата
     * @return mixed результат
     */
    public function single()
    {
        $data = $this->fetch();

        return array_shift($data);
    }

    /**
     * обновление строки
     * @param string $table название таблицы
     * @param array  $data  массив данных, где ключ - названия поля
     * @param array  $where массив условий
     * @param string $sep   оператор условия
     * @return int|boolean кол-во изменённых строк либо false в случае ошибки
     */
    public function update($table = '', $data = [], $where = [], $sep = 'AND')
    {
        if (!$data or !$table) return false;

        $this->str  = 'UPDATE ' . $table . ' SET ' . implode(', ', self::_assoc2plain($data));
        $this->str .= ($where) ? (' WHERE ' . implode(' ' . $sep . ' ', $where)) : '';

        return $this->execute() ? $this->rows : false;
    }

    /**
     * заключение строки в двойные кавычки
     * @param string $str исходная строка
     * @return string результат
     */
    public function varchar($str = '')
    {
        return '"' . $this->escape($str) . '"';
    }

    /**
     * подключение к БД
     */
    private function _connect()
    {
        $this->str = null;

        if ($this->conn_id = mysqli_connect($this->db['host'], $this->db['username'], $this->db['passwd'], $this->db['dbname'])) {
            mysqli_set_charset($this->conn_id, 'utf8');
            return true;
        } else {
            $this->err = mysqli_connect_errno . ': ' . mysqli_connect_error();
        }

        $this->_write2log();
        return false;
    }

    /**
     * запись в лог
     */
    private function _write2log()
    {
        if ($this->path and file_exists($this->path)) {
            $log   = [PHP_EOL];
            $log[] = date('H:i:s');
            $log[] = $this->str;
            $log[] = $this->err;
            error_log(implode(PHP_EOL, $log), 3, $logfile = $this->path . '/mysql.' . date('Y.m.d') . '.log');
            chmod($logfile, 0644);
        }
    }

    /**
     * преобразование ассоциированного массива в гладкий
     */
    static function _assoc2plain($u = [])
    {
        return array_map(function($key, $val) { return $key . ' = ' . $val; }, array_keys($u), $u);
    }

    static function _and($u = [])
    {
        return implode(' AND ', $u);
    }

    static function _int($r = [])
    {
        $int = ['id'];
        foreach ($r as $key => $val) {
            $r[$key] = in_array($key, $int) ? (int)$val : $val;
        }
        return $r;
    }

    /**
     * строковое представление даты/времени
     * @param boolean $full флаг полноты
     * @return string результат
     */
    static function _now($full = true)
    {
        return $full ? date('Y-m-d H:i:s') : date('Y-m-d');
    }

    static function _or($u = [])
    {
        return implode(' OR ', $u);
    }

    /**
     * очистка массива/объекта от слешей
     * @param mixed $obj исходный массив/объект
     * @return mixed результат
     */
    static function _strip($data)
    {
        if (is_array($data)) {
            return array_map(function($val) { return is_string($val) ? stripcslashes($val) : $val; }, $data);
        } else if (is_object($val)) {
            return (object)array_map(function($val) { return is_string($val) ? stripcslashes($val) : $val; }, (array)$data);
        } else if (is_string($data)) {
            return stripcslashes($data);
        } else {
            return $data;
        }
    }
}

<?php
	class Device {
		const IMEI_REG_EXP     = '/^[0-9]{15}$/';
		const IMEI_MD5_REG_EXP = '/^[a-z0-9]{32}$/';
		const PAGER            = 50;

		/**
		 * @var simpleMySQLi $sql объект simpleMySQLi
		 */
		private $sql;

		/**
		 * @var int    $id     идентификатор устройства
		 * @var string $imei   IMEI устройства
		 * @var string $name   название устройства
		 * @var int    $status флаг включённости устройства
		 */
		private $id, $imei, $name, $status;

		public function __construct($sql) {
			$this->sql = $sql;
		}

		public function __destruct() {
		}

		public function __get($key) {
			return ($key == 'sql') ? null : $this->$key;
		}

		public function __set($key, $val) {
			if ($key == 'name') {
				$this->$key = $val;
			}
		}

		public function __debugInfo() {
			return [
				'id'      => $this->id,
				'imei'    => $this->imei,
				'name'    => $this->name,
				'status'  => $this->status
			];
		}

		/**
		 * получение данных (поиск) устройства
		 * @param string     $key   поле для поиска
		 * @param int|string $value поисковое значение
		 * @return array|boolean массив данных, если устройство найдено; либо false
		 */
		public function data($key = 'id', $val = 0) {
			if ($key == 'id' and is_numeric($val)) {
				$w = 'id=' . (int)$val;
			} else if ($key == 'imei' and preg_match(self::IMEI_REG_EXP, $val)) {
				$w = 'imei=' . $this->sql->varchar($val);
			} else if ($key == 'md5' and preg_match(self::IMEI_MD5_REG_EXP, $val)) {
				$w = 'md5(imei)=' . $this->sql->varchar($val);
			} else if ($key == 'name') {
				$w = 'name=' . $this->sql->varchar($val);
			} else {
				return false;
			}

			$this->sql->str = 'select * from devices where ' . $w;
			$r = ($this->sql->execute() and $this->sql->rows) ? $this->sql->assoc() : false;
			$this->sql->free();

			if ($r) {
				$r = $this->_int($r);
				$this->id     = $r['id'];
				$this->imei   = $r['imei'];
				$this->name   = $r['name'];
				$this->status = $r['status'];
			}

			return $r;
		}

		/**
		 * удаление устройства
		 * @return boolean флаг успешности выполнения метода
		 */
		public function delete() {
			if (!$this->id) return false;

			$this->sql->str = 'delete from devices where id=' . $this->id;
			return $this->sql->execute();
		}

		/**
		 * добавление устройства
		 * @param array $data массив данных устройства
		 * @return boolean флаг успешности выполнения метода
		 */
		public function insert($data = []) {
			$this->imei   = isset($data['imei'])   ? $data['imei']   : null;
			$this->name   = isset($data['name'])   ? $data['name']   : null;
			$this->status = isset($data['status']) ? $data['status'] : 0;

			foreach (['imei', 'name'] as $key) {
				$data[$key] = (isset($data[$key]) and $data[$key]) ? $this->sql->varchar($data[$key]) : 'null';
			}

			$data['created'] = $this->sql->varchar($this->sql->now(false));

			$this->id = $this->sql->insert('devices', $data) ? $this->sql->id : null;

			return !is_null($this->id);
		}

		/**
		 * список устройств
		 * @param array $params параметры вывода
		 * @return array|boolean массив устройств либо false
		 */
		public function lista($params = null) {
			if (!is_array($params)) return false;

			$params['page']   = isset($params['page'])   ? $params['page']   : 1;
			$params['sort']   = isset($params['sort'])   ? $params['sort']   : 'id';
			$params['order']  = isset($params['order'])  ? $params['order']  : 'asc';
			$params['status'] = isset($params['status']) ? $params['status'] : false;
			$params['where']  = [];

			if ($params['status'] !== false) {
				$params['where'][] = 'status=' . $params['status'];
			}

			$this->sql->str   = [];
			$this->sql->str[] = 'select * from devices where ' . $this->sql->_and($params['where']);
			$this->sql->str[] = 'order by ' . $params['sort'] . ' ' . $params['order'];
			$this->sql->str[] = 'limit ' . (self::PAGER * ($params['page'] - 1)) . ', ' . self::PAGER;

			$u = $this->sql->execute() ? $this->sql->all() : false;
			$this->sql->free();

			return $u ? array_map(['self', '_int'], $u) : $u;
		}

		/**
		 * пагинатор списка устройств
		 * @param array $params параметры вывода
		 * @return int|boolean кол-во страниц либо false
		 */
		public function pager($params = null) {
			if (!is_array($params)) return false;

			$params['status'] = isset($params['status']) ? $params['status'] : false;

			$params['where']   = [];
			if ($params['status'] !== false) {
				$params['where'][] = 'status=' . $params['status'];
			}

			$this->sql->str   = [];
			$this->sql->str[] = 'select ceil(count(*)/' . CRM::PAGER . ') as pages from devices';
			if ($params['where']) $this->sql->str[] = 'where ' . $this->sql->_and($params['where']);

			$r = ($this->sql->execute() and $this->sql->rows) ? $this->sql->assoc() : false;
			$this->sql->free();

			return $r ? (int)$r['pages'] : false;
		}

		/**
		 * обновление данных устройства
		 * @param array $data массив обновляемых данных
		 * @return boolean флаг успешности выполнения метода
		 */
		public function update($data = []) {
			if (!$this->id) return false;

			$this->imei   = isset($data['imei'])   ? $data['imei']   : null;
			$this->name   = isset($data['name'])   ? $data['name']   : null;
			$this->status = isset($data['status']) ? $data['status'] : 0;

			foreach (['imei', 'name'] as $key) {
				$data[$key] = (isset($data[$key]) and $data[$key]) ? $this->sql->varchar($data[$key]) : 'null';
			}

			return ($this->sql->update('devices', $data, ['id=' . $this->id]) !== false);
		}

		/**
		 * проверка валидности IMEI
		 * @param string $imei значение IMEI
		 * @return boolean флаг валидности
		 */
		public static function isIMEI($imei = '') {
			if (!preg_match(self::IMEI_REG_EXP, $imei)) return false;

			$num = strUtils::str2arr($imei);
			$sum = 0;
			$crc = array_pop($num);

			for ($i = 0; $i < 14; $i ++) {
				$sum += (($i % 2) ? array_sum(strUtils::str2arr(strval(2 * $num[$i]))) : $num[$i]);
			}

			$sum %= 10;

			return ($sum + $crc == 10 or ($sum == 0 and $crc == 0));
		}

		private function _int($r = []) {
			foreach ($r as $key => $val) {
				if (in_array($key, ['imei', 'name'])) continue;
				$r[$key] = ($key == 'created') ? strtotime($val) : (int)$val;
			}

			return $r;
		}
	}
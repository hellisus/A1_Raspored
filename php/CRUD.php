<?php



class CRUD extends PDO
{

	//Ubacivanje godine u klasu


	//Tabela koja se koristi
	public $table = null;
	public $databaza;


	public function __construct($databaza, $db_type = 'mysql', $db_name = 'a1_raspored', $db_host = 'localhost:3306', $db_user = 'root', $db_pword = '', $db_charset = 'UTF8')
	{

		$this->databaza = $databaza;

		try {
			$dsn = "$db_type:dbname=$databaza;host=$db_host;charset=$db_charset";
			parent::__construct($dsn, $db_user, $db_pword);
			$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			die('Greška u konekciji sa bazom podataka : ' . $e->getMessage());
		}
	}
	//---------------------------------------------------------------------------------------------------------------------

	/**
	 * Selektuje podatke iz baze
	 * @param $columns Mixed Str or Array
	 * @param $where  Mixed Int or Array
	 * @param string $custom replace the query with the custom one
	 * @return array
	 */

	public function select($columns, $where = null, $custom = '')
	{
		$this->_isTableSet();

		if (is_array($columns)) {
			$columns = implode(',', $columns);
		}

		//izgradnja WHERE metodom where
		$where_stmt = $this->_whereBuilder($where);

		$sth = $this->prepare("SELECT $columns FROM `{$this->table}`  $where_stmt");

		if ($custom != '') {
			$sth = $this->prepare($custom);
		}

		$sth->execute($where);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	//---------------------------------------------------------------------------------------------------------------------

	/**
	 * Ubacuje podatke u bazu
	 * @param  array $data Mora biti asoc Array [kolona => vrednost]
	 * @return bool/string vraća zadnji primarni ključ ili false
	 */
	public function insert($data)
	{
		$this->_isTableSet();

		$keys_array = array_keys($data);
		$keys       = "`" . implode('`, `', $keys_array) . "`";
		$params     = ":" . implode(', :', $keys_array);

		$sth = $this->prepare("INSERT INTO `{$this->table}` ($keys) VALUES($params)");
		$result =  $sth->execute($data);

		if ($result == 1) {
			return $this->lastInsertId();
		}
		return false;
	}

	//---------------------------------------------------------------------------------------------------------------------

	/**
	 * Menja podatke u bazi
	 * @param array $data Mora biti asoc Array [kolona => vrednost]
	 * @param mixed $where je arej ili numerički primarni ključ
	 * @param bool $log menja samo poslednji id
	 */
	public function update($data, $where, $log = false)
	{
		$this->_isTableSet();

		$set = '';
		foreach ($data as $_key => $_value) {
			$set .= "`$_key` = :$_key,";
		}
		$set = rtrim($set, ",");

		//izgradnja WHERE metodom where
		$where_stmt = $this->_whereBuilder($where);

		$data = array_merge($data, $where);

		if ($log == true) {
			$sth = $this->prepare("UPDATE `{$this->table}` SET $set $where_stmt ORDER BY {$this->table}_id DESC LIMIT 1;");
		} else {
			$sth = $this->prepare("UPDATE `{$this->table}` SET $set $where_stmt");
		}

		$sth->execute($data);
	}
	//---------------------------------------------------------------------------------------------------------------------

	/**
	 * Brisanje podataka gde $where
	 * @param array $where
	 */
	public function delete($where)
	{
		$this->_isTableSet();

		//izgradnja WHERE metodom where
		$where_stmt = $this->_whereBuilder($where);

		$sth = $this->prepare("DELETE FROM `{$this->table}` $where_stmt");
		$sth->execute($where);
	}
	//---------------------------------------------------------------------------------------------------------------------

	private function _whereBuilder($where)
	{
		$where_stmt = null;
		if (is_numeric($where)) {
			$primary = $this->table . '_id';
			$where_stmt = "WHERE `$primary` = :primary_key";
			$where = [
				'primary_key' => $where
			];
		} elseif (is_array($where)) {
			$where_stmt = '';
			foreach ($where as $_key => $_value) {
				$where_stmt .= "`$_key` = :$_key AND ";
			}
			$where_stmt = "WHERE " . rtrim($where_stmt, ' AND ');
		}

		return $where_stmt;
	}

	//---------------------------------------------------------------------------------------------------------------------
	private function _isTableSet()
	{
		if ($this->table == null) {
			die('Morate odabrati tabelu! Tabela nije izabrana!');
		}
	}
}

<?php
namespace Horus;

/**
 * Horus DB
 * ------------------
 * Simple query-builder powered by Mysqli. This is supposed to replace the single DB\Mysqli & DB\PDO packages 
 * 
 * @author Michael Darko
 * @since v2.1.0
 */
class Db {
	/**
	 * Database Connection
	 */
	protected $connection;
	/**
	 * Raw query with query options
	 */
	protected $queryData = [
		"table" => "",
		"type" => "",
		"query" => "",
		"params" => [],
		"uniques" => [],
		"validate" => [],
		"values" => [],
		"hidden" => [],
		"add" => []
	];
	/**
	 * Query result
	 */
	protected $queryResult;
	/**
	 * Any errors caught
	 */
	protected $errorsArray = [];
	/**
	 * Horus Form Module
	 */
	protected $form;
	/**
	 * Horus Response Module
	 */
	protected $response;

	public function __construct($host = null, $user = null, $password = null, $dbname = null)
	{
		$this->form = new Form;
		$this->response = new Http\Response;

		if ($host != null || $user != null || $password != null || $dbname != null) {
			$this->connect($host, $user, $password, $dbname);
		}
	}

	/**
	 * Return the database connection
	 */
	public function connection()
	{
		return $this->connection;
	}

	/**
	 * Connect to database
	 * 
	 * @param string $host: Host Name
	 * @param string $user: Database username
	 * @param string $password: Database password
	 * @param string $dbname: Database name
	 */
	public function connect(string $host, string $user, string $password, string $dbname) : void
	{
		try {
			$connection = mysqli_connect($host, $user, $password, $dbname);
			$this->connection = $connection;
		} catch (\Exception $e) {
			$this->connection = null;
			$this->errorsArray["connection"] = $e->getMessage();
		}
	}

	/**
	 * Connect to database using environment variables
	 */
	public function auto_connect() : void
	{
		$this->connect(
			getenv("DB_HOST"),
			getenv("DB_USERNAME"),
			getenv("DB_PASSWORD"),
			getenv("DB_DATABASE")
		);
	}

	/**
	 * DB Query
	 * ----------------
	 * Create a database query
	 * 
	 * @param string $sql: Query
	 */
	public function query(string $sql): self
	{
		if ($this->connection == null) $this->response->throwErr("Initialise your database first with connect()");
		$this->queryData["type"] = "query";
		$this->queryData["query"] = $sql;

		return $this;
	}

	/**
	 * Db Select
	 * 
	 * Retrieve a row from table
	 * 
	 * @param string $table: Db Table
	 * @param string $items: Specific table columns to fetch
	 */
	public function select(string $table, string $items = "*")
	{
		$this->queryData["query"] .= "SELECT $items FROM $table";
		$this->queryData["type"] = "select";
		$this->queryData["table"] = $table;
		return $this;
	}

	/**
	 * Db Insert
	 * 
	 * Add a new row in a db table
	 * 
	 * @param string $table: Db Table
	 */
	public function insert(string $table) : self
	{
		$this->queryData["query"] .= "INSERT INTO $table";
		$this->queryData["type"] = "insert";
		$this->queryData["table"] = $table;
		return $this;
	}

	/**
	 * Db Update
	 * 
	 * Update a row in a db table
	 * 
	 * @param string $table: Db Table
	 */
	public function update(string $table): self
	{
		$this->queryData["query"] .= "UPDATE $table";
		$this->queryData["type"] = "update";
		$this->queryData["table"] = $table;
		return $this;
	}

	/**
	 * Db Delete
	 * 
	 * Delete a table's records
	 * 
	 * @param string $table: Db Table
	 */
	public function delete(string $table): self
	{
		$this->queryData["query"] .= "DELETE FROM $table";
		$this->queryData["type"] = "delete";
		$this->queryData["table"] = $table;
		return $this;
	}

	/**
	 * Pass in parameters into your query
	 * 
	 * @param array $params Params to pass into query
	 */
	public function params(array $params) : self
	{
		if ($this->queryData["type"] == "query") {
			if (strpos($this->queryData["query"], "INSERT INTO") === 0) $this->queryData["type"] = "insert";
			if (strpos($this->queryData["query"], "UPDATE ") === 0) $this->queryData["type"] = "update";
			if (strpos($this->queryData["query"], "SELECT ") === 0) $this->queryData["type"] = "select";
			if (strpos($this->queryData["query"], "DELETE FROM ") === 0) $this->queryData["type"] = "delete";
		}
		$query = $this->queryData["type"] == "update" ? " SET " : " ";
		$count = 0;
		$dataToBind = [];
		$keys = "";
		$values = "";
		foreach ($params as $key => $value) {
			if ($this->queryData["type"] == "insert") {
				$keys .= $key;
				$values .= "?";
				if ($count < count($params) - 1) {
					$keys .= ", ";
					$values .= ", ";
				}
			} else if ($this->queryData["type"] == "update") {
				$query .= "$key = ?";
				if ($count < count($params) - 1) {
					$query .= ", ";
				}
			}
			$dataToBind[$value] = "s";
			$count += 1;
		}
		if ($this->queryData["type"] == "insert") {
			$query .= "($keys) VALUES ($values)";
		}
		$this->bind($dataToBind);
		$this->queryData["query"] .= $query;
		$this->queryData["values"] = $params;
		return $this;
	}

	/**
	 * Add a where clause to db query
	 * 
	 * @param string|array $condition
	 * @param string|null $value
	 */
	public function where($condition, $value = null) : self
	{
		$query = " WHERE ";
		$count = 0;
		$dataToBind = [];
		$params = [];

		if (is_array($condition)) {
			foreach ($condition as $key => $value) {
				$query .= "$key = ?";
				if ($count < count($condition) - 1) {
					$query .= " AND ";
				}
				if ($this->queryData["type"] === "select" || $this->queryData["type"] === "delete") {
					$params[$key] = $value;
				}
				$dataToBind[$value] = "s";
				$count += 1;
			}
		} else {
			if (!$value) {
				$query .= $condition;
			} else {
				if ($this->queryData["type"] === "select" || $this->queryData["type"] === "delete") {
					$params[$condition] = $value;
				}
				$query .= "$condition = ?";
				$dataToBind[$value] = "s";
			}
		}

		$this->bind($dataToBind);
		if ($this->queryData["type"] === "select" || $this->queryData["type"] === "delete") {
			$this->queryData["values"] = $params;
		}
		$this->queryData["query"] .= $query;
		return $this;
	}

	/**
	 * Fetch a specific number
	 * 
	 * @param mixed $limit The number of rows to fetch
	 */
	public function limit($limit) : self
	{
		$this->queryData["query"] .= " LIMIT $limit";
		return $this;
	}

	/**
	 * Validate data before running a query
	 * 
	 * @param array|string $item The item(s) to validate
	 * @param string|null $rule The validation rule to apply
	 */
	public function validate($item, $rule = "required") : self
	{
		$values = $this->queryData["values"];

		if (is_array($item)) {
			foreach ($item as $key => $value) {
				$this->queryData["validate"][] = [$key, $values[$key], strtolower($value) ?? "required"];
			}
		} else {
			$this->queryData["validate"][] = [$item, $values[$item], strtolower($rule)];
		}
		return $this;
	}

	/**
	 * Make sure a value doesn't already exist in a table to avoid duplicates.
	 */
	public function unique(...$uniques) {
		$data = [];
		foreach ($uniques as $unique) {
			if (is_array($unique)) {
				$data = $unique;
			} else {
				$data[] = $unique;
			}
		}
		$this->queryData["uniques"] = $data;
		return $this;
	}

	/**
	 * Hide particular fields from the final value returned
	 * 
	 * @param mixed $values The value(s) to hide
	 */
	public function hidden(...$values) : self
	{
		$data = [];
		foreach ($values as $value) {
			if (is_array($value)) {
				$data = $value;
			} else {
				$data[] = $value;
			}
		}
		$this->queryData["hidden"] = $data;
		return $this;
	}

	/**
	 * Add particular fields to the final value returned
	 * 
	 * @param string $name What to add
	 * @param string $value The value to add
	 */
	public function add($name, $value = null): self
	{
		$data = [];
		if (is_array($name)) {
			$data = $name;
		} else {
			$data[$name] = $value;
		}
		$this->queryData["add"] = $data;
		return $this;
	}

	/**
	 * Bind parameters to a query
	 * 
	 * @param array|string $data The data to bind to string
	 * @param string $type The type of the value (s, i, b)
	 */
	public function bind($data, $type = "s") : self
	{
		$params = [];

		if (is_array($data)) {
			foreach ($data as $param => $type) {
				$params[] = [$param, $type];
			}
		} else {
			$params[] = [$data, $type];
		}

		foreach ($params as $param) {
			$this->queryData["params"][] = [$param[0], $param[1]];
		}
		
		return $this;
	}

	/**
	 * Execute a query
	 */
	public function execute() {
		$query = $this->queryData["query"];
		$params = $this->queryData["params"];
		$paramValues = $this->queryData["values"];
		$uniques = $this->queryData["uniques"];
		$validate = $this->queryData["validate"];

		if (count($validate) > 0) {
			foreach ($validate as $item) {
				if (!$this->form->validateField($item[0], $item[1], $item[2])) {
					foreach ($this->form->errors() as $name => $error) {
						$this->errorsArray[$name] = $error;
					}
				}
			}
		}

		if (count($this->errorsArray) > 0) return false;

		
		if (count($uniques) > 0 && ($this->queryData["type"] != "select" || $this->queryData["type"] != "delete")) {
			foreach ($uniques as $unique) {
				if (!isset($paramValues[$unique])) {
					$this->response->respond(["error" => "$unique not found, Add $unique to your \$db->add items or check your spelling."]);
					exit();
				} else {
					if (mysqli_fetch_object($this->connection->query("SELECT * FROM {$this->queryData["table"]} WHERE $unique = '$paramValues[$unique]'"))) {
						$this->errorsArray[$unique] = "$unique already exists";
					}
				}
			}
		}

		if (count($this->errorsArray) > 0) return false;

		$types = "";
		$bindings = [];

		foreach ($params as $data) {
			$types .= $data[1];
			$bindings[] = $data[0];
		}

		if (!$types) $types = str_repeat('s', count($bindings));
		
		if (!$bindings) {
			try {
				$this->queryResult = $this->connection->query($query);
			} catch (\Throwable $th) {
				$this->errorsArray["query"] = $th->getMessage();
			}
		} else {
			$stmt = $this->stmt = $this->connection->prepare($query);
			$stmt->bind_param($types, ...$bindings);
			try {
				$stmt->execute();
			} catch (\Throwable $th) {
				$this->errorsArray["query"] = $th->getMessage();
			}
			$this->queryResult = $stmt->get_result();
		}

		$this->queryData = [
			"table" => "",
			"type" => "",
			"query" => "",
			"params" => [],
			"uniques" => [],
			"validate" => [],
			"values" => [],
			"hidden" => [],
			"add" => []
		];
	}

	/**
	 * Get number of rows from SELECT
	 *
	 * @return int $connection->num_rows
	 */
	public function count() : int
	{
		if ($this->execute() === false) return false;
		return mysqli_num_rows($this->queryResult);
	}

	/**
	 * Fetch query results as an associative array
	 */
	public function fetchAssoc()
	{
		if ($this->execute() === false) return false;
		$result = mysqli_fetch_assoc($this->queryResult);
		
		$add = $this->queryData["add"];
		if (count($add) > 0) {
			foreach ($add as $item => $value) {
				$result[$item] = $value;
			}
		}

		$hidden = $this->queryData["hidden"];
		if (count($hidden) > 0) {
			foreach ($hidden as $item) {
				if (isset($result[$item])) unset($result[$item]);
			}
		}

		return $result;
	}

	/**
	 * Fetch query results as object
	 */
	public function fetchObj()
	{
		if ($this->execute() === false) return false;
		$result = mysqli_fetch_object($this->queryResult);

		$add = $this->queryData["add"];
		if (count($add) > 0) {
			foreach ($add as $item => $value) {
				$result->{$item} = $value;
			}
		}

		$hidden = $this->queryData["hidden"];
		if (count($hidden) > 0) {
			foreach ($hidden as $item) {
				if (isset($result->{$item})) unset($result->{$item});
			}
		}

		return $result;
	}

	/**
	 * Fetch all
	 */
	public function fetchAll()
	{
		if ($this->execute() === false) return false;
		$result = mysqli_fetch_all($this->queryResult, \MYSQLI_ASSOC);

		$add = $this->queryData["add"];
		$hidden = $this->queryData["hidden"];
		$final = [];
		if (count($add) > 0 || count($hidden) > 0) {
			foreach ($result as $res) {
				if (count($add) > 0) {
					foreach ($add as $item => $value) {
						$res[$item] = $value;
					}
				}

				if (count($hidden) > 0) {
					foreach ($hidden as $item) {
						if (isset($res[$item])) unset($res[$item]);
					}
				}
				$final[] = $res;
			}
		}

		return $final;
	}

	/**
	 * Return raw query result
	 */
	public function fetch() : array
	{
		if ($this->execute() === false) return false;
		return $this->queryResult;
	}

	/**
	 * Closes MySQL connection
	 */
	public function close(): void
	{
		$this->connection->close();
	}

	/**
	 * Return caught errors if any
	 */
	public function errors() : array
	{
		return $this->errorsArray;
	}
}

<?php

namespace ActiveRecord;

class ConnectionInfo {

	public $protocol = null;
	public $host = null;
	public $port = null;
	public $database = null;

	public $username = null;
	public $password = null;

	public $charset = null;

	public function __construct($input = array()){
		foreach($input as $prop => $value){
			$this->{$prop} = $value;
		}
	}

	/**
	 * Parses a connection url and return a ConnectionInfo object
	 *
	 * Use this for any adapters that can take connection info in the form below
	 * to set the adapters connection info.
	 *
	 * <code>
	 * protocol://username:password@host[:port]/dbname
	 * protocol://urlencoded%20username:urlencoded%20password@host[:port]/dbname?decode=true
	 * protocol://username:password@unix(/some/file/path)/dbname
	 * </code>
	 *
	 * Sqlite has a special syntax, as it does not need a database name or user authentication:
	 *
	 * <code>
	 * sqlite://file.db
	 * sqlite://../relative/path/to/file.db
	 * sqlite://unix(/absolute/path/to/file.db)
	 * sqlite://windows(c%2A/absolute/path/to/file.db)
	 * </code>
	 *
	 * @param string $connection_url A connection URL
	 * @return ConnectionInfo the parsed URL as an object.
	 */
	public static function from_connection_url($connection_url){
		$url = @parse_url($connection_url);

		if (!isset($url['host']))
			throw new DatabaseException('Database host must be specified in the connection string. If you want to specify an absolute filename, use e.g. sqlite://unix(/path/to/file)');

		$info = new self();
		$info->protocol = $url['scheme'];
		$info->host = $url['host'];
		if(isset($url['path'])){
			$info->database = substr($url['path'], 1);
		}
		$info->username = isset($url['user']) ? $url['user'] : null;
		$info->password = isset($url['pass']) ? $url['pass'] : null;

		$allow_blank_db = ($info->protocol == 'sqlite');

		if ($info->host == 'unix(')
		{
			$socket_database = $info->host . '/' . $info->database;

			if ($allow_blank_db)
				$unix_regex = '/^unix\((.+)\)\/?().*$/';
			else
				$unix_regex = '/^unix\((.+)\)\/(.+)$/';

			if (preg_match_all($unix_regex, $socket_database, $matches) > 0)
			{
				$info->host = $matches[1][0];
				$info->database = $matches[2][0];
			}
		} elseif (substr($info->host, 0, 8) == 'windows(')
		{
			$info->host = urldecode(substr($info->host, 8) . '/' . substr($info->database, 0, -1));
			$info->database = null;
		}

		if ($allow_blank_db && $info->database)
			$info->host .= '/' . $info->database;

		if (isset($url['port']))
			$info->port = $url['port'];

		if (isset($url['query']))
		{
			parse_str($url['query'], $params);
			if(isset($params['charset'])){
				$info->charset = $params['charset'];
			}

			if(isset($params['decode']) && $params['decode'] == 'true' )
			{
				if ($info->username)
					$info->username = urldecode($info->username);

				if ($info->password)
					$info->password = urldecode($info->password);
			}
		}

		return $info;
	}

}

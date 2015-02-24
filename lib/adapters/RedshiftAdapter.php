<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

require_once __DIR__ . '/PgsqlAdapter.php';

/**
 * Adapter for Redshift postgres version < 8.0.2 (not completed yet)
 *
 * @package ActiveRecord
 */
class RedshiftAdapter extends PgsqlAdapter
{
	public function supports_sequences()
	{
		return false;
	}

	public function query_column_info($table)
	{
		$sql = <<<SQL
SELECT
	a.attname AS field,
	a.attlen,
	REPLACE(pg_catalog.format_type(a.atttypid, a.atttypmod), 'character varying', 'varchar') AS type,
	a.attnotnull AS not_nullable,
	(SELECT 't'
		FROM pg_index
		WHERE c.oid = pg_index.indrelid
		AND a.attnum = ANY (
		string_to_array(
			textin(
			int2vectorout(pg_index.indkey)
			), ''
		)
		)
		AND pg_index.indisprimary = 't'
	) IS NOT NULL AS pk,
	REGEXP_REPLACE(REGEXP_REPLACE(REGEXP_REPLACE((SELECT pg_attrdef.adsrc
		FROM pg_attrdef
		WHERE c.oid = pg_attrdef.adrelid
		AND pg_attrdef.adnum=a.attnum
	),'::[a-z_ ]+',''),'''$',''),'^''','') AS default
FROM pg_attribute a, pg_class c, pg_type t
WHERE c.relname = ?
	AND a.attnum > 0
	AND a.attrelid = c.oid
	AND a.atttypid = t.oid
ORDER BY a.attnum
SQL;
		$values = array($table);
		return $this->query($sql,$values);
	}
}

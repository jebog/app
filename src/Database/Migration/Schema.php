<?php
namespace Bow\Database\Migration;

use Bow\Support\Str;
use Bow\Database\Database;

class Schema
{
    /**
     * @var string
     */
    private static $table;

    /**
     * @var array
     */
    private static $data;

    /**
     * @var string
     */
    private $fill;

    protected static $types = [
        'integer' => 'number',
        'string' => 'string',
        'date' => 'date',
        'time' => 'timestamps'
    ];

    /**
     * Supprimer une table.
     *
     * @param string $table
     * @return int
     */
    public static function drop($table)
    {
        if (Database::statement('DROP TABLE $table;')) {
            echo "\033[0;32m$table table droped.\033[00m\n";
        } else {
            echo "\033[0;31m$table table not exists.\033[00m\n";
        }
    }

    /**
     * Fonction de creation d'une nouvelle table dans la base de donnée.
     *
     * @param string $table
     * @param callable $cb
     * @param bool $displaySql
     */
    public static function create($table, Callable $cb, $displaySql = false)
    {
        static::$table = $table;

        $fields = new Fields($table);
        call_user_func_array($cb, [$fields]);

        $sql = (new StatementMaker($fields))->toCreateTableStatement();

        if ($sql == null) {
            die("\033[0;31mPlease check your 'up' method.\033[00m\n");
        }

        if ($displaySql) {
            echo $sql . "\n";
        }

        static::$data = $fields->getBindData();

        if (Database::statement($sql)) {
            echo "\033[0;32m$table table created.\033[00m\n";
        } else {
            echo "\033[0;31m$table table already exists.\033[00m\n";
        }
    }

    /**
     * Manipule les informations de la table.
     *
     * @param string $table
     * @param bool $displaySql
     * @param Callable $cb
     */
    public static function table($table, Callable $cb, $displaySql = false)
    {
        call_user_func_array($cb, [new AlterTable($table, $displaySql)]);
    }

    /**
     * fillTable, remplir un table pour permet le developpement.
     *
     * @param string|array $table [optional]
     * @param int $n
     *
     * @return mixed
     */
    public static function fillTable($table = null, $n = 1)
    {
        if (is_int($table)) {
            $n = $table;
            $table = null;
        }

        if (!is_string($table)) {
            $table = static::$table;
        }

        for($i = 0; $i < $n; $i++) {
            $data = [];
            foreach(static::$data as $column) {
                if (in_array($column['type'], ['int', 'longint', 'bigint', 'mediumint', 'smallint', 'tinyint'])) {
                    if ($column['auto']) {
                        $value = null;
                    } else {
                        $value = Filler::number();
                    }
                } else if (in_array($column['type'], ['date', 'datetime'])) {
                    $value = Filler::date();
                } else if (in_array($column['type'], ['double', 'float'])) {
                    $value = Filler::float();
                } else if ($column['type'] == 'timestamp') {
                    $value = 'CURRENT_TIMESTAMP';
                } else {
                    $value = Str::slice(Filler::string(), 0, $column['size']);
                }

                $data[$column['field']] = $value;
            }
            Database::table($table)->insert($data);
        }
    }
}
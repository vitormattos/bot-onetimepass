<?php
namespace Base;
class DB
{
    private static $db;
    /**
     * 
     * @return \PDO
     */
    public static function getInstance()
    {
        if(self::$db) {
            return self::$db;
        }
        $dbopts = parse_url(getenv('DATABASE_URL'));
        self::$db = new \PDO(
            getenv('DB_ADAPTER').':host='.$dbopts["host"].';port='.$dbopts['port'].';dbname='.ltrim($dbopts["path"],'/'),
            $dbopts['user'],
            $dbopts['pass']
        );
        return self::$db;
    }
}

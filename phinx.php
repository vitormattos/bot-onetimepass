<?php
if(file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
}

$dbopts = parse_url(getenv('DATABASE_URL'));

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations'
    ],
    'environments' => [
        'default_database'=> 'development',
        'production' => [
            'adapter' => 'pgsql',
            'host' => $dbopts["host"],
            'name' => ltrim($dbopts["path"],'/'),
            'user' => $dbopts['user'],
            'pass' => $dbopts['pass'],
            'port' => $dbopts['port'],
            'charset' => 'UTF-8'
        ],
        'development' => [
            'adapter' => 'pgsql',
            'host' => $dbopts["host"],
            'name' => ltrim($dbopts["path"],'/'),
            'user' => $dbopts['user'],
            'pass' => $dbopts['pass'],
            'port' => $dbopts['port'],
            'charset' => 'UTF-8'
        ]
    ]
];
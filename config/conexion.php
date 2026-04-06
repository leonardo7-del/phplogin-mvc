<?php

class Conexion
{
    private static ?PDO $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $db = 'patron_mvc';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';
        $socket = '/opt/lampp/var/mysql/mysql.sock';

        if (is_file($socket)) {
            $dsn = "mysql:unix_socket={$socket};dbname={$db};charset={$charset}";
        } else {
            $host = 'localhost';
            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        }

        self::$conexion = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$conexion;
    }
}

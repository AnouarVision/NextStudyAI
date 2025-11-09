<?php

declare(strict_types=1);

class DbConnector
{
    private string $servername;
    private string $username;
    private string $password;
    private string $dbname;

    public function __construct(string $servername, string $username, string $password, string $dbname)
    {
        $this->servername = $servername;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
    }

    public function prepare(string $query): PDOStatement
    {
        $connection = new PDO("mysql:host={$this->servername};dbname={$this->dbname}", $this->username, $this->password);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $connection->prepare($query);
    }
}

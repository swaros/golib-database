# golib-database
golib database layer

### install

composer `composer require gorgo/golibdatabase`

### basic usage

connect to a mysql Database

```php
use golibdatabase\Database\MySql;
$connect = new MySql\ConnectInfo( 'username','password','hostname','default_shema' );
$db = new MySql( $connect );
$result = $db->select( 'SELECT * FROM Tablename' );
if ($result->getErrorNr()) {
        echo " --- mysql error:" . $result->getError();
    } else {
        echo " --- got " . $result->count() . 'entries ';
        var_dump( $result->getResult() );
    }
}
```


### Connection Manager

is written for cases you can not be sure the connection is already existing (for example by replacing a singelton db implementation)

```php
// run the whole code 3 times just to explain what the connection-manager is doing
for ($i = 0; $i < 3; $i++) {
    $connect = new MySql\ConnectInfo( 'username','password','hostname','default_shema' );

    $connectManager = new Database\ConnectManager();

    if ($connectManager->connectionIsStored( $connect )) {
        $db = $connectManager->getStoredConnection( $connect );
        echo ' --- use existing connection --- ';
    } else {
        echo ' ---- create a new connection --- ';
        $db = new MySql( $connect );
        $connectManager->registerConnection( $db );
    }

    $result = $db->select( 'SELECT * FROM Tablename' );

    if ($result->getErrorNr()) {
        echo " --- mysql error:" . $result->getError();

    } else {
        echo " --- got " . $result->count() . 'entries ';
        var_dump( $result->getResult() );

    }
}

```

### Table Example

Database Example setup

Structure

```sql
CREATE TABLE `golib-db` (
`primId` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`Content` VARCHAR( 250 ) NOT NULL ,
`DateExample` DATETIME NOT NULL ,
`ExampleValue` MEDIUMINT NOT NULL
) ENGINE = InnoDB;
```

Data

```sql
INSERT INTO `golib-db` (
`primId` ,
`Content` ,
`DateExample` ,
`ExampleValue`
)
VALUES (
NULL , 'test content', '2017-09-30 00:00:00', '450'
), (
NULL , 'second content', '2017-09-19 00:00:00', '9887'
);
```


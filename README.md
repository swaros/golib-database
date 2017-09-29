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

Explain by example.

Table Structure and Content.
```sql
CREATE TABLE `golib-db` (
`primId` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`Content` VARCHAR( 250 ) NOT NULL ,
`DateExample` DATETIME NOT NULL ,
`ExampleValue` MEDIUMINT NOT NULL
) ENGINE = InnoDB;

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

step 1: Build a Propertie Class that reflects the structure of the database.
this class have to extend from `golib\Types\PropsFactory`

Like so:

```php
/**
 * the property Class descriptes the expected fields
 */
use golib\Types;
/**
 * the property Class descriptes the expected fields
 */
class exampleProp extends Types\PropsFactory {
    /**
     * autoinc, primary
     * @var int
     */
    public $primId = NULL;
    /**
     *
     * @var string
     */
    public $Content = '';

    /**
     * a date example
     * @var Types\Timer
     */
    public $DateExample = Types\MapConst::TIMER;
    /**
     * just a integer
     * @var int
     */
    public $ExampleValue = 0;

}

```

Step 2: Define the Table Class that points to the table, and setup the Propertie Class and the Tablename.

```php
use golib\Types;
/
/**
 * the table class
 * that maps to the table in the database.
 *
 * they need to know about the structure by using
 * the property class
 *
 * and (of course) the table name
 */
class exampleTable extends MySql\Table {
    /**
     * defines the content.
     * how the rows looks like
     * @return \exampleProp
     */
    public function getPropFactory () {
        return new exampleProp( 'primId' );
    }
    /**
     * just the tablename
     * @return string
     */
    public function getTableName () {
        return 'golib-db';
    }

}

```

Thats all what is needed to setup the basic Model for a Table.
to get data.


```php
// initiate the modell of the table
$golibTable = new exampleTable();

// get the content by using a existing database connection
$golibTable->fetchData( $db );

// one way to iterate the content.
$golibTable->foreachCall( function(exampleProp $row) {
    var_dump( $row );
} );
```
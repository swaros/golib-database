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
var_dump( $result->getResult() );
```
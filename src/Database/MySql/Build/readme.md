# Class Builder

this folder contains classes to create Database 
models and Connections depending on the existing
Tables.

## Example Script

here a basic Script that parses the Tables in the Database
, and creates the needed Classes to access these.

```php
<?php
// fit to vendor autoload
require_once __DIR__ . '/../vendor/autoload.php';

// only Table Names will be included they matches with these patterns.
// not regex, just a part of the expected string
// leave empty for all tables
$patterns = array(
);


// set up connection to database
$config = new golibdatabase\Database\MySql\ConnectInfo(
    'USERNAME',
    'PASSWORD',
    'HOST-NAME',
    'DATABASE'
);

// init database connection
$mysql = new \golibdatabase\Database\MySql($config);

// create ClassWriter instance
$writer = new \golibdatabase\Database\MySql\Build\ClassWriter();

// create Class Instance and inject connection
$classes = new \golibdatabase\Database\MySql\Build\Classes($mysql);

// start the run and inject a closure that uses the writer to add tables to
// the class-writer
$classes->exec(function ($tableName, $fields) use ($writer, $patterns) {
    // is this $table-name matching to on of the patterns?

    // initial state depends if patterns are empty or not
    $match = empty($patterns);
    foreach ($patterns as $check){
        $match = $match || strpos($tableName,$check) !== false;
    }



    // add if match found
    if ($match){
        $writer->buildTableContent($tableName, $fields);
    }
});

// get the generated classes
$files = $writer->getFileStorage();

// set the root-source-folder
$writeBase = '';

// if TRUE we just print out what we would do
$justOutPut = true;

// iterates over file-props
foreach ($files as $file) {
    // concatenate the base path
    $writePath = $writeBase . $file->path;

    // some outputs
    echo 'file path:'."\t" . $writePath . PHP_EOL;
    echo 'directory:'."\t";
    // creates directories if not exists
    if (is_dir(dirname($writePath))) {
        echo "\t[exists]\t";
    } else {
        echo "\t*(new)\t";
        if (!$justOutPut) {
            $cde = mkdir(dirname($writePath), 0755, true);
            if (!$cde) {
                echo dirname($writePath) . " ERROR ON CREATE FOLDER ...." . PHP_EOL;
                die();
            }
        }
    }
    echo dirname($writePath);
    echo PHP_EOL;
    // write the class file
    if (!$justOutPut){
        file_put_contents($writePath, $file->content);
    }

}
```
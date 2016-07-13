# Metrol\DBObject
## Automatic mapping of Databases to PHP Objects

In a world full of ORM solutions that generally fall into one of two categories...

1. Creates and tracks tables for you based on code.
1. Requires you to copy the table structure into the code from the DB.

I felt there should be a 3rd solution for this.

- Use the database as a reference and have the code support that.

I don't consider the database  of my web application as some boring persistant storage layer.  In my opinion, it should be treated as an equal and vital partner in bringing a web application to fruition.  It's a starting point for working through the structure of how information should be stored.

This library exists to handle to 90% of database useage, basic CRUD (Create, Read, Update, Delete) operations without having to write the SQL manually.  This should be as simple as telling an object what table to use, and just use it.

```php
$table = new \Metrol\DBTable\PostgreSQL('ImportantStuff');
$db = new \PDO($connectionString);
$object = \Metrol\DBObject($table, $db);
```

Obviously some dependencies in play here.  Using my `Metrol\DBTable` and `Metrol\DBSql` libraries to work through getting the information about the table, and how to write the SQL.  With this I can start to work immediately with that new object.

```php
$object->load(21);
$object->stuff = "Changing info is easy";
$object->save();   // Record is written back! 
```

It really is just that easy.  There's also some protections worked in automatically here.  For instance, say that the `stuff` field was actually an integer.  Passing a string wouldn't have changed the value for that field.  Every field type has basic validation built in to _try_ to do the right thing.  You can also trigger a `strict` mode to throw an exception when types can't be coerced.  The default goal is to prevent errors from showing up.

All the validation work is actually handled by `Metrol\DBTable`, as that's where DBObject passes it through whenever setting a value for use in your PHP program, or when it's ready to be bound for being stored in the database.  Not just for validation, as this allows things like automatically setting a date field into a `DateTime` object.

```php
echo $object->lastUpdate->format('Y-m-d');
```

This also has the additional benefit of always making sure that things like dates are always in the correct format for the database engine you're using.

Due to how `Metrol\DBSql` works with `PDO`, all the values are prepared with bindings then executed.  Although I can't promise no possibility for SQL injection (as no library can) every effort has been made to bring the difficulty level of a hack much higher.

You're not restricted to just using the primary key to load a record.  You can also request one to be loaded based on some other SQL where clause.  For example.

```php
$object->loadFromWhere('relatedID = ? and status = ?', [35, 'Active']);
```

For a single item such as this, the SQL will only pull in the first record it gets from the criteria and load it into the object.  When you need to pull in a set of these objects using more advanced critera you'll need to use a **Set**.

# Extending DBObject

You can call to DBObject directly like shown, but you can also extend a class you would like to represent a record in a database.  It could look something like...

```php
class Widget extends \Metrol\DBObject
{
    const TABLE_NAME = 'Widgets';

    public function __construct($primaryKeyValue = null)
    {
        parent::__construct($this->getTable(), Connect\Bank::get() );
        
        if ( $primaryKeyValue !== null )
        {
            $this->load($primaryKeyValue);
        }
    }

    private function getTable()
    {
        // Returns a DBTable object
    }
}
```

I've personally found it useful to first extend DBObject into an application level, then extend it again to support specific model needs.  Or don't.  You can still make use of DBObject via object composition without the inheritance.  It's entirely up to you what makes sense for your application.

# Data Sets

This library supports 2 kinds of data sets.

1. Any query you'd like to toss at it. (Item Set)
2. Set of specific DBObjects. (DBObject Set) 

## Item Set

If you run a fetch all PDO query you'll get back a simple array of stdClass objects that you would likely iterate through.  You might even have it populate a specific class of values.

The `DBObject\Item\Set` class handles this for you, plus some extra features tossed in on top.  It works with a couple of different kinds of `DBSql` statements to assemble the query, or you can push plain text SQL on in.  Here's a quick example...

```php
$db = new PDO($dsn);
$sql = 'SELECT * FROM itsatable WHERE id = ?';

$set = new \Metrol\DBObject\Item\Set($db);
$set->setRawSQL($sql)
    ->setRawSQLBinding([12])
    ->run();

foreach ( $set as $item )
{
    echo $item->id, PHP_EOL;
}
```

That is fully functional code ready to roll out, assuming you had a real PDO connection that is.  Want to make it a bit more friendly for different SQL engines?  Let's write the same thing using the `DBSql` support built in.

```php
$db = new PDO($dsn);
$set = new \Metrol\DBObject\Item\Set($db);

// Automatically chooses the SQL engine for your DB based on the PDO driver name
$select = $set->getSqlSelect();

$select->from('itsatable')
    ->where('id = ?', 12);
    
$set->run();

// etc...
```

Each item in the set is a generic object that allows you to iterate it for fields and values.  For example...

```php
foreach ( $set as $item )
{
    foreach ( $item as $field => $value )
    {
        echo $field . ' = ' . $value, PHP_EOL;
    }
}
```

All items and sets here are JSON ready to roll out.

```php
echo json_encode($set);
```

Searching for a specific record, or list of records in a set?

```php
$lookingFor = $set->find('name', 'Metrol');
```

```php
$activeList = $set->findAll('status', 'Active');
```

## DBOject Set

To further refine the notion of a set, `DBObject\Set` handles creating a set of specific DBObjects of a certain type.  This is an extension of the `Item\Set` above.

For an example, let's say you wanted to generate a list of all the Employee DBObjects that are in your database.  Assuming that `Employee` is a child of DBObject, it looks like...
 
```php
$set = new \Metrol\DBObject\Set(new Employee);
$set->run();
```

That is a fully populated list of `Employee` objects.  The database connection and table information is already in the DBObject, so the Set is immediately ready to go.

You can pass in filtering where clauses as well.

```php
$set->addFilter('salary < ?', 50000)
    ->addOrder('lastName', 'ASC')
    ->run();
```

The filtering adds to a stack, so add as many as you need.

This is really meant for a list generated from a single database table.  When you need to combine information from multiple tables, the `Item\Set` is built around that need.

## Status

Things are very much starting to stabilize with this.  Most of the real heavy lifting is happening with `DBTable` and `DBSql`.  I'm still adjusting things as I find the need for additional functionality.

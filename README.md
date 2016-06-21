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
$object->setPrimaryKeyValue(15)->load();
$object->stuff = "Changing info is easy";
$object->save();   // Record is written back! 
```

It really is just that easy.  There's also some protections worked in automatically here.  For instance, say that the `stuff` field was actually an integer.  Passing a string wouldn't have changed the value for that field.  Every field type has basic validation built in to _try_ to do the right thing.  You can also trigger a `strict` mode to throw an exception when types can't be coerced.  The default goal is to prevent errors from showing up.

Due to how `Metrol\DBSql` works with `PDO`, all the values are prepared with bindings then executed.  Although I can't promise no possibilities for SQL injection (as no library can) every effort has been made to bring the difficulty level of a hack much higher.

You're not restricted to just using the primary key to load a record.  You can also request one to be loaded based on some other SQL where clause.  For example.

```php
$object->setCriteria('relatedID = ? and status = ?', [35, 'Active'])
       ->load();
```

For a single item such as this, the SQL will only pull in the first record it gets from the criteria and load it into the object.  When you need to pull in a set of these objects using more advanced critera you'll need to use a **Set**.

# Data Sets

Working with a single object has a lot of value by itself, but we all have to do with sets of records as well.  Not just adding to a stack, but also having the ability to construct the SQL to generate the list.

```php
// Need an object as a reference
$object = \Metrol\DBObject\PostgreSQL($table, $db);

// Define the kind of set this is by the object passed in
$set = \Metrol\DBObject\Set\PostgreSQL($object);

// Set some criteria, then generate the list
$set->addWhere('status = ?', ['Active'])  // Adds a where clause to the stack
    ->orderBy('name', 'ASC')
    ->run();
```

Unlike other CRM solutions, no transaction goes to/from the database without the consumer of these objects actives requesting something to happen.  Of course, if you want to have things automatically load you can do that using wrapper methods in your own objects.

Hopefully you've noticed by now the concepts here are kept pretty simple.  This is supposed to make things easier.  Method names like `load()`, `run()`, `save()`, and `delete()` are used throughout and everything pretty much says what it does.

## Status
Very little is ready to roll out yet.  Most of what has been stated thus far is based on an older library that I'm looking to re-implement here.

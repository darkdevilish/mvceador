# Mvceador by Anthony Gonzalez

Mvceador is a lightweight and no BS PHP mvc framework.

To include and use classes you need to create a folder and namespace your class with the folder name and save it in the inc folder, so the framework can initialize the class to be used.

To include any helpers go to inc/helpers.

# Mvceador Controllers

To create them go to app/controllers and create an abstract class with static methods.

To include views you need to declare an args variable and include the view layout in your action controller: 
```php 
$args = func_get_args();
require __DIR__.DS.'..'.DS.'views/layouts/app.php' ;
``` 

Naming conventions:
You have to name the controller starting with the name of the route resource plural and controller camelcase, for example: if you create a resource called user, then your controller should be called UsersController.

### Controller Helpers

To get values from url and from post request use the params function with the name as parameter, if you send more than one parameter, you need to send as the function parameter an array with the names of parameter, it will return an associative array with the name of parameter and value.
```php 
params($param); 
```
[NOTE: if you send an id in url it will return the id as integer for security purposes and to be able to send directly to save in model because strict prepared statements params.]

To redirect use the function redirect to function. 
```php 
redirect_to($location); 
```

# Mvceador Views

The framework does not use a templating engine so feel free to use plain old php in your views.

Go to app/views and create a folder with the name of your resource and a file for each action controller.

# Mvceador Routing

Go to config/routes.php.

### Usage

It will match a url path and called a class method dynamically:

If project in subfolder set base path:
```php
Route::set_base_path('/routeador');
```

Root path, example below it will call the method UsersController::index():
```php
Route::root_to('users#index');
```

Route resourceful routes, the example below it will match the next 8 routes:
Method           Path                    Action
GET              /users                  index
GET              /users/new              new_user
POST             /users                  create
GET              /users/[i:id]           show
GET              /users/[i:id]/edit      edit
PATCH|POST       /users/[i:id]           update
POST             /users/[i:id]/delete    delete
DELETE           /users/[i:id]           destroy
```php
Route::resources('users');
```
If only specific resources are needed pass an associative array with the key only and array as value with specified actions:
```php
Route::resources( 'users', array( 'only' => array('create', 'new_user', 'show') ) );
```
If specific resources are not needed:
```php
Route::resources( 'users', array( 'except' => array('create', 'new_user', 'show') ) );
```

To add a route:
```php
Route::add($method, $path, $action, $name);
```

To add multiple routes as one as multidimensional array:
```php
Route::add_routes($array);
```

To generate path from matched routes:
```php
Route::generate_path($route_name, $params);
```

To get routes list:
```php
Route::get_routes();
```

To add specific match type:
```php
Route::add_match_type($match_types);
```

# Mvceador Models

Configuration:
Go to config/config.php.
```php
defined('DB_SERVER') ? null : define("DB_SERVER", "your_host");
defined('DB_USER')   ? null : define("DB_USER", "your_username");
defined('DB_PASS')   ? null : define("DB_PASS", "your_password");
defined('DB_NAME')   ? null : define("DB_NAME", "db_name");
```

### Usage
Go to app/models to create your models.

You need to create public variables for each table fields and a protected static variable called $table_name equal to the table name.

Example create and table called users with 2 fields id and name, then create the following class:
```php
use mappeador\Mapper;

class User extends Mapper {

  protected static $table_name="users";

  public $id;
	public $name;

}
```

Save function it will return true if saved:
```php
$john = new User();
$john->name = "John";
$john->save();
```

Save function at instantiation with array params:
```php
$john = new User(array( 'name' => 'John' ));
$john->save();
```

Find all function returns an object array:
```php
$users = User::find_all();
foreach($users as $user){
    echo $user->id . " | " . $user->name;
}
```

To find order by you just have to pass a parameter to find_all().
Example:
```php
$users = User::find_all("id DESC");
```

Find by id (the parameter has to be an integer):
```php
$user = User::find_by_id(1);
echo $user->name;
```

Find where (will return one object if LIMIT 1):
```php
$find_johns = User::find_where( "name = ?", array("John") );
$find_john = User::find_where( "name = ? LIMIT 1", array('John') );
```

Count all:
```php
User::count_all();
```

Update(first you need to find a record, it will return true if updated):
```php
$user = User::find_by_id(1);
$user->name = "John";
$user->update();
```
Delete(you need to find record first also, and returns true if deleted):
```php
$user = User::find_by_id(1);
$user->name = "John";
$user->delete();
```
[Note: after deleted it will still be in the object, it will only be deleted from database.]

Find by sql can be use directly with DatabaseObject class, Mapper or class that inherits from Mapper. If the sql doesn't need sanitazation just pass one parameter with sql otherwise pass 2 parameter the sql and an array with the bind params.

Example that doesn't need sanitazation(returns object array):
```php
use mappeador\DatabaseObject;

$sql = "SELECT * FROM users";
$result_set = DatabaseObject::find_by_sql($sql);
```
Example that needs sanitazation:
```php
use mappeador\DatabaseObject;

$param = array(1);
$sql = "SELECT * FROM users WHERE id=? LIMIT 1";
$result_set = DatabaseObject::find_by_sql($sql, $param);
```

Mysql query:
```php
use mappeador\MySQLDatabase;

$db = MySQLDatabase::getInstance();

$db->query($sql);
```
[Dangerous: don't use if you need sanitazation.]

# Mvceador Core Components

For Models [Mappeador](https://github.com/darkdevilish/mappeador).

For Routing [Routeador](https://github.com/darkdevilish/routeador).


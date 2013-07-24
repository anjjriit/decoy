# Decoy

The Decoy 2.x docs are very incomplete.  The old docs can be found here: https://github.com/BKWLD/decoy/blob/laravel-3/README.md 

## Installation

1. Run `php artisan migrate --package=cartalyst/sentry`
2. Run `php artisan migrate --package=bkwld/decoy`
3. Run `php artisan config:publish bkwld/decoy`

## Tests

Decoy 2.x adds some unit tests.  To run them, first do a composer install in the Decoy directory with dev resources: `composer install --dev` or `composer update`.  Then (still from the Decoy package directory) run `vendor/bin/phpunit`.  I hope that we continue to add tests for any issues we fix down the road. 

## Routing

Decoy uses custom routing logic to translate it's heirachially path structure into an admin namespaced controller.  Here are some examples of the types of requests that are supported.

*Index*

* GET admin/articles -> Admin\ArticlesController@index
* GET admin/articles/2/article-slides  -> Admin\ArticleSlidesController@index
* GET admin/articles/2/article-slides/5/assets  -> Admin\AssetsController@index

*Create*

* GET admin/articles/create -> Admin\ArticlesController@create
* GET admin/articles/2/article-slides/create  -> Admin\ArticleSlidesController@create

TODO Add more examples

For more info, check out the tests/Routing/TestWildcard.php unit tests.

## Models

Decoy uses the same models as your app uses.  Thus, put them as per normal in /app/models.  However, instead of extending Eloquent, they should sextend Bkwld\Decoy\Models\Base.

### Many to Many relationships

Both the `withTimestamps` and `withPivot` methods should be called on relationships.  For instance:

```
public function users() { return $this->belongsToMany('User')->withTimestamps()->withPivot('id'); }
```

## Controllers

A lot of Decoy's "magic" comes by having your admin controllers extend the `Bkwld\Decoy\Controllers\Base`.  I typically have the admin controllers extend an application specific base controller (i.e. `Admin\BaseController`) which then extends the `Bkwld\Decoy\Controllers\Base`.

### Protected properties

The following protected proprties allow you to customize how Decoy works from the parent controller without overriding whole restful methods.  They generally affect the behavior of multiple methods.  They are all named with all-caps to indicate their significance and to differentiate them from other properties you might set in your admin controller.

* `MODEL` - The name of the controller associated with the controller.  For instance, "Client" in the examples above.  If left undefined, it's generated in the constructor based on the singular form of the controller name.  In addition, the constructor defines a class_alias of `Model` that you can use to refer to the model.  For instance, in a "Clients" controller, you could write `Model::find(2)` instead of `Client::find(2)`.
* `CONTROLLER` - The "path", in Laravel terms, of the controller (i.e. "admin.clients").  If left undefined, it's generated in the constructor from the controller class name.
* `TITLE` - The title used for the pages generated by the controller. If left undefined, it's generated in the constructor from the controller class name.
* `DESCRIPTION` - An optional sentenance or two that is displayed with the title in the header of the page.
* `COLUMNS` - An array of key value pairs used to describe what table columns to have in the listing view.  The default is: `array('Title' => 'title')`.  The key is the label of the column, shown in the header of the table.  The value is the source for the data for the column.  Decoy first checks if there is a method defined on the model with the value and, if so, executes it to return the value.  If there is no method, it checks to see if the model has a property (or dynamic property) with that name and uses it's value of it does.  Finally, if none of those cases are true, it will use the value literally, rendering it in every row of the table.  Note: the default value, `title`, is the name of a method defined in `Decoy\Base_Model`.
* `SHOW_VIEW` - The path, in the Laravel format, to the view for the new/edit view.  I.e. 'admin.news.show'.
* `SEARCH` - A multidimensional associative array that tells Decoy what fields to make available to the search on index views.  It expects data like:

	```
	array(
		'title', // 'title' column assumed to be a text type
		'description' => 'text', // Label auto generated from field name
		'body' => array( // Most explicit way
			'type' => 'text',
			'label' => 'Body',
		)
		'type' => array( // Creates a pulldown menu
			'type' => 'select',
			'options' => array(
				'photo' => 'Photo',
				'video' => 'Video',
			),
		),
		'like_count' => array( // Numeric input field
			'type' => 'number',
			'label' => 'Like total',
		),
		'created_at' => 'date', // Date input field
	);
	```

The following properties are only relevant if a controller is a parent or child of another, as in `has_many()`, `has_many_and_belongs_to()`, etc.  You can typically use Decoy's default values for these (which are deduced from the `routes` Config property).

* `PARENT_MODEL` - The model used by the parent controller (i.e. "Project").
* `PARENT_CONTROLLER` - The parent controller (i.e. "admin.projects").
* `PARENT_TO_SELF` - The name of the relationship on the parent controller's model that refers to it's child (AKA the *current* controller's model, i.e. for "admin.projects" it would be "projects").
* `SELF_TO_PARENT` - The name of the relationship on the controller's model that refers to it's parent (i.e. for "admin.projects" it would be "client").


## Views

Admin views are stored in /app/views/admin/CONTROLLER where "CONTROLLER" is the lowercased controller name (i.e. "articles", "photos").  For each admin controller, you need to have at least an "edit.php" file in that directory (i.e. /app/views/admin/articles/edit.php).  This file contains a form used for both the /create and /edit routes.

TODO Describe changing the layout and index

## Features

### Enabling CKFinder for file uploads

By default, CKFinder is turned off because a new license must be purchased for every site using it.  Here's how to enable it:

1. Enter the `license_name` and `license_key` in your /app/config/packages/bkwld/decoy/wysiwyg.php config file
2. Tell the wysiwyg.js module to turn on CKFinder.  The easiest way to do that is from your /public/js/admin/main.js like so:

		define('main', function (require) {
			require('decoy/modules/wysiwyg').config.allowUploads();
		});
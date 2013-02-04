<?php namespace Decoy;

// Imports
use BKWLD\Utils\File;
use Laravel\Request;
use Laravel\Database\Eloquent\Model as Eloquent;
use Laravel\Database as DB;
use Laravel\Input;
use Laravel\Config;
use Laravel\Event;
use Laravel\Log;
use Croppa;

abstract class Base_Model extends Eloquent {
	
	//---------------------------------------------------------------------------
	// Overrideable properties
	//---------------------------------------------------------------------------
	
	// Auto populate timestamps
	static public $timestamps = true;
	
	// This should be overridden by Models to store the array of their 
	// Laravel validation rules
	static public $rules = array();
	
	// This is designed to be overridden to store the DB column name that
	// should be used as the source for titles.  Used in the title() function
	// and in autocompletes.
	static public $TITLE_COLUMN;
	
	//---------------------------------------------------------------------------
	// Model event callbacks
	//---------------------------------------------------------------------------
	// Setup listeners for all of Laravel's built in events that fire our no-op
	// callbacks.
	// 
	// These are defined by overriding the methods that fire them instead of in the
	// constructor so that ALL of instances of a model don't start listening to these
	// events.  For instance, if an instance was created to do some operation without
	// first getting hydrated with data, it doesn't need to handle a save event
	
	// Override the events that happen on save
	public function save() {
		$events = array('saving', 'updated', 'created', 'saved');
		foreach($events as $event) {
			Event::listen('eloquent.'.$event.': '.get_class($this), array($this, 'on_'.$event));
		}
		parent::save();
	}
	
	// Override the events that happen on save
	public function delete() {
		$events = array('deleting', 'deleted');
		foreach($events as $event) {
			Event::listen('eloquent.'.$event.': '.get_class($this), array($this, 'on_'.$event));
		}
		parent::delete();
	}
	
	// No-op callbacks.  They all get passed a reference to the object that fired
	// the event.  They have to be defined as public because they are invoked externally, 
	// from Laravel's event system.
	public function on_saving() {}
	public function on_updated() {}
	public function on_created() {}
	public function on_saved() {}
	public function on_deleting() {}
	public function on_deleted() {}
	
		
	//---------------------------------------------------------------------------
	// Overrideable methods
	//---------------------------------------------------------------------------
	
	// Return the title for the row for the purpose of displaying
	// in admin list views and breadcrumbs.  It looks for columns
	// that are named like common things that would be titles
	public function title() {
		$title = '';
		
		// Add a thumbnail to the title if there is an "image" field
		if (method_exists($this, 'image') && $this->image()) $title .= '<img src="'.Croppa::url($this->image(), 40, 40).'"/> ';
		elseif (!method_exists($this, 'image') && !empty($this->image)) $title .= '<img src="'.Croppa::url($this->image, 40, 40).'"/> ';
		
		// Convert to an array so I can test for the presence of values.
		// As an object, it would throw exceptions
		$row = $this->to_array();
		if (!empty(static::$TITLE_COLUMN)) $title .=  $row[static::$TITLE_COLUMN];
		else if (isset($row['name'])) $title .=  $row['name']; // Name before title to cover the case of people with job titles
		else if (isset($row['title'])) $title .= $row['title'];
		else if (Request::route()->controller_action == 'edit')  $title .= 'Edit';
		
		// Return the finished title
		return $title;

	}
	
	// Save out an image or file given the field name.  They are saved
	// to the directory specified in the bundle config
	static public function save_image($input_name = 'image') { return self::save_file($input_name); }
	static public function save_file($input_name = 'file') {
		$path = File::organize_uploaded_file(Input::file($input_name), Config::get('decoy::decoy.upload_dir'));
		$path = File::public_path($path);
		return $path;
	}
	
	// Many models will override this to create custom methods for getting
	// a list of rows
	static public function ordered() {
		return static::order_by(self::table_name().'.created_at', 'desc');
	}
	
	// Get an ordered list of only rows that are marked as visible
	static public function ordered_and_visible() {
		return static::ordered()->where('visible', '=', '1');
	}
	
	//---------------------------------------------------------------------------
	// Utility methods
	//---------------------------------------------------------------------------
	
	// Randomize the results in the DB.  This shouldn't be used for large datasets
	// cause it's not very performant
	static public function randomize() {
		return static::order_by(DB::raw('RAND()'));
	}
	
	// Find by the slug.  Like "find()" but use the slug column instead
	static public function find_slug($slug) {
		return static::where(self::table_name().'.slug', '=', $slug)->first();
	}
	
	// Figure out the current table name but allow it to be called statically
	static protected function table_name() {
		$model = get_called_class();
		$model = new $model;
		return $model->table();
	}
	
	// The pivot_id may be accessible at $this->pivot->id if the result was fetched
	// through a relationship OR it may be named pivot_id out of convention (something
	// currently done in Decoy_Base_Controller->get_index_child()).  This function
	// checks for either
	public function pivot_id() {
		if (!empty($this->pivot->id)) return $this->pivot->id;
		else if (!empty($this->pivot_id)) return $this->pivot_id;
		else return null;
	}
	
}
# Plugin Options Framework

Plugin options framework is a small PHP library designed to simplify WordPress plugin options page
development. You don't need to call `register_setting` and care about adding your own options page 
anymore. So... enough, let's see a real code example in your plugin's code:

## Usage

	// myplugin.php file
	add_action('init', 'my_init');
	function my_init(){
		global $options;
		$fields = array(
			array('type' => 'text', 'name' => 'text_field', 'title' => 'Text setting field', 'default' => 'default text'), // Added a text field
			array('type' => 'tab', 'title' => 'Other options tab'), //Started a new tab
			array('type' => 'section', 'title' => 'some settings section'), //Started a new section at this tab
			array('type' => 'color', 'name' => 'background', 'title' => 'Background color', 
				'default' => '#444',
				'legend' => 'A legend shown at the right side') // Added a background color
			);
			
		$options = new Plugin_Options_Framework_0_2(dirname(__FILE__), 
			$fields, 
			array('page_title' => 'My plugin settings')
		); // Created an instance of the options framework
		
		
		$background_color = $options->get_option('background');//Read background color option
	}
	
The code above will add "My plugin settings" options page to WordPress' "Settings" admin section, and
will build a tabbed options page for you declaratively. You can use `Plugin_Options_Framework` class
instances to read or write plugin settings with `$instance->get_option($name)` / `$instance->set_option($name,$value)`
calls.

##Field types supported
As of version 0.2, next field types are supported:
	
- `text` - Simple text field
- `textarea` - textarea field
- `editor` - WordPress editor, only supported on WordPress 3.3. On earlier versions, works equal
  to textarea.
- `checkbox` - checkbox.
- `radio` - radio buttons set
- `select` - a dropdown field
- `color` - a color picker field
- `custom` - a custom html field

##Adding your own fields
Adding your own custom fields is simple. You need to do the next:

- Inherit a corresponding `Plugin_Options_Framework_Fields_0_2` class:
	
	class My_Options_Fields extends Plugin_Options_Framework_Fields_0_2
	
- Add a method that will implement a field type you need. The method should receive one argument -
	`$field` hash containing options for rendering the field.

	function typography($field){
		echo ('here is typography field HTML output');
	}

- Pass your class name as `fields` option to the `Plugin_Options_Framework` instance when creating it:
	
	$fields_array = array(array('type' => 'typography', 'name' => 'my_typo_field'), ...);
	$pof = new Plugin_Options_Framework_0_2(dirname(__FILE__),
		$fields_array, // field definitions array, see above
		array('fields' => 'My_Options_Fields') // a name of the class you created
	);
	

##Compatibility
Currently, Plugin Options Framework is at version 0.2. But what will happen when 0.3, 0.4 and 3.0
will be released and different plugins will use different versions? The answer is simple. Every new
version released will have a version number inside the classname, like `Plugin_Options_Framework_0_4`
and every plugin will only instantiate a version it is built on, for example:

	//my-superior-plugin.php
	function my_init(){
		....
		$options = new Plugin_Options_Framework_0_2(....); // creating an instance of old good 0.2 version;
	}
	
	
	//an-another-plugin.php
	function another_init(){
		$options = new Plugin_Options_Framework_3_2(...); // creating an instance of bleeding edge version.
	}



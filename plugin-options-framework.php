<?php /* 
WordPress plugin options framework
Copyright: Nikolay Karev, http://karevn.com
Version: 0.2
*/
require('vendor/html-helpers.php');
if (!class_exists('Plugin_Options_Framework_0_2')){
	class Plugin_Options_Framework_0_2{
		var $plugin_path;
		var $options;
		var $renderer;
		var $namespace;
		
		function __construct($plugin_path, $fields = array(), $options = array()){
			$this->plugin_path = $plugin_path;
			$this->fields = $fields;
			$this->options = wp_parse_args($options);
			$this->renderer = isset($options['fields']) ? new $options['fields'](&$this) : new Plugin_Options_Framework_Fields_0_2(&$this);
			$this->namespace = isset($options['namespace']) ? $options['namespace'] : pathinfo($this->plugin_path, PATHINFO_FILENAME);
			add_action('admin_menu', array(&$this, '_admin_menu'));
			add_action('admin_init', array(&$this, '_admin_init'));
			add_filter('whitelist_options', array(&$this, '_whitelist_options'));
		}
		
		function set_fields($fields){
			$this->fields = $fields;
		}
		
		function _admin_enqueue_scripts(){
			wp_enqueue_script('farbtastic');
			wp_enqueue_script('pof-admin', plugins_url('js/admin.js', __FILE__));
		}
		
		function get_default_storage_hash(){
			$res = array();
			foreach($this->fields as $field){
				if (isset($field['name'])){
					$res[$this->get_storage_name($field['name'])] = isset($field['default']) ? 
						$this->addslashes_deep($field['default']) : null;
				}
			}
			return $res;
		}
		
		function addslashes_deep($value) {
			if ( is_array($value) ) {
				$value = array_map('stripslashes_deep', $value);
			} elseif ( is_object($value) ) {
				$vars = get_object_vars( $value );
				foreach ($vars as $key=>$data) {
					$value->{$key} = stripslashes_deep( $data );
				}
			} else {
				$value = addslashes($value);
			}
			return $value;
		}
		
		function _whitelist_options($options){
			global $this_file, $parent_file, $action;
			if ($this_file != 'options.php' || $parent_file != 'options-general.php' ||
			 	(isset($_POST['option_page']) && $_POST['option_page'] != 'aioe') || $action != 'update')
				return $options;
			$_POST = array_merge($_POST, $this->get_default_storage_hash());
			return $options;
		}
		
		function _admin_enqueue_styles(){
			wp_enqueue_style('farbtastic');
			wp_enqueue_style('pof-admin-style', plugins_url('css/admin-style.css', __FILE__));
		}
		
		function _admin_init(){
			foreach($this->fields as $field){
				if (isset($field['name']))
					register_setting($this->namespace, $this->get_storage_name($field['name']), 
						isset($field['sanitize']) ? $field['sanitize'] : array(&$this, '_return_same'));
			}
		}
		
		function _return_same($val){
			return $val;
		}
		
		function extract_plugin_name(){
			$data = get_plugin_data(trailingslashit(WP_PLUGIN_DIR) . $this->plugin_path);
			return $data['Name'];
		}
		
		function page_title(){
			return isset($this->options['page_title']) ? $this->options['page_title'] : $this->extract_plugin_name() . " " . __('Settings');
		}
		
		function _admin_menu(){
			$menu_title = isset($this->options['menu_title']) ? $this->options['menu_title'] : $this->extract_plugin_name();
			add_options_page($this->page_title(), $menu_title, 'manage_options', 
				$this->namespace, array(&$this, 'render'));
			add_action('admin_print_styles-settings_page_' .$this->namespace, array(&$this, '_admin_enqueue_styles'));
			add_action('admin_print_scripts-settings_page_' .$this->namespace, array(&$this, '_admin_enqueue_scripts'));
		}
		
		function get_default_options(){
			$defaults = array();
			foreach($this->fields as $field){
				if (isset($field['name']) && isset($field['default'])){
					$defaults[$field['name']] = $field['default'];
				}
			}
			return $defaults;
		}
		
		function get_option_default($name){
			foreach($this->fields as $field){
				if (isset($field['name']) && isset($field['default']) && $field['name'] == $name){
					return $field['default'];
				}
			}
			return null;
		}
		
		function get_storage_name($name){
			return $this->namespace . '__' . $name;
		}
		
		function get_option($name){
			return get_option($this->get_storage_name($name), $this->get_option_default($name));
		}
		
		function set_option($name, $value){
			update_option($this->get_storage_name($name), $value);
		}
		
		function render(){
			?>
			<div class="wrap">
				<?php screen_icon( 'plugins' ); ?>
				<h2><?php echo $this->page_title() ?></h2>
				<?php if (count($this->fields)): ?>
						<?php $this->renderer->render(&$this) ?>
				<?php else: ?>
					<div class="error">
						<p><?php _e('Please provide some settings fields when creating an options framework instance')?></p>
					</div>
				<?php endif ?>
			</div><?php
		}
	}
}

if (!class_exists('Plugin_Options_Framework_Fields_0_2')){
	class Plugin_Options_Framework_Fields_0_2{
		var $pof;
		var $h;
		function __construct(&$pof){
			$this->pof = $pof;
			$this->h = new HTML_Helpers_0_4();
		}
		
		function render(){
			$fields = $this->pof->fields;
			if (count($this->get_tabs($fields)) && isset($fields[0]) && $fields[0]['type'] != 'tab'){
				//make sure the first tab is defined if there are multiple tabs
					$fields = array_merge(array(array('type' => 'tab', 'title' => __('General'))), $fields);
			}
			echo '<div id="pof">';
			$this->render_tabs($fields);
			$section_index = 0;
			$tab_index = 0;
			echo '<form action="options.php" method="post" id="pof-form">';
			settings_fields($this->pof->namespace);
			if (count($this->get_tabs($fields))){
				echo "\n<div class=\"tabs\" id=\"pof-tabs\">\n";
			}
			foreach($fields as $field){
				switch($field['type']){
					case 'tab':
						if ($section_index){
							echo "\n<div style='clear: both'></div></div></div></div></div>\n";
						}
						if ($tab_index) echo "\n<div style='clear: both'></div></div>\n"; // close current tab
						echo ("\n<div class=\"tab" . ($tab_index ? ' tab-hidden' : '') . "\" id=\"" . esc_attr($this->tab_id($tab_index, $field))  . "\">\r");
						$tab_index++;
						$section_index = 0;
						break;
					case 'section':
						if ($section_index) echo "\n<div style='clear: both'></div></div></div></div></div>\n"; //close current section
						echo "<div class=\"metabox-holder\"" . (isset($field['show_if']) ? 'data-show_if="' . $this->input_name($field['show_if']) . '" ' : '') . ">\n<div class=\"postbox\"><div class=\"group\">";
						echo '<h3>' . $field['title'] . "</h3>\n<div class=\"inside\">";
						$section_index++;
						break;
					case 'section_break':
						if ($section_index) {
							echo "\n<div style='clear: both'></div></div></div></div></div>\n"; //close current section
							$section_index = 0;
						}
						break;
					default:
						$this->render_field($field);
				}
			}
			if ($section_index){
				echo("<div style='clear: both'></div>");
				echo "\n</div></div></div></div>\n";
			}
			
			if ($tab_index){
				echo "\n<div style='clear: both'></div></div></div>\n"; // tabs
			}
			?>
			<div id="pof-submit">
				<input type="submit" class="button-primary" name="update" value="<?php esc_attr_e( 'Save Options' ); ?>" />
				<input type="submit" class="reset-button button-secondary" name="reset" value="<?php esc_attr_e( 'Restore Defaults' ); ?>" onclick="return confirm( '<?php print esc_js( __( 'Click OK to reset. All the settings will be reset to defaults!' ) ); ?>' );" />
				<div class="clear"></div>
			</div>
			</form>
			</div><?php
		}
		
		function input_name($name){
			return $this->pof->get_storage_name($name);
		}
		
		function render_field($field){
			echo "<div class=\"field field-{$field['type']}" . (isset($field['class']) ? " " . $field['class'] : '') . "\" " . (isset($field['name']) ? "id=\"field-{$field['name']}\" " : '') . (isset($field['show_if']) ? 'data-show_if="' . $this->input_name($field['show_if']) . '" ' : '') . ">\n"; 
			echo "<h4 class=\"heading field-title\">{$field['title']}</h4>\n";
			echo "<div class=\"option\">\n";
			$method = $field['type'];
			$this->$method($field);
			echo "</div>\n";
			if (isset($field['legend'])){
				echo "<div class=\"legend\">{$field['legend']}</div>";
			}
			echo "</div>";
		}
		
		function checkbox($field){
			$classes = array('checkbox');
			if (isset($field['class']))
				$classes []= $field['class'];
			$this->h->checkbox($this->input_name($field['name']), 1, 
				$this->pof->get_option($field['name']), array('class' => $classes)
				);
			if (isset($field['label']))
				$this->h->label($field['label'], array('for' => $this->input_name($field['name'])));
		}
		
		function checkboxes($field){
			$classes = array('checkboxes');
			
		}
		
		function select($field){
			$this->h->select($this->input_name($field['name']), $field['values'], 
				$this->pof->get_option($field['name']));
		}
		
		function color($field){
			?>
			<div class="pof-color-picker">
				<?php $this->h->text_field($this->input_name($field['name']), $this->pof->get_option($field['name'])); ?>
				<input type="button" class="pickcolor button hide-if-no-js" value="<?php esc_attr_e( 'Select a Color'); ?>" />
				<div class="picker" id="picker-<?php echo sanitize_title_with_dashes($field['name']) ?>" style="z-index: 100; background:#eee; border:1px solid #ccc; position:absolute; display:none;"></div>
			</div>
			<?php
		}
		
		function custom($field){
			echo $field['html'];
		}
		
		function text($field){
			echo '<input id="' . $field['name'] . '" class="text' . (isset($field['class']) ? " " .
				$field['class'] : '') . '" type="text" name="' . $this->input_name($field['name']) .
				'" value="'. esc_attr($this->pof->get_option($field['name'])) .'" />';
		}
		
		function password($field){
			echo '<input id="' . $field['name'] . '" class="password' . (isset($field['class']) ? " " .
				$field['class'] : '') . '" type="password" name="' . $this->input_name($field['name']) .
				'" value="'. esc_attr($this->pof->get_option($field['name'])) .'" />';
		}
		
		function editor($field){
			if (function_exists('wp_editor')){
				wp_editor( $this->pof->get_option($field['name']), $this->input_name($field['name']), array( 'media_buttons' => true ) );
			} else {
				$this->textarea($field);
				echo "<small>";
				_e('Upgrade to WordPress 3.3 or later to enable WYSIWYG editor');
				echo "</small>\n";
			}
		}
		
		function radio($field){
			foreach($field['options'] as $text => $value){
				echo "<div class=\"radio-wrapper\">\n";
				echo '<input id="' . $field['name'] . "_". esc_attr(sanitize_title_with_dashes($value)) . '" class="radio' . (isset($field['class']) ? " " .
					$field['class'] : '') . '" type="radio" name="' . $this->input_name($field['name']) . 
					'" '. checked( $this->pof->get_option($field['name']), $value, false) .' value="' . esc_attr($value) . '" />';
					echo '<label class="title" for="'. $field['name'] . "_" . esc_attr(sanitize_title_with_dashes($value)) . '">' . $text . '</label>';
				echo "</div>\n";
			}
		}
		
		function textarea($field){
			$classes = array('textarea');
			if (isset($field['class']))
				$classes []= $field['class'];
			$rows = isset($field['rows']) ? $field['rows'] : get_option('default_post_edit_rows');
			$this->h->textarea($this->input_name($field['name']),
				$this->pof->get_option($field['name']), array(
					'class' => $classes,
					'rows' => $rows
				)
			);
		}
		
		function tab_id($tab_index, $field){
			return isset($field['id']) ? $field['id'] : 'nav-tab-' . $tab_index++;
		}
		
		function get_tabs($fields){
			$tab_index = 0;
			$tabs = array();
			foreach($fields as $field){
				if ($field['type'] == 'tab'){
					$tabs[$this->tab_id($tab_index++, $field)] = $field['title'];
				}
			}
			return $tabs;
		}
		
		function render_tabs($fields){
			$tabs = $this->get_tabs($fields);
			if (count($tabs)){
				?>
				<h2 class="nav-tab-wrapper" id="pof-tabs-nav">
					<?php $i = 0 ?>
					<?php foreach($tabs as $id => $title):?>
						<a href="#<?php echo $id ?>" class="nav-tab <?php echo !$i++ ? 'nav-tab-active' : '' ?>"><?php echo esc_html($title) ?></a>
					<?php endforeach ?>
				</h2>
				<?php
			}
		}
	}
}

?>
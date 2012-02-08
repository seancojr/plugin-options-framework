<?php
/*
Plugin Name: HTML Helpers
Plugin URI: http://wordpress.org/extend/plugins/html-helpers/
Description: Simple HTML rendering API for WordPress
Version: 0.4-beta
Author: Nikolay Karev
Author URI: http://karevn.com
*/

/*
Copyright (C) Nikolay Karev, karev.n@gmail.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


if (!class_exists('HTML_Helpers_0_4')){
	class HTML_Helpers_0_4{
		/* HTML Generator functions */
		function _tag($tag, $attributes = array(), $content = null, $close = null){
			if (!is_array($attributes)){
				$close = $content;
				$content = $attributes;
				$attributes = array();
			}
			if (is_bool($content)){
				$close = $content;
				$content = null;
			}
			if ($content === null && $close === null)
				$close = true;
			else if ($close === null)
				$close = $content != null;
			else
				$close = $close === null ? true : $close;
			$parts = explode(' ', $tag, 2);
			$tag = $parts[0];
			$additional_attrs = count($parts) > 1 ? $parts[1] : null;
			return $this->_tag_start($tag . ($additional_attrs ? " " . $additional_attrs : ''), $attributes) . 
				$content . 
				($close ? $this->_tag_end($tag) : '');
		}

		function tag($tag, $attributes = array(), $content = null, $close = null){
			echo $this->_tag($tag, $attributes, $content, $close);
		}

		function _tag_start($tag, $attributes = array()){
			$attr_string = '';
			foreach( $attributes as $key => $val)
				$attr_string .= " $key=\"" . esc_attr(is_array($val) ? join(' ', $val) : $val) . "\"";
			return "<$tag $attr_string>";
		}

		function tag_start($tag, $attributes = array()){
			echo $this->_tag_start($tag, $attributes);
		}

		function _tag_end($tag){
			return "</{$tag}>";
		}
	
		function tag_end($tag){
			echo $this->_tag_end($tag);
		}

		function _img($src, $alt = null, $attributes = array()){
			if ('' !== $alt && !$alt){
				$alt = ucwords(pathinfo($src, PATHINFO_FILENAME));
			}
			if ($alt) $attributes['alt'] = $alt;
			if (!pathinfo($src, PATHINFO_EXTENSION))
				$src .= ".png";
			$attributes['src'] = preg_match('/\//', $src) ? $src : get_bloginfo('template_url') . "/images/$src";
			return $this->_tag('img', $attributes, null, false);
		}

		function img($src, $alt = null, $attributes = array()){
			echo $this->_img($src, $alt, $attributes);
		}

		function _link_to($text, $url = '', $attributes = array()){
			$attributes['href'] = $url;
			return $this->_tag('a', $attributes, $text, true);
		}

		function link_to($text, $url = '', $attributes = array()){
			echo $this->_link_to($text, $url, $attributes);
		}

		function _select($name, $values = array(), $selected = null, $attributes = array(), 
		$options = array()){
			$multi = isset($attributes['multiple']) && $attributes['multiple'];
			if ($multi)
				$attributes['multiple'] = "multiple";
			$res = '';
			if (isset($options['empty']) && $options['empty']){
				$res .= $this->_tag('option', array('value' => null), $options['empty']);
			}
			foreach ($values as $arr){
				$text = $arr[0];
				if (isset($arr[1]))
					$value = $arr[1];
				else unset($value);
				$attrs = array();
				if ((!$multi && ((isset($value) && $value == $selected) || (!isset($value) && $selected == null) || $text === $selected)) ||
					($multi && ($value && in_array($value, $selected) || in_array($text, $selected)))) 
					$attrs['selected'] = 'selected';
				if (isset($value))
					$attrs['value'] = $value;
				$res .= $this->_tag("option", $attrs, $text);      
			}
			$attributes['name'] = $name;
			if (empty($attributes['id']))
				$attributes['id'] = sanitize_title_with_dashes($attributes['name']);
			return $this->_tag('select', $attributes, false) . $res . $this->_tag_end('select');
		}

		function select($name, $values = array(), $selected = null, $attributes = array(),
			$options = array()){
			echo $this->_select($name, $values, $selected, $attributes, $options);
		}

		function _text_field($name, $value = null, $attributes = array()){
			$attributes['value'] = $value;
			$attributes['name'] = $name;
			$attributes['type'] = 'text';
			if (empty($attributes['id']))
				$attributes['id'] = sanitize_title_with_dashes($attributes['name']);
			return $this->_tag('input', $attributes, null, false);
		}

		function text_field($name, $value = null, $attributes = array()){
			echo $this->_text_field($name, $value, $attributes);
		}

		function _password_field($name, $value = null, $attributes = array()){
			$attributes['value'] = $value;
			$attributes['name'] = $name;
			$attributes['type'] = 'password';
			if (empty($attributes['id']))
				$attributes['id'] = sanitize_title_with_dashes($attributes['name']);
			return $this->_tag('input', $attributes, null, false);
		}

		function password_field($name, $value = null, $attributes = array()){
			echo $this->_password_field($name, $value, $attributes);
		}

		function _hidden_field($name, $value = null, $attributes = array()){
			$attributes['value'] = $value;
			$attributes['name'] = $name;
			$attributes['type'] = 'hidden';
			return $this->_tag('input', $attributes, null, false);
		}

		function hidden_field($name, $value = null, $attributes = array()){
			echo $this->_hidden_field($name, $value, $attributes);
		}

		function _checkbox($name, $value = null, $checked = false, $attributes = array()){
			if ($checked)
				$attributes['checked'] = 'checked';
			$attributes['type'] = 'checkbox';
			$attributes = array_merge($attributes, array('name' => $name, 'value' => $value));
			if (empty($attributes['id']))
				$attributes['id'] = sanitize_title_with_dashes($attributes['name']);
			return $this->_tag('input', $attributes, null, false);
		}

		function checkbox($name, $value = null, $checked = false, $attributes = array()){
			echo $this->_checkbox($name, $value, $checked, $attributes);
		}
	

		function _radiobutton($name, $value = null, $checked = false, $attributes = array()){
			if ($checked)
				$attributes['checked'] = 'checked';
			$attributes['type'] = 'radio';
			$attributes = array_merge($attributes, array('name' => $name, 'value' => $value));
			if (empty($attributes['id']))
				$attributes['id'] = sanitize_title_with_dashes($attributes['name']);
			return $this->_tag('input', $attributes);
		}

		function radiobutton($name, $value = null, $checked = false, $attributes = array()){
			echo $this->_radiobutton($name, $value, $checked, $attributes);
		}

		function _textarea($name, $value, $attributes = array(), $escape = true){
			$attributes['name'] = $name;
			if (empty($attributes['id']))
				$attributes['id'] = sanitize_title_with_dashes($attributes['name']);
			return $this->_tag('textarea', $attributes, $escape ? esc_html($value) : $value, true);
		}
	
		function textarea($name, $value = '', $attributes = array(), $escape = true){
			echo $this->_textarea($name, $value, $attributes, $escape);
		}

		function _label($content, $attrs = array()){
			return $this->_tag('label', $attrs, $content);
		}

		function label($content, $attrs = array()){
			echo $this->_label($content, $attrs);
		}

		function _cycle($array, $context = null){
			global $cycles;
			if (!$context)
				$context = 'default';
			if (!isset($cycles[$context]))
				$cycles[$context] = 0;
			return $array[$cycles[$context]++ % count($array)];
		}
	
		function cycle($array, $context = null){
			echo $this->_cycle($array, $context);
		}

		function reset_cycle($context = 'default'){
			global $cycles;
			if (!$cycles) return;
			$cycles[$context] = 0;
		}

		/* Helper functions */

		function collection2hastag($collection, $key_field, $value_field){
			$result = array();
			foreach($collection as $obj){
				$result[$obj->$key_field] = $obj->$value_field;
			}
			return $result;
		}

		function collection2options($collection, $key_field, $value_field, $empty = null){
			$result = array();
			if ($empty){
				$result []= array($empty, '');
			}
			foreach($collection as $obj){
				$result []= array($obj->$value_field, $obj->$key_field);
			}
			return $result;
		}

		function hash2options($hash, $empty = null){
			$options = array();
			if ($empty){
				$options []= array($empty, '');
			}
			foreach($hash as $key => $value){
				$options []= array($value, $key);
			}
			return $options;
		}

		function array2options($array, $empty = null){
			$options = array();
			if ($empty){
				$options []= array($empty, '');
			}
			foreach($array as $item){
				$options []= array($item, null);
			}
			return $options;
		}
	}
}

if (!function_exists('get_the_post_meta')){
	
	/* "The" functions */
	function get_the_post_meta($names, $options = array()){
		global $wp_query, $id;
		if (!isset($options['separator']) && is_array($names)) $options['separator'] = ' ';
		if (!is_array($names)) $options['separator'] = '';
		if (is_string($names)) $names = array($names);
		$values = array();
		foreach($names as $name) $values []= get_post_meta($id, $name, true);
		return join($options['separator'], apply_filters('get_the_post_meta_values', $values, $options));
	}

	function the_post_meta($names, $options = array()){
		$meta = get_the_post_meta($names, $options);
		echo $meta;
		if (isset($options['break_after']) && $options['break_after'] && !empty($meta)) echo "<br>";
	}

	function the_post_meta_tag($name, $tag = 'div', $attributes = array(), $options = array()){
		$meta = get_the_post_meta($name, $options);
		if (!empty($meta)){
			echo _tag($tag, $attributes, apply_filters('the_content', get_the_post_meta($name, $options)));
		}
	}

	function the_post_meta_content($name, $options = array()){
		echo apply_filters('the_content', get_the_post_meta($name, $options));
	}

	function has_the_post_meta($name){
		$meta = get_the_post_meta($name);
		return !empty($meta);
	}

	function wp_enqueue_conditional_style($id, $path, $condition) {
		global $wp_styles;
		wp_enqueue_style($id, $path);
		$wp_styles->add_data($id, "conditional", $condition);
	}
}
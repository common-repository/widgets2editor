<?php

class Tiny_Template extends WP_Widget {

	function Tiny_Template() {
		$widget_ops = array('classname' => 'widget_tiny_template', 'description' => __('HTML code used as Tiny Editable templates'));
		$control_ops = array('width' => 400, 'height' => 350);
		$this->WP_Widget('templates', __('TinyMCE Template'), $widget_ops, $control_ops);
	}

	function widgets_init() {
		register_widget('Tiny_Template');
	} # widgets_init()
	
	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['title']) ? 'No Title' : $instance['title']);
		$text = apply_filters( 'widget_tiny_template', $instance['text'] );
		echo $before_widget;
		echo ($before_title == '%BEG_OF_TITLE%'?$before_title.$title.$after_title:'');
		echo $text;
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if ( current_user_can('unfiltered_html') )
			$instance['text'] =  stripslashes($new_instance['text']);
		else
			$instance['text'] = stripslashes(wp_filter_post_kses( $new_instance['text'] ));
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '' ) );
		$title = strip_tags($instance['title']);
		$text = format_to_edit($instance['text']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>
<?php
	}
}
add_action('widgets_init', array('Tiny_Template', 'widgets_init'));
?>
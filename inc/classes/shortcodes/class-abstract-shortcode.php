<?php

abstract class PSource_Support_Shortcode {
	public abstract function render( $atts );

	public function end() {
		echo '</div><div style="clear:both">';
		return ob_get_clean();
	}

	public function start() {
		echo '<div id="support-system">';
		ob_start();
	}

}
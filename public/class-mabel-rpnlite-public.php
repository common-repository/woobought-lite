<?php
if(!defined('ABSPATH')){die;}

class Mabel_RPNLite_Public {
	private $options;
	private $defaults;
	private $settingskey;
	
	public function __construct($defaults,$options,$settingskey ) {
		$this->defaults = $defaults;
		$this->options = $options;
		$this->settingskey = $settingskey;
	}
	
	// Register frontend CSS
	public function enqueueStyles(){
		wp_enqueue_style( MABEL_RPN_LITE_SLUG, MABEL_RPN_LITE_URL . '/public/css/mabel-rpnlite-public.min.css', array(), MABEL_RPN_LITE_VERSION, 'all' );
	}
	
	// Register frontend js.
	public function enqueueScripts(){
		wp_enqueue_script(MABEL_RPN_LITE_SLUG, MABEL_RPN_LITE_URL . '/public/js/mabel-rpnlite-public.min.js',array('jquery'), MABEL_RPN_LITE_VERSION, true);
		
		// Add settings to frontend javascript
		$translation_array = array(
			'disable' => $this->t( 'Disable'),
			'message' => $this->valueOrTranslateDefault('text'),
            'someone' => $this->t('someone')
		);

		$alloptions = $this->mergeOptions();
		$alloptions['ajaxurl'] =  admin_url('admin-ajax.php');
		$alloptions['rpntranslations'] = $translation_array;
		$alloptions['mabelrpnrun'] = (string)(1);

		wp_localize_script(MABEL_RPN_LITE_SLUG,'mabelrpnsettings', $alloptions);

	}
	
	// Private Helpers
	private function getOption($id){
		return (isset($this->options[$id]) && !empty($this->options[$id]))?$this->options[$id]:$this->defaults[$id];
	}
	
	private function t($key){
		return __($key,MABEL_RPN_LITE_SLUG);
	}
	
	// Merge options with defaults;
	private function mergeOptions(){
		return array_merge($this->defaults,$this->options);
	}
	
	private function valueOrTranslateDefault($key){
		return (empty($this->options[$key])? $this->t($this->defaults[$key]) : $this->options[$key]);
	}
}
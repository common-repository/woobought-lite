<?php
if(!defined('ABSPATH')){die;}

class Mabel_RPNLite_Admin{
	private $options;
	private $defaults;
	private $settingskey;
	private $imagebaseurl;
	
	public function __construct($defaults,$options,$settingskey){
		$this->defaults = $defaults;
		$this->imagebaseurl = MABEL_RPN_LITE_URL.'/admin/img';
		$this->options = $options;
		$this->settingskey = $settingskey;
	}

	public function getNewestPurchasedProducts(){
		try {
			$cachekey = 'mabel-rpnlite-cached-products';
			$products = get_transient( $cachekey );
			if ( ! $products ) {
				global $wpdb;

				$args = array(
					'post_type' => 'shop_order',
					'post_status' => 'wc-on-hold, wc-completed, wc-pending, wc-processing',
					'orderby' => 'ID',
					'order' => 'DESC',
					'posts_per_page' => $this->getOption( 'limit' ),
					'date_query' => array(
						'after' => date('Y-m-d', strtotime('-'.$this->getOption( 'notificationage' ).' days'))
					)
				);

				$posts = get_posts($args);
				$products = array();
				$version_is_before_3 = version_compare( WC()->version, '3.0', '<')?true:false;

				foreach($posts as $post) {

					$order = new WC_Order($post->ID);
					$order_items = $order->get_items();

					if(!empty($order_items)) {
						$first_item = array_values($order_items)[0];
						$product_id = $first_item['product_id'];
						$product = wc_get_product($product_id);
						if(!empty($product)) {
							preg_match( '/src="(.*?)"/', $product->get_image($this->getOption('imagesize')), $imgurl);

							$p = array(
								'id'    => $first_item['order_id'],
								'name'  => $product->get_title(),
								'url'   => $product->get_permalink(),
								'date'  => $post->post_date_gmt,
								'image' => count($imgurl) === 2 ? $imgurl[1] : null,
								'price' => $this->formatProductPrice($version_is_before_3 ? $product->get_display_price() : wc_get_price_to_display($product) ),
								'buyer' => $this->createBuyerArray($order)
							);

							$p = apply_filters('woobought_product_data',$p, $product);

							array_push( $products, $p);
						}
					}
				}

				set_transient( $cachekey, $products, 60); // Cache the results for 1 minute
			}
			echo( json_encode( $products ) );
			wp_die();
		}catch(Exception $e){
			wp_die();
		}
	}

	private function formatProductPrice($price) {
        if(empty($price))
            $price = 0;

        return sprintf(
            get_woocommerce_price_format(),
            get_woocommerce_currency_symbol(),
            number_format($price,wc_get_price_decimals(),wc_get_price_decimal_separator(),wc_get_price_thousand_separator())
        );	}

	private function createBuyerArray($order){
		$address = $order->get_address('billing');
		if(!isset($address['city']) || strlen($address['city']) == 0 )
			$address = $order->get_address('shipping');

		$buyer = array(
			'name' => isset($address['first_name']) && strlen($address['first_name']) > 0 ? ucfirst($address['first_name']) : $this->t('someone'),
			'city' => isset($address['city']) && strlen($address['city']) > 0 ? ucfirst($address['city']) : 'N/A',
			'state' => isset($address['state']) && strlen($address['state']) > 0 ? ucfirst($address['state']) : 'N/A',
			'country' =>  isset($address['country']) && strlen($address['country']) > 0 ? WC()->countries->countries[$address['country']] : 'N/A',
		);

		return $buyer;
	}

	public function enqueueStyles() {
		if(isset($_GET['page']) && $_GET['page'] == MABEL_RPN_LITE_SLUG){
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( MABEL_RPN_LITE_SLUG, MABEL_RPN_LITE_URL.'/admin/css/mabel-rpnlite-admin.min.css', array(), MABEL_RPN_LITE_VERSION, 'all' );
		}
	}
	
	// Register js for the admin area.
	public function enqueueScripts() {
		if(isset($_GET['page']) && $_GET['page'] == MABEL_RPN_LITE_SLUG){
			wp_enqueue_script(MABEL_RPN_LITE_SLUG, MABEL_RPN_LITE_URL . '/admin/js/mabel-rpnlite-admin.min.js', array('jquery','wp-color-picker'), MABEL_RPN_LITE_VERSION, false);
		}
	}
	
	// Add a menu item in Dashboard>settings if you have the 'manage options' right and draw its options page via settings.php.
	public function addSettingsMenu(){
		add_options_page('Plugin '.$this->t('Settings'), MABEL_RPN_LITE_NAME, 'manage_options', MABEL_RPN_LITE_SLUG, array($this,'drawSettings') );
	}

	public function drawSettings(){
        include_once('partials/settings.php');
    }

    public function addSettingsLinkToPlugin( $links ) {
		$pro_link = array('<a style="color:green;" href="https://www.studiowombat.com/plugin/woobought/">Go Pro!</a>');
		$settings_link = array('<a href="' . admin_url( 'options-general.php?page=' . MABEL_RPN_LITE_SLUG ) . '">' . $this->t('Settings'). '</a>');
		return array_merge(  $settings_link,$pro_link, $links );
	}
	
	// Register sections & settings
	public function initSettings(){
		register_setting( 'box-options-rpn-lite', $this->settingskey);
		add_settings_section("section", "", null,'box-options-rpn-lite');
		
		add_settings_field('boxlayout',$this->t('Layout'),array($this,'displayBoxLayout'),'box-options-rpn-lite','section');
		add_settings_field('boxplacement',$this->t('Placement'),array($this,'displayBoxPlacement'),'box-options-rpn-lite','section');
		add_settings_field('boxbgcolor',$this->t('Background color'),array($this,'displayColorpicker'),'box-options-rpn-lite','section',array('id'=>'boxbgcolor'));
		
		register_setting( 'text-options-rpn-lite', $this->settingskey,array($this,'sanitizeInput'));
		add_settings_section("section2", "", null,'text-options-rpn-lite');
		
		add_settings_field('textcolor',$this->t('Color'),array($this,'displayColorpicker'),'text-options-rpn-lite','section2',array('id'=>'textcolor'));
		add_settings_field('text',$this->t('Message'),array($this,'displayTextOption'),'text-options-rpn-lite','section2');
		
		register_setting( 'display-options-rpn-lite', $this->settingskey);
		add_settings_section("section3", "", null,'display-options-rpn-lite');

		$s = $this->t('seconds');
		$ms = $this->t('minutes');
		$ds = $this->t('days');
		
		add_settings_field('notificationage',$this->t("Don't show purchases older than"),array($this,'displayDropDown'),'display-options-rpn-lite','section3',array('id'=>'notificationage','options'=>array('1 '.$this->t('day')=>1,'2 '.$ds=>2,'3 '.$ds=>3,'5 '.$ds=>5,'1 '.$this->t('week')=>7,'10 '.$ds=>10,'2 '.$this->t('weeks')=>14,'3 '.$this->t('weeks')=>21,'4 '.$this->t('weeks')=>28,'8 '.$this->t('weeks')=>56,'12 '.$this->t('weeks')=>84)));
		add_settings_field('notificationdelay',$this->t("Time between notifications") ,array($this,'displayDropDown'),'display-options-rpn-lite','section3',array('id'=>'notificationdelay','options'=>array('10 '.$s=>10,'20 '.$s=>20,'30 '.$s=>30,'40 '.$s=>40,'50 '.$s=>50,'1 '.$this->t('minute')=>60,'1.5 '.$ms=>90,'2 '.$ms=>120),'comment'=>$this->t("The time to wait before showing the next notification")));
	}
	
	// Display functions
	public function displayDropDown($args){
		$options = $args['options'];
		$id = $args['id'];
		$comment = isset($args['comment'])?$args['comment']:null;
		require('partials/fields/dropdownlist.php');
	}
	public function displayExcludeList($args){
		$id = $args['id'];
		$comment = $args['comment'];
		$values = json_decode($this->getOption($id),true);
		$pages = get_pages(array('post_type' => 'page'));
		require('partials/fields/textarea.php');
	}
	public function displayTextOption(){
		require('partials/fields/textoption.php');
	}
	public function displayBoxSize(){
		$selected = $this->getOption('boxsize');
		require('partials/fields/boxsize.php');
	}
	public function displayBoxLayout(){
		$selected = $this->getOption('boxlayout');
		require('partials/fields/boxlayout.php');
	}
	public function displayBoxPlacement(){
		$selected = $this->getOption('boxplacement');
		require('partials/fields/boxplacement.php');
	}
	public function displayColorpicker($args){
		$id = $args['id'];
		$value = $this->getOption($id);
		require('partials/fields/colorpicker.php');
	}
	
	// Sanitizing
	public function sanitizeInput($input){
		$output = array();
		 
		// Loop through each of the incoming options
		foreach( $input as $key => $value ) {
			// Check to see if the current option has a value. If so, process it.
			if(isset($input[$key])){
				$output[$key] = $input[$key]; // sanitize_text_field($input[$key]);
			}
		}
		// Return the array processing any additional functions filtered by this action
		return $output;
	}
	
	// Private Helpers
	private function getOption($id){
		$o = isset($this->options[$id])?$this->options[$id]:$this->defaults[$id];
		return $o;
	}
	
	private function t($key,$echo = false){
		if($echo) _e($key,MABEL_RPN_LITE_SLUG);
		else return __($key,MABEL_RPN_LITE_SLUG);
	}
	
	private function valueortranslatedefault($key){
		return (empty($this->options[$key])? $this->t($this->defaults[$key]) : $this->options[$key]);
	}
}
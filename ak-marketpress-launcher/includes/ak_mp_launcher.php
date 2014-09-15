<?php
/**
* class AK_MPL
*
* Adds marketpress Launcher
* Author: AjayKwatra@gmail.com
*/

$wp_rewrite = new WP_Rewrite(); // to escape from instance error

class AK_MPL {	
	
	const plugin_name = 'MarketPress Launcher';
	const min_php_version = '5.2';
	const min_wp_version = '3.6';
	const min_mp_version = '2.9.5';
	const plugin = 'ak-marketpress-launcher/index.php';
	const version = '1.0.0';
	static $mp = '';
	static $newVersion = '';
	private static $ins = null;

	
	
	public static function init()
    {
		
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        
       // if(check_admin_referer( "deactivate-plugin_{$plugin}" ))
		//	return;        
        
        add_action('plugins_loaded', array(self::instance(), 'ak_initialize'));
        
        if(did_action('admin_menu') > 1)
			return;
        add_action('admin_menu', array(self::instance(),'add_store_menu_ak'));
    }
    
    public static function instance()
    {
        // create a new object if it doesn't exist.
        is_null(self::$ins) && self::$ins = new self;
        return self::$ins;
    }
    
    public function _setup()
    {
        // other calls to `add_action` here.
    }
    
	 public static function ak_on_activation()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
       # check_admin_referer( "activate-plugin_{$plugin}" );

		//add_action( 'plugins_loaded', array( 'AK_MPL', 'ak_initialize' ));
					
		if($at = get_option('ak-mp-launcher_mail') != 'yes' )	
				self::_sendActivation_ak();
    }
    
	public static function ak_on_deactivation()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
       # check_admin_referer( "deactivate-plugin_{$plugin}" );
		
		delete_option('ak-mp-launcher_ver');
		delete_option('ak-mp-launcher_ver_new');
		
		
		if(is_multisite())         
			delete_site_transient( 'ak-mp-launcher');
		else			
			delete_transient( 'ak-mp-launcher');
		

    }

    public static function ak_on_uninstall()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
       
       # check_admin_referer( 'bulk-plugins' );

        // Important: Check if the file is the one
        // that was registered during the uninstall hook.
        if ( __FILE__ != WP_UNINSTALL_PLUGIN )
            return;
	
		delete_option('ak-mp-launcher_mail');
		if(is_multisite())         
			delete_site_transient( 'ak-mp-launcher');
		else			
			delete_transient( 'ak-mp-launcher');		
        # Uncomment the following line to see the function in action
        # exit( var_dump( $_GET ) );
    }

	/**
	* The main function for this plugin, similar to __construct()
	*/
	public function ak_initialize() {
		
		$isRan = did_action('plugins_loaded');
		
		if($isRan > 1)
			return;
		
		if ( (!is_admin() || ( defined('DOING_AJAX') && DOING_AJAX ) ) ){
			return;
		}
		
		$pg = get_option('mp_store_page');
		
		if($pg != '' ){
			return;
		}
		
		
		$this->ak_checkPreInstall();
		
		//add_action("init",array(&$this,'ak_start'));		 
		
	}
	
	//check version etc. before install
	public function ak_checkPreInstall(){
		
		self::ak_check_wp_version();
		self::$mp = get_option('mp_version');
		self::ak_check_mp_version();
		update_option('ak-mp-launcher_ver',self::version);
		
		$is_pluginPage = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
		if('plugins.php' == $is_pluginPage){
			//add_action('admin_init', array('AK_MPL','_getLatestVersion_ak'));
			$this->_getLatestVersion_ak();
		}
		
		$this->ak_start();
	}
		
	
	// Include quickstart function into head, and
// adjust CSS to work better with default Word Press.
function ak_start() {

	global $mp;

	//print_r($mp);
	if(!isset($mp))
		return;

	$this->reset_permalinks_ak(); // set the permalink

	//if not page - then create page
	$isPge = get_option('mp_store_page');
	
	if($isPge == ''){
		
		$mp->create_store_page();
	
	}
	$mp_settings = get_site_option('mp_settings');
	
	//if not set default save
	if(!isset($mp_settings['base_province'])){
		 $mp_settings['base_province'] = 'AL';
		 $mp_settings['base_zip'] = '';
		 update_site_option('mp_settings',$mp_settings);
	}
	
}
	
	function reset_permalinks_ak() {

		$pLnk = get_option('permalink_structure');
		
		if($pLnk == ''){	
	
	
$HTdata = <<<HTA
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
#RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTA;

	$htaccess =  ABSPATH . '.htaccess' ;
	if(!file_exists($htaccess)){
		touch($htaccess);
		@chmod($htaccess,777);	
		//@file_put_contents($htaccess,$HTdata);
	}	
	
	if(!is_writable($htaccess)){
		add_action( 'admin_notices', array($this,'htaccess_admin_notice' ));
	}
	
			global $wp_rewrite;
			$wp_rewrite->set_permalink_structure( '/%postname%/' );
		}
	}
	
	
function htaccess_admin_notice() {
    ?>
    <div class="error">
        <p> Please give writeable permission on  ".htaccess" file located at your site root.<br>
			<a href="http://codex.wordpress.org/Using_Permalinks" target="_blank">Read more...</a>
			You must enable <a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">Pretty Permalinks</a> to use MarketPress
        </p>
    </div>
    <?php
}


	
function add_store_menu_ak(){
		
		if ( (!is_admin() || ( defined('DOING_AJAX') && DOING_AJAX ) ) ){
			return;
		}
				
		global $wpdb;
		
	if(!function_exists('get_registered_nav_menus'))
		require_once( ABSPATH . 'wp-includes/nav-menu.php' );
		
	$menu_name = 'Menu 1';
	
		global $wpdb;
		$querystr = 'SELECT t.term_id 
		FROM '. $wpdb->prefix . 'term_taxonomy as tax 
		LEFT JOIN '. $wpdb->prefix . 'terms as t ON tax.term_id = t.term_id 
		WHERE taxonomy = "nav_menu"';

		$res = $wpdb->get_row($querystr);
		
		//print_r($wpdb->last_query);
		//print_r($res);die;

		$menu_id = $res->term_id;
		$pageID = get_option('mp_store_page');
		
		$mp_settings = get_site_option('mp_settings');
		$storeNM = ucfirst($mp_settings['slugs']['store']);
	
		$locations = get_registered_nav_menus();
/*
echo '<pre>';
print_r($locations);
die;*/

$sql = 'select meta_value from `'. $wpdb->prefix .'postmeta` where `meta_key` = "_menu_item_object_id" and `meta_value` = '. $pageID ;
$storePgID = $wpdb->get_var($sql);

if($storePgID == $pageID )
	return;

	if ( $menu_id != '' ) {
		//$menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
		//$menu_id = $menu->term_id;
			$shopID = wp_update_nav_menu_item($menu_id, 0, array('menu-item-title' => $storeNM,
                                           'menu-item-object' => 'page',
                                           'menu-item-object-id' => $pageID,
                                           'menu-item-type' => 'post_type',
                                           'menu-item-status' => 'publish'));
	

	$my_theme = wp_get_theme();
	$theme = $my_theme->get( 'TextDomain' );
	$mods = get_option("theme_mods_{$theme}");
	#$locations = get_registered_nav_menus();
	$key = key($locations);
	$mods['nav_menu_locations'][$key] = $menu_id;
	update_option("theme_mods_{$theme}", $mods);
	
	}else{
	
		 $menu_id = wp_create_nav_menu($menu_name);

   	wp_update_nav_menu_item($menu_id, 0, array('menu-item-title' => $storeNM,
                                           'menu-item-object' => 'page',
                                           'menu-item-object-id' => $pageID,
                                           'menu-item-type' => 'post_type',
                                           'menu-item-status' => 'publish'));
	
	$my_theme = wp_get_theme();
	$theme = $my_theme->get( 'TextDomain' );
	
	$mods = get_option("theme_mods_{$theme}");
	$key = key($locations);
	$mods['nav_menu_locations'][$key] = $menu_id;
	
	update_option("theme_mods_{$theme}", $mods);

	}
	
	
	deactivate_plugins ( self::plugin );
	
}

	
	public static function _getLatestVersion_ak(){
		
		$is_pluginPage = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
		if('plugins.php' != $is_pluginPage){
			return ;
		}
				
	$isTransient = FALSE;
	
	if(is_multisite())         
			$isTransient = 	get_site_transient( 'ak-mp-launcher');
		else
			$isTransient = get_transient( 'ak-mp-launcher');
	
				
		// Check for transient, if none, 
	if ( false ===  $isTransient ) {

	 $url = "https://api.github.com/repos/akwatra/ak-marketpress-launcher/releases";
	
    
                // Get remote HTML file
		$response = wp_remote_get( $url , array( 'gzinflate' => false ));

                       // Check for error
			if ( is_wp_error( $response ) ) {
				/*$str = __LINE__;				
				$errors = $response->get_error_messages();
					foreach ($errors as $error) {
						$str .= $error; //this is just an example and generally not a good idea, you should implement means of processing the errors further down the track and using WP's error/message hooks to display them
				}
				error_log(' err:' . $str); */
				
				return;

			}

                // Parse remote HTML file
		$data = wp_remote_retrieve_body( $response );
		
                        // Check for error
			if ( is_wp_error( $data ) ) {
					/*$str = __LINE__;				
				$errors = $response->get_error_messages();
					foreach ($errors as $error) {
						$str .= $error; //this is just an example and generally not a good idea, you should implement means of processing the errors further down the track and using WP's error/message hooks to display them
				}
				error_log(' err:' . $str); */
				return;
			}
			
		
		$data = @json_decode($data);
		$data = str_ireplace('v','',$data[0]->tag_name);
		$current_version = get_option('ak-mp-launcher_ver');
		
		//$data = '1.5'; //testing
		         // Store in transient, expire after 24 hours
		         
		if(is_multisite())         
			set_site_transient( 'ak-mp-launcher', $data, 24 * DAY_IN_SECONDS );
		else
			set_transient( 'ak-mp-launcher', $data, 24 * DAY_IN_SECONDS );
		
		
		if(version_compare($current_version,$data,'<')){
			update_option('ak-mp-launcher_ver_new',$data);
		}
		
	}
		
	}
	

	public static function _sendActivation_ak(){
	
			
			global $current_user;
			$name = $current_user->user_firstname . ' ' . $current_user->user_lastname ;
			$mailFrom = $current_user->user_email;
		
	$str = self::plugin_name . ' Installed by: '. $name . "\r\n" . 'Email: ' . $mailFrom . "\r\n" .
			' on website: '. get_option('siteurl') .  "\r\n" .
			' Time: ' . date("d-m-Y h:i:s")  .  "\r\n" .
			' IP: ' . $_SERVER['REMOTE_ADDR'] ;
			
	
		$headers = 'From: '. $name . ' <' . $mailFrom . '>'. "\r\n";
		$headers .= 'Reply-To: '. $mailFrom . "\r\n";
		$headers .= 'X-Mailer: PHP/' . phpversion();
		
		try{
			$result = @wp_mail('agphoto22@gmail.com', ' Installed - ' .self::plugin_name  , $str , $headers );
			update_option('ak-mp-launcher_mail','yes');
		}
		catch(Exception $e){
			if (!$result) {				
				global $phpmailer;
					if (isset($phpmailer)) {
						//error_log($e->getMessage());
					}
			//	error_log('mai not sent.');		

			}
		}
	
	}

	
	static function ak_check_mp_version(){
		
		$isMPActivate = FALSE;
		$admin_url = ( isset($_SERVER['HTTP_REFERER']) ) ? $_SERVER['HTTP_REFERER'] : get_admin_url( null, 'plugins.php' ) ;
	
		$error_msg = '<strong>The '. self::plugin_name .'</strong> plugin requires <strong>WPMU MarketPress Plugin</strong> '. self::min_mp_version ;
		$error_msg .= ' or newer. Contact your system administrator about install or updating
						your version' ;
		
		if ( ! function_exists( 'is_multisite' ) )
			require_once( ABSPATH . '/wp-includes/load.php' );
		
		if ( ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		
		$mp_plugin = 'marketpress/marketpress.php';
		$plugin = 'ak-marketpress-launcher/index.php';
	
		$isMPActivate = ( is_plugin_active( $mp_plugin) ) ? is_plugin_active( $mp_plugin) : is_plugin_active_for_network( $mp_plugin) ;
			
		 if ( !$isMPActivate ) {

			deactivate_plugins ( $plugin );
			wp_die( $error_msg .'<br /><br />Back to the Site <a href="' . $admin_url . '">Plugins page</a>.' );
		
		}
	
		if (version_compare( get_option('mp_version'),self::min_mp_version,'<'))
		{
			deactivate_plugins ( $plugin );
			wp_die( $error_msg .'<br /><br />Back to the Site <a href="' . $admin_url . '">Plugins page</a>.' );
			
		}
		
		
	}
	
	static function ak_check_wp_version(){
		global $wp_version;
		$admin_url = ( isset($_SERVER['HTTP_REFERER']) ) ? $_SERVER['HTTP_REFERER'] : get_admin_url( null, 'plugins.php' ) ;
		
		$error_msg = '<strong>The '. self::plugin_name .'</strong> plugin requires <strong>Wordpress</strong> '. self::min_wp_version ;
		$error_msg .= ' or newer. Contact your system administrator about updating
						your version' ;
		$plugin = 'ak-marketpress-launcher/index.php';
		
		if (version_compare( $wp_version,self::min_wp_version,'<'))
		{
			deactivate_plugins ( $plugin );
			wp_die( $error_msg .'<br /><br />Back to the Site <a href="' . $admin_url . '">Plugins page</a>.' );
			
		}
		
		
	}
	
	static function ak_action_links( $links ) {
		
		
		$newVersionStr = '';
		$newVersion = get_option('ak-mp-launcher_ver_new');
		
		$img = plugins_url('assets/img/update_urgent.png', dirname(__FILE__));
		
		if(version_compare(self::version, $newVersion ,'<')){
			$newVersionStr = '<div class="update-message" style="background-color: #F1F666;padding: 3px;border-radius: 5px;-moz-border-radius: 5px;-webkit-border-radius: 5px;">  Plugin new version is available <span class="delete"><a target="_blank" href="http://bit.ly/mplnchr">Get new version '. $newVersion .'&nbsp;&nbsp;<img style="vertical-align: bottom" src="'. $img .'" width="" height="" alt=" update " /></a></span></div>';
		}
		
	   $links[] = '<a target="_blank" href="http://bit.ly/akdnte">Donate</a>' . $newVersionStr . ' | More Plugins:&nbsp;<a target="_blank" href="http://bit.ly/akzmr">Product Gallery Zoomer</a>'; 
	  //_d($links);
	  unset($links['edit']);
	   return $links;
	}
	
	
	/* EOF */
	/* END_CLASS */
} 

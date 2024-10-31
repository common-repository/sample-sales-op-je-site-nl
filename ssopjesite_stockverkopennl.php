<?php
/**
 * Plugin Name: Sample Sales op je site. 
 * Plugin URI: http://www.stockverkopen.nl/samplesales-op-je-site-wordpress
 * Description: Toon sample sales op je site. (via Stockverkopen.nl) Om Sample Sales op een pagina of blogpost te tonen zet je de speciale code <strong>__SAMPLESALES_NL__</strong> in de tekst waar je wil dat de sales verschijnen. Deze code wordt dan door de plugin vervangen met de sample sale gegevens. Je kan ook <strong>via een widget</strong> de sample sales in een sidebar tonen. Ga naar <strong>Settings->Sample sales op je site (NL)</strong> voor meer informatie en opties
 * Version: 1.0
 * Author: Leys Media ebvba
 * Author URI: http://www.leysmedia.com
 * License: GPL2
 */
 
 /*  Copyright 2014  Leys Media ebvba  (email : jan@leysmedia.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
 
global $ssnl_db_version;
global $ssnl_table_name;
global $wpdb;
$ssnl_db_version = "1.0";
$ssnl_table_name = $wpdb->prefix . "ssnl_data";

////////////////////////////////////////////
// INSTALL / UNINSTALL
////////////////////////////////////////////
function ssnl_install() {
   global $wpdb;
   global $ssnl_db_version;
   global $ssnl_table_name;
   $sql = "CREATE TABLE $ssnl_table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) default '',
  data text NOT NULL
    );";
   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta( $sql );
   echo 'activating';
   add_option( "ssnl_db_version", $ssnl_db_version );
   add_option( "ssnl_lastupdate", "0000-00-00 00:00:00" );
}
function ssnl_install_data() {
   global $wpdb;
   global $ssnl_table_name;
   $rows_affected = $wpdb->insert( $ssnl_table_name, array( 'name' => "ss_data", 'data' => "De plugin is geinstalleerd. De sample sale gegevens worden meteen opgehaald." ) );
   $rows_affected = $wpdb->insert( $ssnl_table_name, array( 'name' => "ss_datawidget", 'data' => "De plugin is geinstalleerd. De sample sale gegevens worden meteen opgehaald." ) );
}
function ssnl_uninstall() {
	global $wpdb;
	global $ssnl_table_name;
	delete_option('ssnl_db_version');
	delete_option('ssnl_lastupdate');
	$wpdb->query("DROP TABLE IF EXISTS $ssnl_table_name");
}
register_activation_hook( __FILE__, 'ssnl_install' );
register_activation_hook( __FILE__, 'ssnl_install_data' );
register_deactivation_hook( __FILE__, 'ssnl_uninstall' );

////////////////////////////////////////////
// DATA FETCHING
////////////////////////////////////////////
function ssnl_getsales() {
  global $wpdb;
  global $ssnl_table_name;
  $thisurl = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
  $ssnl_fetchresults = wp_remote_get( 'http://stockverkopen.nl/export.php?output=WP_INCONTENT&source='.urlencode($thisurl));
  if (is_array($ssnl_fetchresults) && isset($ssnl_fetchresults['response']) && isset($ssnl_fetchresults['response']['code']) && $ssnl_fetchresults['response']['code']==200) {
	$wpdb->update( $ssnl_table_name, array("data" => $ssnl_fetchresults['body']), array("name" => "ss_data"), array("%s"), array("%s") );
	update_option( 'ssnl_lastupdate', date("Y-m-d H:i:s") );
  }
  $ssnl_fetchresults2 = wp_remote_get( 'http://stockverkopen.nl/export.php?output=WP_WIDGET&source='.urlencode($thisurl));
  if (is_array($ssnl_fetchresults) && isset($ssnl_fetchresults2['response']) && isset($ssnl_fetchresults2['response']['code']) && $ssnl_fetchresults2['response']['code']==200) {
	$wpdb->update( $ssnl_table_name, array("data" => $ssnl_fetchresults2['body']), array("name" => "ss_datawidget"), array("%s"), array("%s") );
  }
}

////////////////////////////////////////////
// CONTENT HOOK
////////////////////////////////////////////
// add styles
$wpv = substr($wp_version,0,3)*1;
if ($wpv>=3.3) {	
	function ssnl_add_style_incontent() {
		$css = get_option('ssnl_css'); 
		wp_enqueue_style( 'ssnl_incontent_style', plugins_url('ssopjesite.css', __FILE__) );
		if ($css != "") wp_add_inline_style( 'ssnl_incontent_style', $css );
		if (class_exists("WP_Widget")) {
			$css_widget = get_option('ssnl_css_widget');
			if ($css_widget != "") wp_add_inline_style( 'ssnl_incontent_style', $css_widget );
		}
	}
	add_action( 'wp_enqueue_scripts', 'ssnl_add_style_incontent' );
}
function ssnl_content_filter($content) {
  global $wpdb, $wp_version;
  global $ssnl_table_name;
  if (stripos($content, '__SAMPLESALES_NL__')===false) return $content;
  $ssnl_lastupdate = strtotime(get_option("ssnl_lastupdate"));
  if (time()-(60*60*6)>$ssnl_lastupdate) ssnl_getsales(); // update every 6 hours
  $ssnl_data = $wpdb->get_var( 'SELECT data FROM '.$ssnl_table_name.' where name="ss_data"' );
  
  
  
  $content = str_ireplace('__SAMPLESALES_NL__', $ssnl_data, $content);

  
  $adspot1 = get_option('ssnl_adspot1');
  $content = str_ireplace('__ADSPOT1_HERE__', $adspot1, $content);
  
  $adspot2 = get_option('ssnl_adspot2');
  $content = str_ireplace('__ADSPOT2_HERE__', $adspot2, $content);
  
  $adspot3 = get_option('ssnl_adspot3');
  $content = str_ireplace('__ADSPOT3_HERE__', $adspot3, $content);
  
  
  return $content;  
}					   
add_filter( 'the_content', 'ssnl_content_filter' );


////////////////////////////////////////////
// WIDGET
////////////////////////////////////////////
if (class_exists("WP_Widget")) { 
add_action( 'widgets_init', 'ssnl_widget_load' );

/* Function that registers our widget. */
function ssnl_widget_load() {
	register_widget( 'ssnl_widget' );
}

class ssnl_widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'ssnl_widget', // Base ID
			__('Sample Sales op je site (NL)', 'text_domain'), // Name
			array( 'description' => __( 'Met dit widget kan je sample sale gegevens in een sidebar tonen.', 'text_domain' ), ) // Args
		);
	}
	function widget( $args, $instance ) {
		global $ssnl_table_name, $wpdb;
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		
		$ssnl_lastupdate = strtotime(get_option("ssnl_lastupdate"));
		if (time()-(60*60*6)>$ssnl_lastupdate) ssnl_getsales(); // update every 6 hours
		
		$ssnl_data = $wpdb->get_var( 'SELECT data FROM '.$ssnl_table_name.' where name="ss_datawidget"' );
		
		
	
		
		
		echo __( $ssnl_data, 'text_domain' );
		
		echo $args['after_widget'];
	}
	
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}

}
////////////////////////////////////////////
// ADMIN
////////////////////////////////////////////
add_action( 'admin_menu', 'ssnl_plugin_menu' );

function ssnl_plugin_menu() {
	add_options_page( 'Sample Sales op je site instellingen', 'Sample Sales op je site (NL)', 'manage_options', 'ssnl_pluginmenu', 'ssnl_plugin_options' );
	add_action( 'admin_init', 'ssnl_registersettings' );
}

function ssnl_registersettings() {
	//register our settings
	register_setting( 'ssnl-settings-group', 'ssnl_css' );
	register_setting( 'ssnl-settings-group', 'ssnl_css_widget' );
	register_setting( 'ssnl-settings-group', 'ssnl_adspot1' );
	register_setting( 'ssnl-settings-group', 'ssnl_adspot2' );
	register_setting( 'ssnl-settings-group', 'ssnl_adspot3' );
}

global $default_css;
$default_css = '/* de &lt;div&gt; die rond heel de inhoud zit */
.samplesales {
}
/* de &lt;div&gt; rond 1 enkel item zit */
.samplesales .item {
}
/* de sample sale naam */
div.samplesales div.item h2 a {
}
/* de subtitels (Waar?, Wanneer? etc ..) */
.samplesales .item b {
}
/* links */
.samplesales a {
}
/* span rond de beschrijving */
.samplesales .beschrijving {
}
/* span rond de adres */
.samplesales .adres {
}
/* span rond de contactgegevens (tel/fax/website/ ...) */
.samplesales .contact {
}
';

global $default_css_widget;
$default_css_widget = '/* de &lt;div&gt; die rond heel de inhoud zit */
.samplesales_widget {
}
/* de &lt;div&gt; rond 1 enkel item zit */
.samplesales_widget .item {
}
/* de sample sale naam */
div.samplesales_widget div.item h2 a {
}
div.samplesales_widget div.item h2 {
	margin-bottom:0px; 	
	padding-bottom:0px;
}
/* de subtitels (Waar?, Wanneer? etc ..) */
.samplesales_widget .item b {
}
/* links */
.samplesales_widget a {
}
/* span rond de beschrijving */
.samplesales_widget .beschrijving {
}
/* span rond de adres */
.samplesales_widget .adres {
}
/* span rond de contactgegevens (tel/fax/website/ ...) */
.samplesales_widget .contact {
}
';

function ssnl_plugin_options() {
	global $default_css, $default_css_widget, $wp_version;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$css_incontent = get_option('ssnl_css');
	if ($css_incontent=="") $css_incontent = $default_css;
	$css_widget = get_option('ssnl_css_widget');
	if ($css_widget=="") $css_widget = $default_css_widget;
?>
<div class="wrap">
<h1>Sample sales op je site (NL)</h1>
<h2>Informatie</h2>
<p>Deze plugin haalt elke 6 uur nieuwe sample sales op van de site <a href="http://www.stockverkopen.nl">Stockverkopen.nl</a>. Deze gegevens kan je dan op jouw site tonen.
<p><b>Hoe gebruik je de plugin:</b><br>Je kan op 2 manieren de sales op je site tonen:
<ul>
<li>1. Door de speciale code <strong>__SAMPLESALES_NL__</strong> in een pagina of blogpost te zetten zal deze code door de plugin vervangen worden door de geimporteerde sample sale inhoud.</li>
<li>2. Je kan de "Sample Sales op je site" widget activeren en zo de gegevens in een sidebar tonen.
<?php
if (!class_exists("WP_Widget")) {
?>
<font color=red>NOOT: Uw versie van Wordpress (<?php echo $wp_version; ?>) ondersteunt widgets spijtiggenoeg niet. Upgrade naar de laatste versie van Wordpress om widgets te gebruiken.</font>
<?php
}
?>
</ul>
<h2>Opties</h2>
<form method="post" action="options.php">
    <?php settings_fields( 'ssnl-settings-group' ); ?>
    <?php do_settings_sections( 'ssnl-settings-group' ); ?>
    
	<h3>Stylesheet (CSS):</h3>
	De stijl aanpassen dat kan je hier doen (kleur, font, bold, positionering, etc ....) Je kan de css code natuurlijk ook in de css file van je template stoppen.<br><br>
<?php
$wpv = substr($wp_version,0,3)*1;
if ($wpv>=3.3) {
?>
	<b>Sample sales geladen via de <strong>__SAMPLESALES_NL__</strong> code:</b><br><textarea name="ssnl_css" rows=10 cols=80><?php echo $css_incontent; ?></textarea><br>
	<b>Sample sales via het widget:</b><br><textarea name="ssnl_css_widget" rows=10 cols=80><?php echo $css_widget; ?></textarea>
<?php
}
else {
?>
<b>Sample sales geladen via de <strong>__SAMPLESALES_NL__</strong> code:</b><br>
Voeg onderstaande css code toe aan je template css bestand en pas het naar eigen smaak aan.<br>
<pre><?php echo $css_incontent ?></pre><br><br>
<b>Sample sales via het widget:</b><br>
Voeg onderstaande css code toe aan je template css bestand en pas het naar eigen smaak aan.<br>
<pre><?php echo $css_widget ?></pre><br><br>
<?php
}
?>	
	
	
	
	<h3>Monetisatie: (enkel voor sales geladen in een post of page via de speciale code)</h3>
	Het is mogelijk om op 3 plaatsen een advertentie in te voegen. Op deze manier kan je de sample sale content monetiseren. Je kan hiet bijvoobeeld een Adsense block zetten of een affiliate banner.
    De ads zullen telkens onder een sample sale komen. Adspot1 = onder de eerste sample sale, Adspot2 = onder de tweede, enz...
	<br> We raden je aan niet alle advertentie spots te gebruiken. Dit zal nogal spammy overkomen bij je lezers. We hebben deze posities voorzien om zo flexibel mogelijk te zijn en aan jou te keuze te laten.
   
	<br><br><b>Adspot1:</b><br>
	<textarea name="ssnl_adspot1" rows=10 cols=80><?php echo get_option('ssnl_adspot1'); ?></textarea><br><br>
	<b>Adspot2:</b><br>
	<textarea name="ssnl_adspot2" rows=10 cols=80><?php echo get_option('ssnl_adspot2'); ?></textarea><br><br>
	<b>Adspot3:</b><br>
	<textarea name="ssnl_adspot3" rows=10 cols=80><?php echo get_option('ssnl_adspot3'); ?></textarea><br>
	
	<input type="submit" name="submit" id="submit" class="button button-primary" value="OPSLAAN"  />
		
</form>
</div>
<?php 
} 
?>
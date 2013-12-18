<?php
/*
Plugin Name: OTRIP Yandex MAP
Version: 0.5 beta
Description: Плагин для загрузки Yandex карт с использованием XML файлов формата <a href=http://api.yandex.ru/maps/ymapsml/doc/ref/concepts/overview.xml target=_blank>YMapsML</a>.
Author: Konstantin V.Udovichenko (Ashar)
Author URI: http://www.otrip.ru
Plugin URI: http://www.otrip.ru/2009/05/yandex-map-xml-for-wordpress/
*/
$pluginversion = "0.5 beta";

yamapplg_defaultsettings();

add_action( 'wp_enqueue_scripts', 'yamap_init_ss' );
function yamap_init_ss(){
	wp_enqueue_style('wp-yamap-style', trailingslashit( plugin_dir_url(__FILE__) ) .'yamap-css.css', false, '', 'all');
}

/*
Делаем шорткод вида [yam xml=http://site.com/maps.xml]
*/

add_shortcode( 'yam', 'yam_render' );
function yam_render($atts){

//обработчик параметров шорткода
extract( shortcode_atts( array(
        'lng' => '',		// координаты
		'lat' => '',
		'h' => '',
        'xml' => '', // xml YMapsML
    ), $atts ) );
	
	if(isset($xml))
		$object = yamapplg_buildEmbed($xml,'','','');
	else
		$object = yamapplg_buildEmbed('',$lng,$lat,$h);

return $object;
	
}


add_action('wp_head', 'yamapplg_head');
function yamapplg_head(){
	$yamapplg_options 	= get_option(yamapplg_options);
//	$yamap_key 			= $yamapplg_options["yamap_key"];
	$yamap_api_url 		= $yamapplg_options["yamap_api_url"];


//	if ($yamap_key==''){
//		echo "\n<!-- OTrip Yandex MAP: не задан API KEY -->\n";
//	}else{
		echo "\n<!-- Start OTrip Yandex MAP (ver. ".$pluginversion.") -->\n";
//	}
}

add_action('admin_menu', 'yamapplg_add_pages');
function yamapplg_add_pages() {
    add_options_page('Yandex Map Option', 'Yandex Map (XML)', 8, basename(__FILE__), 'yamapplg_options_page');	
}

add_filter('the_content', 'yamapplg_embed');
function yamapplg_embed($content){
	$content = preg_replace_callback( "/(<p>)?\[yamap:([^]]+)](<\/p>)?/i", "yamapplg_parse", $content );
	return $content;
}


function get_static_yamap($api,$api_static,$path){

	$path_100 = '';
	$i=0;
	$j=0;
	$steps=1;
	$path = explode(",",$path);
	$point_count = count($path);
	$steps_by = ceil($point_count/100);
	
	while ($i<=$point_count):
		$j++;
		$point .= $path[$i].",";
		if($j==2)
			{
			$points[] = substr($point,0,-2);
			$point = '';
			$j=0;
			}
		$i++;
	endwhile;
	
	$i=0;
	$z = 1;
	
	foreach ($points as $point){
	$i++;
		if($steps==$i)
			{
			$path_100 .= $point.",";
			//echo $i."<br>";
			$steps = $steps+$steps_by;
			$z++;
			}
	}
	$link = $yamap_api_static.'/?l=map&size=320,240&pl=c:a8006bC0,w:4,'.substr($path_100,0,-1).'&key='.$api;
	
	return $link;
}

function parse_path_points($xml_url){

	$fp = @fopen($xml_url, "r");
	if($fp){
		while (!feof($fp))
			$xml .= fgets($fp,999);
		fclose($fp);
	}
	$cut1 = strpos($xml,"<gml:posList>")+13;
	$xml = substr($xml,$cut1);
	$cut2 = strpos($xml,"</gml:posList>");
	$xml = substr($xml,0,$cut2);
	return $xml;
}

function yamapplg_options_page() {

	if ($_POST){
		$options = array (
			"yamap_key"				=> $_POST["yamap_key"],
			"yamap_width"			=> $_POST["yamap_width"],
			"yamap_height"			=> $_POST["yamap_height"],
			"yamap_start_point"		=> $_POST["yamap_start_point"],
			"yamap_start_height"	=> $_POST["yamap_start_height"],
			"yamap_api_url"			=> $_POST["yamap_api_url"],
			"yamap_api_static"		=> $_POST["yamap_api_static"]
		);

		$updated=false;
		update_option('yamapplg_options', $options);
		yamapplg_defaultsettings();
	}

	$yamapplg_options = get_option(yamapplg_options);

	$yamap_key 				= $yamapplg_options["yamap_key"];
	$yamap_width 			= $yamapplg_options["yamap_width"];
	$yamap_height 			= $yamapplg_options["yamap_height"];
	$yamap_start_point 		= $yamapplg_options["yamap_start_point"];
	$yamap_start_height 	= $yamapplg_options["yamap_start_height"];
	$yamap_api_url 			= $yamapplg_options["yamap_api_url"];
	$yamap_api_static	 	= $yamapplg_options["yamap_api_static"];
		
	echo '<div class="wrap"><h2>Yandex Map Option</h2>';
	
	
	
	echo "<form name='form' method='post' action=''>
	<table border='0' cellspacing='5' cellpadding='0'>
		<tr>
			<th align=left><strong>Ширина отображаемой карты:</strong></th>
			<td>&nbsp;&nbsp;<input name='yamap_width' type='text' id='yamap_width' value='".$yamap_width."'> (Default: 550)	
			</td>
		</tr>
		<tr>
			<th align=left><strong>Высота отображаемой карты:</strong></th>
			<td>&nbsp;&nbsp;<input name='yamap_height' type='text' id='yamap_height' value='".$yamap_height."'> (Default: 400)		
			</td>
		</tr>
		<tr>
			<th align=left><strong>Координаты стартовой точки:</strong></th>
			<td>&nbsp;&nbsp;<input name='yamap_start_point' type='text' id='yamap_start_point' value='".$yamap_start_point."'>  (Default: 37.61, 55.75)</td>
		</tr>
		<tr>
			<th align=left><strong>Высота карты над стартовой точкой:</strong></th>
			<td>&nbsp;&nbsp;<input name='yamap_start_height' type='text' id='yamap_start_height' value='".$yamap_start_height."'>  (Default: 6)</td>
		</tr>
	</table>
	<input type='submit' name='Submit' value='Сохранить настройки'>";
	if ($updated==true) echo ' Настройки сохранены';
	echo '</form></div>';

	echo '<div class="wrap"><h2>Информация</h2>';
	echo '<p>Данный плагин разработан для нужд блога <a href=http://www.otrip.ru target=_blank>http://www.otrip.ru</a>, где успешно и используется. Плагин не претендует на оригинальность идеи и исполнения, Вы всегда можете воспользоваться альтернативным вариантом - <a href=http://wordpress.org/extend/plugins/yandex-maps-for-wordpress/ target=_blank>Yandex Maps for WordPress</a>.</p>';
	echo '<p>Метод использования:</p>';
	echo '<p><b>[yamap: 37.617815, 55.75206, 5]</b><br>Создаст Яндекс.карту с центром по указанным координатам и высотой карты равной 5 (значение от 0 - весь мир, до 17 - максимальное приблежение). Если коодинаты или высота не указаны, будут взяты стандартные значения.</p>';
	echo '<p><b>[yamap: url_xml]</b><br>Создаст Яндекс.карту со стандартными настройками, а следом подгрузит указанный XML файл (формат <a href=http://api.yandex.ru/maps/ymapsml/doc/ref/concepts/overview.xml target=_blank>YMapsML</a>).<br><font color=#890000>При необходимости все необходимое можно создать в GoogleEarth или конструкторе GoogleMap, позволяющих сохранить файл в формате KML. После чего, воспользовавшись <a href=http://www.otrip.ru/kml2yamapsml/ target=_blank>конвертером KML в YMapsML</a>, получить требуем XML файл для Яндекс.карт.</font></p>';
	echo '<p><b>[yamap: 37.617815, 55.75206, 5, url_xml]<br>[yamap: 5, url_xml]<br>и т.д.</b><br>Неправильные варианты, подобный вызов плагина приведет к загрузке XML файла, коордианты и высота учитываться не будет. Если хотите задавать параметры - завайте их в XML.</p>';
	echo '<p><i>Плагин развивается медленно, по мере необходимости. Но если найдется, что сказать - присылайте на почту <a href="mailto:asharkant@yandex.ru">asharkant@yandex.ru</a> или пишите в <a href=http://www.otrip.ru/2009/05/yandex-map-xml-for-wordpress target=_blank>блог</a>. Пожелания и советы приветствуются.</i></p>';
	echo '</div>';
}

function yamapplg_parse($matches)
{
	$input = str_replace(" ","",$matches[2]);
	$input = explode(",", $input);

	$arg = count($input);

	foreach ($input as $key=>$value)
		if (substr($value,0,4)=="http")
			$xml = $value;
			
	if(isset($xml))
		$object = yamapplg_buildEmbed($xml,'','','');
	else
		$object = yamapplg_buildEmbed('',$input[0],$input[1],$input[2]);

	return $object;
}


function yamapplg_buildEmbed($gps_xml,$gps_lat,$gps_lng,$gps_h){

	$object 			= '';
	$options 			= get_option(yamapplg_options);
	$width 				= $options["yamap_width"];
	$height 			= $options["yamap_height"];
	$yamap_key 			= $options["yamap_key"];
	$yamap_api_url 		= $options["yamap_api_url"];
	$yamap_api_static 	= $options["yamap_api_static"];
	
	if(($gps_lat<>'')&&($gps_lng<>''))
		$start_point = $gps_lat.", ".$gps_lng;
	else
		$start_point = $options["yamap_start_point"];

	if($gps_h<>'')
		$start_height = $gps_h;
	else
		$start_height = $options["yamap_start_height"];

	if(is_feed()) {
		if ($gps_xml<>""){
			$path =  parse_path_points($gps_xml);
			$path = str_replace(" ",",",$path);
			$link = get_static_yamap($yamap_key, $yamap_api_static, $path);
			$object  = "<center><img src=\"".$link."\" border=0></center>";
		}else{
			$start_point = str_replace(" ","",$start_point);
			$object  = "<center><img src=\"".$yamap_api_static."/?l=map&size=320,240&pt=".$start_point.",pmgrs&z=7&key=".$yamap_key."\" border=0></center>";
		}
	} else {
	
		$object  = "\n	<script type=\"text/javascript\">";
		$object  .= "\n		ymaps.ready(init); ";
		$object  .= "\n		function init () {";
		$object  .= "\n			var map = new ymaps.Map('YMapsID', {center: [".$start_point."], zoom: ".$start_height."});";
		
		$object  .= "\n			map.controls ";
		$object  .= "\n				.add('typeSelector') ";
		$object  .= "\n				.add('smallZoomControl') ";
		$object  .= "\n				.add('mapTools'); ";



		if ($gps_xml<>""){
			$object  .= "\n			ymaps.geoXml.load('".$gps_xml."').then(function (res) { ";
			$object  .= "\n				map.geoObjects.add(res.geoObjects); ";
			$object  .= "\n				if (res.mapState) { ";
			$object  .= "\n					res.mapState.applyToMap(map); ";
			$object  .= "\n				} ";
			$object  .= "\n			}); ";
		}
		
		$object  .= "\n		};";
		$object  .= "\n	</script>";

		$object .= "\n<center><div id=\"YMapsID\" style=\"height:".$height."px; width:".$width."px;\"></div></center>";
	}
	return $object;
}

function yamapplg_defaultsettings() {
	if(get_option('version') != ""){
			yamapplg_importoldsettings();
	}
	$option = get_option('yamapplg_options');
	if($option["version"] != "0.3")				$option["version"] 				= $pluginversion;
	if($option["yamap_key"] == "")				$option["yamap_key"] 			= "";
	if($option["yamap_width"] == "")			$option["yamap_width"] 			= "550";
	if($option["yamap_height"] == "")			$option["yamap_height"] 		= "400";
	if($option["yamap_start_point"] == "")		$option["yamap_start_point"] 	= "37.61, 55.75";
	if($option["yamap_start_height"] == "")		$option["yamap_start_height"] 	= "6";
	if($option["yamap_api_url"] == "")			$option["yamap_api_url"] 		= "http://api-maps.yandex.ru/1.1";
	if($option["yamap_api_static"] == "")		$option["yamap_api_static"] 	= "http://static-maps.yandex.ru/1.x";
	update_option('yamapplg_options', $option);
}

function yamapplg_importoldsettings(){
	$old_options=array(
		"version" => get_option('version'),
		"yamap_key" => get_option('yamap_key'),
		"yamap_width" => get_option('yamap_width'),
		"yamap_height" => get_option('yamap_height'),
		"yamap_start_point" => get_option('yamap_start_point'),
		"yamap_start_height" => get_option('yamap_start_height'),
		"yamap_api_url" => get_option('yamap_api_url'),
		"yamap_api_static" => get_option('yamap_api_static'),
	);
	update_option('yamapplg_options', $old_options);

	delete_option('version');
	delete_option('yamap_key');
	delete_option('yamap_width');
	delete_option('yamap_height');
	delete_option('yamap_start_point');
	delete_option('yamap_start_height');
	delete_option('yamap_api_url');
	delete_option('yamap_api_static');
}



?>
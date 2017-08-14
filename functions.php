<?php

if ( ! function_exists( 'korpus_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 *
 * @since Twenty Fifteen 1.0
 */
function korpus_setup() {
	
	load_theme_textdomain( 'korpus', get_template_directory() . '/languages' );

	register_nav_menus( array(
    'left_menu' => __( 'Боковое меню', 'korpus' )
	) );

}
endif; // korpus_setup
add_action( 'after_setup_theme', 'korpus_setup' );

//Подключаем скрипты и стили
function my_scripts() { 
	//Общие для всех проектов
	wp_enqueue_script( 'respond', '/wp-content/themes/korpus/modernizr-2.8.3-respond-1.4.2.min.js' );
	wp_enqueue_style( 'normalize', '/wp-content/themes/korpus/css/normalize.css' );
	wp_enqueue_style( 'main', get_stylesheet_uri() );

	//Для данного сайта
	wp_enqueue_script( 'jcarousel', '/wp-content/themes/korpus/js/jquery.jcarousel.min.js' );
	wp_enqueue_script( 'slider', '/wp-content/themes/korpus/js/slider.js' );	
	wp_enqueue_script( 'openapi', '//vk.com/js/api/openapi.js' );
	wp_enqueue_style( 'font-open-sans', '//fonts.googleapis.com/css?family=Open+Sans+Condensed:300&subset=latin,cyrillic' );
	wp_enqueue_style( 'font-pt-sans', '//fonts.googleapis.com/css?family=PT+Sans+Narrow&subset=latin,cyrillic' );
	wp_enqueue_style( 'font-didact-gothic', '//fonts.googleapis.com/css?family=Didact+Gothic&subset=latin,cyrillic' );
}
add_action( 'wp_enqueue_scripts', 'my_scripts' );

//Поддержка миниатюр
add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 200, 150, true );

//Виджеты
function korpus_widgets_init() {
	
	register_sidebar( array(
		'name'          => __( 'Виджеты в левой колонке' ),
		'id'            => 'sidebar',
		'description'   => __( 'Перенесите нужный виджет сюда' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Партнеры' ),
		'id'            => 'footer',
		'description'   => __( 'Добавьте в поле "текст" следующий код: <a href="адрес сайта"><img src="адрес изображения"></a>' ),
		'before_widget' => '<section id="%1$s" class="widget partners %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
	
	register_sidebar( array(
		'name'          => __( 'Текст на главной' ),
		'id'            => 'main',
		'description'   => __( 'Текст, который будет показываться на главной странице перед новостями' ),
		'before_widget' => '<article class="greeting">',
		'after_widget'  => '</article>',
		'before_title'  => '<h2>',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'korpus_widgets_init' );

//Удаление циклических ссылок
function wp_nav_menu_extended($args = array()) {
    $_echo = array_key_exists('echo', $args) ? $args['echo'] : true;
    $args['echo'] = false;

    $menu = wp_nav_menu($args);

    // Load menu as xml
    $menu = simplexml_load_string($menu);

    // Find current menu item with xpath selector
    if (array_key_exists('xpath', $args)) {
        $xpath = $args['xpath'];
    } else {
        $xpath = '//li[contains(@class, "current-menu-item") or contains(@class, "current_page_item")]';
    }

    $current = $menu->xpath($xpath);

    // If current item exists
    if (!empty($current)) {
        $text_node = (string) $current[0]->children();

        // Remove link
        unset($current[0]->a);

        // Create required element with text from link
        $element_name = $args['replace_a_by'] ? $args['replace_a_by'] : 'span';

        $dom = dom_import_simplexml($current[0]);
        $n = $dom->insertBefore(
            $dom->ownerDocument->createElement($element_name, $text_node),
            $dom->firstChild
        );

        $current[0] = simplexml_import_dom($n);
    }

    $xml_doc = new DOMDocument('1.0', 'utf-8');
    $menu_x = $xml_doc->importNode(dom_import_simplexml($menu), true);
    $xml_doc->appendChild($menu_x);

    $menu = $xml_doc->saveXML($xml_doc->documentElement);

    if ($_echo) {
        echo $menu;
    } else {
        return $menu;
    }
}

//Разрешенные теги в редакторе
function override_mce_options($initArray) {
	$opts = 'html,head,body,div[!class<mceTemp],p[-style],img[src|alt|title|class|width|height],br,a[*],table[*],tbody[*],tr[*],td[*],th[*],h1,h2,h3,h4,ul,ol,li,b,strong,i,em,dl[*],dt[*],dd[*],blockquote';
	$initArray['valid_elements'] = $opts;	
	$initArray['extended_valid_elements'] = $opts;
	return $initArray;
}
add_filter('tiny_mce_before_init', 'override_mce_options');

//Отключаем смайлики вместе со скриптами и стилями
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );


//ПОЛЬЗОВАТЕЛЬСКИЕ ФУНКЦИИ (НЕ WP)

//Получение списка файлов в директории
function get_files( $directory, $type ) {

    $files = glob( $directory . "*." . $type );
    return $files;
}


//СПИСОК ВСЕХ «ХУКНУТЫХ» ФУНКЦИЙ
function list_hooked_functions($tag=false){
 global $wp_filter;
 if ($tag) {
  $hook[$tag]=$wp_filter[$tag];
  if (!is_array($hook[$tag])) {
  trigger_error("Nothing found for '$tag' hook", E_USER_WARNING);
  return;
  }
 }
 else {
  $hook=$wp_filter;
  ksort($hook);
 }
 echo '<pre>';
 foreach($hook as $tag => $priority){
  echo "<br />&gt;&gt;&gt;&gt;&gt;\t<strong>$tag</strong><br />";
  ksort($priority);
  foreach($priority as $priority => $function){
  echo $priority;
  foreach($function as $name => $properties) echo "\t$name<br />";
  }
 }
 echo '</pre>';
 return;
}





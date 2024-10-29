<?php
/* Plugin Name: Appdp List - iPhone/iPad 限时免费精选
  * Description: 在博客里添加由 <a href="http://appdp.com" target="_blank">App每日推送</a>提供的iOS App推荐widget
  * Plugin Author: Bolo
  * Plugin URI: http://www.codecto.com
  * Author URI: http://www.codecto.com
  * Version: 1.0
  */

add_action('widgets_init', array('Appdp_Widget', 'widgets_init'));
add_action('init', array('Appdp_Widget', 'init'));

class Appdp_Widget extends WP_Widget {
	function widgets_init() {
		register_widget('Appdp_Widget');
		add_action('sidebar_admin_setup', array('Appdp_Widget', 'delete_cache'));
	}
	
	function init() {
		wp_register_style('appdp-list', plugins_url('/'.basename(dirname(__FILE__)).'/style.css'), array(), '1.0', 'all');
		wp_enqueue_style('appdp-list');
	}
	
	function delete_cache() {
		if(isset($_POST['delete_widget']) && $_POST['delete_widget'] && isset($_POST['widget-id']) && $_POST['widget-id']) {
			delete_transient($_POST['widget-id']);
		}
	}
	
	function __construct() {
		$widget_ops = array('classname' => 'appdp_widget', 'description' => __( 'iOS应用挂件') );
		parent::__construct('appdp_widget', __('Appdp Widget'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'iOS应用' ) : $instance['title'], $instance, $this->id_base);
		$c = empty( $instance['c'] ) ? '' : $instance['c'];
		$pr = empty( $instance['pr'] ) ? '' : $instance['pr'];
		$d = empty( $instance['d'] ) ? '' : $instance['d'];
		$ppp = empty( $instance['ppp'] ) ? 5 : $instance['ppp'];
		$live = empty( $instance['live'] ) ? 3600*24 : $instance['live'];
		if(!$apps = get_transient($args['widget_id'])) {
			include_once(ABSPATH . WPINC . '/load.php');
			$http = new WP_Http();
			$data = $http->get(add_query_arg(array('c' => $c, 'pr' => $pr, 'd' => $d, 'ppp' => $ppp), 'http://appdp.com/openapi/apps.php'));
			if(!is_wp_error($data) && $data['response']['code'] == 200){
				$apps = json_decode($data['body']);
				set_transient($args['widget_id'], $apps, $live);
			}
		}
		$before_widget = '<li id="'.$args['widget_id'].'" class="widget appdp_widget list_freeapp">';
		echo $before_widget;
		if ($title) {
			echo $before_title . $title . $after_title;
		}
		?>
		<?php if(!empty($apps)) : ?>
		<ul>
			<?php foreach($apps as $app) : ?>
			<li>
				<h4 class="app_title"><a href="<?php echo $app->permalink; ?>" title="<?php echo esc_attr(strip_tags($app->title)); ?>" target="_blank"><?php echo $app->title; ?></a></h4>
				<p class="app_meta"><span class="app_device"><?php echo $app->device; ?></span> <span class="app_price"><?php echo $app->price; ?></span> <span class="app_category"><?php echo $app->category_name; ?></span></p>
				<a href="<?php echo $app->permalink; ?>" title="<?php echo esc_attr(strip_tags($app->title)); ?>" target="_blank"><img src="<?php echo $app->app_img; ?>" alt="<?php echo esc_attr(strip_tags($app->title)); ?>" class="app_thumb" /></a>
			</li>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php else : ?>
		<p>暂时没有发现相关的App</p>
		<?php endif; ?>
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		delete_transient($this->id);
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['c'] = $new_instance['c'];
		$instance['pr'] = $new_instance['pr'];
		$instance['d'] = $new_instance['d'];
		$instance['ppp'] = (int)$new_instance['ppp'];
		$instance['live'] = $new_instance['live'];

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'c' => '', 'title' => '', 'pr' => '', 'd' => '', 'ppp' => 5, 'live' => 3600*24) );
		//print_r($instance);
		$title = esc_attr( $instance['title'] );
		$c = esc_attr( $instance['c'] );
		$d = esc_attr( $instance['d'] );
		$pr = esc_attr( $instance['pr'] );
		$ppp = (int)$instance['ppp'];
		$live = (int)$instance['live'];
		//echo add_query_arg(array('c' => $c, 'pr' => $pr, 'd' => $d), 'http://appdp.com/openapi/apps.php');
	?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<p><label for="<?php echo $this->get_field_id('c'); ?>"><?php _e('分类'); ?></label> 
		<select id="<?php echo $this->get_field_id('c'); ?>" class="widefat" name="<?php echo $this->get_field_name('c'); ?>">
			<option value=""<?php selected( $instance['c'], '' ); ?>>全部分类</option>
			<option value="children-education"<?php selected( $instance['c'], 'children-education' ); ?>>儿童教育</option>
			<option value="books"<?php selected( $instance['c'], 'books' ); ?>>书籍</option>
			<option value="business"<?php selected( $instance['c'], 'business' ); ?>>商业</option>
			<option value="education"<?php selected( $instance['c'], 'education' ); ?>>教育</option>
			<option value="entertainment"<?php selected( $instance['c'], 'entertainment' ); ?>>娱乐</option>
			<option value="finance"<?php selected( $instance['c'], 'finance' ); ?>>财务</option>
			<option value="games"<?php selected( $instance['c'], 'games' ); ?>>游戏</option>
			<option value="healthcare-fitness"<?php selected( $instance['c'], 'healthcare-fitness' ); ?>>健康</option>
			<option value="lifestyle"<?php selected( $instance['c'], 'lifestyle' ); ?>>生活</option>
			<option value="medical"<?php selected( $instance['c'], 'medical' ); ?>>医疗</option>
			<option value="music"<?php selected( $instance['c'], 'music' ); ?>>音乐</option>
			<option value="navigation"<?php selected( $instance['c'], 'navigation' ); ?>>导航</option>
			<option value="news"<?php selected( $instance['c'], 'news' ); ?>>新闻</option>
			<option value="photography"<?php selected( $instance['c'], 'photography' ); ?>>摄影</option>
			<option value="productivity"<?php selected( $instance['c'], 'productivity' ); ?>>效率</option>
			<option value="reference"<?php selected( $instance['c'], 'reference' ); ?>>参考</option>
			<option value="social-networking"<?php selected( $instance['c'], 'social-networking' ); ?>>社交</option>
			<option value="sports"<?php selected( $instance['c'], 'sports' ); ?>>体育</option>
			<option value="travel"<?php selected( $instance['c'], 'travel' ); ?>>旅行</option>
			<option value="utilities"<?php selected( $instance['c'], 'utilities' ); ?>>工具</option>
			<option value="general"<?php selected( $instance['c'], 'general' ); ?>>应用</option>
		</select></p>
		<p>
			<label for="<?php echo $this->get_field_id('pr'); ?>"><?php _e( '价格' ); ?></label>
		<select id="<?php echo $this->get_field_id('pr'); ?>" class="widefat" name="<?php echo $this->get_field_name('pr'); ?>">
			<option value=""<?php selected( $instance['pr'], '' ); ?>>全部</option>
			<option value="free"<?php selected( $instance['pr'], 'free' ); ?>>免费</option>
			<option value="paid"<?php selected( $instance['pr'], 'paid' ); ?>>收费</option>
			<option value="price-drop"<?php selected( $instance['pr'], 'price-drop' ); ?>>限时免费</option>
		</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('d'); ?>"><?php _e( '设备类型' ); ?></label>
		<select id="<?php echo $this->get_field_id('d'); ?>" class="widefat" name="<?php echo $this->get_field_name('d'); ?>">
			<option value=""<?php selected( $instance['d'], '' ); ?>>全部</option>
			<option value="iphone"<?php selected( $instance['d'], 'iphone' ); ?>>iPhone</option>
			<option value="ipad"<?php selected( $instance['d'], 'ipad' ); ?>>iPad</option>
			<option value="universal"<?php selected( $instance['d'], 'universal' ); ?>>通用</option>
		</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('ppp'); ?>"><?php _e( '数量' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('ppp'); ?>" name="<?php echo $this->get_field_name('ppp'); ?>" type="text" value="<?php echo $ppp; ?>" /></p>
		<p>
			<label for="<?php echo $this->get_field_id('live'); ?>"><?php _e( '缓存时间(秒)' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('live'); ?>" name="<?php echo $this->get_field_name('live'); ?>" type="text" value="<?php echo $live; ?>" /></p>
<?php
	}
}

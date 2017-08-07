<?php
/*
 * Plugin Name:  WordPress / WooCommerce AJAX Search
 * Plugin URI: http://www.xadapter.com/
 * Description: Fast and Easy ajax search of posts using shortcode [eh_ajax_search].
 * Author: XAdapter
 * Author URI: http://www.xadapter.com/
 * Version: 1.0.1
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('EH_AJAX_SEARCH_MAIN_URL')) {
    define('EH_AJAX_SEARCH_MAIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('EH_AJAX_SEARCH_MAIN_PATH')) {
    define('EH_AJAX_SEARCH_MAIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('EH_AJAX_SEARCH_MAIN_JS')) {
    define('EH_AJAX_SEARCH_MAIN_JS', EH_AJAX_SEARCH_MAIN_URL . "assets/js/");
}
if (!defined('EH_AJAX_SEARCH_MAIN_CSS')) {
    define('EH_AJAX_SEARCH_MAIN_CSS', EH_AJAX_SEARCH_MAIN_URL . "assets/css/");
}
if(!class_exists("EH_Ajax_Search"))
{
    class EH_Ajax_Search {
        public $search_history;
        function __construct() {
            add_shortcode('eh_ajax_search', array($this, 'eh_ajax_search_callback'));
            add_action('wp_ajax_eh_ajax_search_data',array($this,'eh_ajax_search_data_count'));
            add_action('wp_ajax_nopriv_eh_ajax_search_data',array($this,'eh_ajax_search_data_count'));
            add_action('wp_ajax_eh_ajax_search_data_save',array($this,'eh_ajax_search_data_save'));
            add_action('wp_ajax_nopriv_eh_ajax_search_data_save',array($this,'eh_ajax_search_data_save'));
            add_action('admin_menu', array( $this, 'search_admin_menu' ) );
            add_filter( 'set-screen-option', array($this,'set_screen'), 10, 3 );
            add_action('admin_enqueue_scripts',array($this,'eh_admin_scripts'));
        }
        function set_screen( $status, $option, $value ) {
            return $value;
	}
        function eh_ajax_search_callback($atts) {
            wp_enqueue_script("jquery");
            $search = "post,product,page,fourm,topic";
            $thumb = "yes";
            $content = "yes";
            $search_in = "title,content";
            $target = "same";
            $placeholder = "";
            $save_result = "no";
            if(isset($atts['search']))
            {
                $search = $atts['search'];
            }
            if(isset($atts['placeholder']))
            {
                $placeholder = $atts['placeholder'];
            }
            if(isset($atts['save_results']))
            {
                if($atts['save_results'] === 'yes' || $atts['save_results'] === 'no')
                {
                    $save_result = $atts['save_results'];
                }
            }
            if(isset($atts['show_thumbs']))
            {   if($atts['show_thumbs'] === 'yes' || $atts['show_thumbs'] === 'no')
                {
                    $thumb = $atts['show_thumbs'];
                }
            }
            if(isset($atts['show_contents']))
            {
                if($atts['show_contents'] === 'yes' || $atts['show_contents'] === 'no')
                {
                    $content = $atts['show_contents'];
                }
            }
            if(isset($atts['target']))
            {
                if($atts['target'] === 'same' || $atts['target'] === 'new')
                {
                    $target = $atts['target'];
                }
            }
            if(isset($atts['search_in']))
            {
                $in_a = explode(",",$atts['search_in']);
                $in_e = array();
                if(in_array("title", $in_a))
                {
                    array_push($in_e,"title");
                }
                if(in_array("content", $in_a))
                {
                    array_push($in_e,"content");
                }
                $search_in = implode(",", $in_e);
            }
            wp_enqueue_style("eh_ajax_search",EH_AJAX_SEARCH_MAIN_CSS ."eh-ajax-search.css");
            wp_enqueue_script("eh_ajax_search",EH_AJAX_SEARCH_MAIN_JS . "eh-ajax-search.js");
            wp_localize_script("eh_ajax_search", "eh_ajax_search_object", array('home_url'=>esc_url(home_url('/')),'ajax_url' => admin_url('admin-ajax.php'),'search'=>$search,"show_thumbs"=>$thumb,"search_in"=>$search_in,"show_contents"=>$content,"target"=>$target,"save_results"=>$save_result));
            $content = '
                        <div class="eh-ajax-search-div">
                        <form role="search" method="get" id="eh-ajax-search-form" action="'.esc_url(home_url('/')).'">
                            <input type="text" name="s" id="eh-ajax-search-text" class="eh-ajax-search-icon " placeholder="'.$placeholder.'">
                        </form>
                        <div class="eh-ajax-search-result">
                            <center><div class="eh-ajax-search-result-arrow-up"></div></center>
                            <div class="eh-ajax-search-append" />
                        </div>
                        ';
            return $content;
        }
        function eh_ajax_search_data_count() {
            global $wpdb;
            $table = $wpdb->prefix . 'posts';
            $like = sanitize_text_field($_POST['q']);
            $search = explode(",", sanitize_text_field($_POST['search']));
            $search_in = explode(",",sanitize_text_field($_POST['search_in']));
            $show_thumbs = $_POST['show_thumbs'];
            $show_contents = $_POST['show_contents'];
            $query = "";
            if(in_array("title", $search_in))
            {
                $query = "(LOWER(post_title) LIKE lower('%$like%'))";
            }
            if(in_array("content", $search_in))
            {
                if($query === "")
                {
                    $query = "(LOWER(post_content) LIKE lower('%$like%'))";
                }
                else
                {
                    $query .= " OR (LOWER(post_content) LIKE lower('%$like%'))";
                }
            }
            $search_query = "SELECT ID FROM " . $table . " WHERE $query AND post_status ='publish'";
            $quote_ids = array();
            $response = array();
            $results = $wpdb->get_results($search_query, ARRAY_A);
            for ($i = 0; $i < count($results); $i++) {
                $quote_ids[$i] = $results[$i]['ID'];
            }
            $args = array(
                'orderby' => 'ID',
                'numberposts' => -1,
                'post_type' => $search,
                'post__in' => $quote_ids
            );
            $posts = array();
            if(!empty($quote_ids))
            {
                $posts = get_posts($args);
            }
            for ($i = 0; $i < count($posts); $i++) {
                $response[$i]['id'] = $posts[$i]->ID;
                $response[$i]['title'] = $posts[$i]->post_title;
                $response[$i]['guid'] = get_permalink($posts[$i]->ID);
                if($show_contents === "yes")
                {
                    $response[$i]['content'] = (strlen($posts[$i]->post_content) > 100 ? substr($posts[$i]->post_content,0,100)."..." : $posts[$i]->post_content);
                }
                $response[$i]['type'] = ucfirst($posts[$i]->post_type);
                if($show_thumbs === "yes")
                {
                    $response[$i]['thumb'] = get_the_post_thumbnail_url($posts[$i]->ID);
                }
            }
            $res = array("total_count" => count($posts), "items" => $response);
            die(json_encode($res));
        }
        function eh_ajax_search_data_save() {
            $search     = sanitize_text_field($_POST['search']);
            $saved      = get_option("eh_ajax_search_data_count");
            $updated    = get_option("eh_ajax_search_data_updated");
            $updated[$search] = time();
            if(isset($saved[$search]))
            {
                $count = $saved[$search];
                $saved[$search]= $count+1;
            }
            else
            {
                $saved[$search]= 1;
            }
            update_option("eh_ajax_search_data_count", $saved);
            update_option("eh_ajax_search_data_updated", $updated);
            die();
        }
        function search_admin_menu() {
            $hook = add_options_page( "Search History", "Search History", "manage_options", "eh_search_history", array($this,"eh_search_menu_callback"));
            add_action( "load-$hook", [ $this, 'screen_option' ] );
        }
        function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Search History',
			'default' => 5,
			'option'  => 'results_per_page'
		];

		add_screen_option( $option, $args );
		$this->search_history = new EH_Search_History();
	}
        function eh_admin_scripts() {
            if(isset($_GET['page']) && $_GET['page'] === "eh_search_history")
            {
                wp_enqueue_script("jquery");
                wp_enqueue_script("eh-admin-search-scripts", EH_AJAX_SEARCH_MAIN_JS."eh_admin_search.js");
            }
        }
        function eh_search_menu_callback() {
            $search = "";
            if(isset($_GET['search_history']) && $_GET['search_history']==="clear")
            {
                update_option("eh_ajax_search_data_count", array());
                update_option("eh_ajax_search_data_updated", array());
                header("Location:".admin_url("options-general.php?page=eh_search_history"));
            }
            if(isset($_GET['s']))
            {
                $search = sanitize_text_field($_GET['s']);
            }
            if(isset($_GET['search_history']) && $_GET['search_history']==="export")
            {
                $count = get_option('eh_ajax_search_data_count');
                $update = get_option("eh_ajax_search_data_updated");
                $result = array();
                $i=0;
                foreach ($count as $key => $value) {
                    $result[$i]['keyword'] = $key;
                    $result[$i]['count'] = $value;
                    $result[$i]['updated'] = get_date_from_gmt(date("M d, Y h:i:s A",$update[$key]), get_option("links_updated_date_format"));
                    $i++;
                }
                $filename = "search_history_".get_date_from_gmt(date("M d, Y h:i:s A",time()), get_option("links_updated_date_format")).".csv"; 
                $f = fopen("php://output",'x+');
                ob_clean();
                fputcsv($f, array('Keyword', 'Number of Searches', 'Last Searched on'),",");
                foreach ($result as $line) {
                    fputcsv($f, $line,",");
                }
                header("Content-Disposition: attachment; filename='".$filename."'");
                header("Content-Type: text/csv");
                fclose($f);
                exit;
            }
            ?>
            <style>
                .metabox-holder
                {
                    width:100%;
                }
            </style>
            <div class="wrap" id="eh_search_history_wrap">
                <h1 class="wp-heading-inline">Search History</h1>
                <?php
                    $data = get_option("eh_ajax_search_data_count");
                    if(count($data)!=0)
                    {
                        ?>
                        <a href="<?php echo admin_url("options-general.php?page=eh_search_history&search_history=clear"); ?>" class="page-title-action" id="clear_history_a">Clear History</a>
                        <a href="<?php echo admin_url("options-general.php?page=eh_search_history&search_history=export"); ?>" class="page-title-action">Export History</a>
                        <?php
                    }
                ?>
                <hr class="wp-header-end">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <div class="meta-box-sortables ui-sortable">
                                <form method="get" action="<?php echo admin_url("options-general.php");?>">
                                    <input type="hidden" name="page" value="eh_search_history">
                                    <?php
                                        $this->search_history->search = $search;
                                        $this->search_history->prepare_items();
                                        $this->search_history->search_box('Search History', 'keyword');
                                        $this->search_history->display(); 
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <br class="clear">
                </div>
            </div>
            <?php
        } 
    }
}

if (!class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if(!class_exists("EH_Search_History"))
{
    class EH_Search_History extends WP_List_Table {
        public $search;
        public static function get_history( $per_page = 5, $page_number = 1,$search = "", $order='desc') {
            $offset = ($page_number-1)*$per_page;
            $data = get_option("eh_ajax_search_data_count");
            $updated = get_option("eh_ajax_search_data_updated");
            $result=array();
            if(!empty($data))
            {
                if($search!="")
                {
                    $data = array_filter($data, function ($item) use ($search) {
                        if (stripos($item, $search) !== false) {
                            return true;
                        }
                        return false;
                    },ARRAY_FILTER_USE_KEY);
                }
                $slice = array_slice($data,$offset,$per_page);
                if($order === 'asc')
                {
                    asort($slice,SORT_REGULAR);
                }
                else
                {
                    arsort($slice,SORT_REGULAR);
                }
                $i=0;
                foreach ($slice as $key => $value) {
                    $result[$i]['keyword'] = $key;
                    $result[$i]['count'] = $value;
                    $result[$i]['updated'] = get_date_from_gmt(date("M d, Y h:i:s A",$updated[$key]), get_option("links_updated_date_format"));
                    $i++;
                }
            }
            return $result;
        }
        public static function delete_history( $id ) {
            $data = get_option("eh_ajax_search_data_count");
            $updated = get_option("eh_ajax_search_data_updated");
            unset($data[$id]);
            unset($updated[$id]);
            update_option("eh_ajax_search_data_count", $data);
            update_option("eh_ajax_search_data_updated", $updated);
        }
        public static function record_count() {
            $data = get_option("eh_ajax_search_data_count");
            return count($data);
        }
        public function no_items() {
            _e( 'No Search History.', 'eh_ajax_search' );
        }
        function column_cb( $item ) {
            return sprintf(
                '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['keyword']
            );
        }
        function column_keyword( $item ) {
            return $item['keyword'];
        }
        function column_count( $item ) {
            return $item['count'];
        }
        function column_updated( $item ) {
            return $item['updated'];
        }
        function get_columns() {
            $columns = [
                'cb'      => '<input type="checkbox" />',
                'keyword'    => __( 'Search Keywords', 'eh_ajax_search' ),
                'count' => __( 'Number of Searches', 'eh_ajax_search' ),
                'updated' => __( 'Last Searched on', 'eh_ajax_search' )
            ];

            return $columns;
        }
        public function get_sortable_columns() {
            $sortable_columns = array(
                'count' => array( 'count', true )
            );
            return $sortable_columns;
        }
        public function get_bulk_actions() {
            $actions = [
                'bulk-delete' => 'Delete'
            ];
            return $actions;
        }
        public function prepare_items() {
            $this->_column_headers = $this->get_column_info();
            $this->process_bulk_action();
            $per_page     = $this->get_items_per_page( 'results_per_page', 5 );
            $current_page = $this->get_pagenum();
            $total_items  = self::record_count();
            $this->set_pagination_args( [
                    'total_items' => $total_items,
                    'per_page'    => $per_page
            ] );
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc';
            $data = self::get_history( $per_page, $current_page,$this->search,$order);
            $this->items = $data;
        }

        public function process_bulk_action() {
            if ( 'delete' === $this->current_action() ) {
                $nonce = esc_attr( $_REQUEST['_wpnonce'] );
                if ( ! wp_verify_nonce( $nonce, 'eh_delete_history' ) ) {
                        die( 'Access Denied' );
                }
                else {
                    self::delete_history( absint( $_GET['keyword'] ) );
                    wp_redirect( esc_url_raw(add_query_arg()) );
                    exit;
                }
            }
            if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
                 || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
            ) {
                $delete_ids = esc_sql( $_POST['bulk-delete'] );
                foreach ( $delete_ids as $id ) {
                    self::delete_history( $id );
                }
                wp_redirect( esc_url_raw(add_query_arg()) );
                exit;
            }
        }
    }
}
add_action("init", 'eh_ajax_search_init');
function eh_ajax_search_init()
{
    new EH_Ajax_Search();
}

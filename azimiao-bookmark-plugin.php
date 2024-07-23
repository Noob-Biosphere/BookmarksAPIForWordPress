<?php
/**
 * Plugin Name: Azimiao's Website Bookmarks
 * Requires Plugins: simple-custom-post-order
 * Plugin URI: https://github.com/Azimiao/BookmarksAPIForWordPress
 * Description: 在后台添加或删除书签链接，并提供一个获取列表的 AJAX 接口，以此作为其他导航前端的数据源（注：大部分代码由 ChatGPT 完成）。
 * Version: 1.0
 * Author: Azimiao
 * Author URI: https://www.azimiao.com
 * License: GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/


// custom post type：meow_bookmark
add_action('init', 'create_meow_bookmark_post_type');

function create_meow_bookmark_post_type()
{
    $labels = array(
        'name'               => __('Bookmarks'),
        'singular_name'      => __('Bookmark'),
        'menu_name'          => __('Bookmarks'),
        'name_admin_bar'     => __('Bookmark'),
        'add_new'            => __('Add'),
        'add_new_item'       => __('Add New Bookmark'),
        'new_item'           => __('New Bookmark'),
        'edit_item'          => __('Edit Bookmark'),
        'view_item'          => __('View Bookmark'),
        'all_items'          => __('All Bookmarks'),
        'search_items'       => __('Search Bookmarks'),
        'parent_item_colon'  => __('Parent Bookmarks:'),
        'not_found'          => __('No bookmarks found.'),
        'not_found_in_trash' => __('No bookmarks found in Trash.')
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __('Description.'),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => true,
        'menu_position'      => null,
        'supports'           => array('title', 'author'),
        'menu_icon'          => 'dashicons-admin-links'
    );

    register_post_type('meow_bookmark', $args);
}


// custom taxonmies: meow_bookmark_taxonomy
add_action('init', 'create_meow_bookmark_taxonomies');

function create_meow_bookmark_taxonomies()
{
    $labels = array(
        'name' => __('Bookmark Categories'),
        'singular_name' => __('Categories'),
        'search_items' => __('Search Categories'),
        'all_items' => __('All Categories'),
        'parent_item' => __('Parent Category'),
        'parent_item_colon' => __('Parent Category'),
        'edit_item' => __('Edit Category'),
        'update_item' => __('Update Category'),
        'add_new_item' => __('Add New Category'),
        'new_item_name' => __('New Category'),
        'menu_name' => __('Bookmark Categories'),
    );
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'tax_position' => true,
        'show_admin_column' => true,
        'show_ui' => true,
        'show_in_rest' => true,
    );
    register_taxonomy('meow_bookmark_taxonomy', 'meow_bookmark', $args);
}

add_action('rest_api_init', 'create_meow_bookmark_meta');

function create_meow_bookmark_meta()
{
    register_rest_field('meow_bookmark', 'raw', array(
        'get_callback'      => 'meow_bookmarks_meta_callback',
        'update_callback'   => null,
        'schema'            => null,
    ));
}

add_filter( 'rest_meow_bookmark_collection_params', 'meow_bookmark_add_rest_orderby_params', 10, 1 );

function meow_bookmark_add_rest_orderby_params( $params ) {
    $params['orderby']['enum'][] = 'menu_order';
    return $params;
}

function meow_bookmarks_meta_callback($bookmarks, $field_name, $request)
{

    $bookmark_id = $bookmarks['id'];
    $bookmark_name =  $bookmarks['title']['raw'];
    $bookmark_taxonomy =  $bookmarks['meow_bookmark_taxonomy'];
    $bookmark_description = get_post_meta($bookmark_id, 'bookmark_desc', true);
    $bookmark_link = get_post_meta($bookmark_id, 'bookmark_link', true);
    $bookmark_icon_id = get_post_meta($bookmark_id, 'bookmark_icon_local', true);
    $bookmark_icon_external = get_post_meta($bookmark_id, 'bookmark_icon', true);

    return array(
        'id' => $bookmark_id,
        'name' => $bookmark_name,
        'categories' => $bookmark_taxonomy,
        'description' => $bookmark_description,
        'link' => $bookmark_link,
        'icon' => $bookmark_icon_id ? wp_get_attachment_url($bookmark_icon_id) : "",
        'icon_third' => $bookmark_icon_external,
    );
}



// custom meta box
add_action('add_meta_boxes', 'add_meow_bookmark_meta_box');

function add_meow_bookmark_meta_box()
{
    add_meta_box(
        'bookmark_meta_box',
        __('Details'),
        'display_meow_bookmark_meta_box',
        'meow_bookmark',
        'normal',
        'high'
    );
}

// 显示书签元框内容
function display_meow_bookmark_meta_box($post)
{
    // 获取已保存的值
    $bookmark_desc = get_post_meta($post->ID, 'bookmark_desc', true);
    $bookmark_link = get_post_meta($post->ID, 'bookmark_link', true);
    $bookmark_icon = get_post_meta($post->ID, 'bookmark_icon', true);
    $bookmark_icon_local_id = get_post_meta($post->ID, 'bookmark_icon_local', true);

    // 添加 nonce 以进行安全检查
    wp_nonce_field(basename(__FILE__), 'meow_bookmark_meta_box_nonce');

    // 显示字段表单
?>
    <p>
        <label for="bookmark_desc"><?php _e('Description'); ?></label>
        <input type="text" id="bookmark_desc" name="bookmark_desc" value="<?php echo esc_attr($bookmark_desc); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="bookmark_link"><?php _e('Link'); ?></label>
        <input type="text" id="bookmark_link" name="bookmark_link" value="<?php echo esc_attr($bookmark_link); ?>" style="width: 100%;">
    </p>

    <div class="bookmark_icon_local_container">
        <label for="bookmark_icon_local"><?php _e('Image'); ?></label>
        <input type="hidden" id="bookmark_icon_local" name="bookmark_icon_local" value="<?php echo esc_attr($bookmark_icon_local_id) ?>" />
        <div id="bookmark_icon_local_preview">
            <?php
            if ($bookmark_icon_local_id) {
                echo wp_get_attachment_image($bookmark_icon_local_id, 'thumbnail');
            }
            ?>
        </div>

        <button id="meow_bookmarks-select-media-button" class="meow_bookmarks-select-media-button button"><?php _e('Select image'); ?></button>
        <button id="meow_bookmarks-remove-media-button" class="bookmark_icon_local_remove_button button"><?php _e('Remove image'); ?></button>
    </div>
    <p>
        <label for="bookmark_icon"><?php _e('Image URL'); ?>(external)</label>
        <input type="text" id="bookmark_icon" name="bookmark_icon" value="<?php echo esc_attr($bookmark_icon); ?>" style="width: 100%;">
    </p>
    <script>
        jQuery(document).ready(function($) {
            // 上传/选择图片按钮点击事件
            $('#meow_bookmarks-select-media-button').click(function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: "<?php _e('Add media') ?>",
                    multiple: false,
                    button: {
                        text: "<?php _e('OK') ?>",
                    },
                    library: {
                        type: 'image',
                    },
                });

                frame.on('select', function(e) {
                    var uploaded_image = frame.state().get('selection').first().toJSON();
                    $('#bookmark_icon_local').val(uploaded_image.id);
                    $('#bookmark_icon_local_preview').html('<img src="' + uploaded_image.url + '" />');
                });

                frame.open();
            });

            // 删除图片按钮点击事件
            $('#meow_bookmarks-remove-media-button').click(function(e) {
                e.preventDefault();
                $('#bookmark_icon_local').val('');
                $('#bookmark_icon_local_preview').html('');
            });
        });
    </script>
<?php
}

// save meta data when save post
add_action('save_post', 'save_meow_bookmark_meta_box_data');

function save_meow_bookmark_meta_box_data($post_id)
{

    if (!isset($_POST['meow_bookmark_meta_box_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['meow_bookmark_meta_box_nonce'], basename(__FILE__))) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // 保存数据
    if (isset($_POST['bookmark_desc'])) {
        update_post_meta($post_id, 'bookmark_desc', sanitize_text_field($_POST['bookmark_desc']));
    }
    if (isset($_POST['bookmark_link'])) {
        update_post_meta($post_id, 'bookmark_link', sanitize_text_field($_POST['bookmark_link']));
    }

    if (isset($_POST['bookmark_icon_local'])) {
        update_post_meta($post_id, 'bookmark_icon_local', sanitize_text_field($_POST['bookmark_icon_local']));
    }

    if (isset($_POST['bookmark_icon'])) {
        update_post_meta($post_id, 'bookmark_icon', sanitize_text_field($_POST['bookmark_icon']));
    }
}


// 管理页面加载 media 
add_action('admin_enqueue_scripts', 'load_media_files');

function load_media_files()
{
    global $typenow;
    // global $pagenow;

    if (
        //     //$pagenow === 'edit.php' && 
        $typenow == 'meow_bookmark'
    ) {
        wp_enqueue_media();
    }
}


// show custom filter dropdown
add_action('restrict_manage_posts', 'add_meow_bookmark_filter_to_posts_admin');

function add_meow_bookmark_filter_to_posts_admin()
{
    global $typenow;
    if ('meow_bookmark' === $typenow) {
        $taxonomy = 'meow_bookmark_taxonomy';
        $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        $info_taxonomy = get_taxonomy($taxonomy);
        wp_dropdown_categories(array(
            'show_option_all' => __('All Categories'),
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => $selected,
            'show_count'      => true,
            'hide_empty'      => true,
            'value_field'     => 'slug'
        ));
    }
}

// regist ajax handler
add_action('wp_ajax_get_meow_bookmarks', 'get_meow_bookmarks_callback');
add_action('wp_ajax_nopriv_get_meow_bookmarks', 'get_meow_bookmarks_callback'); // 处理未登录用户的请求
function get_meow_bookmarks_callback()
{
    header("Access-Control-Allow-Origin: *");


    $allTerms = get_terms(array(
        'taxonomy' => 'meow_bookmark_taxonomy',
        'order' => 'ASC',
        'orderby'  => 'menu_order',
    ));
    
    $allTermsCopy = array();

    foreach($allTerms as &$currentTerm){
        $newTerm = array(
            'id' => $currentTerm->term_id,
            'term_taxonomy_id' => $currentTerm->term_taxonomy_id,
            'name' =>  $currentTerm->name,
            'slug' => $currentTerm->slug,
            'description' => $currentTerm->description,
            'parent' => $currentTerm->parent
        );
        $allTermsCopy[] = $newTerm;
    }
    
    unset($currentTerm);

    $args = array(
        'post_type' => 'meow_bookmark',
        'posts_per_page' => -1,
        'order' => 'ASC',
        "orderby" => 'menu_order',
    );


    $bookmarks = array();
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $bookmark_id = get_the_ID();
            $bookmark_name = get_the_title();
            $bookmark_taxonomy = get_the_terms($bookmark_id, 'meow_bookmark_taxonomy');
            $bookmark_description = get_post_meta($bookmark_id, 'bookmark_desc', true);
            $bookmark_link = get_post_meta($bookmark_id, 'bookmark_link', true);
            $bookmark_icon_id = get_post_meta($bookmark_id, 'bookmark_icon_local', true);
            $bookmark_icon_external = get_post_meta($bookmark_id, 'bookmark_icon', true);

            $bookmark_taxonomy_ids = array();

            if ($bookmark_taxonomy != null && count($bookmark_taxonomy) > 0) {

                $bookmark_taxonomy_count = count($bookmark_taxonomy);

                for ($i = 0; $i < $bookmark_taxonomy_count; $i++) {
                    # code...
                    $bookmark_taxonomy_ids[] = $bookmark_taxonomy[$i]->term_id;
                }
            }

            $bookmark_data = array(
                'id' => $bookmark_id,
                'name' => $bookmark_name,
                'categories' => $bookmark_taxonomy_ids,
                'description' => $bookmark_description,
                'link' => $bookmark_link,
                'icon' => $bookmark_icon_id ? wp_get_attachment_url($bookmark_icon_id) : "",
                'icon_third' => $bookmark_icon_external,
            );

            $bookmarks[] = $bookmark_data;
        }
    }

    wp_reset_postdata();
    $result = array();
    $result['categories'] = $allTermsCopy;
    $result['bookmarks'] = $bookmarks;
    // 返回 JSON 数据
    wp_send_json($result);
}

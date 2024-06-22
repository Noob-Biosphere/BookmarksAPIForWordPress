<?php
/*
Plugin Name: Azimiao's Website Bookmarks
Plugin URI: https://www.azimiao.com
Description: 在后台添加或删除书签链接，并提供一个获取列表的 AJAX 接口，以此作为其他导航前端的数据源（注：大部分代码由 ChatGPT 完成）。
Version: 1.0
Author: Azimiao
Author URI: https://github.com/Azimiao/BookmarksAPIForWordPress
License: GPL-3.0+
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
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
        // 'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'author'),
    );

    register_post_type('meow_bookmark', $args);
}


// custom taxonmies: meow_bookmark_taxonomy
add_action('init', 'create_meow_bookmark_taxonomies');

function create_meow_bookmark_taxonomies()
{
    $labels = array(
        'name' => __('Categories list'),
        'singular_name' => __('Categories'),
        'search_items' => __('Search Categories'),
        'all_items' => __('All Categories'),
        'parent_item' => __('Parent Category'),
        'parent_item_colon' => __('Parent Category'),
        'edit_item' => __('Edit Category'),
        'update_item' => __('Update Category'),
        'add_new_item' => __('Add New Category'),
        'new_item_name' => __('New Category'),
        'menu_name' => __('Categories list'),
    );
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_ui' => true,
        // 'show_in_rest' => true,
    );
    register_taxonomy('meow_bookmark_taxonomy', 'meow_bookmark', $args);
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
        <input type="hidden" id="bookmark_icon_local" name="bookmark_icon_local" value=" <?php esc_attr($bookmark_icon_local_id) ?>" />
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

function meow_bookmark_taxonomy_add_meta_field()
{
?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="priority"><?php _e('Priority'); ?></label>
        </th>
        <td>
            <input type="number" name="priority" id="priority" value="0" />
            <p class="description"><?php _e('Enter priority number for sorting.'); ?></p>
        </td>
    </tr>
<?php
}

add_action('meow_bookmark_taxonomy_add_form_fields', 'meow_bookmark_taxonomy_add_meta_field');

// 添加字段输入框到分类编辑页面
function meow_bookmark_taxonomy_edit_meta_field($term)
{

    $priority = get_term_meta($term->term_id, 'priority', true);
?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="priority"><?php _e('Priority'); ?></label>
        </th>
        <td>
            <input type="number" name="priority" id="priority" value="<?php echo esc_attr($priority); ?>" />
            <p class="description"><?php _e('Enter priority number for sorting.'); ?></p>
        </td>
    </tr>
<?php
}

add_action('meow_bookmark_taxonomy_edit_form_fields', 'meow_bookmark_taxonomy_edit_meta_field');

// 保存字段数据
function save_meow_bookmark_taxonomy_custom_meta($term_id)
{

    if (isset($_POST['priority'])) {
        $priority = sanitize_text_field($_POST['priority']);
        update_term_meta($term_id, 'priority', intval($priority));
    } else {
        update_term_meta($term_id, 'priority', 0);
    }
}

add_action('created_meow_bookmark_taxonomy', 'save_meow_bookmark_taxonomy_custom_meta');
add_action('edited_meow_bookmark_taxonomy', 'save_meow_bookmark_taxonomy_custom_meta');

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
        'order' => 'DESC',
        'orderby'  => 'meta_value_num',
        'meta_key' => 'priority',
    ));

    $allTermsCopy = array();
    $allTermsCount = count($allTerms);

    //var_dump($allTerms);
    for ($i = 0; $i < $allTermsCount; $i++) {
        # code...
        $currentTerm = $allTerms[$i];

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


    $args = array(
        'post_type' => 'meow_bookmark',
        'posts_per_page' => -1,
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

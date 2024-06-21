<?php
/*
Plugin Name: Azimiao's Website Bookmarks
Plugin URI: https://www.azimiao.com
Description: 在后台添加或删除书签链接，并提供一个获取列表的 AJAX 接口，以此作为其他导航前端的数据源（注：大部分代码由 Chatgpt 完成）。
Version: 1.0
Author: Azimiao
Author URI: https://www.azimiao.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

// 注册自定义 post type：bookmarks
// TODO: translate
function create_meow_bookmark_post_type() {
    $labels = array(
        'name'               => __( 'Bookmarks'),
        'singular_name'      => __( 'Bookmark'),
        'menu_name'          => __( 'Bookmarks'),
        'name_admin_bar'     => __( 'Bookmark'),
        'add_new'            => __( 'Add New'),
        'add_new_item'       => __( 'Add New Bookmark'),
        'new_item'           => __( 'New Bookmark'),
        'edit_item'          => __( 'Edit Bookmark'),
        'view_item'          => __( 'View Bookmark'),
        'all_items'          => __( 'All Bookmarks'),
        'search_items'       => __( 'Search Bookmarks'),
        'parent_item_colon'  => __( 'Parent Bookmarks:'),
        'not_found'          => __( 'No bookmarks found.'),
        'not_found_in_trash' => __( 'No bookmarks found in Trash.')
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Description.'),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title','author'),
    );

    register_post_type( 'meow-bookmark', $args );
}
add_action( 'init', 'create_meow_bookmark_post_type' );

// 添加书签扩展字段
function add_meow_bookmark_meta_box() {
    add_meta_box(
        'bookmark_meta_box',
        __('Details'),
        'display_meow_bookmark_meta_box',
        'meow-bookmark',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'add_meow_bookmark_meta_box' );

// 显示书签元框内容
function display_meow_bookmark_meta_box( $post ) {
    // 获取已保存的值
    $bookmark_desc = get_post_meta( $post->ID, 'bookmark_desc', true );
    $bookmark_link = get_post_meta( $post->ID, 'bookmark_link', true );
    $bookmark_icon = get_post_meta( $post->ID, 'bookmark_icon', true );

    // 添加 nonce 以进行安全检查
    wp_nonce_field( basename( __FILE__ ), 'meow_bookmark_meta_box_nonce' );

    // 显示字段表单
    ?>
    <p>
        <label for="bookmark_desc"><?php _e('Description'); ?>:</label>
        <input type="text" id="bookmark_desc" name="bookmark_desc" value="<?php echo esc_attr( $bookmark_desc ); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="bookmark_link"><?php _e('Link'); ?>:</label>
        <input type="text" id="bookmark_link" name="bookmark_link" value="<?php echo esc_attr( $bookmark_link ); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="bookmark_icon"><?php _e('Image URL'); ?>:</label>
        <input type="text" id="bookmark_icon" name="bookmark_icon" value="<?php echo esc_attr( $bookmark_icon ); ?>" style="width: 100%;">
    </p>
    <?php
}

// 保存书签元框数据
function save_meow_bookmark_meta_box_data( $post_id ) {
    // 检查是否发送了 nonce
    if ( ! isset( $_POST['meow_bookmark_meta_box_nonce'] ) ) {
        return;
    }

    // 验证 nonce
    if ( ! wp_verify_nonce( $_POST['meow_bookmark_meta_box_nonce'], basename( __FILE__ ) ) ) {
        return;
    }

    // 检查是否自动保存
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 检查用户权限
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 保存数据
    if(isset($_POST['bookmark_desc'])){
        update_post_meta( $post_id, 'bookmark_desc', sanitize_text_field( $_POST['bookmark_desc'] ) );
    }
    if ( isset( $_POST['bookmark_link'] ) ) {
        update_post_meta( $post_id, 'bookmark_link', sanitize_text_field( $_POST['bookmark_link'] ) );
    }

    if ( isset( $_POST['bookmark_icon'] ) ) {
        update_post_meta( $post_id, 'bookmark_icon', sanitize_text_field( $_POST['bookmark_icon'] ) );
    }
}
add_action( 'save_post', 'save_meow_bookmark_meta_box_data' );


function create_meow_bookmark_taxonomies() {
    $labels = array(
    'name' => __('Categories list'),
    'singular_name' => __('Categories'),
    'search_items' => __( 'Search Categories' ),
    'all_items' => __( 'All Categories' ),
    'parent_item' => __( 'Parent Category' ),
    'parent_item_colon' => __( 'Parent Category' ),
    'edit_item' => __( 'Edit Category' ),
    'update_item' => __( 'Update Category' ),
    'add_new_item' => __( 'Add New Category' ),
    'new_item_name' => __( 'New Category' ),
    'menu_name' => __( 'Categories list' ),
    );
    $args = array('labels' => $labels,'hierarchical' => true,'show_admin_column' => true);
    register_taxonomy( 'meow-bookmark-taxonomy', 'meow-bookmark', $args );
}
add_action( 'init', 'create_meow_bookmark_taxonomies', 0 );


function add_meow_bookmark_filter_to_posts_admin() {
    global $typenow;
    if ( 'meow-bookmark' === $typenow ) { // 只在文章类型为'bookmark'的情况下添加分类过滤器
        $taxonomy = 'meow-bookmark-taxonomy';
        $selected = isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : '';
        $info_taxonomy = get_taxonomy( $taxonomy );
        wp_dropdown_categories( array(
            'show_option_all' => __('All Categories'),
            'taxonomy'        => $taxonomy,
            'name'            => $taxonomy,
            'orderby'         => 'name',
            'selected'        => $selected,
            'show_count'      => true,
            'hide_empty'      => true,
            'value_field'     => 'slug'
        ) );
    }
}
add_action( 'restrict_manage_posts', 'add_meow_bookmark_filter_to_posts_admin' );


// AJAX 处理函数：获取书签列表
add_action( 'wp_ajax_get_meow_bookmarks', 'get_meow_bookmarks_callback' );
add_action( 'wp_ajax_nopriv_get_bookmarks', 'get_meow_bookmarks_callback' ); // 处理未登录用户的请求
function get_meow_bookmarks_callback() {

    $args = array(
        'post_type' => 'meow-bookmark',
        'posts_per_page' => -1,
    );

    $bookmarks = array();
    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $bookmark_id = get_the_ID();
            $bookmark_name = get_the_title();
            $bookmark_taxonomy = get_the_terms($bookmark_id, 'meow-bookmark-taxonomy');
            $bookmark_description = get_post_meta( $bookmark_id, 'bookmark_desc', true );
            $bookmark_link = get_post_meta( $bookmark_id, 'bookmark_link', true );
            $bookmark_icon = get_post_meta( $bookmark_id, 'bookmark_icon', true );

            $bookmark_data = array(
                'id' => $bookmark_id,
                'name' => $bookmark_name,
                'taxonomy' => $bookmark_taxonomy ? $bookmark_taxonomy : [],
                'description' => $bookmark_description,
                'link' => $bookmark_link,
                'icon' => $bookmark_icon,
            );

            $bookmarks[] = $bookmark_data;
        }
    }

    wp_reset_postdata();

    // 返回 JSON 数据
    wp_send_json( $bookmarks );
}
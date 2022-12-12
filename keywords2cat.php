<?php

// Plugin Name: Keywords2Cat
// Plugin URI: https://www.lightonseo.com/kewords2cat
// Description: Add new post to the right WP Category based on keyword recognition.
// Version: 1.0
// Author: Walid Gabteni
// Author URI: https://www.lightonseo.com
// License: GPL2

// create custom table
function create_keywords2cat_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'keywords2cat';

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      keyword varchar(255) NOT NULL,
      cat_id mediumint(9) NOT NULL,
      PRIMARY KEY  (id)
   ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook(__FILE__, 'create_keywords2cat_table');

// add admin page
function keywords2cat_menu() {
    add_menu_page('Keywords2Cat', 'Keywords2Cat', 'manage_options', 'keywords2cat', 'keywords2cat_admin_page', 'dashicons-admin-page');
}
add_action('admin_menu', 'keywords2cat_menu');

// admin page
function keywords2cat_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'keywords2cat';

    $message = '';
    if (isset($_GET['del'])) {
        $del_id = $_GET['del'];
        $wpdb->delete($table_name, array('id' => $del_id));
        $message = '<div class="updated notice is-dismissible"><p>Association deleted.</p></div>';
    }

    if (isset($_POST['new'])) {
        if ($_POST['keyword'] != '' && $_POST['cat_id'] != '') {
            $wpdb->insert($table_name, array(
                'keyword' => $_POST['keyword'],
                'cat_id' => $_POST['cat_id']
            ));
            $message = '<div class="updated notice is-dismissible"><p>Association added.</p></div>';
        } else {
            $message = '<div class="error notice is-dismissible"><p>Please enter keyword and category.</p></div>';
        }
    }

    if (isset($_POST['update'])) {
        $update_id = $_POST['update'];
        $wpdb->update($table_name, array(
            'keyword' => $_POST['keyword'],
            'cat_id' => $_POST['cat_id']
        ), array('id' => $update_id));
        $message = '<div class="updated notice is-dismissible"><p>Association updated.</p></div>';
    }

    $results = $wpdb->get_results('SELECT * FROM ' . $table_name);
    ?>

    <div class="wrap">
        <h1>Keywords2Cat</h1>
        <?php echo $message; ?>
        <h3>Add New Association</h3>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>
                        <label for="keyword">Keyword</label>
                    </th>
                    <td>
                        <input type="text" name="keyword" id="keyword" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="cat_id">Category</label>
                    </th>
                    <td>
                        <select name="cat_id" id="cat_id">
                            <?php
                            $categories = get_categories();
                            foreach ($categories as $category) {
                                echo '<option value="' . $category->term_id . '">' . $category->name . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <input type="submit" name="new" value="Add Association" class="button button-primary">
        </form>
        <h3>Associations</h3>
        <form method="post">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($results as $row) {
                    ?>
                    <tr>
                        <td>
                            <input type="text" name="keyword" value="<?php echo $row->keyword; ?>" class="regular-text">
                            <input type="hidden" name="update" value="<?php echo $row->id; ?>">
                        </td>
                        <td>
                            <select name="cat_id">
                                <?php
                                foreach ($categories as $category) {
                                    echo '<option value="' . $category->term_id . '"';
                                    if ($category->term_id == $row->cat_id) echo ' selected';
                                    echo '>' . $category->name . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <input type="submit" name="update" value="Update" class="button button-primary">
                            <a href="?page=keywords2cat&del=<?php echo $row->id; ?>" class="button button-secondary">Delete</a>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </form>
    </div>

    <?php
}

// automatically assign category to posts
add_action('save_post', 'keywords2cat_save_post');
function keywords2cat_save_post($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'keywords2cat';
    $results = $wpdb->get_results('SELECT * FROM ' . $table_name);

    $post_content = get_post_field('post_content', $post_id);
    foreach ($results as $row) {
        if (strpos(strtolower($post_content), strtolower($row->keyword)) !== false) {
            wp_set_post_categories($post_id, array($row->cat_id), true);
        }
    }
}
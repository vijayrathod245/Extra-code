<?php
/**
 * Plugin Name: Pricing Table and Blog Plugin with Custom Metabox
 * Description: A custom plugin to display a pricing table with custom metabox fields and a blog section using shortcodes.
 * Version: 1.1
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function create_pricing_table_cpt() {
    register_post_type('pricing_table', [
        'labels'      => [
            'name'          => __('Pricing Tables', 'textdomain'),
            'singular_name' => __('Pricing Table', 'textdomain'),
        ],
        'public'      => true,
        'has_archive' => true,
        'supports'    => ['title'],
    ]);
}
add_action('init', 'create_pricing_table_cpt');

// Add Metabox
function add_pricing_metabox() {
    add_meta_box(
        'pricing_details',
        __('Pricing Plans', 'textdomain'),
        'pricing_metabox_callback',
        'pricing_table',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_pricing_metabox');

// Callback function to show metabox fields
function pricing_metabox_callback($post) {
    $pricing_data = get_post_meta($post->ID, '_pricing_data', true);

    if (!is_array($pricing_data)) {
        $pricing_data = [];
    }

    wp_nonce_field('save_pricing_metabox', 'pricing_metabox_nonce');

    ?>
    <div id="pricing-fields-container">
        <?php foreach ($pricing_data as $index => $data) { ?>
            <div class="pricing-field">
                <label>Plan Name:</label>
                <input type="text" name="pricing_data[<?php echo $index; ?>][plan]" value="<?php echo esc_attr($data['plan']); ?>" />
                
                <label>Price:</label>
                <input type="text" name="pricing_data[<?php echo $index; ?>][price]" value="<?php echo esc_attr($data['price']); ?>" />
                
                <label>Features:</label>
                <textarea name="pricing_data[<?php echo $index; ?>][features]"><?php echo esc_textarea($data['features']); ?></textarea>

                <button type="button" class="remove-pricing">Remove</button>
            </div>
        <?php } ?>
    </div>

    <button type="button" id="add-pricing">Add More</button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let container = document.getElementById('pricing-fields-container');
            let addButton = document.getElementById('add-pricing');

            addButton.addEventListener('click', function() {
                let count = document.querySelectorAll('.pricing-field').length;
                let div = document.createElement('div');
                div.classList.add('pricing-field');
                div.innerHTML = `
                    <label>Plan Name:</label>
                    <input type="text" name="pricing_data[${count}][plan]" value="" />
                    
                    <label>Price:</label>
                    <input type="text" name="pricing_data[${count}][price]" value="" />
                    
                    <label>Features:</label>
                    <textarea name="pricing_data[${count}][features]"></textarea>

                    <button type="button" class="remove-pricing">Remove</button>
                `;
                container.appendChild(div);

                div.querySelector('.remove-pricing').addEventListener('click', function() {
                    div.remove();
                });
            });

            document.querySelectorAll('.remove-pricing').forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.remove();
                });
            });
        });
    </script>

    <style>
        .pricing-field {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        .remove-pricing {
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            display: block;
            margin-top: 5px;
        }
        #add-pricing {
            background: green;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
        }
    </style>
    <?php
}

function save_pricing_metabox($post_id) {
    if (!isset($_POST['pricing_metabox_nonce']) || !wp_verify_nonce($_POST['pricing_metabox_nonce'], 'save_pricing_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['pricing_data']) && is_array($_POST['pricing_data'])) {
        update_post_meta($post_id, '_pricing_data', $_POST['pricing_data']);
    } else {
        delete_post_meta($post_id, '_pricing_data');
    }
}
add_action('save_post', 'save_pricing_metabox');

function display_pricing_table($atts) {
    ?>
    <style>
        .pricing-tables {
            display: flex;
            gap: 20px;
        }
        .pricing-plan {
            border: 1px solid #ddd;
            padding: 20px;
            text-align: center;
            width: 30%;
            background: #f9f9f9;
        }
        .pricing-plan h3 {
            margin-bottom: 10px;
        }
        .pricing-plan ul {
            list-style: none;
            padding: 0;
        }
        .pricing-plan li {
            font-size: 18px;
            margin: 5px 0;
        }
    </style>
    <?php 
    $args = array(
        'post_type'      => 'pricing_table',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        echo '<div class="pricing-tables">';

        while ($query->have_posts()) {
            $query->the_post();
            $pricing_data = get_post_meta(get_the_ID(), '_pricing_data', true);

            echo '<div class="pricing-plan">';
            echo '<h3>' . get_the_title() . '</h3>';
            
            if (!empty($pricing_data)) {
                echo '<ul>';
                foreach ($pricing_data as $data) {
                    echo '<li><strong>' . esc_html($data['plan']) . ':</strong> $' . esc_html($data['price']) . '</li>';
                    echo '<p>' . esc_html($data['features']) . '</p>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
        }

        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No pricing tables found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('pricing_table', 'display_pricing_table');



// Register Pricing Table Custom Post Type
/*function create_pricing_table_cpt() {
    register_post_type('pricing_table', [
        'labels'      => [
            'name'          => __('Pricing Tables', 'textdomain'),
            'singular_name' => __('Pricing Table', 'textdomain'),
        ],
        'public'      => true,
        'has_archive' => true,
        'supports'    => ['title','editor'],
    ]);
}
add_action('init', 'create_pricing_table_cpt');

// Add Group Metabox
function custom_group_metabox() {
    add_meta_box(
        'group_fields_meta',
        __('Custom Group Fields', 'textdomain'),
        'group_metabox_callback',
        'pricing_table', // Post type
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'custom_group_metabox');*/

// Callback function to display metabox
/*function group_metabox_callback($post) {
    $group_data = get_post_meta($post->ID, '_group_data', true);

    // Ensure it's an array
    if (!is_array($group_data)) {
        $group_data = [];
    }

    wp_nonce_field('save_group_metabox', 'group_metabox_nonce');

    ?>
    <div id="group-fields-container">
        <?php
        foreach ($group_data as $index => $data) {
            ?>
            <div class="group-field">
                <label>Title:</label>
                <input type="text" name="group_data[<?php echo $index; ?>][title]" value="<?php echo esc_attr($data['title']); ?>" />
                
                <label>Price:</label>
                <input type="text" name="group_data[<?php echo $index; ?>][price]" value="<?php echo esc_attr($data['price']); ?>" />

                <button type="button" class="remove-group">Remove</button>
            </div>
            <?php
        }
        ?>
    </div>

    <button type="button" id="add-group">Add More</button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let container = document.getElementById('group-fields-container');
            let addButton = document.getElementById('add-group');

            addButton.addEventListener('click', function() {
                let count = document.querySelectorAll('.group-field').length;
                let div = document.createElement('div');
                div.classList.add('group-field');
                div.innerHTML = `
                    <label>Title:</label>
                    <input type="text" name="group_data[${count}][title]" value="" />

                    <label>Price:</label>
                    <input type="text" name="group_data[${count}][price]" value="" />

                    <button type="button" class="remove-group">Remove</button>
                `;
                container.appendChild(div);

                div.querySelector('.remove-group').addEventListener('click', function() {
                    div.remove();
                });
            });

            document.querySelectorAll('.remove-group').forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.remove();
                });
            });
        });
    </script>

    <style>
        .group-field {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
        }
        .remove-group {
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            display: block;
            margin-top: 5px;
        }
        #add-group {
            background: green;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
        }
    </style>
    <?php
}*/

// Save metabox data
/*function save_group_metabox($post_id) {
    if (!isset($_POST['group_metabox_nonce']) || !wp_verify_nonce($_POST['group_metabox_nonce'], 'save_group_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['group_data']) && is_array($_POST['group_data'])) {
        update_post_meta($post_id, '_group_data', $_POST['group_data']);
    } else {
        delete_post_meta($post_id, '_group_data');
    }
}
add_action('save_post', 'save_group_metabox');


function display_group_data($content) {
    if (is_single()) {
        $group_data = get_post_meta(get_the_ID(), '_group_data', true);

        if (!empty($group_data)) {
            $content .= '<h3>Pricing Details:</h3>';
            $content .= '<ul>';
            foreach ($group_data as $data) {
                $content .= '<li><strong>' . esc_html($data['title']) . ':</strong> $' . esc_html($data['price']) . '</li>';
            }
            $content .= '</ul>';
        }
    }
    return $content;
}
add_filter('the_content', 'display_group_data');*/

// Add Metabox for Pricing Details
/*function add_pricing_metabox() {
    add_meta_box(
        'pricing_details',
        __('Pricing Details', 'textdomain'),
        'pricing_metabox_callback',
        'pricing_table',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_pricing_metabox');

function pricing_metabox_callback($post) {
    $price = get_post_meta($post->ID, '_price', true);
    $desc = get_post_meta($post->ID, '_desc', true);
    ?>
    <label for="price">Price:</label>
    <input type="text" name="price" id="price" value="<?php echo esc_attr($price); ?>" />
    <label for="price">Description:</label>
    <input type="text" name="desc" id="desc" value="<?php echo esc_attr($desc); ?>" />
    <?php
}

// Save Metabox Data
function save_pricing_metabox($post_id) {
    if (isset($_POST['price'])) {
        update_post_meta($post_id, '_price', sanitize_text_field($_POST['price']));
    }
    if (isset($_POST['desc'])) {
        update_post_meta($post_id, '_desc', sanitize_text_field($_POST['desc']));
    }
}
add_action('save_post', 'save_pricing_metabox');

// Shortcode for Pricing Table
function custom_pricing_table() {
    ob_start();
    $query = new WP_Query(['post_type' => 'pricing_table', 'posts_per_page' => -1]);
    ?>
    <div class="pricing-table">
        <style>
            .pricing-table {
                display: flex;
                gap: 20px;
            }
            .pricing-plan {
                border: 1px solid #ddd;
                padding: 20px;
                text-align: center;
                width: 30%;
                background: #f9f9f9;
            }
            .pricing-plan h3 {
                margin-bottom: 10px;
            }
            .pricing-plan .price {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 10px;
            }
        </style>
        <?php while ($query->have_posts()) : $query->the _post(); ?>
            <div class="pricing-plan">
                <h3><?php //the_title(); ?></h3>
                <p class="price">$<?php echo get_post_meta(get_the_ID(), '_price', true); ?>/month</p>

                <p class="desc"><?php echo get_post_meta(get_the_ID(), '_desc', true); ?></p>
                <ul>
                    <li><?php echo get_the_excerpt(); ?></li>
                </ul>
            </div>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('pricing_table', 'custom_pricing_table');
*/
// Shortcode for Blog Posts
/*function custom_blog_section() {
    ob_start();
    $query = new WP_Query([ 'posts_per_page' => 5 ]);
    if ($query->have_posts()) {
        echo '<div class="blog-section">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="blog-post">';
            echo '<h3><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
            echo '<p>' . get_the_excerpt() . '</p>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No blog posts found.</p>';
    }
    return ob_get_clean();
}
add_shortcode('blog_section', 'custom_blog_section');*/


// Shortcode for Blog Posts with Category Details
/*function custom_blog_section() {
    ob_start();
    $query = new WP_Query([ 'posts_per_page' => 5 ]);
    $blog_data = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $categories = get_the_category();
            $category_names = [];
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
            $blog_data[] = [
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'excerpt' => get_the_excerpt(),
                'categories' => $category_names,
            ];
        }
        wp_reset_postdata();
    }
    echo '<pre>' . print_r($blog_data, true) . '</pre>';
    return ob_get_clean();
}
add_shortcode('blog_section', 'custom_blog_section');*/

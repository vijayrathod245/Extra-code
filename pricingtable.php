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

// Register Pricing Table Custom Post Type
function create_pricing_table_cpt() {
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

// Add Metabox for Pricing Details
function add_pricing_metabox() {
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
    ?>
    <label for="price">Price:</label>
    <input type="text" name="price" id="price" value="<?php echo esc_attr($price); ?>" />
    <?php
}

// Save Metabox Data
function save_pricing_metabox($post_id) {
    if (isset($_POST['price'])) {
        update_post_meta($post_id, '_price', sanitize_text_field($_POST['price']));
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
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <div class="pricing-plan">
                <h3><?php the_title(); ?></h3>
                <p class="price">$<?php echo get_post_meta(get_the_ID(), '_price', true); ?>/month</p>
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
function custom_blog_section() {
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
add_shortcode('blog_section', 'custom_blog_section');
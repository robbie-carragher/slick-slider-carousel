<?php
/**
 * Plugin Name: Slick Carousel
 * Description: A plugin to display a carousel of partner logos.
 * Version: 1.0
 * Author: Robbie Carragher
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Register custom post type
function slick_carousel_partners_register_post_type() {
    $labels = array(
        'name'               => 'Slides',
        'singular_name'      => 'Slide',
        'menu_name'          => 'Slides',
        'name_admin_bar'     => 'Slide',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Slide',
        'new_item'           => 'New Slide',
        'edit_item'          => 'Edit Slide',
        'view_item'          => 'View Slide',
        'all_items'          => 'All Slides',
        'search_items'       => 'Search Slides',
        'parent_item_colon'  => 'Parent Slides:',
        'not_found'          => 'No slides found.',
        'not_found_in_trash' => 'No slides found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'slide' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'thumbnail' )
    );

    register_post_type( 'slide', $args );
}
add_action( 'init', 'slick_carousel_partners_register_post_type' );

// Add meta box
function slick_carousel_add_meta_box() {
    add_meta_box(
        'slick_carousel_link',
        'Slide Link',
        'slick_carousel_meta_box_callback',
        'slide',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'slick_carousel_add_meta_box' );

// Meta box callback function
function slick_carousel_meta_box_callback( $post ) {
    wp_nonce_field( 'slick_carousel_save_meta_box_data', 'slick_carousel_meta_box_nonce' );
    $value = get_post_meta( $post->ID, '_slick_carousel_link', true );
    echo '<label for="slick_carousel_link">Link URL: </label>';
    echo '<input type="text" id="slick_carousel_link" name="slick_carousel_link" value="' . esc_attr( $value ) . '" size="25" />';
}

// Save meta box data
function slick_carousel_save_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['slick_carousel_meta_box_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['slick_carousel_meta_box_nonce'], 'slick_carousel_save_meta_box_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }
    if ( ! isset( $_POST['slick_carousel_link'] ) ) {
        return;
    }
    $my_data = sanitize_text_field( $_POST['slick_carousel_link'] );
    update_post_meta( $post_id, '_slick_carousel_link', $my_data );
}
add_action( 'save_post', 'slick_carousel_save_meta_box_data' );

// Enqueue Slick Carousel scripts and styles
function slick_carousel_partners_enqueue_scripts() {
    wp_enqueue_style( 'slick-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.css' );
    wp_enqueue_style( 'slick-theme-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.css' );
    wp_enqueue_style( 'slick-custom-css', plugins_url( 'css/slick-carousel.css', __FILE__ ) );

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'slick-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', array('jquery'), null, true );
    wp_enqueue_script( 'slick-custom-js', plugins_url( 'js/slick-carousel.js', __FILE__ ), array('jquery', 'slick-js'), null, true );
}
add_action( 'wp_enqueue_scripts', 'slick_carousel_partners_enqueue_scripts' );

// Shortcode function
function slick_carousel_partners_shortcode() {
    ob_start();
    ?>
    <div class="container">
     
        <section class="customer-logos slider">
            <?php
            $args = array(
                'post_type' => 'slide',
                'posts_per_page' => -1,
                'post_status' => 'publish' // Ensure only published slides are fetched
            );
            $loop = new WP_Query( $args );
            if ( $loop->have_posts() ) :
                while ( $loop->have_posts() ) : $loop->the_post();
                    if ( has_post_thumbnail() ) {
                        $image_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0];
                        $link = get_post_meta( get_the_ID(), '_slick_carousel_link', true );
                        ?>
                        <div class="slide">
                            <a href="<?php echo esc_url( $link ); ?>" target="_blank">
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title(); ?>">
                            </a>
                        </div>
                        <?php
                    }
                endwhile;
            else :
                echo '<p>No slides found.</p>';
            endif;
            wp_reset_postdata();
            ?>
        </section>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'slick_carousel_partners', 'slick_carousel_partners_shortcode' );
?>


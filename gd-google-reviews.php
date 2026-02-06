<?php
/**
 * Plugin Name: GD Google Reviews
 * Description: Display Google Reviews (from wp_gd_google_reviews table) + Site Reviews for GeoDirectory listings. Works on detail and archive/listing cards.
 * Version: 2.5.0
 * Author: Raj
 */

if (!defined('ABSPATH')) exit;

class GD_Google_Reviews {
    public function __construct() {
        add_shortcode('gd_google_reviews', [$this, 'shortcode_output']);
    }

    /** Shortcode: [gd_google_reviews] */
    public function shortcode_output($atts) {
        global $wpdb, $post;

        $atts = shortcode_atts([
            'post_id' => 0
        ], $atts, 'gd_google_reviews');

        $post_id = intval($atts['post_id']);

        // 1️⃣ If shortcode has no post_id, try global $post
        if (!$post_id && isset($post->ID)) {
            $post_id = $post->ID;
        }

        // 2️⃣ Extra fallback: GeoDirectory loop global
        if (!$post_id && isset($GLOBALS['gd_post']->ID)) {
            $post_id = $GLOBALS['gd_post']->ID;
        }

        // 3️⃣ Still no ID? Exit
        if (!$post_id) return '';

        // ✅ Google reviews from custom DB
        $google_html = '';
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT rating, review_count FROM {$wpdb->prefix}gd_google_reviews WHERE post_id = %d", $post_id),
            ARRAY_A
        );

        if ($row && $row['rating'] > 0 && $row['review_count'] > 0) {
            $rating = floatval($row['rating']);
            $count  = intval($row['review_count']);

            ob_start(); ?>
            <div class="google-review-right">
                <div class="gd-google-stars">
                    <strong class="star-count"><?php echo number_format($rating, 1); ?></strong>
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        if ($rating >= $i) {
                            echo '<span class="star full">★</span>';
                        } elseif ($rating >= $i - 0.5) {
                            echo '<span class="star half">★</span>';
                        } else {
                            echo '<span class="star empty">★</span>';
                        }
                    }
                    ?>
                    <div class="gd-google-count">
                        <?php echo $count; ?> reviews
                        <span class="gd-tooltip">
                            <i class="fas fa-info-circle"></i>
                            <span class="gd-tooltip-text">Google Reviews</span>
                        </span>
                    </div>
                </div>
            </div>
            <?php
            $google_html = ob_get_clean();
        }

        // ✅ Site/Attorney reviews
        $attorney_html = '';
        $gd_row = $wpdb->get_row(
            $wpdb->prepare("SELECT overall_rating, rating_count FROM {$wpdb->prefix}geodir_gd_place_detail WHERE post_id = %d", $post_id),
            ARRAY_A
        );
        if ($gd_row && $gd_row['overall_rating'] > 0 && $gd_row['rating_count'] > 0) {
            $gd_rating = floatval($gd_row['overall_rating']);
            $gd_count  = intval($gd_row['rating_count']);

            ob_start(); ?>
            <div class="gd-attorney-card <?php echo !empty($google_html) ? 'with-border' : ''; ?>">
                <div class="attorney-review-right" onclick="document.getElementById('review_section').scrollIntoView({behavior:'smooth'});">
                    <img class="site_Rating_logo" src="/wp-content/uploads/2025/09/Reach-Attorney-Banner-avec-Rich-R-removebg-preview.png" alt="review_logo"/>
                    <span class="rating-text">Rating:</span>
                    <div class="gd-attorney-stars">
                        <strong class="star-count"><?php echo number_format($gd_rating, 1); ?></strong>
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($gd_rating >= $i) {
                                echo '<span class="star full">★</span>';
                            } elseif ($gd_rating >= $i - 0.5) {
                                echo '<span class="star half">★</span>';
                            } else {
                                echo '<span class="star empty">★</span>';
                            }
                        }
                        ?>
                        <div class="gd-attorney-count">
                            <?php echo $gd_count; ?> reviews
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $attorney_html = ob_get_clean();
        }

        if (empty($google_html) && empty($attorney_html)) return '';

        return '<div class="gd-google-card">' . $google_html . $attorney_html . '</div>';
    }
}

new GD_Google_Reviews();

/** CSS Styling */
add_action('wp_head', function () { ?>
    <style>
        .google-review-right { text-align: left; padding-left: 10px; }
        .gd-google-stars .star-count { margin: 3px 0 0 5px; color: #f5bd4a; font-size: 16px; font-weight: 600; }
        .gd-tooltip { position: relative; display: inline-block; cursor: pointer; margin-left: 5px; color: #888; font-size: 14px; vertical-align: middle; }
        .gd-tooltip .gd-tooltip-text {
            visibility: hidden; width: 120px; background-color: #333; color: #fff;
            text-align: center; border-radius: 5px; padding: 5px;
            position: absolute; z-index: 1; bottom: 125%; left: 50%;
            margin-left: -60px; opacity: 0; transition: opacity 0.3s; font-size: 12px;
        }
        .gd-tooltip .gd-tooltip-text::after {
            content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px;
            border-width: 5px; border-style: solid; border-color: #333 transparent transparent transparent;
        }
        .gd-tooltip:hover .gd-tooltip-text { visibility: visible; opacity: 1; }
        .gd-google-card { background: #fff; border-radius: 10px; padding: 15px 20px; max-width: 100%;
            text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); font-family: Arial, sans-serif;
            margin: 10px 0 15px; gap: 20px; display: flex; align-items: center; }
        .gd-google-stars { font-size: 12px; display: flex; align-items: center; }
        .gd-google-stars .star { font-size: 27px; margin-right: 2px; line-height: 100%; color: #ccc; }
        .gd-google-stars .star.full { color: #fbbc04; }
        .gd-google-stars .star.half { color: #fdd663; }
        .gd-google-stars .star.empty { color: #eee; }
        .gd-google-count, .gd-attorney-count { font-size: 18px; font-weight: 400; color: #495057; margin-left: 10px; }
        .gd-attorney-card.with-border { border-left: 1px solid #c1c1c1; padding-left: 20px; }
        .gd-attorney-stars { display: flex; align-items: center; }
        .gd-attorney-stars span.star { font-size: 27px; color: #ccc; position: relative; }
        .gd-attorney-stars span.star.full { color: #5fef00; }
        .gd-attorney-stars span.star.half::before {
            content: '★'; color: #5fef00; position: absolute; left: 0; width: 50%; overflow: hidden;
        }
        .attorney-review-right { cursor: pointer; transition: background 0.2s ease; }
        .gd-google-stars strong.star-count, strong.star-count {
            font-size: 24px; color: #000; margin-right: 9px; margin-top: 0;
        }
        .gd-google-count > br, .gd-tooltip > br, .gd-attorney-count > br { display: none; }
        .gd-attorney-stars, .attorney-review-right, .gd-google-card { display: flex; align-items: center; }
        .rating-text { font-size: 18px; font-weight: 500; margin: 0px 10px; display: inline-block; }
        .google-review-right > p, .gd-attorney-card.with-border > p ,.gd-google-card p { margin: 0; }
        .attorney-review-right img.site_Rating_logo { max-width: 155px; height: auto; object-fit: contain; margin-right: 10px; }
        @media (max-width: 992px){ 
            .gd-google-card { flex-wrap: wrap; } 
            .gd-attorney-card.with-border { border: 0; padding-left: 10px; } 
        }
        @media (max-width: 767px) {
            .attorney-review-right, .gd-google-stars { flex-wrap: wrap; }
        }
    </style>
<?php });

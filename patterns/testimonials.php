<?php
if ( ! defined( "ABSPATH" ) ) exit;

/**
 * Title: Testimonials
 * Slug: themekickstarter/testimonials
 * Categories: themekickstarter
 * Description: Two customer quotes in a simple testimonial layout.
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">What Our Clients Say</h2>
    <!-- /wp:heading -->
    <!-- wp:columns -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><p>Excellent service and support. Highly recommended.</p><cite>Client Name</cite></blockquote>
            <!-- /wp:quote -->
        </div>
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:quote -->
            <blockquote class="wp-block-quote"><p>They helped us launch faster than we expected.</p><cite>Client Name</cite></blockquote>
            <!-- /wp:quote -->
        </div>
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->

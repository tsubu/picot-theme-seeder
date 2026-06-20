<?php
if ( ! defined( "ABSPATH" ) ) exit;

/**
 * Title: Text with Image
 * Slug: themekickstarter/text-image
 * Categories: themekickstarter
 * Description: Headline and text alongside an image block.
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:columns {"verticalAlignment":"center"} -->
    <div class="wp-block-columns are-vertically-aligned-center">
        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:heading -->
            <h2 class="wp-block-heading">Headline with image</h2>
            <!-- /wp:heading -->
            <!-- wp:paragraph -->
            <p>Pair compelling copy with a photo or illustration. Swap the image block on the right.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
            <figure class="wp-block-image size-large"><img alt="" /></figure>
            <!-- /wp:image -->
        </div>
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->

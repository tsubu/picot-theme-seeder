<?php
if ( ! defined( "ABSPATH" ) ) exit;

/**
 * Title: Image Gallery
 * Slug: themekickstarter/gallery
 * Categories: themekickstarter
 * Description: Image gallery block—add your photos in the editor.
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Gallery</h2>
    <!-- /wp:heading -->
    <!-- wp:gallery {"columns":3,"linkTo":"none"} -->
    <figure class="wp-block-gallery has-nested-images columns-3 is-cropped"></figure>
    <!-- /wp:gallery -->
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Click the gallery block to add your images.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

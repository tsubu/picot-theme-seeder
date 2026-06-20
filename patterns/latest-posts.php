<?php
if ( ! defined( "ABSPATH" ) ) exit;

/**
 * Title: Latest Posts
 * Slug: themekickstarter/latest-posts
 * Categories: themekickstarter
 * Description: Grid showing the three most recent blog posts.
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Latest Posts</h2>
    <!-- /wp:heading -->
    <!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false}} -->
    <div class="wp-block-query">
        <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
        <!-- wp:post-title {"isLink":true} /-->
        <!-- wp:post-excerpt /-->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->
</div>
<!-- /wp:group -->

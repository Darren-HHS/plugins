<?php

/**
 * Template Name: 100% Width Template
 * Template Post Type: page
 */

get_header(); ?>

  <div class="col-md-12 p-0" style="">
      <main id="ch-homepage" class="ch-homepage">
		  <?php while ( have_posts() ) : the_post();
			  the_content();
		  endwhile; ?>
      </main>
  </div>

<?php get_footer();

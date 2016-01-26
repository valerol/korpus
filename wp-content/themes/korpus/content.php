
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	
		<header class="entry-header">
			<?php
				if ( is_single() ) :
					the_title( '<h1 class="entry-title">', '</h1>' );
				else :
					the_title( '<h2 class="entry-title">', '</h2>' );
				endif;
			?>
				<p class="date"> <?php echo get_the_date(); ?> </p>
		</header><!-- .entry-header -->

		<section class="entry-content">
			<?php
				/* translators: %s: Name of current post */
				the_post_thumbnail();
				the_content( sprintf(
					__( 'Continue reading %s', 'korpus' ),
					the_title( '<span class="screen-reader-text">', '</span>', true )
				) );

				wp_link_pages( array(
					'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'korpus' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'korpus' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				) );
			?>
		</section><!-- .entry-content -->

		<footer class="entry-footer">
			<?php// twentyfifteen_entry_meta(); ?>
			<?php edit_post_link( __( 'Edit', 'twentyfifteen' ), '<span class="edit-link">', '</span>' ); ?>
		</footer><!-- .entry-footer -->

	</article><!-- #post-## -->

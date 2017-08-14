<aside>
	<nav class="left-menu clearfix">
		<?php if ( has_nav_menu( 'left_menu' ) ) : ?>

				<?php
					wp_nav_menu( array(
						'depth'          => 1,
//						'link_before'    => '<span class="screen-reader-text">',
//						'link_after'     => '</span>',
					) );
				?>

		<?php endif; ?>
	</nav>
	<div id="widget-area" class="widget-area" role="complementary">

		<?php if ( is_active_sidebar( 'sidebar' ) ) : ?>

				<?php dynamic_sidebar( 'sidebar' ); ?>

		<?php endif; ?>
		
	</div><!-- .widget-area -->
</aside>

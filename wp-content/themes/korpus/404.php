<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */

get_header(); ?>

<div class="main-container">
	<div class="main wrapper clearfix">
		<?php get_sidebar(); ?>
		<main>

			<section class="error-404 not-found">
				<header class="page-header">
					<h1 class="page-title"><?php _e( 'Страница отсутствует' ); ?></h1>
				</header><!-- .page-header -->

				<div class="page-content">
					<p><?php _e( 'К сожалению, страница по данному адресу отсутствует или перенесена. Попробуйте воспользоваться поиском.' ); ?></p>
					<?php get_search_form(); ?>
					
					<?php //list_hooked_functions('wp_enqueue_scripts'); ?>
					
				</div><!-- .page-content -->
			</section><!-- .error-404 -->

		</main>
	</div> <!-- #main -->
</div> <!-- #main-container -->

<?php get_footer(); ?>

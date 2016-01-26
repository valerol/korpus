<?php
/**
 * The template for displaying search results pages.
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */

get_header(); ?>

	<div class="main-container">
		<div class="main wrapper clearfix">
			<?php get_sidebar(); ?>
			<main class="search">

				<?php if ( have_posts() ) : ?>

					<header class="page-header">
						<h1 class="page-title"><?php printf( __( 'Search Results for: %s', 'twentyfifteen' ), get_search_query() ); ?></h1>
					</header><!-- .page-header -->

					<?php
					// Start the loop.
					while ( have_posts() ) : the_post(); ?>

						<?php
						/*
						 * Run the loop for the search to output the results.
						 * If you want to overload this in a child theme then include a file
						 * called content-search.php and that will be used instead.
						 */
						get_template_part( 'content', 'search' );

					// End the loop.
					endwhile;

					// Previous/next page navigation.
					the_posts_pagination( array(
						'prev_text'          => __( 'Назад' ),
						'next_text'          => __( 'Вперед' ),
						'end_size'     => 1,     // количество страниц на концах
						'mid_size'     => 5,     // количество страниц вокруг текущей
						'prev_next'    => true,  // выводить ли боковые ссылки "предыдущая/следующая страница"
						'add_fragment' => '',     // Текст который добавиться ко всем ссылкам.
						'screen_reader_text' => '',
					//	'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentyfifteen' ) . ' </span>',
					) );

				// If no content, include the "No posts found" template.
				else :
					get_template_part( 'content', 'none' );

				endif;
				?>
			</main>
		</div><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_footer(); ?>

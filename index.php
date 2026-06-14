<?php
/**
 * The main template file
 *
 * @package Futbol_Fest_Landing
 */

get_header();
?>

<main id="primary" class="site-main">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			the_content();
		}
	} else {
		echo '<p>' . esc_html__( 'No content found', 'futbolfest-landing' ) . '</p>';
	}
	?>
</main>

<?php
get_footer();

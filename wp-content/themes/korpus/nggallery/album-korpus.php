<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?><?php if (!empty ($galleries)) : ?>

<div class="album">	
	<!-- List of galleries -->
	<?php foreach ($galleries as $gallery) : ?>
		<div class="gallery">
			<p><a href="<?php echo $gallery->pagelink ?>"><?php echo $gallery->title ?></a></p>

			<a href="<?php echo $gallery->pagelink ?>"><img class="Thumb" alt="<?php echo $gallery->title ?>" src="<?php echo $gallery->previewurl ?>"/></a>

			<p><?php echo $gallery->galdesc ?></p>
		</div>
 	<?php endforeach; ?>

	<!-- Pagination -->
 	<?php echo $pagination ?>
 	
</div>

<?php endif; ?>

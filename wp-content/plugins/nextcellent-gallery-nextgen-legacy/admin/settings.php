<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * Rebuild slugs for albums, galleries and images via AJAX request
 * 20150124 FZSM: A class only for one method (and is not the constructor) needs to be improved.
 * 20150124 FZSM: suggested rule: One file, one class...
 * 20150124: function called statically when is not...
 * @sine 1.7.0
 * @access internal
 */
class ngg_rebuild_unique_slugs {

	function start_rebuild() {
		global $wpdb;

		$total = array();
		// get the total number of images
		$total['images'] = intval( $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggpictures") );
		$total['gallery'] = intval( $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggallery") );
		$total['album'] = intval( $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->nggalbum") );

		$messages = array(
			'images' => __( 'Rebuild image structure : %s / %s images', 'nggallery' ),
			'gallery' => __( 'Rebuild gallery structure : %s / %s galleries', 'nggallery' ),
			'album' => __( 'Rebuild album structure : %s / %s albums', 'nggallery' ),
		);

		foreach ( array_keys( $messages ) as $key ) {

			$message = sprintf( $messages[ $key ] ,
				"<span class='ngg-count-current'>0</span>",
				"<span class='ngg-count-total'>" . $total[ $key ] . "</span>"
			);

			echo "<div class='$key updated'><p class='ngg'>$message</p></div>";
		}

		$ajax_url = add_query_arg( 'action', 'ngg_rebuild_unique_slugs', admin_url( 'admin-ajax.php' ) );
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	var ajax_url = '<?php echo $ajax_url; ?>',
		_action = 'images',
		images = <?php echo $total['images']; ?>,
		gallery = <?php echo $total['gallery']; ?>,
		album = <?php echo $total['album']; ?>,
		total = 0,
		offset = 0,
		count = 50;

	var $display = $('.ngg-count-current');
	$('.finished, .gallery, .album').hide();
	total = images;

	function call_again() {
		if ( offset > total ) {
			offset = 0;
			// 1st run finished
			if (_action == 'images') {
				_action = 'gallery';
				total = gallery;
				$('.images, .gallery').toggle();
				$display.html(offset);
				call_again();
				return;
			}
			// 2nd run finished
			if (_action == 'gallery') {
				_action = 'album';
				total = album;
				$('.gallery, .album').toggle();
				$display.html(offset);
				call_again();
				return;
			}
			// 3rd run finished, exit now
			if (_action == 'album') {
				$('.ngg')
					.html('<?php esc_html_e( 'Done.', 'nggallery' ); ?>')
					.parent('div').hide();
				$('.finished').show();
				return;
			}
		}

		$.post(ajax_url, {'_action': _action, 'offset': offset}, function(response) {
			$display.html(offset);

			offset += count;
			call_again();
		});
	}

	call_again();
});
</script>
<?php
	}
}

//20150124 FZSM: Suggested rule: no class should call a spaghuetti code directly...
/**
 * Class nggOptions - the settings page.
 */
class nggOptions {

	public function __construct() {

		// same as $_SERVER['REQUEST_URI'], but should work under IIS 6.0
	   $this->filepath    = admin_url() . 'admin.php?page=' . $_GET['page'];

		//Look for POST updates
		if ( !empty($_POST) ) {
			$this->processor();
		}
	}

	/**
	 * Save/Load options and add a new hook for plugins
	 */
	public function processor() {

		global $ngg, $nggRewrite;

		$old_state = $ngg->options['usePermalinks'];
		$old_slug  = $ngg->options['permalinkSlug'];

		if ( isset($_POST['updateoption']) ) {
			check_admin_referer('ngg_settings');
			// get the hidden option fields, taken from WP core
			if ( $_POST['page_options'] )
				$options = explode(',', stripslashes($_POST['page_options']));

			if ($options) {
				foreach ($options as $option) {
					$option = trim($option);
					$value = false;
					if ( isset( $_POST[ $option ] ) ) {
						$value = trim( $_POST[ $option ] );
						if ($value === "true") {
							$value = true;
						}

						if ( is_numeric( $value ) ) {
							$value = (int) $value;
						}
					}

					$ngg->options[$option] = $value;
				}

				// do not allow a empty string
				if ( empty ( $ngg->options['permalinkSlug'] ) )
					$ngg->options['permalinkSlug'] = 'nggallery';

				// the path should always end with a slash
				$ngg->options['gallerypath']    = trailingslashit($ngg->options['gallerypath']);
				$ngg->options['imageMagickDir'] = trailingslashit($ngg->options['imageMagickDir']);

				// the custom sortorder must be ascending
				$ngg->options['galSortDir'] = ($ngg->options['galSort'] == 'sortorder') ? 'ASC' : $ngg->options['galSortDir'];
			}
			// Save options
			update_option('ngg_options', $ngg->options);

			// Flush Rewrite rules
			if ( $old_state != $ngg->options['usePermalinks'] || $old_slug != $ngg->options['permalinkSlug'] )
				$nggRewrite->flush();

			nggGallery::show_message(__('Settings updated successfully','nggallery'));
		}

		if ( isset($_POST['clearcache']) ) {
			check_admin_referer('ngg_settings');

			$path = WINABSPATH . $ngg->options['gallerypath'] . 'cache/';

			if (is_dir($path))
				if ($handle = opendir($path)) {
					while (false !== ($file = readdir($handle))) {
						if ($file != '.' && $file != '..') {
						  @unlink($path . '/' . $file);
						}
					}
					closedir($handle);
				}

			nggGallery::show_message(__('Cache cleared','nggallery'));
		}

		if ( isset($_POST['createslugs']) ) {
			check_admin_referer('ngg_settings');
			ngg_rebuild_unique_slugs::start_rebuild();
		}

		do_action( 'ngg_update_options_page' );

	}

	/**
	 * Render the page content
	 * 20150124:FZSM: there should be a cleaner way to handle this, instead making dynamic functions and actions.
	 */
	public function show_page() {

		// get list of tabs
		$tabs = $this->get_tabs();

		?>
		<div class="wrap">
			<h2><?php _e('Settings', 'nggallery') ?></h2>
			<div id="slider" style="display: none;">
			<ul id="tabs">
				<?php
				foreach($tabs as $tab_key => $tab_name) {
					echo "\n\t\t<li><a class='nav-tab' href='#$tab_key'>$tab_name</a></li>";
				}
				?>
			</ul>
			<?php
			foreach($tabs as $tab_key => $tab_name) {
				echo "\n\t<div id='$tab_key'>\n";
				// Looks for the internal class function, otherwise enable a hook for plugins
				if ( method_exists( $this, "tab_$tab_key" ))
					call_user_func( array( $this , "tab_$tab_key") );
				else
					do_action( 'ngg_tab_content_' . $tab_key );
				echo "\n\t</div>";
			}
			?>
			</div>
		</div>
		<?php
		$this->get_javascript();

	}

	private function get_javascript() {
		?>
		<script type="text/javascript">
			function insertcode(value) {
				var effectcode;
				switch (value) {
					case 'none':
						effectcode = "";
						break;
					case "thickbox":
						effectcode = 'class="thickbox" rel="%GALLERY_NAME%"';
						break;
					case "lightbox":
						effectcode = 'rel="lightbox[%GALLERY_NAME%]"';
						break;
					case "highslide":
						effectcode = 'class="highslide" onclick="return hs.expand(this, { slideshowGroup: %GALLERY_NAME% })"';
						break;
					case "shutter":
						effectcode = 'class="shutterset_%GALLERY_NAME%"';
						break;
					default:
						break;
				}
				jQuery("#thumbCode").val(effectcode);
			}

			jQuery(document).ready( function($) {
				//$('html,body').scrollTop(0);
				//Set tabs.
				$('#slider').tabs({ fxFade: true, fxSpeed: 'fast' }).css('display', 'block');

				//Set colorpicker.
				$('.picker').wpColorPicker();

				//Set preview for watermark.
				$('#wm-preview-select').on("nggAutocompleteDone", function() {
					$('#wm-preview-image').attr("src", '<?php echo home_url( 'index.php' ); ?>' + '?callback=image&pid=' + this.value + '&mode=watermark');
                    $('#wm-preview-image-url').attr("href", '<?php echo home_url( 'index.php' ); ?>' + '?callback=image&pid=' + this.value + '&mode=watermark');
				});

                jQuery("#wm-preview-select").nggAutocomplete( {
                    type: 'image',domain: "<?php echo home_url('index.php', is_ssl() ? 'https' : 'http'); ?>"
                });
			});
		</script>
		<?php
	}

	/**
	 * Create array for tabs and add a filter for other plugins to inject more tabs
	 *
	 * @return array $tabs
	 */
	private function get_tabs() {

		$tabs = array();

		$tabs['general'] = __('General', 'nggallery');
		$tabs['images'] = __('Images', 'nggallery');
		$tabs['gallery'] = __( 'Gallery', 'nggallery' );
		$tabs['effects'] = __('Effects', 'nggallery');
		$tabs['watermark'] = __('Watermark', 'nggallery');
		$tabs['slideshow'] = __('Slideshow', 'nggallery');

		$tabs = apply_filters('ngg_settings_tabs', $tabs);

		return $tabs;

	}

	/**
	 * Show the general options.
	 */
	private function tab_general() {
		global $ngg;
		?>
		<h3><?php _e( 'General settings', 'nggallery' ); ?></h3>
		<form name="generaloptions" method="post" action="<?php echo $this->filepath; ?>">
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="gallerypath,silentUpgrade,deleteImg,useMediaRSS,usePicLens,usePermalinks,permalinkSlug,graphicLibrary,imageMagickDir,activateTags,appendType,maxImages" />
			<table class="form-table ngg-options">
				<tr>
					<th><label for="gallerypath"><?php _e('Gallery path','nggallery'); ?></label></th>
					<td>
						<input <?php $this->readonly(is_multisite()); ?> type="text" class="regular-text code" name="gallerypath" id="gallerypath" value="<?php echo $ngg->options['gallerypath']; ?>" />
						<p class="description"><?php esc_html_e('This is the default path for all galleries','nggallery') ?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Silent database upgrade','nggallery'); ?></th>
					<td>
						<input <?php disabled(is_multisite()); ?> type="checkbox" name="silentUpgrade" id="silentUpgrade" value="true" <?php checked( $ngg->options['silentUpgrade']); ?> />
						<label for="silentUpgrade"><?php _e('Update the database without notice.','nggallery') ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Image files','nggallery'); ?></th>
					<td>
						<input <?php disabled(is_multisite()); ?> type="checkbox" name="deleteImg" id="deleteImg" value="true" <?php checked( $ngg->options['deleteImg']); ?>>
						<label for="deleteImg">
						<?php _e("Delete files when removing a gallery from the database",'nggallery'); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Select graphic library','nggallery'); ?></th>
					<td>
						<fieldset>
							<label>
								<input name="graphicLibrary" type="radio" value="gd" <?php checked('gd', $ngg->options['graphicLibrary']); ?>>
								<?php _e('GD Library', 'nggallery');?>
							</label><br>
							<label>
								<input name="graphicLibrary" type="radio" value="im" <?php checked('im', $ngg->options['graphicLibrary']); ?>>
								<?php _e('ImageMagick (Experimental)', 'nggallery'); ?>
							</label>
						</fieldset>
						<label>
							<?php _e('Path to the ImageMagick library:', 'nggallery'); ?>
							<input <?php $this->readonly(is_multisite()); ?> type="text" class="regular-text code" name="imageMagickDir" value="<?php echo $ngg->options['imageMagickDir']; ?>">
						</label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Media RSS feed','nggallery'); ?></th>
					<td>
						<input type="checkbox" name="useMediaRSS" id="useMediaRSS" value="true" <?php checked( $ngg->options['useMediaRSS']); ?>>
						<label for="useMediaRSS"><?php esc_html_e('Add a RSS feed to you blog header. Useful for CoolIris/PicLens','nggallery') ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('PicLens/CoolIris','nggallery'); ?> (<a href="http://www.cooliris.com">CoolIris</a>)</th>
					<td>
						<input type="checkbox" id="usePicLens" name="usePicLens" value="true" <?php checked( $ngg->options['usePicLens']); ?>>
						<label for="usePicLens"><?php _e('Include support for PicLens and CoolIris','nggallery'); ?></label>
						<p class="description"><?php _e('When activated, JavaScript is added to your site footer. Make sure that wp_footer is called in your theme.','nggallery') ?></p>
					</td>
				</tr>
			</table>
			<h3><?php _e('Permalinks','nggallery') ?></h3>
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Use permalinks','nggallery'); ?></th>
					<td>
						<input type="checkbox" name="usePermalinks" id="usePermalinks" value="true" <?php checked( $ngg->options['usePermalinks']); ?>>
						<label for="usePermalinks"><?php _e('Adds a static link to all images','nggallery'); ?></label>
						<p class="description"><?php _e('When activating this option, you need to update your permalink structure once','nggallery'); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="permalinkSlug"><?php _e('Gallery slug:','nggallery'); ?></label></th>
					<td><input type="text" class="regular-text code" name="permalinkSlug" id="permalinkSlug" value="<?php echo $ngg->options['permalinkSlug']; ?>"></td>
				</tr>
				<tr>
					<th><label for="createslugs"><?php _e('Recreate URLs','nggallery'); ?></label></th>
					<td>
						<input type="submit" name="createslugs" id="createslugs" class="button-secondary"  value="<?php _e('Start now &raquo;','nggallery') ;?>"/>
						<p class="description"><?php _e( "If you've changed these settings, you'll have to recreate the URLs.",'nggallery'); ?></p>
					</td>
				</tr>
			</table>
			<h3><?php _e('Related images','nggallery'); ?></h3>
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Add related images','nggallery'); ?></th>
					<td>
						<input name="activateTags" id="activateTags" type="checkbox" value="true" <?php checked( $ngg->options['activateTags']); ?>>
						<label for="activateTags"><?php _e('This will add related images to every post','nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Match with','nggallery'); ?></th>
					<td>
						<fieldset>
							<label>
								<input name="appendType" type="radio" value="category" <?php checked('category', $ngg->options['appendType']); ?>>
								<?php _e('Categories', 'nggallery') ;?>
							</label>
							<br>
							<label>
								<input name="appendType" type="radio" value="tags" <?php checked('tags', $ngg->options['appendType']); ?>>
								<?php _e('Tags', 'nggallery') ;?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th><label for="maxImages"><?php _e('Max. number of images','nggallery'); ?></label></th>
					<td>
						<input name="maxImages" id="maxImages" type="number" step="1" min="1" value="<?php echo $ngg->options['maxImages']; ?>" class="small-text">
						<p class="description"><?php _e('0 will show all images','nggallery'); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __('Save Changes'), 'primary', 'updateoption' ); ?>
		</form>
	<?php
	}

	/**
	 * Show the image and thumbnail related options.
	 */
	private function tab_images() {
		global $ngg;
		?>
		<h3><?php _e('Image settings','nggallery'); ?></h3>
		<form name="imagesettings" method="POST" action="<?php echo $this->filepath.'#images'; ?>">
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="imgResize,imgWidth,imgHeight,imgQuality,imgBackup,imgAutoResize,thumbwidth,thumbheight,thumbfix,thumbquality">
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Resize images','nggallery') ?></th>
					<td>
						<label for="imgWidth"><?php _e('Width','nggallery') ?></label>
						<input type="number" step="1" min="0" class="small-text" name="imgWidth" id="imgWidth" value="<?php echo $ngg->options['imgWidth']; ?>">
						<label for="imgHeight"><?php _e('Height','nggallery') ?></label>
						<input type="number" step="1" min="0" class="small-text" name="imgHeight" id="imgHeight" value="<?php echo $ngg->options['imgHeight']; ?>">
						<p class="description"><?php _e('Width and height (in pixels). NextCellent Gallery will keep the ratio size.','nggallery') ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="imgQuality"><?php _e('Image quality','nggallery'); ?></label></th>
					<td><input type="number" step="1" min="0" max="100" class="small-text" name="imgQuality" id="imgQuality" value="<?php echo $ngg->options['imgQuality']; ?>">%</td>
				</tr>
				<tr>
					<th><?php _e('Backup original','nggallery'); ?></th>
					<td>
						<label>
							<input type="checkbox" name="imgBackup" value="true" <?php checked( $ngg->options['imgBackup']); ?>>
							<?php _e('Create a backup for the resized images','nggallery'); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Automatically resize','nggallery'); ?></th>
					<td>
						<label>
							<input type="checkbox" name="imgAutoResize" value="1" <?php checked( $ngg->options['imgAutoResize']); ?>>
							<?php _e('Automatically resize images on upload.','nggallery') ?>
						</label>
					</td>
				</tr>
			</table>
		<h3><?php _e('Thumbnail settings','nggallery'); ?></h3>
			<p><?php _e('Please note: if you change these settings, you need to recreate the thumbnails under -> Manage Gallery .', 'nggallery') ?></p>
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Thumbnail size','nggallery'); ?></th>
					<td>
						<label for="thumbwidth"><?php _e('Width','nggallery') ?></label>
						<input type="number" step="1" min="0" class="small-text" name="thumbwidth" id="thumbwidth" value="<?php echo $ngg->options['thumbwidth']; ?>">
						<label for="thumbheight"><?php _e('Height','nggallery') ?></label>
						<input type="number" step="1" min="0" class="small-text" name="thumbheight" id="thumbheight" value="<?php echo $ngg->options['thumbheight']; ?>">
						<p class="description"><?php _e('These values are maximum values.','nggallery'); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Fixed size','nggallery'); ?></th>
					<td>
						<input type="checkbox" name="thumbfix" id="thumbfix" value="true" <?php checked( $ngg->options['thumbfix']); ?>>
						<label for="thumbfix"><?php _e('Ignore the aspect ratio, so no portrait thumbnails.','nggallery') ?></label>
					</td>
				</tr>
				<tr>
					<th><label for="thumbquality"><?php _e('Thumbnail quality','nggallery'); ?></label></th>
					<td><input type="number" step="1" min="0" max="100" class="small-text" name="thumbquality" id="thumbquality" value="<?php echo $ngg->options['thumbquality']; ?>">%</td>
				</tr>
			</table>
			<h3><?php _e('Single picture','nggallery') ?></h3>
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Clear cache folder','nggallery'); ?></th>
					<td><input type="submit" name="clearcache" class="button-secondary"  value="<?php _e('Proceed now &raquo;','nggallery') ;?>"/></td>
				</tr>
			</table>
			<?php submit_button( __('Save Changes'), 'primary', 'updateoption' ); ?>
		</form>

	<?php
	}

	/**
	 * Show gallery related settings
	 */
	private function tab_gallery() {
		global $ngg;
		?>
		<h3><?php _e('Gallery settings','nggallery'); ?></h3>
		<form name="galleryform" method="POST" action="<?php echo $this->filepath.'#gallery'; ?>">
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="galNoPages,galImages,galColumns,galShowSlide,galTextSlide,galTextGallery,galShowOrder,galImgBrowser,galSort,galSortDir,galHiddenImg,galAjaxNav">
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Inline gallery','nggallery') ?></th>
					<td>
						<input name="galNoPages" id="galNoPages" type="checkbox" value="true" <?php checked( $ngg->options['galNoPages']); ?>>
						<label for="galNoPages"><?php _e('Galleries will not be shown on a subpage, but on the same page.','nggallery') ?></label>
					</td>
				</tr>
				<tr>
					<th><label for="galImages"><?php _e('Images per page','nggallery'); ?></label></th>
					<td>
						<input type="number" step="1" min="0" class="small-text" name="galImages" id="galImages" value="<?php echo $ngg->options['galImages']; ?>">
						<?php _e( 'images', 'nggallery'); ?>
						<p class="description"><?php _e('0 will disable pagination and show all images on one page.','nggallery') ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="galColumns"><?php esc_html_e('Columns','nggallery'); ?></label></th>
					<td>
						<input type="number" step="1" min="0" class="small-text" name="galColumns" id="galColumns" value="<?php echo $ngg->options['galColumns']; ?>">
						<?php _e( 'columns per page', 'nggallery'); ?>
						<p class="description"><?php _e('0 will display as much columns as possible. This is normally only required for captions below the images.','nggallery') ?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Slideshow','nggallery'); ?></th>
					<td>
						<label>
							<input name="galShowSlide" type="checkbox" value="true" <?php checked( $ngg->options['galShowSlide']); ?>>
							<?php _e('Enable slideshow','nggallery'); ?>
						</label>
							<br>
						<label>
							<?php _e('Text to show:','nggallery'); ?>
							<input type="text" class="regular-text" name="galTextSlide" value="<?php echo $ngg->options['galTextSlide'] ?>">
						</label>
						<input type="text" name="galTextGallery" value="<?php echo $ngg->options['galTextGallery'] ?>" class="regular-text">
						<p class="description"> <?php _e('This is the text the visitors will have to click to switch between display modes.','nggallery'); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Show first','nggallery'); ?></th>
					<td>
						<fieldset>
							<label>
								<input name="galShowOrder" type="radio" value="gallery" <?php checked('gallery', $ngg->options['galShowOrder']); ?>>
								<?php _e('Thumbnails', 'nggallery') ;?>
							</label>
							<br>
							<label>
								<input name="galShowOrder" type="radio" value="slide" <?php checked('slide', $ngg->options['galShowOrder']); ?>>
								<?php _e('Slideshow', 'nggallery') ;?>
							</label>
						</fieldset>
						<p class="description"><?php _e( 'Choose what visitors will see first.', 'nggallery'); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('ImageBrowser','nggallery'); ?></th>
					<td>
						<label>
							<input name="galImgBrowser" type="checkbox" value="true" <?php checked( $ngg->options['galImgBrowser']); ?>>
							<?php _e('Use ImageBrowser instead of another effect.', 'nggallery'); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Hidden images','nggallery'); ?></th>
					<td>
						<label>
							<input name="galHiddenImg" type="checkbox" value="true" <?php checked( $ngg->options['galHiddenImg']); ?>>
							<?php _e('Loads all images for the modal window, when pagination is used (like Thickbox, Lightbox etc.).','nggallery'); ?>
						</label>
						<p class="description"> <?php _e('Note: this increases the page load (possibly a lot)', 'nggallery'); ?>
					</td>
				</tr>
				<tr>
					<th><?php _e('AJAX pagination','nggallery'); ?></th>
					<td>
						<label>
							<input name="galAjaxNav" type="checkbox" value="true" <?php checked( $ngg->options['galAjaxNav']); ?>>
							<?php _e('Use AJAX pagination to browse images without reloading the page.','nggallery'); ?>
						</label>
						<p class="description"><?php esc_html_e('Note: works only in combination with the Shutter effect.', 'nggallery'); ?></p>
					</td>
				</tr>
			</table>
			<h3><?php _e('Sort options','nggallery'); ?></h3>
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Sort thumbnails','nggallery'); ?></th>
					<td>
						<fieldset>
							<label>
								<input name="galSort" type="radio" value="sortorder" <?php checked('sortorder', $ngg->options['galSort']); ?>>
								<?php _e('Custom order', 'nggallery'); ?>
							</label><br>
							<label>
								<input name="galSort" type="radio" value="pid" <?php checked('pid', $ngg->options['galSort']); ?>>
								<?php _e('Image ID', 'nggallery'); ?>
							</label><br>
							<label>
								<input name="galSort" type="radio" value="filename" <?php checked('filename', $ngg->options['galSort']); ?>>
								<?php _e('File name', 'nggallery') ;?>
							</label><br>
							<label>
								<input name="galSort" type="radio" value="alttext" <?php checked('alttext', $ngg->options['galSort']); ?>>
								<?php _e('Alt / Title text', 'nggallery') ;?>
							</label><br>
							<label>
								<input name="galSort" type="radio" value="imagedate" <?php checked('imagedate', $ngg->options['galSort']); ?>>
								<?php _e('Date / Time', 'nggallery') ;?>
							</label>
						</fieldset>

					</td>
				</tr>
				<tr>
					<th><?php _e('Sort direction','nggallery') ?></th>
					<td>
						<label>
							<input name="galSortDir" type="radio" value="ASC" <?php checked('ASC', $ngg->options['galSortDir']); ?>>
							<?php _e('Ascending', 'nggallery') ;?>
						</label><br>
						<label>
							<input name="galSortDir" type="radio" value="DESC" <?php checked('DESC', $ngg->options['galSortDir']); ?>>
							<?php _e('Descending', 'nggallery') ;?>
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( __('Save Changes'), 'primary', 'updateoption' ); ?>
		</form>
		<?php
	}

	/**
	 * Show the effect related settings.
	 */
	private function tab_effects() {
		global $ngg;
	?>
		<h3><?php _e('Effects','nggallery'); ?></h3>
		<form name="effectsform" method="POST" action="<?php echo $this->filepath.'#effects'; ?>">
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="thumbEffect,thumbCode">
			<p>
				<?php _e('Here you can select the thumbnail effect, NextCellent Gallery will integrate the required HTML code in the images. Please note that only the Shutter and Thickbox effect will automatic added to your theme.','nggallery'); ?>
				<?php _e('With the placeholder <strong> %GALLERY_NAME% </strong> you can activate a navigation through the images (depend on the effect). Change the code line only , when you use a different thumbnail effect or you know what you do.','nggallery'); ?>
			</p>
			<table class="form-table ngg-options">
				<tr>
					<th><label for="thumbEffect"><?php _e('JavaScript Thumbnail effect','nggallery') ?></label></th>
					<td>
						<select size="1" id="thumbEffect" name="thumbEffect" onchange="insertcode(this.value)">
							<option value="none" <?php selected('none', $ngg->options['thumbEffect']); ?>><?php _e('None', 'nggallery') ;?></option>
							<option value="thickbox" <?php selected('thickbox', $ngg->options['thumbEffect']); ?>><?php _e('Thickbox', 'nggallery') ;?></option>
							<option value="lightbox" <?php selected('lightbox', $ngg->options['thumbEffect']); ?>><?php _e('Lightbox', 'nggallery') ;?></option>
							<option value="highslide" <?php selected('highslide', $ngg->options['thumbEffect']); ?>><?php _e('Highslide', 'nggallery') ;?></option>
							<option value="shutter" <?php selected('shutter', $ngg->options['thumbEffect']); ?>><?php _e('Shutter', 'nggallery') ;?></option>
							<option value="custom" <?php selected('custom', $ngg->options['thumbEffect']); ?>><?php _e('Custom', 'nggallery') ;?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="thumbCode"><?php _e('Link Code line','nggallery'); ?></label></th>
					<td><textarea class="normal-text code" id="thumbCode" name="thumbCode" cols="50" rows="5"><?php echo htmlspecialchars(stripslashes($ngg->options['thumbCode'])); ?></textarea></td>
				</tr>
			</table>
			<?php submit_button( __('Save Changes'), 'primary', 'updateoption' ) ?>
		</form>
	<?php
	}

	/**
	 * Show watermark related settings.
	 */
	private function tab_watermark() {

		global $ngg;

		// take the first image as sample
		$image_array = nggdb::find_last_images(0, 1);
		$ngg_image = $image_array[0];
		$imageID  = $ngg_image->pid;

		?>
		<h3><?php _e('Watermark','nggallery'); ?></h3>
		<p><?php _e('Please note : you can only activate the watermark under -> Manage Galleries. This action cannot be undone.', 'nggallery') ?></p>
		<form name="watermarkform" method="POST" action="<?php echo $this->filepath.'#watermark'; ?>">
			<?php wp_nonce_field('ngg_settings') ?>
			<input type="hidden" name="page_options" value="wmPos,wmXpos,wmYpos,wmType,wmPath,wmFont,wmSize,wmColor,wmText,wmOpaque" />
			<div id="wm-preview">
				<h3><?php esc_html_e('Preview','nggallery') ?></h3>
				<label for="wm-preview-select"><?php _e('Select an image','nggallery'); ?></label>
				<select id="wm-preview-select" name="wm-preview-img" style="width: 200px">
					<?php echo '<option value="' . $ngg_image->pid . '">' . $ngg_image->pid . ' - ' . $ngg_image->alttext . '</option>'; ?>
				</select>
				<div id="wm-preview-container">
					<a id="wm-preview-image-url" href="<?php echo home_url( 'index.php' ); ?>?callback=image&pid=<?php echo intval( $imageID ); ?>&mode=watermark" target="_blank" title="<?php _e("View full image", 'nggallery'); ?>">
                        <img id="wm-preview-image" src="<?php echo home_url( 'index.php' ); ?>?callback=image&pid=<?php echo intval( $imageID ); ?>&mode=watermark" />
                    </a>
				</div>
				<h3><?php _e('Position','nggallery') ?></h3>
				<table id="wm-position">
					<tr>
						<td>
							<strong><?php _e('Position','nggallery') ?></strong>
							<table>
								<tr>
									<td><input type="radio" name="wmPos" value="topLeft" <?php checked('topLeft', $ngg->options['wmPos']); ?> /></td>
									<td><input type="radio" name="wmPos" value="topCenter" <?php checked('topCenter', $ngg->options['wmPos']); ?> /></td>
									<td><input type="radio" name="wmPos" value="topRight" <?php checked('topRight', $ngg->options['wmPos']); ?> /></td>
								</tr>
								<tr>
									<td><input type="radio" name="wmPos" value="midLeft" <?php checked('midLeft', $ngg->options['wmPos']); ?> /></td>
									<td><input type="radio" name="wmPos" value="midCenter" <?php checked('midCenter', $ngg->options['wmPos']); ?> /></td>
									<td><input type="radio" name="wmPos" value="midRight" <?php checked('midRight', $ngg->options['wmPos']); ?> /></td>
								</tr>
								<tr>
									<td><input type="radio" name="wmPos" value="botLeft" <?php checked('botLeft', $ngg->options['wmPos']); ?> /></td>
									<td><input type="radio" name="wmPos" value="botCenter" <?php checked('botCenter', $ngg->options['wmPos']); ?> /></td>
									<td><input type="radio" name="wmPos" value="botRight" <?php checked('botRight', $ngg->options['wmPos']); ?> /></td>
								</tr>
							</table>
						</td>
						<td>
							<strong><?php _e('Offset','nggallery') ?></strong>
							<table border="0">
								<tr>
									<td><label for="wmXpos">x:</label></td>
									<td><input type="number" step="1" min="0" class="small-text" name="wmXpos" id="wmXpos" value="<?php echo $ngg->options['wmXpos'] ?>">px</td>
								</tr>
								<tr>
									<td><label for="wmYpos">y:</label></td>
									<td><input type="number" step="1" min="0" class="small-text" name="wmYpos" id="wmYpos" value="<?php echo $ngg->options['wmYpos'] ?>" />px</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<h3><label><input type="radio" name="wmType" value="image" <?php checked('image', $ngg->options['wmType']); ?>><?php _e('Use image as watermark','nggallery') ?></label></h3>
			<table class="wm-table form-table">
				<tr>
					<th><label for="wmPath"><?php _e('URL to file','nggallery'); ?></label></th>
					<td><input type="text" class="regular-text code" name="wmPath" id="wmPath" value="<?php echo $ngg->options['wmPath']; ?>"><br>
				</tr>
			</table>
			<h3><label><input type="radio" name="wmType" value="text" <?php checked('text', $ngg->options['wmType']); ?>><?php _e('Use text as watermark','nggallery') ?></label></h3>
			<table class="wm-table form-table">
				<tr>
					<th><?php _e('Font','nggallery') ?></th>
					<td>
						<select name="wmFont" size="1">
							<?php
							$fontlist = $this->get_fonts();
							foreach ( $fontlist as $fontfile ) {
								echo "\n".'<option value="'.$fontfile.'" '. selected($fontfile, $ngg->options['wmFont']).' >'.$fontfile.'</option>';
							}
							?>
						</select><br>
						<span>
							<?php if ( !function_exists('ImageTTFBBox') ) {
								_e( 'This function will not work, cause you need the FreeType library', 'nggallery' );
							} else {
								_e( 'You can upload more fonts in the folder <strong>nggallery/fonts</strong>', 'nggallery' );
							} ?>
						</span>
					</td>
				</tr>
				<tr>
					<th><label for="wmSize"><?php _e('Size','nggallery'); ?></label></th>
					<td><input type="number" step="1" min="0" class="small-text" name="wmSize" id="wmSize" value="<?php echo $ngg->options['wmSize']; ?>">px</td>
				</tr>
				<tr>
					<th><label for="wmColor"><?php _e('Color','nggallery'); ?></label></th>
					<td><input class="picker" type="text" id="wmColor" name="wmColor" value="<?php echo $ngg->options['wmColor'] ?>">
				</tr>
				<tr>
					<th><label for="wmText"><?php _e('Text','nggallery'); ?></label></th>
					<td><textarea name="wmText" id="wmText" cols="50" rows="5" class="normal-text"><?php echo $ngg->options['wmText'] ?></textarea></td>
				</tr>
				<tr>
					<th><label for="wmOpaque"><?php _e('Opaque','nggallery'); ?></label></th>
					<td><input type="number" step="1" min="0" max="100" class="small-text" name="wmOpaque" id="wmOpaque" value="<?php echo $ngg->options['wmOpaque'] ?>">%</td>
				</tr>
			</table>
			<div class="clear"></div>
			<?php submit_button( __('Save Changes'), 'primary', 'updateoption' ); ?>
		</form>
	<?php
	}

	/**
	 * Get the fonts for the slideshow.
	 *
	 * @return array The fonts.
	 */
	private function get_fonts() {

		$ttf_fonts = array ();

		// Files in wp-content/plugins/nggallery/fonts directory
		$plugin_root = NGGALLERY_ABSPATH . 'fonts';

		$plugins_dir = @ dir($plugin_root);
		if ($plugins_dir) {
			while (($file = $plugins_dir->read()) !== false) {
				if (preg_match('|^\.+$|', $file))
					continue;
				if (is_dir($plugin_root.'/'.$file)) {
					$plugins_subdir = @ dir($plugin_root.'/'.$file);
					if ($plugins_subdir) {
						while (($subfile = $plugins_subdir->read()) !== false) {
							if (preg_match('|^\.+$|', $subfile))
								continue;
							if (preg_match('|\.ttf$|', $subfile))
								$ttf_fonts[] = "$file/$subfile";
						}
					}
				} else {
					if (preg_match('|\.ttf$|', $file))
						$ttf_fonts[] = $file;
				}
			}
		}

		return $ttf_fonts;
	}

	/**
	 * Show slideshow related settings
	 */
	private function tab_slideshow() {

		global $ngg;
		?>
		<form name="player_options" method="POST" action="<?php echo $this->filepath.'#slideshow'; ?>">
			<?php wp_nonce_field('ngg_settings'); ?>
			<input type="hidden" name="page_options" value="irAutoDim,slideFx,irWidth,irHeight,irRotatetime,irLoop,irDrag,irNavigation,irNavigationDots,irAutoplay,irAutoplayTimeout,irAutoplayHover,irNumber,irClick" />
			<h3><?php _e('Slideshow','nggallery'); ?></h3>
			<table class="form-table ngg-options">
				<tr>
					<th><?php _e('Fit to space','nggallery'); ?></th>
					<td>
						<input type="checkbox" name="irAutoDim" id="irAutoDim" value="true" <?php checked( $ngg->options['irAutoDim']); ?>">
						<label for="irAutoDim"><?php _e( "Let the slideshow fit in the available space.", 'nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Default size','nggallery'); ?></th>
					<td>
						<label for="irWidth"><?php _e('Width','nggallery'); ?></label>
						<input <?php $this->readonly($ngg->options['irAutoDim']); ?> type="number" step="1" min="0" class="small-text" name="irWidth" id="irWidth" value="<?php echo $ngg->options['irWidth']; ?>">
						<label for="irHeight"><?php _e('Height','nggallery'); ?></label>
						<input <?php $this->readonly($ngg->options['irAutoDim']); ?> type="number" step="1" min="0" class="small-text" name="irHeight" id="irHeight" value="<?php echo $ngg->options['irHeight']; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="slideFx"><?php _e('Transition / Fade effect','nggallery'); ?></label></th>
					<td>
						<select size="1" name="slideFx" id="slideFx">
							<?php
							$options = array(
								__( 'Attention Seekers', 'nggallery' )  => array( "bounce", "flash", "pulse", "rubberBand", "shake", "swing", "tada", "wobble"),
								__( 'Bouncing Entrances', 'nggallery' ) => array( "bounceIn", "bounceInDown", "bounceInLeft", "bounceInRight", "bounceInUp" ),
								__( 'Fading Entrances', 'nggallery' )   => array( "fadeIn", "fadeInDown", "fadeInDownBig", "fadeInLeft", "fadeInLeftBig", "fadeInRight", "fadeInRightBig", "fadeInUp", "fadeInUpBig"),
								__( 'Fading Exits', 'nggallery' )       => array( "fadeOut", "fadeOutDown", "fadeOutDownBig", "fadeOutLeft", "fadeOutLeftBig", "fadeOutRight", "fadeOutRightBig", "fadeOutUp", "fadeOutUpBig"),
								__( 'Flippers', 'nggallery' )           => array( "flip", "flipInX", "flipInY", "flipOutX", "flipOutY" ),
								__( 'Lightspeed', 'nggallery' )         => array( "lightSpeedIn", "lightSpeedOut"),
								__( 'Rotating Entrances', 'nggallery' )	=> array( "rotateIn", "rotateInDownLeft", "rotateInDownRight", "rotateInUpLeft", "rotateInUpRight" ),
								__( 'Rotating Exits', 'nggallery' )     => array( "rotateOut", "rotateOutDownLeft", "rotateOutDownRight", "rotateOutUpLeft", "rotateOutUpRight" ),
								__( 'Specials', 'nggallery' )           => array( "hinge", "rollIn", "rollOut" ),
								__( 'Zoom Entrances', 'nggallery' )     => array( "zoomIn", "zoomInDown", "zoomInLeft", "zoomInRight", "zoomInUp" )
							);

							foreach( $options as $option => $val ) {
								echo $this->convert_fx_to_optgroup( $val, $option );
							}
							?>
						</select>
						<p class="description">
							<?php _e("These effects are powered by"); ?> <strong>animate.css</strong>. <a target="_blank" href="http://daneden.github.io/animate.css/"><?php _e("Click here for examples of all effects and to learn more."); ?></a></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Loop','nggallery') ?></th>
					<td>
						<input type="checkbox" name="irLoop" id="irLoop" value="true" <?php checked( $ngg->options['irLoop']); ?>">
						<label for="irLoop"><?php _e( "Infinity loop. Duplicate last and first items to get loop illusion.", 'nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Mouse/touch drag','nggallery') ?></th>
					<td>
						<input type="checkbox" name="irDrag" id="irDrag" value="true" <?php checked( $ngg->options['irDrag'] ); ?>">
						<label for="irDrag"><?php _e( "Enable dragging with the mouse (or touch).", 'nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Previous / Next','nggallery') ?></th>
					<td>
						<input type="checkbox" name="irNavigation" id="irNavigation" value="true" <?php checked( $ngg->options['irNavigation'] ); ?>>
						<label for="irNavigation"><?php _e( "Show next/previous buttons.", 'nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Show dots','nggallery') ?></th>
					<td>
						<input type="checkbox" name="irNavigationDots" id="irNavigationDots" value="true" <?php checked( $ngg->options['irNavigationDots'] ); ?>>
						<label for="irNavigationDots"><?php _e( "Show dots for each image.", 'nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Autoplay','nggallery') ?></th>
					<td>
						<input type="checkbox" name="irAutoplay" id="irAutoplay" value="true" <?php checked( $ngg->options['irAutoplay'] ); ?>>
						<label for="irAutoplay"><?php _e( "Automatically play the images.", 'nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><label for="irRotatetime"><?php _e('Duration','nggallery') ?></label></th>
					<td>
						<input <?php $this->readonly( false, $ngg->options['irAutoplay'] ); ?> type="number" step="1" min="0" class="small-text" name="irRotatetime" id="irRotatetime" value="<?php echo $ngg->options['irRotatetime'] ?>">
						<?php _e('sec.', 'nggallery') ;?>
					</td>
				</tr>
				<tr>
					<th><?php _e('Pause on hover','nggallery') ?></th>
					<td>
						<input <?php disabled(false, $ngg->options['irAutoplay']); ?> type="checkbox" name="irAutoplayHover" id="irAutoplayHover" value="true" <?php checked( $ngg->options['irAutoplayHover']); ?>>
						<label for="irAutoplayHover"><?php _e( "Pause when hovering over the slideshow.", 'nggallery'); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e('Click for next','nggallery') ?></th>
					<td>
						<input type="checkbox" name="irClick" id="irClick" value="true" <?php checked( $ngg->options['irClick']); ?>>
						<label for="irClick"><?php _e( "Click to go to the next image.", 'nggallery'); ?></label></td>
				</tr>
				<tr>
					<th><?php _e('Number of images','nggallery') ?></th>
					<td>
						<input type="number" step="1" min="1" class="small-text" name="irNumber" id="irNumber" value="<?php echo $ngg->options['irNumber'] ?>">
						<label for="irNumber"><?php _e('images', 'nggallery') ;?></label>
						<p class="description"><?php _e( "Number of images to display when using random or latest.", 'nggallery'); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __('Save Changes'), 'primary', 'updateoption' ); ?>
	</form>
	<?php
	}

	/**
	 * Convert an array of slideshow styles to a html dropdown group.
	 *
	 * @param $data array The option values (and display).
	 * @param $title string (optional) The label of the optgroup.
	 *
	 * @return string The output.
	 */
	private function convert_fx_to_optgroup( $data, $title = null ) {

		global $ngg;

		if ( is_null($title) ) {
			$out = null;
		} else {
			$out = '<optgroup label="' . $title . '">';
		}

		foreach( $data as $option) {
			$out .= '<option value="' . $option . '" ' . selected( $option, $ngg->options['slideFx'] ) . '>' . $option . '</option>';
		}

		if ( !is_null( $title ) ) {
			$out .= '</optgroup>';
		}

		return $out;
	}

	/**
	 * Compare two values and echo readonly if they are.
	 *
	 * @param mixed $current The current value.
	 * @param mixed $other (optional) The other value.
	 */
	private function readonly($current, $other = true) {
		if ( $current == $other ) {
			echo 'readonly="readonly"';
		}
	}
}
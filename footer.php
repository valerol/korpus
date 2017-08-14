        <div class="footer-container">
            <footer class="wrapper">
				<section class="partners">
						<?php if ( is_active_sidebar( 'footer' ) ) : ?>

								<?php dynamic_sidebar( 'footer' ); ?>

						<?php endif; ?>
				</section>
				<p class="copyright">&copy; &laquo;Корпус волонтеров Приморского края&raquo;, 2013–<?php echo date('Y'); ?></p>
            </footer>
            <?php wp_footer(); ?>
        </div>

        <!-- Yandex.Metrika counter --><script type="text/javascript"> (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter4207894 = new Ya.Metrika({ id:23959834, clickmap:true, trackLinks:true, accurateTrackBounce:true }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://mc.yandex.ru/metrika/watch.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks");</script><noscript><div><img src="https://mc.yandex.ru/watch/4207894" style="position:absolute; left:-9999px;" alt="" /></div></noscript><!-- /Yandex.Metrika counter -->
   </body>
</html>

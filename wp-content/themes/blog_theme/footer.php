</div>
<script type="text/javascript">
<?php
  $theme_options = get_option('theme_options');  
  $gaID = $theme_options['google_analytics'];
?>
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '<?php echo $gaID; ?>']);
_gaq.push(['_trackPageview']);

(function() {
  var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
  ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
  var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();

</script>
</body>
</html>
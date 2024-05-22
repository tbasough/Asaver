/**
 * Plugin Template admin js.
 *
 *  @package WordPress Plugin Template/JS
 */

jQuery( document ).ready(function ( $ ) {
    function copyAndHighlight(e) {
      const input = document.getElementById('copy_shortcode');
      e.text='Copied';
      input.select();
      document.execCommand('copy');
      input.classList.add('highlighted');
      setTimeout(function() {
        input.classList.remove('highlighted');
        e.text='Copy';
    }, 1000);
  }
});




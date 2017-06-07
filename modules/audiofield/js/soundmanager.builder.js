/**
 * @file
 * Audiofield build SoundManager audio player.
 */

(function ($, Drupal) {
  Drupal.behaviors.audiofieldsoundmanager = {
    attach: function (context, settings) {
      // Soundmanager intercepts everything so the setup is very simple.
      soundManager.setup({
        // Required: path to directory containing SM2 SWF files.
        url: drupalSettings.audiofieldsoundmanager.swfpath,
        preferFlash: false,
        defaultOptions: {
          volume: drupalSettings.audiofieldsoundmanager.volume,
        },
      });
    }
  };
})(jQuery, Drupal);

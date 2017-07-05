/**
 * @file
 * Audiofield build MediaElement audio player.
 */

(function ($, Drupal) {
  Drupal.behaviors.audiofieldmediaelement = {
    attach: function (context, settings) {
      $.each(drupalSettings.audiofieldmediaelement, function (key, setting_entry) {
        // Loop over each file.
        $.each(setting_entry.elements, function (key, file_entry) {
          // Create the media player.
          $(file_entry, context).once('generate-mediaelement').mediaelementplayer({
            startVolume: setting_entry.volume,
            loop: false,
            enableAutosize: true,
            isVideo: false,
          });
        });
      });
    }
  };
})(jQuery, Drupal);

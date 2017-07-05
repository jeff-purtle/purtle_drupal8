/**
 * @file
 * Audiofield build Projekktor audio player.
 */

(function ($, Drupal) {
  Drupal.behaviors.audiofieldprojekktor = {
    attach: function (context, settings) {
      // Have to encapsulate because of the way library is created.
      jQuery(function () {
        $.each(drupalSettings.audiofieldprojekktor, function (key, setting_entry) {
          // Loop over the attached files.
          $.each(setting_entry.files, function (key2, file) {
            // Create the audioplayer for each file.
            $(context).find('#' + file).once('generate-projekktor').each(function () {
              var myPlayer = projekktor('#' + file, {
                debug: false,
                playerFlashMP4: setting_entry.swfpath,
                playerFlashMP3: setting_entry.swfpath,
                enableFullscreen: false,
                streamType: 'http',
                controls: true,
                thereCanBeOnlyOne: true,
                volume: setting_entry.volume,
                plugin_display: {}
              }, function (player) {});
            });
          });
        });
      });
    }
  };
})(jQuery, Drupal);

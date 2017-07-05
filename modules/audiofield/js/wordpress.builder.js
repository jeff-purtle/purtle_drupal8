/**
 * @file
 * Audiofield build WordPress audio player.
 */

(function ($, Drupal) {
  Drupal.behaviors.audiofieldwordpress = {
    attach: function (context, settings) {
      $.each(drupalSettings.audiofieldwordpress, function (key, setting_entry) {
        // Initialize the audioplayer.
        AudioPlayer.setup("/libraries/wordpress-audio/player.swf", {
          width: 400,
          initialvolume: setting_entry.volume,
          transparentpagebg: "yes",
        });
        // Loop over the files.
        $.each(setting_entry.files, function (key2, file_entry) {
          // Generate the player for each file.
          $(context).find("#wordpressaudioplayer_" + file_entry.unique_id).once('generate-wordpress').each(function () {
            AudioPlayer.embed($(this).attr('id'), {
              soundFile: file_entry.file,
              titles: file_entry.title,
              autostart: "no",
              loop: "no",
              initialvolume: setting_entry.volume,
              checkpolicy: "yes",
              animation: setting_entry.animate,
            });
          });
        });
      });
    }
  };
})(jQuery, Drupal);

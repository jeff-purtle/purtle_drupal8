/**
 * @file
 * Audiofield build jPlayer audio players of various types.
 */

(function ($, Drupal) {
  Drupal.behaviors.audiofieldjplayer = {
    attach: function (context, settings) {
      $.each(drupalSettings.audiofieldjplayer, function (key, setting_entry) {
        // Default audio player.
        if (setting_entry.playertype == 'default') {
          // We can just initialize the audio player direcly.
          $("#jquery_jplayer_" + setting_entry.unique_id, context).once('generate-jplayer').jPlayer(
            {
              cssSelectorAncestor: "#jp_container_" + setting_entry.unique_id
            },
            {
              ready: function (event) {
                var media_array = {
                  title: setting_entry.description,
                };
                media_array[setting_entry.filetype] = setting_entry.file;
                $(this).jPlayer("setMedia", media_array);
              },
              swfPath: "/libraries/jplayer/dist/jplayer",
              supplied: setting_entry.filetype,
              wmode: "window",
              useStateClassSkin: true,
              autoBlur: false,
              smoothPlayBar: true,
              keyEnabled: true,
              remainingDuration: false,
              toggleDuration: false,
              volume: setting_entry.volume,
            }
          );
        }
        // Playlist audio player.
        else if (setting_entry.playertype == 'playlist') {
          // Initialize the container audio player.
          $(context).find('#jquery_jplayer_' + setting_entry.unique_id).once('generate-jplayer').each(function () {
            var myPlaylist = new jPlayerPlaylist({
                jPlayer: $(this),
                cssSelectorAncestor: "#jp_container_" + setting_entry.unique_id
              }, [], {
              playlistOptions: {
                enableRemoveControls: false
              },
              swfPath: "/libraries/jplayer/dist/jplayer",
              wmode: "window",
              useStateClassSkin: true,
              autoBlur: false,
              smoothPlayBar: true,
              keyEnabled: true,
              volume: setting_entry.volume,
            });

            // Loop over each file.
            $.each(setting_entry.files, function (key, file_entry) {
              // Build the media array.
              var media_array = {
                title: file_entry.description,
              };
              media_array[file_entry.filetype] = file_entry.file;
              // Add the file to the playlist.
              myPlaylist.add(media_array);
            });
          });
        }
        // Circle audio player.
        else if (setting_entry.playertype == 'circle') {
          // Loop over the files.
          $.each(setting_entry.files, function (key, file_entry) {
            $(context).find('#jquery_jplayer_' + file_entry.fid).once('generate-jplayer').each(function () {
              // Build the media array for this player.
              var media_array = {};
              media_array[file_entry.filetype] = file_entry.file;
              // Initialize the player.
              var myCirclePlayer = new CirclePlayer(
                $(this),
                media_array,
                {
                  cssSelectorAncestor: "#cp_container_" + file_entry.fid,
                  swfPath: "/libraries/jplayer/dist/jplayer",
                  wmode: "window",
                  keyEnabled: true,
                  supplied: file_entry.filetype,
                }
              );
            });
          });
        }
      });
    }
  };
})(jQuery, Drupal);

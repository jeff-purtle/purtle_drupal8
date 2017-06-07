/**
 * @file
 * Audiofield build Wavesurfer audio player.
 */

(function ($, Drupal) {
  Drupal.behaviors.audiofieldwavesurfer = {
    attach: function (context, settings) {
      $.each(settings.audiofieldwavesurfer, function (key, setting_entry) {
        // Default audio player.
        if (setting_entry.playertype == 'default') {
          // Loop over the files.
          $.each(setting_entry.files, function (key2, file) {
            $(context).find('#' + file.id).once('generate-waveform').each(function () {
              // Store the wavesurfer container.
              var wavecontainer = $(this);

              // Create waveform.
              var wavesurfer = WaveSurfer.create({
                  container: '#' + $(this).attr('id') + ' .waveform',
              });

              // Load the file.
              wavesurfer.load(file.path);

              // Set the default volume.
              wavesurfer.setVolume(setting_entry.volume);

              // Handle play/pause.
              $(this).find('.player-button.playpause').on('click', function () {
                wavesurferPlayPause(wavecontainer, wavesurfer);
              });

              // Handle volume change.
              $(this).find('.volume').on('change', function () {
                wavesurfer.setVolume(($(this).val() / 10));
              });
            });
          });
        }
        else if (setting_entry.playertype == 'playlist') {
          $(context).find('#wavesurfer_playlist').once('generate-waveform').each(function () {
            // Store the wavesurfer container.
            var wavecontainer = $(this);

            // Create waveform.
            var wavesurfer = WaveSurfer.create({
                container: '#' + $(this).attr('id') + ' .waveform',
            });

            // Set the default volume.
            wavesurfer.setVolume(setting_entry.volume);

            // Load the first file.
            var first = $(this).find('.playlist .track').first();
            // Get the label and update it with the first filename.
            var label = $(this).find('label').first();
            label.html('Playing: ' + first.html());
            // Set the playing class on the first element.
            first.addClass('playing');
            // Load the file.
            wavesurfer.load(first.attr('data-src'));

            // Handle play/pause.
            $(this).find('.player-button.playpause').on('click', function () {
              wavesurferPlayPause(wavecontainer, wavesurfer);
            });

            // Handle next/previous.
            $(this).find('.player-button.next').on('click', function () {
              wavesurferNext(wavecontainer, wavesurfer);
            });
            $(this).find('.player-button.previous').on('click', function () {
              wavesurferPrevious(wavecontainer, wavesurfer);
            });

            // Handle clicking track.
            $(this).find('.playlist .track').on('click', function () {
              // Load the track.
              wavesurferLoad(wavecontainer, wavesurfer, $(this));
              // Play the track.
              wavesurferPlayPause(wavecontainer, wavesurfer);
            });

            // Handle volume change.
            $(this).find('.volume').on('change', function () {
              wavesurfer.setVolume(($(this).val() / 10));
            });

            // Handle track finishing.
            wavesurfer.on('finish', function () {
              wavesurferNext(wavecontainer, wavesurfer);
            });
          });

        }
      });

      /**
       * Play or pause the wavesurfer and set appropriate classes.
       */
      function wavesurferPlayPause(wavecontainer, wavesurfer) {
        wavesurfer.playPause();
        var button = wavecontainer.find('.player-button.playpause');
        if (wavesurfer.isPlaying()) {
          wavecontainer.addClass('playing');
          button.html('Pause');
        }
        else {
          wavecontainer.removeClass('playing');
          button.html('Play');
        }
      }

      /**
       * Load track on wavesurfer and set appropriate classes.
       */
      function wavesurferLoad(wavecontainer, wavesurfer, track) {
        // Load the track.
        wavesurfer.on('ready', function () {
          wavesurfer.play();
          wavecontainer.removeClass('playing');
          wavecontainer.addClass('playing');
          wavecontainer.find('.player-button.playpause').html('Pause');
        });
        wavesurfer.load(track.attr('data-src'));
        // Remove playing from all other tracks.
        wavecontainer.find('.track').removeClass('playing');
        // Set the class on this track.
        track.addClass('playing');
        // Show what's playing.
        wavecontainer.find('label').first().html('Playing: ' + track.html());
      }

      /**
       * Skip track forward on wavesurfer and set appropriate classes.
       */
      function wavesurferNext(wavecontainer, wavesurfer) {
        if (wavesurfer.isPlaying()) {
          wavesurferPlayPause(wavecontainer, wavesurfer);
        }
        // Find the next track.
        var track = wavecontainer.find('.track.playing').next();
        if (typeof track.attr('data-src') == 'undefined') {
          track = wavecontainer.find('.track').first();
        }
        // Load the track.
        wavesurferLoad(wavecontainer, wavesurfer, track);
      }

      /**
       * Skip track back on wavesurfer and set appropriate classes.
       */
      function wavesurferPrevious(wavecontainer, wavesurfer) {
        if (wavesurfer.isPlaying()) {
          wavesurferPlayPause(wavecontainer, wavesurfer);
        }
        // Find the next track.
        var track = wavecontainer.find('.track.playing').prev();
        if (typeof track.attr('data-src') == 'undefined') {
          track = wavecontainer.find('.track').last();
        }
        // Load the track.
        wavesurferLoad(wavecontainer, wavesurfer, track);
      }
    }
  };
})(jQuery, Drupal);

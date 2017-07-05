/**
 * @file
 * Audiofield build AudioJs audio player.
 */

(function ($, Drupal) {
  Drupal.behaviors.audiofieldaudiojs = {
    attach: function (context, settings) {
      $.each(drupalSettings.audiofieldaudiojs, function (key, setting_entry) {

        $(context).find('#' + setting_entry.element).once('generate-audiojs').each(function () {
          // Initialize the audio player.
          var audioPlayer = audiojs.create($(this).find('audio').get(0), {
            css: false,
            createPlayer: {
              markup: false,
              playPauseClass: 'play-pauseZ',
              scrubberClass: 'scrubberZ',
              progressClass: 'progressZ',
              loaderClass: 'loadedZ',
              timeClass: 'timeZ',
              durationClass: 'durationZ',
              playedClass: 'playedZ',
              errorMessageClass: 'error-messageZ',
              playingClass: 'playingZ',
              loadingClass: 'loadingZ',
              errorClass: 'errorZ'
            },
            // Handle the end of a track.
            trackEnded: function () {
              var next = $(this).find('ol li.playing').next();
              if (!next.length) {
                next = $(this).find('ol li').first();
              }
              next.addClass('playing').siblings().removeClass('playing');
              audioPlayer.load($('a', next).attr('data-src'));
              audioPlayer.play();
            }
          });

          // Load in the first track.
          first = $(this).find('ol a').first().attr('data-src');
          $(this).find('ol li').first().addClass('playing');
          audioPlayer.load(first);

          // Load in a track on click.
          $(this).find('ol li').click(function (e) {
            e.preventDefault();
            $(this).addClass('playing').siblings().removeClass('playing');
            audioPlayer.load($('a', this).attr('data-src'));
            audioPlayer.play();
          });
        });
      });

    }
  };
})(jQuery, Drupal);

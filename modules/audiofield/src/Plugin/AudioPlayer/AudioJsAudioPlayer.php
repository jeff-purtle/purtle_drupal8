<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the audio.js Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "audiojs_audio_player",
 *   title = @Translation("audio.js audio player"),
 *   description = @Translation("Drop-in javascript library using native <audio> tag."),
 *   fileTypes = {
 *     "mp3",
 *   },
 *   libraryName = "audiojs",
 *   librarySource = "http://kolber.github.io/audiojs/",
 * )
 */
class AudioJsAudioPlayer extends AudioFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderPlayer(FieldItemListInterface $items, $langcode, $settings) {
    // Check to make sure we're installed.
    if (!$this->checkInstalled()) {
      // Show the error.
      $this->showInstallError();

      // Simply return the default rendering so the files are still displayed.
      $default_player = new DefaultMp3Player();
      return $default_player->renderPlayer($items, $langcode, $settings);
    }

    // Start building settings to pass to the javascript audio.js builder.
    $player_settings = array(
      // Audio.js expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'element' => '',
    );

    $markup = '';
    foreach ($items as $item) {
      // If this entity has passed validation, we render it.
      if ($this->validateEntityAgainstPlayer($item)) {
        // Get render information for this item.
        $renderInfo = $this->getAudioRenderInfo($item);

        // Used to generate unique container.
        $player_settings['element'] = 'audiofield_audiojs_' . $renderInfo->id;

        // Generate HTML markup for the player.
        $markup .= '<li><a href="#" data-src="' . $renderInfo->url->toString() . '">' . $renderInfo->description . '</a></li>';
      }
    }

    // If we have at least one audio file, we render.
    if (!empty($markup)) {
      // Add the HTML framework to the track listing.
      $markup = '<div id="' . $player_settings['element'] . '" class="audiofield-audiojs-frame">
        <div class="audiofield-audiojs">
          <audio preload="auto"></audio>
          <div class="play-pauseZ">
            <p class="playZ"></p>
            <p class="pauseZ"></p>
            <p class="loadingZ"></p>
            <p class="errorZ"></p>
          </div>
          <div class="scrubberZ">
            <div class="progressZ"></div>
            <div class="loadedZ"></div>
          </div>
          <div class="timeZ">
            <em class="playedZ">00:00</em>/<strong class="durationZ">00:00</strong>
          </div>
          <div class="error-messageZ"></div>
        </div>
        <ol>' . $markup . '</ol>
      </div>
      ';

      return [
        'audioplayer' => [
          '#prefix' => '<div class="audiofield">',
          '#markup' => Markup::create($markup),
          '#suffix' => '</div>',
        ],
        'downloads' => $this->createDownloadList($items, $settings),
        '#attached' => [
          'library' => [
            // Attach the audio.js library.
            'audiofield/audiofield.' . $this->getPluginLibrary(),
          ],
          'drupalSettings' => [
            'audiofieldaudiojs' => [
              $renderInfo->id => $player_settings,
            ],
          ],
        ],
      ];
    }

    return [];
  }

}

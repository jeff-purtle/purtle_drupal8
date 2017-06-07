<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the MediaElement Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "mediaelement_audio_player",
 *   title = @Translation("MediaElement audio player"),
 *   description = @Translation("A dependable HTML media framework."),
 *   fileTypes = {
 *     "mp3", "oga", "ogg", "wav",
 *   },
 *   libraryName = "mediaelement",
 *   librarySource = "http://mediaelementjs.com/",
 * )
 */
class MediaElementAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript MediaElement builder.
    $player_settings = array(
      // MediaElement expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'elements' => [],
    );

    $markup = '';
    foreach ($items as $item) {
      // If this entity has passed validation, we render it.
      if ($this->validateEntityAgainstPlayer($item)) {
        // Get render information for this item.
        $renderInfo = $this->getAudioRenderInfo($item);

        // Pass the element name for the player so we know what to render.
        $player_settings['elements'][] = '#mediaelement_player_' . $renderInfo->id;

        // Generate HTML markup for the player.
        $markup .= '<div class="mediaelementaudio_frame">
            <audio id="mediaelement_player_' . $renderInfo->id . '" controls>
              <source src="' . $renderInfo->url->toString() . '">
              Your browser does not support the audio element.
            </audio>
          </div>
          <label for="mediaelement_player_' . $renderInfo->id . '">' . $renderInfo->description . '</label>';
      }
    }

    return [
      'audioplayer' => [
        '#prefix' => '<div class="audiofield">',
        '#markup' => Markup::create($markup),
        '#suffix' => '</div>',
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the MediaElement library.
          'audiofield/audiofield.' . $this->getPluginLibrary(),
        ],
        'drupalSettings' => [
          'audiofieldmediaelement' => [
            $renderInfo->id => $player_settings,
          ],
        ],
      ],
    ];
  }

}

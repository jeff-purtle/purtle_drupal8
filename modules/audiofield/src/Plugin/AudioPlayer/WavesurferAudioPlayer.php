<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the Wavesurfer Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "wavesurfer_audio_player",
 *   title = @Translation("Wavesurfer audio player"),
 *   description = @Translation("A customizable audio waveform visualization, built on top of Web Audio API and HTML5 Canvas."),
 *   fileTypes = {
 *     "mp3", "ogg", "oga", "wav",
 *   },
 *   libraryName = "wavesurfer",
 *   librarySource = "https://github.com/katspaugh/wavesurfer.js",
 * )
 */
class WavesurferAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript wavesurfer builder.
    $player_settings = [
      // Projekktor expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'playertype' => 'default',
      'files' => [],
    ];

    $markup = '';

    // If we are combining into a single player, make some modifications.
    if ($settings['audio_player_wavesurfer_combine_files']) {
      // Store the playertype.
      $player_settings['playertype'] = 'playlist';

      $markup .= '<div class="audiofield-wavesurfer playlist" id="wavesurfer_playlist">
        <div class="waveform"></div>
        <div class="player-button previous">Previous</div>
        <div class="player-button playpause play">Play</div>
        <div class="player-button next">Next</div>
        <input type="range" class="volume" min="0" max="10" value="' . $settings['audio_player_initial_volume'] . '">
        <label>Playing:</label>
        <ol class="playlist">
      ';

      foreach ($items as $item) {
        // If this entity has passed validation, we render it.
        if ($this->validateEntityAgainstPlayer($item)) {
          // Get render information for this item.
          $renderInfo = $this->getAudioRenderInfo($item);

          $markup .= '<li class="track" data-src="' . $renderInfo->url->toString() . '">' . $renderInfo->description . '</li>';
        }
      }
      $markup .= '
        </ol>
      </div>
      ';
    }
    else {
      foreach ($items as $item) {
        // If this entity has passed validation, we render it.
        if ($this->validateEntityAgainstPlayer($item)) {
          // Get render information for this item.
          $renderInfo = $this->getAudioRenderInfo($item);

          // Add this file to the render settings.
          $player_settings['files'][] = [
            'id' => 'wavesurfer_' . $renderInfo->id,
            'path' => $renderInfo->url->toString(),
          ];

          $markup .= '<div class="audiofield-wavesurfer" id="wavesurfer_' . $renderInfo->id . '">
            <div class="waveform"></div>
            <div class="player-button playpause play">Play</div>
            <input type="range" class="volume" min="0" max="10" value="' . $settings['audio_player_initial_volume'] . '">
            <label>' . $renderInfo->description . '</label>
          </div>';
        }
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
          // Attach the wavesurfer library.
          'audiofield/audiofield.' . $this->getPluginLibrary(),
        ],
        'drupalSettings' => [
          'audiofieldwavesurfer' => [
            $renderInfo->id => $player_settings,
          ],
        ],
      ],
    ];
  }

}

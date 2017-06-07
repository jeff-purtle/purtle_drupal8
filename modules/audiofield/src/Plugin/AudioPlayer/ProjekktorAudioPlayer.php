<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the Projekktor Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "projekktor_audio_player",
 *   title = @Translation("Projekktor audio player"),
 *   description = @Translation("Free Web Video Player (converted for audio)"),
 *   fileTypes = {
 *     "mp3", "mp4", "ogg", "oga", "wav",
 *   },
 *   libraryName = "projekktor",
 *   librarySource = "http://www.projekktor.com/",
 * )
 */
class ProjekktorAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript projekktor builder.
    $player_settings = [
      // Projekktor expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'swfpath' => $this->getPluginLibraryPath() . '/swf/Jarisplayer/jarisplayer.swf',
      'files' => [],
    ];

    $markup = '';
    foreach ($items as $item) {
      // If this entity has passed validation, we render it.
      if ($this->validateEntityAgainstPlayer($item)) {
        // Get render information for this item.
        $renderInfo = $this->getAudioRenderInfo($item);

        // Add this file to the render settings.
        $player_settings['files'][] = $renderInfo->id;

        $markup .= '<audio id="' . $renderInfo->id . '" class="audiofield-projekktor projekktor" controls>
             <source src="' . $renderInfo->url->toString() . '" type="audio/mpeg">
             Your browser does not support the audio element.
          </audio>
          <label>' . $renderInfo->description . '</label>';
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
          // Attach the projekktor library.
          'audiofield/audiofield.' . $this->getPluginLibrary(),
        ],
        'drupalSettings' => [
          'audiofieldprojekktor' => [
            $renderInfo->id => $player_settings,
          ],
        ],
      ],
    ];
  }

}

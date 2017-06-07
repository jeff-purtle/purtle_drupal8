<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the Default HTML5 Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "default_mp3_player",
 *   title = @Translation("default HTML5 audio player"),
 *   description = @Translation("Default html5 player - built into HTML specification."),
 *   fileTypes = {
 *     "mp3", "mp4", "m4a", "3gp", "aac", "wav", "ogg", "oga", "flac", "webm",
 *   },
 *   libraryName = "default",
 *   librarySource = "",
 * )
 */
class DefaultMp3Player extends AudioFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderPlayer(FieldItemListInterface $items, $langcode, $settings) {
    $render = [];

    foreach ($items as $item) {
      $markup = '';
      // If this entity has passed validation, we render it.
      if ($this->validateEntityAgainstPlayer($item)) {
        // Get render information for this item.
        $renderInfo = $this->getAudioRenderInfo($item);

        $markup = '<audio controls>
             <source src="' . $renderInfo->url->toString() . '" type="audio/mpeg">
             Your browser does not support the audio element.
          </audio>
          <label>' . $renderInfo->description . '</label>';
      }

      $render[] = [
        '#markup' => Markup::create($markup),
      ];
    }

    // Add download links.
    $render[] = $this->createDownloadList($items, $settings);

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function checkInstalled() {
    // This is built in to HTML5, so it is always "installed".
    return TRUE;
  }

}

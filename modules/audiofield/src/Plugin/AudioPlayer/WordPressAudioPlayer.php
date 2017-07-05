<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the WordPress Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "wordpress_audio_player",
 *   title = @Translation("WordPress audio player"),
 *   description = @Translation("Standalone audio player originally built for WordPress"),
 *   fileTypes = {
 *     "mp3",
 *   },
 *   libraryName = "wordpress",
 *   librarySource = "http://wpaudioplayer.com",
 * )
 */
class WordPressAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript WordPress builder.
    $player_settings = array(
      // WordPress expects this as a 0 - 100 range.
      'volume' => ($settings['audio_player_initial_volume'] * 10),
      'animate' => ($settings['audio_player_wordpress_animation'] ? 'yes' : 'no'),
      'files' => [],
    );

    // Create an array to hold the markup for each player.
    $player_html_markup = [];
    foreach ($items as $item) {
      // If this entity has passed validation, we render it.
      if ($this->validateEntityAgainstPlayer($item)) {
        // Get render information for this item.
        $renderInfo = $this->getAudioRenderInfo($item);

        // Pass settings for the file.
        $player_settings['files'][] = [
          'file' => $renderInfo->url->toString(),
          'title' => $renderInfo->description,
          'unique_id' => $renderInfo->id,
        ];

        // Generate HTML markup.
        $player_html_markup[] = '<div class="wordpressaudio_frame"><div class="wordpressaudioplayer" id="wordpressaudioplayer_' . $renderInfo->id . '">' . $renderInfo->description . '</div></div>';
      }
    }

    // If we are combining into a single player, make some modifications.
    if ($settings['audio_player_wordpress_combine_files']) {
      // Wordpress expects comma-deliminated lists
      // when using multiple files in a single player.
      $wp_files = [];
      $wp_titles = [];
      foreach ($player_settings['files'] as $wp_file) {
        $wp_files[] = $wp_file['file'];
        $wp_titles[] = $wp_file['title'];
      }

      // Redeclare settings with only a single (combined) file.
      $player_settings['files'] = [
        [
          'file' => implode(',', $wp_files),
          'title' => implode(',', $wp_titles),
          'unique_id' => $player_settings['files'][0]['unique_id'],
        ],
      ];

      // Only need the first player to be rendered for markup.
      $markup = $player_html_markup[0];
    }
    else {
      // We need all markup so just combine them.
      $markup = implode('', $player_html_markup);
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
          // Attach the WordPress library.
          'audiofield/audiofield.' . $this->getPluginLibrary(),
        ],
        'drupalSettings' => [
          'audiofieldwordpress' => [
            $renderInfo->id => $player_settings,
          ],
        ],
      ],
    ];
  }

}

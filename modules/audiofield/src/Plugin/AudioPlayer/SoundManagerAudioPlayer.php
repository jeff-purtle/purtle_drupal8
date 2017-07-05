<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the SoundManager Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "soundmanager_audio_player",
 *   title = @Translation("SoundManager audio player"),
 *   description = @Translation("Simple, reliable cross-platform audio."),
 *   fileTypes = {
 *     "mp3", "mp4", "ogg", "oga", "wav", "flac",
 *   },
 *   libraryName = "soundmanager",
 *   librarySource = "http://www.schillmania.com/projects/soundmanager2",
 * )
 */
class SoundManagerAudioPlayer extends AudioFieldPluginBase {

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

    // Start building settings to pass to the javascript SoundManager builder.
    $player_settings = array(
      // SoundManager expects this as a 0 - 100 range.
      'volume' => ($settings['audio_player_initial_volume'] * 10),
      'swfpath' => $this->getPluginLibraryPath() . '/swf/',
    );

    $markup = '';
    // Loop over each item.
    foreach ($items as $item) {
      // If this entity has passed validation, we render it.
      if ($this->validateEntityAgainstPlayer($item)) {
        // Get render information for this item.
        $renderInfo = $this->getAudioRenderInfo($item);

        // Generate HTML markup for the player (different for each theme).
        switch ($settings['audio_player_soundmanager_theme']) {
          case 'default':
            $markup .= '<div class="soundmanageraudio_frame"><a href="' . $renderInfo->url->toString() . '" class="sm2_button">' . $renderInfo->description . '</a> <label>' . $renderInfo->description . '</label></div>';
            break;

          case 'player360':
            $markup .= '<div class="ui360"><a href="' . $renderInfo->url->toString() . '">' . $renderInfo->description . '</a></div>';
            break;

          case 'barui':
            $markup .= '<li><a href="' . $renderInfo->url->toString() . '">' . $renderInfo->description . '</a></li>';
            break;

          case 'inlineplayer':
            $markup .= '<ul class="graphic"><li><a href="' . $renderInfo->url->toString() . '">' . $renderInfo->description . '</a></li></ul>';
            break;
        }
      }
    }

    // These themes require additional markup.
    if ($settings['audio_player_soundmanager_theme'] == 'barui') {
      $markup = '
        <div class="sm2-bar-ui ' . ((count($items) > 1) ? 'playlist-open' : '') . '">
          <div class="bd sm2-main-controls">
            <div class="sm2-inline-texture"></div>
            <div class="sm2-inline-gradient"></div>
            <div class="sm2-inline-element sm2-button-element">
              <div class="sm2-button-bd">
                <a href="#play" class="sm2-inline-button play-pause">Play / pause</a>
              </div>
            </div>
            <div class="sm2-inline-element sm2-inline-status">
              <div class="sm2-playlist">
                <div class="sm2-playlist-target"><noscript><p>JavaScript is required.</p></noscript></div>
              </div>
              <div class="sm2-progress">
                <div class="sm2-row">
                  <div class="sm2-inline-time">0:00</div>
                  <div class="sm2-progress-bd">
                    <div class="sm2-progress-track">
                      <div class="sm2-progress-bar"></div>
                      <div class="sm2-progress-ball"><div class="icon-overlay"></div></div>
                    </div>
                  </div>
                  <div class="sm2-inline-duration">0:00</div>
                </div>
              </div>
            </div>
            <div class="sm2-inline-element sm2-button-element sm2-volume">
              <div class="sm2-button-bd">
                <span class="sm2-inline-button sm2-volume-control volume-shade"></span>
                <a href="#volume" class="sm2-inline-button sm2-volume-control">volume</a>
              </div>
            </div>
            <div class="sm2-inline-element sm2-button-element sm2-menu">
              <div class="sm2-button-bd">
                <a href="#menu" class="sm2-inline-button menu">menu</a>
              </div>
            </div>
          </div>
          <div class="bd sm2-playlist-drawer sm2-element">
            <div class="sm2-playlist-wrapper">
              <ul class="sm2-playlist-bd">' . $markup . '</ul>
            </div>
            <div class="sm2-extra-controls">
              <div class="bd">
                <div class="sm2-inline-element sm2-button-element">
                  <a href="#prev" title="Previous" class="sm2-inline-button previous">&lt; previous</a>
                </div>
                <div class="sm2-inline-element sm2-button-element">
                  <a href="#next" title="Next" class="sm2-inline-button next">&gt; next</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      ';
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
          // Attach the SoundManager library.
          'audiofield/audiofield.' . $this->getPluginLibrary(),
          // Attach the skin library.
          'audiofield/audiofield.' . $this->getPluginLibrary() . '.' . $settings['audio_player_soundmanager_theme'],
        ],
        'drupalSettings' => [
          'audiofieldsoundmanager' => $player_settings,
        ],
      ],
    ];
  }

}

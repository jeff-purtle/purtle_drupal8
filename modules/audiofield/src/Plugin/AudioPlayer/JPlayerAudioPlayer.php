<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Render\Markup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;

/**
 * Implements the jPlayer Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "jplayer_audio_player",
 *   title = @Translation("jPlayer audio player"),
 *   description = @Translation("Free and open source media library."),
 *   fileTypes = {
 *     "mp3", "mp4", "wav", "ogg", "oga", "webm",
 *   },
 *   libraryName = "jplayer",
 *   librarySource = "http://jplayer.org/",
 * )
 */
class JPlayerAudioPlayer extends AudioFieldPluginBase {

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

    // JPlayer circle has to render differently - no playlist support, etc.
    if ($settings['audio_player_jplayer_theme'] == 'audiofield.jplayer.theme_jplayer_circle') {
      // @todo circle player broken for some reason.
      // Only require the default library.
      $library = 'audiofield/audiofield.' . $this->getPluginLibrary();

      // Start building settings to pass to the javascript jplayer builder.
      $player_settings = array(
        'playertype' => 'circle',
        // JPlayer expects this as a 0 - 1 value.
        'volume' => ($settings['audio_player_initial_volume'] / 10),
        'files' => [],
      );
      $markup = '';
      foreach ($items as $item) {
        // If this entity has passed validation, we render it.
        if ($this->validateEntityAgainstPlayer($item)) {
          // Get render information for this item.
          $renderInfo = $this->getAudioRenderInfo($item);

          // Add entry to player settings for this file.
          $player_settings['files'][] = [
            'file' => $renderInfo->url->toString(),
            'description' => $renderInfo->description,
            'filetype' => $renderInfo->filetype,
            'fid' => $renderInfo->id,
          ];

          $markup .= '
            <div id="jquery_jplayer_' . $renderInfo->id . '" class="cp-jplayer"></div>
            <div class="cp-circle-frame">
              <div id="cp_container_' . $renderInfo->id . '" class="cp-container">
                <div class="cp-buffer-holder">
                  <div class="cp-buffer-1"></div>
                  <div class="cp-buffer-2"></div>
                </div>
                <div class="cp-progress-holder">
                  <div class="cp-progress-1"></div>
                  <div class="cp-progress-2"></div>
                </div>
                <div class="cp-circle-control"></div>
                <ul class="cp-controls">
                  <li><a class="cp-play" tabindex="1">play</a></li>
                  <li><a class="cp-pause" style="display:none;" tabindex="1">pause</a></li>
                </ul>
              </div>
              <label for="cp_container_' . $renderInfo->id . '">' . $renderInfo->description . '</label>
            </div>
          ';
        }
      }
    }
    // This is a normal jPlayer skin, so we render normally.
    else {
      // Need to derermine quantity of valid items.
      $valid_item_count = 0;
      foreach ($items as $item) {
        if ($this->validateEntityAgainstPlayer($item)) {
          $valid_item_count++;
        }
      }

      // If there is only a single file, we render as a standard player.
      if ($valid_item_count == 1) {
        // Only require the default library.
        $library = 'audiofield/audiofield.' . $this->getPluginLibrary();

        // Load the first item.
        $item = $items->first();

        // If this entity has passed validation, we render it.
        if ($this->validateEntityAgainstPlayer($item)) {
          // Get render information for this item.
          $renderInfo = $this->getAudioRenderInfo($item);

          // Start building settings to pass to the javascript jplayer builder.
          $player_settings = [
            'playertype' => 'default',
            'file' => $renderInfo->url->toString(),
            'description' => $renderInfo->description,
            'unique_id' => $renderInfo->id,
            'filetype' => $renderInfo->filetype,
            // JPlayer expects this as a 0 - 1 value.
            'volume' => ($settings['audio_player_initial_volume'] / 10),
          ];

          // Generate the html for the player.
          $markup = '
            <div id="jquery_jplayer_' . $player_settings['unique_id'] . '" class="jp-jplayer"></div>
            <div id="jp_container_' . $player_settings['unique_id'] . '" class="jp-audio" role="application" aria-label="media player">
              <div class="jp-type-single">
                <div class="jp-gui jp-interface">
                  <div class="jp-controls">
                    <button class="jp-play" role="button" tabindex="0">play</button>
                    <button class="jp-stop" role="button" tabindex="0">stop</button>
                  </div>
                  <div class="jp-progress">
                    <div class="jp-seek-bar">
                      <div class="jp-play-bar"></div>
                    </div>
                  </div>
                  <div class="jp-volume-controls">
                    <button class="jp-mute" role="button" tabindex="0">mute</button>
                    <button class="jp-volume-max" role="button" tabindex="0">max volume</button>
                    <div class="jp-volume-bar">
                      <div class="jp-volume-bar-value"></div>
                    </div>
                  </div>
                  <div class="jp-time-holder">
                    <div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
                    <div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
                    <div class="jp-toggles">
                      <button class="jp-repeat" role="button" tabindex="0">repeat</button>
                    </div>
                  </div>
                </div>
                <div class="jp-details">
                  <div class="jp-title" aria-label="title">&nbsp;</div>
                </div>
                <div class="jp-no-solution">
                  <span>Update Required</span>
                  To play the media you will need to either update your browser to a recent version or update your <a href="http:// get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
                </div>
              </div>
            </div>
          ';
        }
      }
      // If we have multiple files, we need to render this as a playlist.
      else {
        // Requires the playlist library.
        $library = 'audiofield/audiofield.' . $this->getPluginLibrary() . '.playlist';

        // Start building settings to pass to the javascript jplayer builder.
        $player_settings = [
          'playertype' => 'playlist',
          // JPlayer expects this as a 0 - 1 value.
          'volume' => ($settings['audio_player_initial_volume'] / 10),
          'files' => [],
          'filetypes' => [],
        ];
        foreach ($items as $item) {
          // If this entity has passed validation, we render it.
          if ($this->validateEntityAgainstPlayer($item)) {
            // Get render information for this item.
            $renderInfo = $this->getAudioRenderInfo($item);

            // Add entry to player settings for this file.
            $player_settings['files'][] = [
              'file' => $renderInfo->url->toString(),
              'description' => $renderInfo->description,
              'filetype' => $renderInfo->filetype,
            ];
            $player_settings['filetypes'][] = $renderInfo->filetype;

            // Used to generate unique container.
            $player_settings['unique_id'] = $renderInfo->id;
          }
        }

        // Use only unique values in the filetypes.
        $player_settings['filetypes'] = array_unique($player_settings['filetypes']);

        // Generate markup.
        $markup = '
          <div id="jp_container_' . $player_settings['unique_id'] . '" class="jp-video jp-video-270p" role="application" aria-label="media player">
            <div class="jp-type-playlist">
              <div id="jquery_jplayer_' . $player_settings['unique_id'] . '" class="jp-jplayer"></div>
              <div class="jp-gui">
                <div class="jp-interface">
                  <div class="jp-progress">
                    <div class="jp-seek-bar">
                      <div class="jp-play-bar"></div>
                    </div>
                  </div>
                  <div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
                  <div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
                  <div class="jp-controls-holder">
                    <div class="jp-controls">
                      <button class="jp-previous" role="button" tabindex="0">previous</button>
                      <button class="jp-play" role="button" tabindex="0">play</button>
                      <button class="jp-next" role="button" tabindex="0">next</button>
                      <button class="jp-stop" role="button" tabindex="0">stop</button>
                    </div>
                    <div class="jp-volume-controls">
                      <button class="jp-mute" role="button" tabindex="0">mute</button>
                      <button class="jp-volume-max" role="button" tabindex="0">max volume</button>
                      <div class="jp-volume-bar">
                        <div class="jp-volume-bar-value"></div>
                      </div>
                    </div>
                    <div class="jp-toggles">
                      <button class="jp-repeat" role="button" tabindex="0">repeat</button>
                      <button class="jp-shuffle" role="button" tabindex="0">shuffle</button>
                      <button class="jp-full-screen" role="button" tabindex="0">full screen</button>
                    </div>
                  </div>
                  <div class="jp-details">
                    <div class="jp-title" aria-label="title">&nbsp;</div>
                  </div>
                </div>
              </div>
              <div class="jp-playlist">
                <ul>
                  <!-- The method Playlist.displayPlaylist() uses this unordered list -->
                  <li>&nbsp;</li>
                </ul>
              </div>
              <div class="jp-no-solution">
                <span>Update Required</span>
                To play the media you will need to either update your browser to a recent version or update your <a href="http:// get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
              </div>
            </div>
          </div>
        ';
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
          // Attach the jPlayer library.
          $library,
          // Attach the jPlayer theme.
          'audiofield/' . $settings['audio_player_jplayer_theme'],
        ],
        'drupalSettings' => [
          'audiofieldjplayer' => [
            $renderInfo->id => $player_settings,
          ],
        ],
      ],
    ];
  }

}

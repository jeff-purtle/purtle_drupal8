<?php

namespace Drupal\audiofield\Plugin\Field\FieldFormatter;

use Drupal\audiofield\AudioFieldPlayerManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of audio player file formatter.
 *
 * @FieldFormatter(
 *   id = "audiofield_audioplayer",
 *   label = @Translation("Audiofield Audio Player"),
 *   field_types = {
 *     "file", "link"
 *   }
 * )
 */
class AudioFieldFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  protected $audioPlayerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AudioFieldPlayerManager $audio_player_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->audioPlayerManager = $audio_player_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.audiofield')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get the fieldname in a format that works for all forms.
    $fieldname = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();

    // Loop over each plugin type and create an entry for it.
    $plugin_definitions = $this->audioPlayerManager->getDefinitions();
    $plugins = [
      'available' => [],
      'unavailable' => [],
    ];
    foreach ($plugin_definitions as $plugin_id => $plugin) {
      // Create an instance of the player.
      $player = $this->audioPlayerManager->createInstance($plugin_id);
      if ($player->checkInstalled()) {
        $plugins['available'][$plugin_id] = $plugin['title'];
      }
      else {
        $plugins['unavailable'][$plugin_id] = $plugin['title'];
      }
    }
    ksort($plugins['available']);

    // Build settings form for display on the structure page.
    $elements = parent::settingsForm($form, $form_state);
    $default_player = $this->getSetting('audio_player');
    if (isset($plugins['unavailable'][$default_player])) {
      $default_player = 'default_mp3_player';
    }
    // Let user select the audio player.
    $elements['audio_player'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Player'),
      '#default_value' => $default_player,
      '#options' => $plugins['available'],
    ];
    if (count($plugins['unavailable']) > 0) {
      ksort($plugins['unavailable']);
      $elements['unavailable'] = [
        '#type' => 'radios',
        '#title' => $this->t('Disabled Players (install per Audiofield README in order to use)'),
        '#default_value' => NULL,
        '#options' => $plugins['unavailable'],
        '#disabled' => TRUE,
      ];
    }
    // Settings for jPlayer.
    // Only show when jPlayer is the selected audio player.
    $jplayer_options = [
      'none' => 'None (for styling manually with CSS)',
      // Add the circle skin in (special non-standard custom skin for jPlayer).
      'audiofield.jplayer.theme_jplayer_circle' => 'jPlayer circle player',
    ];
    // Build the list of jPlayer available skins.
    foreach (_audiofield_list_skins('jplayer_audio_player') as $skin) {
      $jplayer_options[$skin['library_name']] = $skin['name'];
    }
    $elements['audio_player_jplayer_theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select jPlayer Skin'),
      '#description' => $this->t('jPlayer comes bundled with multiple skins by default. You can install additional skins by placing them in /libraries/jplayer/dist/skin/'),
      '#default_value' => $this->getSetting('audio_player_jplayer_theme'),
      '#options' => $jplayer_options,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'jplayer_audio_player'],
        ],
      ],
    ];
    // Settings for WaveSurfer.
    // Only show when WaveSurfer is the selected audio player.
    $elements['audio_player_wavesurfer_combine_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine audio files into a single audio player'),
      '#description' => $this->t('By default Wavesurfer displays files individually. This option combines the files into a playlist so only one file shows at a time.'),
      '#default_value' => $this->getSetting('audio_player_wavesurfer_combine_files'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player'],
        ],
      ],
    ];
    // Settings for WordPress.
    // Only show when WordPress is the selected audio player.
    $elements['audio_player_wordpress_combine_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine audio files into a single audio player'),
      '#description' => $this->t('This can be more difficult to see for the WordPress plugin. Multiple files are represented only by small "next" and "previous" arrows. Unchecking this box causes each file to be rendered as its own player.'),
      '#default_value' => $this->getSetting('audio_player_wordpress_combine_files'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wordpress_audio_player'],
        ],
      ],
    ];
    $elements['audio_player_wordpress_animation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Animate player?'),
      '#description' => $this->t('If unchecked, the player will always remain open with the title visible.'),
      '#default_value' => $this->getSetting('audio_player_wordpress_animation'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wordpress_audio_player'],
        ],
      ],
    ];
    // Settings for SoundManager.
    // Only show when SoundManager is the selected audio player.
    $elements['audio_player_soundmanager_theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select SoundManager Skin'),
      '#default_value' => $this->getSetting('audio_player_soundmanager_theme'),
      '#options' => [
        'default' => 'Default theme',
        'player360' => '360 degree player',
        'barui' => 'Bar UI',
        'inlineplayer' => 'Inline Player',
      ],
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'soundmanager_audio_player'],
        ],
      ],
    ];
    // Settings for multiple players.
    $elements['audio_player_initial_volume'] = [
      '#type' => 'range',
      '#title' => $this->t('Set Initial Volume'),
      '#default_value' => $this->getSetting('audio_player_initial_volume'),
      '#min' => 0,
      '#max' => 10,
      '#states' => [
        'visible' => [
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'jplayer_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'mediaelement_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'projekktor_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'soundmanager_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wavesurfer_audio_player']],
          [':input[name="fields[' . $fieldname . '][settings_edit_form][settings][audio_player]"]' => ['value' => 'wordpress_audio_player']],
        ],
      ],
    ];
    // Setting for optional download link.
    $elements['download_link'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display download link below player'),
      '#default_value' => $this->getSetting('download_link'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $plugin_definitions = $this->audioPlayerManager->getDefinitions();

    $settings = $this->getSettings();

    // Show which player we are currently using for the field.
    $summary = array(
      Markup::create('Selected player: <strong>' . $plugin_definitions[$settings['audio_player']]['title'] . '</strong>'),
    );
    // If this is jPlayer, add those settings.
    if ($settings['audio_player'] == 'jplayer_audio_player') {
      // Display theme.
      $theme = 'None (for styling manually with CSS)';
      // If this is the custom jplayer circle theme.
      if ($settings['audio_player_jplayer_theme'] == 'audiofield.jplayer.theme_jplayer_circle') {
        $theme = 'jPlayer circle player';
      }
      // Search for the theme we're using.
      else {
        foreach (_audiofield_list_skins('jplayer_audio_player') as $skin) {
          if ($skin['library_name'] == $settings['audio_player_jplayer_theme']) {
            $theme = $skin['name'];
          }
        }
      }
      $summary[] = Markup::create('Skin: <strong>' . $theme . '</strong>');
    }
    // If this is wavesurfer, add those settings.
    elseif ($settings['audio_player'] == 'wavesurfer_audio_player') {
      $summary[] = Markup::create('Combine files into single player? <strong>' . ($settings['audio_player_wavesurfer_combine_files'] ? 'Yes' : 'No') . '</strong>');
    }
    // If this is wordpress, add those settings.
    elseif ($settings['audio_player'] == 'wordpress_audio_player') {
      $summary[] = Markup::create('Combine files into single player? <strong>' . ($settings['audio_player_wordpress_combine_files'] ? 'Yes' : 'No') . '</strong>');
      $summary[] = Markup::create('Animate player? <strong>' . ($settings['audio_player_wordpress_animation'] ? 'Yes' : 'No') . '</strong>');
    }
    // If this is soundmanager, add those settings.
    elseif ($settings['audio_player'] == 'soundmanager_audio_player') {
      $skins = [
        'default' => 'Default theme',
        'player360' => '360 degree player',
        'barui' => 'Bar UI',
        'inlineplayer' => 'Inline Player',
      ];
      $summary[] = Markup::create('Skin: <strong>' . $skins[$settings['audio_player_soundmanager_theme']] . '</strong>');
    }
    // Show combined settings for multiple players.
    if (in_array($settings['audio_player'], [
      'jplayer_audio_player',
      'mediaelement_audio_player',
      'projekktor_audio_player',
      'soundmanager_audio_player',
      'wavesurfer_audio_player',
      'wordpress_audio_player',
    ])) {
      // Display volume.
      $summary[] = Markup::create('Initial volume: <strong>' . $settings['audio_player_initial_volume'] . ' out of 10</strong>');
    }
    // Check to make sure the library is installed.
    $player = $this->audioPlayerManager->createInstance($settings['audio_player']);
    if (!$player->checkInstalled()) {
      $summary[] = Markup::create('<strong style="color:red;">' . t('Error: this player library is currently not installed. Please select another player or reinstall the library.') . '</strong>');
    }

    // Show whether or not we are displaying direct downloads.
    $summary[] = Markup::create('Display download link: <strong>' . ($settings['download_link'] ? 'Yes' : 'No') . '</strong>');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'audio_player' => 'default_mp3_player',
      'audio_player_jplayer_theme' => 'none',
      'audio_player_wavesurfer_combine_files' => FALSE,
      'audio_player_wordpress_combine_files' => FALSE,
      'audio_player_wordpress_animation' => TRUE,
      'audio_player_soundmanager_theme' => 'default',
      'audio_player_initial_volume' => 8,
      'download_link' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Early opt-out if the field is empty.
    if (count($items) <= 0) {
      return $elements;
    }

    $plugin_id = $this->getSetting('audio_player');
    $player = $this->audioPlayerManager->createInstance($plugin_id);

    $elements[] = $player->renderPlayer($items, $langcode, $this->getSettings());
    return $elements;
  }

}

<?php

namespace Drupal\heytaco\Plugin\Block;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Entity;

// Pad taco stats by the following amount.
const PADDING_NUMBER = 100;
// HeyTaco API call URL. Replace XXXXXXXX with your HeyTaco Team Code
const HEYTACO_URL = 'https://www.heytaco.chat/api/v1/json/leaderboard/XXXXXXXXX';

/**
 * Provides a Hey Taco Results Block
 *
 * @Block(
 *   id = "heytaco_block",
 *   admin_label = @Translation("HeyTaco! Leaderboard"),
 * )
 */
class HeyTacoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var $account \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountProxy $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account) {
    $this->account = $account;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Dependency injection used create() and __construct() pattern.
    $user_id = $this->account->id();
    return array(
      '#theme' => 'heytaco_block',
      '#results' => $this->returnLeaderboard($user_id),
      '#partner_asterisk_blurb' => $this->isNotPartnerBlurb($user_id),
      '#cache' => [
        'keys' => ['heytaco_block'],
        'contexts' => ['user'],
        'tags' => ['user_list'],
        'max-age' => 3600,
      ],
    );
  }

  /**
   * Return asterisk blurb if this is not a partner.
   */
  private function isNotPartnerBlurb($user_id) {
    // Don't tell partners we are padding their stats!
    if (in_array($user_id, array(2, 3, 4))) {
      return '';
    }
    return t('<small>* Partners\' results padded by @num tacos</small>', array('@num' => PADDING_NUMBER));
  }

  /**
   * Return Hey Taco leaderboard.
   */
  private function returnLeaderboard($user_id) {
    // HeyTaco API sends back Slack user names so they need to be matched.
    // Artificially matching up HeyTaco and Drupal user names, Drupal uids.
    $peeps = array(
      'partnerone' => array('name' => 'partnerone name', 'uid' => 2),
      'partnertwo' => array('name' => 'partnertwo name', 'uid' => 3),
      'partnerthree' => array('name' => 'partnerthree name', 'uid' => 4),
      'empone' => array('name' => 'empone name', 'uid' => 5),
      'emptwo' => array('name' => 'emptwo name', 'uid' => 6),
      'empthree' => array('name' => 'empthree name', 'uid' => 7),
      'empfour' => array('name' => 'empfour name', 'uid' => 8),
      'empfive' => array('name' => 'empfive name', 'uid' => 9),
      'empsix' => array('name' => 'empsix name', 'uid' => 10),
      'empseven' => array('name' => 'empseven name', 'uid' => 11),
      'empeight' => array('name' => 'empeight name', 'uid' => 12),
    );

    $results = array();
    // Using Guzzle.
    $response = \Drupal::httpClient()->get(HEYTACO_URL);
    // Get JSON from HeyTaco. Returned usernames are from Slack.
    $json_string = (string) $response->getBody();
    $leaderboard = array_shift(json_decode($json_string, TRUE));
    // Populate array with partners' user names.
    $partners = array('partnerone', 'partnertwo', 'partnerthree');
    foreach ($leaderboard as $key => $info) {
      // Most are non-partners and do not require an asterisk, so start empty.
      if (isset($info['username']) && isset($info['count'])) {
        $results[$info['username']]['score'] = $info['count'];
        $results[$info['username']]['name'] = $peeps[$info['username']]['name'];
        $results[$info['username']]['is_partner'] = FALSE;
        $results[$info['username']]['asterisk'] = '';
        // Suck up to partners by adding free tacos.
        if (in_array($info['username'], $partners)) {
          $results[$info['username']]['score'] = $info['count'] + PADDING_NUMBER;
          $results[$info['username']]['is_partner'] = TRUE;
          // Asterisks for non-partners only. Partner uids = 2, 3, 4.
          if(!in_array($user_id, array(2, 3, 4))) {
            $results[$info['username']]['asterisk'] = '*';
          }
        }

        // Figure out user picture.
        if ($user = \Drupal\user\Entity\User::load($peeps[$info['username']]['uid'])) {
          if (!$user->user_picture->isEmpty()) {
            $picture = $user->user_picture->view('thumbnail');
          }
          else {
            $picture = 'Pic unavailable';
          }
          $results[$info['username']]['name'] = $user->name->value;
        }
        $results[$info['username']]['user_pic'] = $picture;
      }
    }
    array_multisort($results, SORT_DESC);
    return $results;
  }
}

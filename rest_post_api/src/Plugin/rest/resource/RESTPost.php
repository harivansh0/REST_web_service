<?php

/**
 * @file
 * Contains Drupal/rest_post_api/Plugin/rest/resource/RESTPost.
 */

namespace Drupal\rest_post_api\Plugin\rest\resource;

use Drupal\Core\Render\Element\Value;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Plugin\views\argument\Vid;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use Drupal\Core\File;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\FileInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "rest_post_api",
 *   label = @Translation("REST post API"),
 *   uri_paths = {
 *     "create" = "/api/post"
 *   }
 * )
 */
class RESTPost extends ResourceBase {

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param array $data
   * @return \Drupal\rest\ResourceResponse Throws Exception expected.
   * Throws Exception expected.
   */
  public function post($data) {
    // Use current user after pass authentication to validate access.
    if (!(\Drupal::currentUser())->hasPermission('administer site content')) {
      throw new AccessDeniedHttpException('access denied');
    }

    foreach ($data as $key => $value) {

      // print_r($value);
      // die();
      // $vocabularies = Vocabulary::loadMultiple();
      // print_r($vocabularies);
      // die();
      // if (!isset($vocabularies['vid'])) {
      if ($terms = taxonomy_term_load_multiple_by_name($value['termName'], $value['vid'])){
        $term = reset($terms);
      }
      else {
        $term = Term::create([
          'name' => $value['termName'],
          'vid' => $value['vid'],
        ]);
        $term->save();
      }
      // }
      // else {
      // vocabulary already exit
      // $query = \Drupal::entityQuery('taxonomy_term');
      // $query->condition('vid', $value['vid']);
      // $tids = $query->execute();
      // }
      // print_r($vocabularies);
      // die();
      // $term = Term::create([
      // 'vid' => $value['vid'],
      // 'name' => $value['catogery'],
      // ]);
      // $term->save();
      if ($users = user_load_by_name($value['userName'])) {
        $usr_id = $users->id();
        // print_r($usr_id);
        // die();
        $user = reset($users);
        // $usr_id = $user->id();
      }
      else {
        $user = User::create([
          'name' => $value['userName'],
          'mail' => $value['userEmail'],
          'pass' => $value['passWord'],
          'status' => $value['userStatus'],
          "roles" => [$value['userRoles']],
        ]);
        $user->save();
        $usr_id = $user->id();

      }
      // $uids = \Drupal::entityQuery('user')
      // ->condition('name', $value['userName'])
      // ->execute();
      // if (count($uids) > 0) {
      // $user = User::create([
      // 'name' => $value['userName'],
      // 'mail' => $value['userEmail'],
      // 'pass' => $value['passWord'],
      //     'status' => $value['userStatus'],
      //   ]
      //   );
      //   $user->save();
      // }
      // else {
      // $user = reset($user);
      // }

      // provides api to upload Imge media
      $file_image = $value["imagePath"];
      $file_image_content = file_get_contents($file_image);
      $file_image_name = basename($file_image);
      // print_r($file_image);
      // print_r($file_image_content);
      // die();
      $imageDirectory = 'public://Images';
      // printf($imageDirectory);
      // die();
      // Check that the directory exists and is writable.
      \Drupal::service('file_system')->prepareDirectory($imageDirectory, FileSystemInterface::CREATE_DIRECTORY);
      // print_r($test);
      // die();
      // basename() gets the filename from a given path.
      $files_image = file_save_data($file_image_content, $imageDirectory . $file_image_name, FileSystemInterface::EXISTS_REPLACE);
      // print_r($files_image);
      // die();  
      $image_media = Media:: create(([
        'bundle' => 'image',
        'uid' => $usr_id,
        'field_media_image' => [
          'target_id' => $files_image->id(),
        ]
        ]));
      $image_media->save();
      // Provides api to upload video media.
      $file_video = $value["videoPath"];
      $file_video_content = file_get_contents($file_video);
      $file_video_name = basename($file_video);
      $videoDirectory = 'public://Images/';
      // $test = \Drupal::service('file_system')->prepareDirectory($videoDirectory, FileSystemInterface::CREATE_DIRECTORY);
      $files_video = file_save_data($file_video_content, $videoDirectory . $file_video_name, FileSystemInterface::EXISTS_REPLACE);
      // Creating video media.
      $video_media = Media:: create(([
        'bundle' => 'video',
        'uid' => $usr_id,
        'field_media_video_file' => [
          'target_id' => $files_video->id(),
        ],
      ])
      );
      $video_media->save();

      $node = Node::create(
        [
          'type' => $value['nodetype'],
          'title' => $value['title'],
          'body' => [
            'summary' => '',
            'value' => $value['body'],
            'format' => 'full_html',
          ],
          'uid' => $usr_id,
            // 'target_id' => $value['targetid'],
            // 'target_type' => $value['user'],
            // 'name' => $value["name"],
          // ],
          'status' => $value['published'],
          // 'field_media' => [
          //   'field_media_image' => [
          //     "href" => $value['imageurl'],
          //   ],
          // ],
          'field_media' => [
            [
              'target_id' => $image_media->id(),
            ],
            // [
            //   'target_id' => $files_image->id(),
            // ],
            [
              'target_id' => $video_media->id(),
             ],
          ],
        ]
        );
      $node->enforceIsNew();
      $node->save();
      // $this->logger->notice($this->t('Node with nid saved!\n', ['nid' => $node->id()]));
      // $node[] = $node->id();
    }
    $response = new ResourceResponse($node);
    // $response->addCacheableDependency($node);
    return $response;
  }

}
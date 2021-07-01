<?php

namespace Drupal\restapi\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Provide a rest api test
 *
 * @RestResource(
 *   id = "rest api",
 *   label = @Translation("TestApi"),
 *   uri_paths = {
 *     "canonical" = "/restapi/node/{nids}"
 *   }
 * )
 */
class TestApi extends ResourceBase {
  /**
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response Object
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception excpected.
   */

  public function get($nids = NULL) {
    // Use current user after pass authentication to vaidate resources.
    if (!(\Drupal::currentUser())->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    if ($nids) {
      //nids to store the node id.      
      //$nids = \Drupal::entityQuery('node')->condition('nid', $type)->execute();
      // print_r($nids);
      // die();
      //$entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
      //if ($nids) {
      $nodes = \Drupal\node\Entity\Node::load($nids);
        // print_r($nodes);
        // die();
        //$nodes = \Drupal::entityTypeManager()->getStorage('node')->load($nids);
        // $url = metatag_generate_entity_metatags($nodes);
        // $node_path = $url['canonical_url']['#attributes']['href'];
        // print_r($node_path);
        // die()
      $media_field = $nodes->get('field_media')->getValue(); // Get media ID from  field. 
      //print_r($media_field); 
      // $media_entity_load = Media::loadMultiple($media_field); // Loading media entity.
      //print_r($media_entity_load);
      //die();
      foreach ($media_field as $key => $media_id) {
        //print_r($media_id);
        //print($media_id['target_id']);
        $media_entity_load = Media::load($media_id['target_id']); // Loading media entity
        //print_r($media_entity_load);
        if ($media_entity_load->hasField('field_media_image')) {
          $fid_image = $media_entity_load->field_media_image->target_id;
          $file_image = File::load($fid_image);
          $uri_image = $file_image->getFileUri();
          // print_r($uri_image);
          // die();
          $image_url = file_create_url($uri_image);
          // print_r($image_url);
        }
        if ($media_entity_load->hasField('field_media_video_file')) {
          $fid_video = $media_entity_load->field_media_video_file->target_id;
          $file_video = File::load($fid_video);
          $uri_video = $file_video->getFileUri();
          $video_url = file_create_url($uri_video);
          //print_r($video_url);
        }
        if ($media_entity_load->hasField('field_media_document')) {
          $fid_doc = $media_entity_load->field_media_document->target_id;
          $file_doc = File::load($fid_doc);
          $uri_doc = $file_doc->getFileUri();
          $doc_url = file_create_url($uri_doc);
         // print_r($doc_url);
        }
       // $fid_image = $media_entity_load->field_media_image->target_id;
        //print_r($fid_image);
       // $fid_video = $media_entity_load->field_media_video_file->target_id->value;
        //print($fid_video);
        //$file_image = File::load($fid_image);
        //$file_video = File::load($fid_video);
        //print_r($file_video);
        //$uri_image = $file_image->getFileUri();
        //print_r($uri_image);
       // $uri_video = $file_video->getFileUri();
       // print_r($uri_video);
        //die();
        //print_r($url);
        //$image_url = file_create_url($uri_image); 
        //$video_url = file_create_url($uri_video);
        //print_r($image_url);
        //print_r($uri_video);
      }
      $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$nodes->id());
      // print_r($alias);
      // die();
      $data[] = [
        'id' => $nodes->id(),
        'url' => $alias,
        'image URL' => $image_url,
        'video URL' => $video_url,
        'document URL' => $doc_url,
      ];
    }

    $response = new ResourceResponse($data);
    $response->addCacheableDependency($data);
    return $response;
  }

}

<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Search;

/**
 * Class AfformSearchMetadataInjector
 * @package Civi\Search
 */
class AfformSearchMetadataInjector {

  /**
   * Injects settings data into search displays embedded in afforms
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterAngular()
   */
  public static function preprocess($e) {
    $changeSet = \Civi\Angular\ChangeSet::create('searchSettings')
      ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
        $displayTags = array_column(\Civi\Search\Display::getDisplayTypes(['name']), 'name');

        if ($displayTags) {
          foreach (pq(implode(',', $displayTags), $doc) as $component) {
            $searchName = pq($component)->attr('search-name');
            $displayName = pq($component)->attr('display-name');
            if ($searchName && $displayName) {
              $display = \Civi\Api4\SearchDisplay::get(FALSE)
                ->addWhere('name', '=', $displayName)
                ->addWhere('saved_search.name', '=', $searchName)
                ->addSelect('settings', 'saved_search.api_entity', 'saved_search.api_params')
                ->execute()->first();
              if ($display) {
                pq($component)->attr('settings', htmlspecialchars(\CRM_Utils_JS::encode($display['settings'] ?? [])));
                pq($component)->attr('api-entity', htmlspecialchars(\CRM_Utils_JS::encode($display['saved_search.api_entity'])));
                pq($component)->attr('api-params', htmlspecialchars(\CRM_Utils_JS::encode($display['saved_search.api_params'])));

                // Add entity names to the fieldset so that afform can populate field metadata
                $fieldset = pq($component)->parents('[af-fieldset]');
                if ($fieldset->length) {
                  $entityList = array_merge([$display['saved_search.api_entity']], array_column($display['saved_search.api_params']['join'] ?? [], 0));
                  $fieldset->attr('api-entities', htmlspecialchars(\CRM_Utils_JS::encode($entityList)));
                }
              }
            }
          }
        }
      });
    $e->angular->add($changeSet);

  }

}

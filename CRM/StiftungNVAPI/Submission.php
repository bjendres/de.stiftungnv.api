<?php
/*------------------------------------------------------------+
| Stiftung Neue Verantwortung API extension                   |
| Copyright (C) 2017 SYSTOPIA                                 |
| Author: B. Endres (endres@systopia.de)                      |
|         J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

class CRM_StiftungNVAPI_Submission {

  const CUSTOM_FIELD_ID_SUBJECTS = 2;

  public static function getContactData($params) {
    // Get the ID of the contact matching the given contact data, or create a
    // new contact if none exists for the given contact data.
    $contact_data = array_intersect_key($params, array(
      'prefix_id' => TRUE,
      'formal_title' => TRUE,
      'first_name' => TRUE,
      'last_name' => TRUE,
      'phone' => TRUE,
      'email' => TRUE,
    ));
    // Check for existance of prefix_id
    $prefixes = civicrm_api3(
      'Contact',
      'getoptions',
      ['field' => 'prefix_id']
    )['values'];
    if (!array_key_exists($params['prefix_id'], $prefixes)) {
      unset($contact_data['prefix_id']);
    }

    if (!empty($params['institution'])) {
      // Get the custom field ID for "Institution".
      $institution_field = civicrm_api3('CustomField', 'getsingle', array(
        'sequential' => 1,
        'name' => "Institution",
      ));
      if (!empty($institution_field['is_error'])) {
        throw new CiviCRM_API3_Exception('Custom field "Institution" could not be found.', 'api_error');
      }
      $contact_data['custom_' . $institution_field['id']] = $params['institution'];
    }

    return $contact_data;
  }

  /**
   * Retrieves the contact matching the given contact data or creates a new
   * contact.
   *
   * @param string $contact_type
   *   The contact type to look for/to create.
   * @param array $contact_data
   *   Data to use for contact lookup/to create a contact with.
   *
   * @return array
   *   The ID of the matching/created contact, or NULL if no matching contact
   *   was found and no new contact could be created, and a flag for whether the
   *   contact has been created.
   * @throws \CiviCRM_API3_Exception
   *   When invalid data was given.
   */
  public static function getContact($contact_type, $contact_data) {
    $contact = [
      'contact_id' => NULL,
      'was_created' => NULL,
    ];
    // If no parameters are given, do nothing.
    if (!empty($contact_data)) {
      // Prepare values: country.
      if (!empty($contact_data['country'])) {
        if (is_numeric($contact_data['country'])) {
          // If a country ID is given, update the parameters.
          $contact_data['country_id'] = $contact_data['country'];
          unset($contact_data['country']);
        }
        else {
          // Look up the country depending on the given ISO code.
          $country = civicrm_api3('Country', 'get', array('iso_code' => $contact_data['country']));
          if (!empty($country['id'])) {
            $contact_data['country_id'] = $country['id'];
            unset($contact_data['country']);
          }
          else {
            throw new CiviCRM_API3_Exception("Unknown country '{$contact_data['country']}'", 'invalid_format');
          }
        }
      }

      // Pass to XCM.
      $contact_data['contact_type'] = $contact_type;
      $contact = civicrm_api3('Contact', 'createifnotexists', $contact_data);
    }

    return [$contact['id'], $contact['was_created']];
  }
  
  public static function subscribeNewsletter($contact_id, $group_ids) {
    $group_contacts = array();
    if (!is_array($group_ids)) {
      $group_ids = [$group_ids];
    }
    // Get all mailing lists.
    $mailing_lists = civicrm_api3(
      'Group',
      'get',
      [
        'is_active' => 1,
        'group_type' => [
          'LIKE' => '%2%',
        ],
        'option.limit' => 0,
      ]
    );
    $mailing_lists = array_keys($mailing_lists['values']);

    // Retrieve all groups the contact is currently member of.
    $current_groups_result = civicrm_api3(
      'Contact',
      'getsingle',
      [
        'sequential' => 1,
        'return' => 'group',
        'option.limit' => 0,
        'contact_id' => $contact_id,
      ]
    );
    $current_groups_unfiltered = explode(
      ',',
      $current_groups_result['groups']
    );
    // Filter for mailing lists only.
    $current_groups = array_intersect(
      $current_groups_unfiltered,
      $mailing_lists
    );
    $group_ids = array_intersect(
      $group_ids,
      $mailing_lists
    );
    // Decide which mailing list the contact is to be removed from.
    $remove = array_diff($current_groups, $group_ids);

    // Add the contact to all requested groups.
    foreach ($group_ids as $group_id) {
      $group_contacts[$group_id] = civicrm_api3(
        'GroupContact',
        'create',
        [
          'group_id' => $group_id,
          'contact_id' => $contact_id,
          'status' => 'Added',
        ]
      );
    }
    // Remove the contact from all other mailing lists they have been member
    // of before.
    foreach ($remove as $group_id) {
      $group_contacts[$group_id] = civicrm_api3(
        'GroupContact',
        'create',
        [
          'group_id' => $group_id,
          'contact_id' => $contact_id,
          'status' => 'Removed',
        ]
      );
    }

    // Set custom field.
    //      $current_subjects = civicrm_api3('Contact', 'getsingle', array(
    //        'return' => 'custom_' . CRM_StiftungNVAPI_Submission::CUSTOM_FIELD_ID_SUBJECTS,
    //        'id' => $contact_id,
    //      ));
    //      if (!empty($current_subjects['custom_' . CRM_StiftungNVAPI_Submission::CUSTOM_FIELD_ID_SUBJECTS])) {
    //        $current_subjects = array_values($current_subjects['custom_' . CRM_StiftungNVAPI_Submission::CUSTOM_FIELD_ID_SUBJECTS]);
    //      }
    //      else {
    //        $current_subjects = array();
    //      }
    //      $subjects = array_unique(array_merge($current_subjects, $params['group_ids']));
    // Do not merge, but overwrite the current selection, therefore the above
    // is commented.
    $subjects = $group_ids;
    civicrm_api3('Contact', 'create', array(
      'id' => $contact_id,
      'custom_' . CRM_StiftungNVAPI_Submission::CUSTOM_FIELD_ID_SUBJECTS => $subjects,
    ));

    return $group_contacts;
  }

  public static function addLanguageTag($contact_id) {
    try {
      $tag_exists = civicrm_api3('EntityTag', 'getsingle', array(
        'entity_table' => 'civicrm_contact',
        'entity_id' => $contact_id,
        'tag_id' => 19,
      ));
    }
    catch (Exception $exception) {
      civicrm_api3('EntityTag', 'create', array(
        'entity_table' => 'civicrm_contact',
        'entity_id' => $contact_id,
        'tag_id' => 19,
      ));
    }
  }

}

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

/**
 * Submit a newsletter subscription.
 *
 * @param array $params
 *   Associative array of property name/value pairs.
 *
 * @return array api result array
 *
 * @access public
 */
function civicrm_api3_stiftung_n_v_newsletter_subscription_submit($params) {
  // Log the API call to the CiviCRM debug log.
  if (defined('STIFTUNGNV_API_LOGGING') && STIFTUNGNV_API_LOGGING) {
    CRM_Core_Error::debug_log_message('StiftungNVNewsletterSubscription.submit: ' . json_encode($params));
  }

  try {
    // Get the ID of the contact matching the given contact data, or create a
    // new contact if none exists for the given contact data.
    $contact_data = array_intersect_key($params, array(
      'prefix_id' => TRUE,
      'formal_title' => TRUE,
      'first_name' => TRUE,
      'last_name' => TRUE,
      'phone' => TRUE,
      'email' => TRUE,
      'source' => 'Newsletteranmeldung',
    ));

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

    if (!$contact_id = CRM_StiftungNVAPI_Submission::getContact('Individual', $contact_data)) {
      throw new CiviCRM_API3_Exception('Individual contact could not be found or created.', 'invalid_format');
    }

    // If given the flag, update contact data with the submitted values.
    if (isset($params['update_contact']) && $params['update_contact'] == 1) {
      $contact_data['id'] = $contact_id;
      civicrm_api3('Contact', 'create', $contact_data);
    }

    // Add the contact to the given groups and set the respective custom field.
    $group_contacts = array();
    if (!empty($params['group_ids'])) {
      if (!is_array($params['group_ids'])) {
        $params['group_ids'] = array($params['group_ids']);
      }
      // Get all mailing lists.
      $mailing_lists = civicrm_api3('Group', 'get', array(
        'is_active' => 1,
        'group_type' => array(
          'LIKE' => '%2%',
        ),
        'option.limit' => 0,
      ));
      $mailing_lists = array_keys($mailing_lists['values']);

      // Retrieve all groups the contact is currently member of.
      $current_groups_result = civicrm_api3('Contact', 'getsingle', array(
        'sequential' => 1,
        'return' => 'group',
        'option.limit' => 0,
        'contact_id' => $contact_id,
      ));
      $current_groups_unfiltered = explode(',', $current_groups_result['groups']);
      // Filter for mailing lists only.
      $current_groups = array_intersect($current_groups_unfiltered, $mailing_lists);
      // Decide which mailing list the contact is to be removed from.
      $remove = array_diff($current_groups, $params['group_ids']);

      // Add the contact to all requested groups.
      foreach ($params['group_ids'] as $group_id) {
        $group_contacts[$group_id] = civicrm_api3('GroupContact', 'create', array(
          'group_id' => $group_id,
          'contact_id' => $contact_id,
          'status' => 'Added',
        ));
      }
      // Remove the contact from all other mailing lists they have been member
      // of before.
      foreach ($remove as $group_id) {
        $group_contacts[$group_id] = civicrm_api3('GroupContact', 'create', array(
          'group_id' => $group_id,
          'contact_id' => $contact_id,
          'status' => 'Removed',
        ));
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
      $subjects = $params['group_ids'];
      civicrm_api3('Contact', 'create', array(
        'id' => $contact_id,
        'custom_' . CRM_StiftungNVAPI_Submission::CUSTOM_FIELD_ID_SUBJECTS => $subjects,
      ));
    }

    return civicrm_api3_create_success($group_contacts, $params, NULL, NULL, $dao = NULL, array());

  }
  catch (CiviCRM_API3_Exception $exception) {
    if (defined('STIFTUNGNV_API_LOGGING') && STIFTUNGNV_API_LOGGING) {
      CRM_Core_Error::debug_log_message('StiftungNVNewsletterSubscription:submit:Exception caught: ' . $exception->getMessage());
    }

    $extraParams = $exception->getExtraParams();

    return civicrm_api3_create_error($exception->getMessage(), $extraParams);
  }
}

/**
 * Parameter specification for the "Submit" action on
 * "StiftungNVNewsletterSubscription" entities.
 *
 * @param $params
 */
function _civicrm_api3_stiftung_n_v_newsletter_subscription_submit_spec(&$params) {
  $params['prefix_id'] = array(
    'name'         => 'prefix_id',
    'title'        => 'Prefix',
    'type'         => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description'  => 'The contact\'s prefix ID.',
  );
  $params['formal_title'] = array(
    'name'         => 'formal_title',
    'title'        => 'Formal title',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s formal title.',
  );
  $params['first_name'] = array(
    'name'         => 'first_name',
    'title'        => 'First Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s first name.',
  );
  $params['last_name'] = array(
    'name'         => 'last_name',
    'title'        => 'Last Name',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s last name.',
  );
  $params['phone'] = array(
    'name'         => 'phone',
    'title'        => 'Phone',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s phone number.',
  );
  $params['email'] = array(
    'name'         => 'email',
    'title'        => 'Email',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The contact\'s email.',
  );
  $params['institution'] = array(
    'name'         => 'institution',
    'title'        => 'Institution',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description'  => 'The contact\'s institution or organization',
  );
  $params['group_ids'] = array(
    'name'         => 'group_ids',
    'title'        => 'Group IDs',
    'type'         => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description'  => 'The IDs of the mailing list groups to subscribe the contact to.',
  );
  $params['update_contact'] = array(
    'name' => 'update_contact',
    'title' => 'Update contact',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'description' => 'Whether the provided contact data should overwrite possibly existing data on the contact.',
  );
}

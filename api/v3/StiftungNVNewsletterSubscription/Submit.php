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
    CRM_Core_Error::debug_log_message(
      'StiftungNVNewsletterSubscription.submit: '
      . json_encode($params, JSON_PRETTY_PRINT)
    );
  }

  try {
    // Get the ID of the contact matching the given contact data, or create a
    // new contact if none exists for the given contact data.
    $contact_data = CRM_StiftungNVAPI_Submission::getContactData($params);
    $contact_data += array(
      'source' => 'Newsletteranmeldung',
    );
    [$contact_id, $was_created] = CRM_StiftungNVAPI_Submission::getContact(
      'Individual',
      $contact_data
    );
    if (!$contact_id) {
      throw new CiviCRM_API3_Exception(
        'Individual contact could not be found or created.',
        'invalid_format'
      );
    }
    // If given the flag, update contact data with the submitted values.
    if (!empty($params['update_contact'])) {
      $contact_data['id'] = $contact_id;
      civicrm_api3('Contact', 'create', $contact_data);
    }

    // Add tag 19 ("english") for english contacts.
    if ($params['language'] == 'en') {
      CRM_StiftungNVAPI_Submission::addLanguageTag($contact_id);
    }

    // Add the contact to the given groups and set the respective custom field.
    $group_contacts = array();
    if (!empty($params['group_ids'])) {
      $group_contacts = CRM_StiftungNVAPI_Submission::subscribeNewsletter(
        $contact_id,
        $params['group_ids']
      );
    }

    return civicrm_api3_create_success($group_contacts, $params);

  }
  catch (CiviCRM_API3_Exception $exception) {
    if (defined('STIFTUNGNV_API_LOGGING') && STIFTUNGNV_API_LOGGING) {
      CRM_Core_Error::debug_log_message(
        'StiftungNVNewsletterSubscription:submit:Exception caught: '
        . $exception->getMessage()
      );
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
  $params['language'] = array(
    'name' => 'language',
    'title' => 'Language',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The language used for the submitting form.',
  );
}

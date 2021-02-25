<?php
/*------------------------------------------------------------+
| Stiftung Neue Verantwortung API extension                   |
| Copyright (C) 2020 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

use CRM_StiftungNVAPI_ExtensionUtil as E;

/**
 * StiftungNVEventRegistration.Submit API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_stiftung_n_v_event_registration_Submit_spec(&$spec) {
  $spec['prefix_id'] = [
    'name' => 'prefix_id',
    'title' => 'Prefix',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => 'The contact\'s prefix ID.',
  ];
  $spec['formal_title'] = [
    'name' => 'formal_title',
    'title' => 'Formal title',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The contact\'s formal title.',
  ];
  $spec['first_name'] = [
    'name' => 'first_name',
    'title' => 'First Name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => 'The contact\'s first name.',
  ];
  $spec['last_name'] = [
    'name' => 'last_name',
    'title' => 'Last Name',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => 'The contact\'s last name.',
  ];
  $spec['phone'] = [
    'name' => 'phone',
    'title' => 'Phone',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The contact\'s phone number.',
  ];
  $spec['email'] = [
    'name' => 'email',
    'title' => 'Email',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => 'The contact\'s email.',
  ];
  $spec['institution'] = [
    'name' => 'institution',
    'title' => 'Institution',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The contact\'s institution or organization',
  ];
  $spec['newsletter'] = array(
    'name' => 'newsletter',
    'title' => 'Newsletter',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'api.default' => 0,
    'description' => 'Whether the contact wants to subscribe to newsletter(s).',
  );
  $spec['group_ids'] = [
    'name' => 'group_ids',
    'title' => 'Group IDs',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The IDs of the mailing list groups to subscribe the contact to.',
  ];
  $spec['update_contact'] = array(
    'name' => 'update_contact',
    'title' => 'Update contact',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'description' => 'Whether the provided contact data should overwrite possibly existing data on the contact.',
  );
  $spec['language'] = array(
    'name' => 'language',
    'title' => 'Language',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The language used for the submitting form.',
  );
  $spec['event_id'] = array(
    'name' => 'event_id',
    'title' => 'Event ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'description' => 'The (external) ID of the event to register for.',
  );
  $spec['event_title'] = array(
    'name' => 'event_title',
    'title' => 'Event Title',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'description' => 'The title of the event to register for.',
  );
  $spec['event_time'] = array(
    'name' => 'event_time',
    'title' => 'Event Time',
    'type' => CRM_Utils_Type::T_DATE,
    'api.required' => 0,
    'description' => 'The date/time of the event to register for.',
  );
  $spec['event_location'] = array(
    'name' => 'event_location',
    'title' => 'Event Location',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'description' => 'The location of the event to register for.',
  );
}

/**
 * StiftungNVEventRegistration.Submit API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_stiftung_n_v_event_registration_Submit($params) {
  // Log the API call to the CiviCRM debug log.
  if (defined('STIFTUNGNV_API_LOGGING') && STIFTUNGNV_API_LOGGING) {
    CRM_Core_Error::debug_log_message(
      'StiftungNVEventRegistration.submit: '
      . json_encode($params, JSON_PRETTY_PRINT)
    );
  }

  // TODO: Validate parameters.

  // Identify or create contact.
  // Get the ID of the contact matching the given contact data, or create a
  // new contact if none exists for the given contact data.
  $contact_data = CRM_StiftungNVAPI_Submission::getContactData($params);
  $contact_data += array(
    'source' => 'Veranstaltungsanmeldung',
  );
  [$contact_id, $contact_was_created] = CRM_StiftungNVAPI_Submission::getContact(
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

  // Create activity of type "Event Registration"
  $registration_details = [
    E::ts('Event: %1', [1 => $params['event_title']]),
    E::ts('External Event ID: %1', [1 => $params['event_id']])
  ];
  if (!empty($params['event_location'])) {
    $registration_details[] = E::ts('Event location: %1', [1 => $params['event_location']]);
  }
  if (!empty($params['event_time'])) {
    $registration_details[] = E::ts('Event Time: %1', [1 => $params['event_time']]);
  }
  $registration_activity = civicrm_api3(
    'Activity',
    'create',
    [
      'sequential' => TRUE,
      'activity_type_id' => CRM_Core_PseudoConstant::getKey(
        'CRM_Activity_BAO_Activity',
        'activity_type_id',
        'event_registration'
      ),
      'status_id' => 'Completed',
      'subject' => E::ts(
        'Event registration for event %1',
        [1 => $params['event_title']]
      ),
      'details' =>
        '<p>' . E::ts('A contact registered for an event via the API (Website).') . '</p>'
        . '<ul><li>' . implode('</li><li>', $registration_details) . '</li></ul>',
      'target_id' => $contact_id,
    ]
  );

  // Create activity of type "Delete Contact" for newly created contacts without
  // GDPR consent.
  if ($contact_was_created && empty($params['newsletter'])) {
    $delete_activity = civicrm_api3(
      'Activity',
      'create',
      [
        'sequential' => TRUE,
        'activity_type_id' => CRM_Core_PseudoConstant::getKey(
          'CRM_Activity_BAO_Activity',
          'activity_type_id',
          'delete_contact'
        ),
        // TODO: time() + X days.
        'activity_date_time' => date_create('today + 365 days')
          ->format('YmdHis'),
        'status_id' => 'Scheduled',
        'subject' => E::ts('Delete contact without GDPR consent'),
        'details' => E::ts('A contact created during an event registration is to be deleted due to missing GDPR consent.'),
        'target_id' => $contact_id,
      ]
    );
  }

  // Subscribe to newsletter.
  if (!empty($params['newsletter']) && !empty($params['group_ids'])) {
    $group_contacts = CRM_StiftungNVAPI_Submission::subscribeNewsletter(
      $contact_id,
      $params['group_ids']
    );
  }

  return civicrm_api3_create_success(
    [$registration_activity['id'] => $registration_activity['values'][0]],
    $params,
    'StiftungNVEventRegistration',
    'submit',
    $dao = NULL,
    [
      'delete_activity' => $delete_activity['values'][0] ?? NULL,
      'newsletter_groups' => $group_contacts ?? NULL,
    ]
  );
}

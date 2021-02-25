# Stiftung Neue Verantwortung API extension

This extension provides customized interfaces for applications to communicate
with Stiftung Neue Verantwortung CiviCRM. This is useful for synchronizing
contact data, coming from form submissions or other requests, with the CiviCRM
instance in a streamlined way without the need to understand the underlying data
structure in CiviCRM.

## Requirements

Depending on the API call being issued, there may be dependencies. Those are
explicitly listed in the following API call documentation.

## Installation

Install like any other CiviCRM extension.

## Configuration

There is currently no user interface available for configuring the extension.

## Usage

The extension provides API calls. Invoking them may be done using any API method
supported by CiviCRM, e.g. the CiviCRM REST API. The site key and an API key may
be necessary.

### API calls

The extension provides the following API calls:

#### Newsletter subscription

- Entity: `StiftungNVNewsletterSubscription`
- Action: `Submit`

| Parameter        | Type   | Cardinality | Required | Allowed values                | Description                                                                               |
|------------------|--------|-------------|----------|-------------------------------|-------------------------------------------------------------------------------------------|
| `prefix_id`      | int    | 1           | yes      | a valid CiviCRM prefix ID     | The ID of the CiviCRM prefix for the contact.                                             |
| `formal_title`   | string | 1           | no       |                               | The contact's formal title.                                                               |
| `first_name`     | string | 1           | yes      | maxlength 64                  | The contact's first name.                                                                 |
| `last_name`      | string | 1           | yes      | maxlength 64                  | The contact's last name.                                                                  |
| `phone`          | string | 1           | no       |                               | The contact's phone number.                                                               |
| `email`          | string | 1           | yes      | A valid e-mail address        | The contact's e-mail address.                                                             |
| `institution`    | string | 1           | no       | maxlength 255                 | The contact's institution or organization.                                                |
| `group_ids`      | int[]  | unlimited   | yes      | An array of CiviCRM group IDs | The IDs of the CiviCRM groups to add the contact to.                                      |
| `update_contact` | bool   | 1           | no       | `1` or `0`, defaulting to `0` | Whether the provided contact data should overwrite possibly existing data on the contact. |
| `language`       | string | 1           | no       | A 2-digit ISO language code   | The language used for the submitting form.                                                |

#### Event registration

This API creates an activity for event registrations on the Website and another
activity for scheduling contact deletions when the contact has not consented to
the GDPR.

- Entity: `StiftungNVEventRegistration`
- Action: `Submit`

| Parameter        | Type   | Cardinality | Required                      | Allowed values                                              | Description                                                                               |
|------------------|--------|-------------|-------------------------------|-------------------------------------------------------------|-------------------------------------------------------------------------------------------|
| `prefix_id`      | int    | 1           | yes                           | a valid CiviCRM prefix ID                                   | The ID of the CiviCRM prefix for the contact.                                             |
| `formal_title`   | string | 1           | no                            |                                                             | The contact's formal title.                                                               |
| `first_name`     | string | 1           | yes                           | maxlength 64                                                | The contact's first name.                                                                 |
| `last_name`      | string | 1           | yes                           | maxlength 64                                                | The contact's last name.                                                                  |
| `email`          | string | 1           | yes                           | A valid e-mail address                                      | The contact's e-mail address.                                                             |
| `institution`    | string | 1           | no                            | maxlength 255                                               | The contact's institution or organization.                                                |
| `newsletter`     | bool   | 1           | no                            | `1` or `0`, defaulting to `0`                               | Whether the contact wants to subscribe to newsletter(s).                                  |
| `group_ids`      | int[]  | unlimited   | yes, when `newsletter` is `1` | An array of CiviCRM group IDs                               | The IDs of the CiviCRM groups to add the contact to.                                      |
| `update_contact` | bool   | 1           | no                            | `1` or `0`, defaulting to `0`                               | Whether the provided contact data should overwrite possibly existing data on the contact. |
| `language`       | string | 1           | no                            | A 2-digit ISO language code                                 | The language used for the submitting form.                                                |
| `event_id`       | int    | 1           | yes                           |                                                             | The (external) ID of the event to register for.                                           |
| `event_title`    | string | 1           | yes                           |                                                             | The title of the event to register for.                                                   |
| `event_time`     | date   | 1           | no                            | A CiviCRM-formatted date/time string in the format `YmdHis` | The date/time of the event to register for.                                               |
| `event_location` | string | 1           | no                            |                                                             | The location of the event to register for.                                                |

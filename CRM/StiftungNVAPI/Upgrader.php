<?php
use CRM_StiftungNVAPI_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_StiftungNVAPI_Upgrader extends CRM_StiftungNVAPI_Upgrader_Base {

  /**
   * @throws \Exception
   */
  public function enable() {
    // Install activity types.
    $customData = new CRM_StiftungNVAPI_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('resources/option_group_activity_type.json'));
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_5120() {
    $this->ctx->log->info('Applying update 5120');
    $this->ctx->log->info('Synchronize activity types.');

    // Install activity types.
    $customData = new CRM_StiftungNVAPI_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('resources/option_group_activity_type.json'));

    return TRUE;
  }

}

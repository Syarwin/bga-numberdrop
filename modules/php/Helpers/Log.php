<?php
namespace NUMDROP\Helpers;
use NUMDROP\Core\Game;
use NUMDROP\Core\Notifications;
use NUMDROP\Managers\Players;

class Log extends \APP_DbObject
{
  /////////////////////////////////
  /////////////////////////////////
  //////////   Setters   //////////
  /////////////////////////////////
  /////////////////////////////////
  public static function clearTurn($pId)
  {
    // Cancel the notifications
    return self::cancelNotifs($pId);
  }

  //////////////////////////////////////////////
  //////////////////////////////////////////////
  //////////   CANCEL NOTIFICATIONS   //////////
  //////////////////////////////////////////////
  //////////////////////////////////////////////

  /*
   * getCancelMoveIds : get all cancelled notifs IDs from BGA gamelog, used for styling the notifications on page reload
   */
  protected function extractNotifIds($notifications)
  {
    $notificationUIds = [];
    foreach ($notifications as $notification) {
      $data = \json_decode($notification, true);
      array_push($notificationUIds, $data[0]['uid']);
    }
    return $notificationUIds;
  }

  public function getCanceledNotifIds()
  {
    return self::extractNotifIds(
      self::getObjectListFromDb('SELECT `gamelog_notification` FROM gamelog WHERE `cancel` = 1', true)
    );
  }

  /*
   * getLastStartTurnNotif : find the packet_id of the last notifications
   */
  protected function getLastStartTurnNotif()
  {
    $packets = self::getObjectListFromDb(
      'SELECT `gamelog_packet_id`, `gamelog_notification` FROM gamelog WHERE `gamelog_player` IS NULL ORDER BY gamelog_packet_id DESC'
    );
    foreach ($packets as $packet) {
      $data = \json_decode($packet['gamelog_notification'], true);
      foreach ($data as $notification) {
        if ($notification['type'] == 'throwDices') {
          return $packet['gamelog_packet_id'];
        }
      }
    }
    return 0;
  }

  protected function cancelNotifs($pId)
  {
    $packetId = self::getLastStartTurnNotif();
    $whereClause = "WHERE `gamelog_current_player` = $pId AND `gamelog_packet_id` > $packetId";
    $notifIds = self::extractNotifIds(
      self::getObjectListFromDb("SELECT `gamelog_notification` FROM gamelog $whereClause", true)
    );
    self::DbQuery("UPDATE gamelog SET `cancel` = 1 $whereClause");
    return $notifIds;
  }
}

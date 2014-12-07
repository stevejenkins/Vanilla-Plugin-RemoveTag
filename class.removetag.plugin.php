<?php if (!defined('APPLICATION')) exit();

$PluginInfo['RemoveTag'] = array(
   'Name' => 'RemoveTag',
   'Description' => "Allows User to remove Tag based on userid and associated tagid",
   'Version' => '1.1',
   'MobileFriendly' => TRUE,
   'Author' => "Peregrine",
);


// to modify program  
// 1. add or delete or change userids to $TagRemoveUserIDList array on line 24.
// 2. add, delete, or adjust  case statements around lines 62 -70 


class RemoveTagPlugin extends Gdn_Plugin {

   public function Base_DiscussionOptions_Handler($Sender, $Args) {
      $Session = Gdn::Session();
      
      // list any userids you want "RemoveTag" in the discussionoption flyout.
      // it is now set to show RemoveTag for userid's 4, 12, and 16 (numbers do not have to be sequential)
      $TagRemoveUserIDList  = array("6");
      
      // only add option to users in $TagRemoveUserIDList
      if (!InArrayI($Session->UserID, $TagRemoveUserIDList)) return;
      
      $Discussion = $Args['Discussion'];
      $Label = T('RemoveTag');
      $Url = "/discussion/removetag?discussionid={$Discussion->DiscussionID}";
      // deal with inconsistencies in how options are passed
      if (isset($Sender->Options)) {
         $Sender->Options .= Wrap(Anchor($Label, $Url, 'RemoveTag'), 'li');
      }
      else {
         $Args['DiscussionOptions']['RemoveTag'] = array(
               'Label' => $Label,
               'Url' => $Url,
               'Class' => 'RemoveTag'
            );
         }
      }

   public function DiscussionController_RemoveTag_Create($Sender, $Args) {

      // initialize
      $Tagid = 0; 

      $Session = Gdn::Session();
      $SID = $Session->UserID;

      // each case statement represents a specific Userid
      // change the case line  and the associated Tagid for each user

      switch ($SID) {
         case "6":           
            $Tagid = "92";    // e.g. userid 6  associated with tagid 92
            break;
      // add more case statements as necessary
      /* 
         case "22":        
            $Tagid = "4";    // e.g. userid 22 associated with tagid 4
            break;
      */
      }  // end switch 

      if ($Tagid < 1)  return;
      $TagModel = new TagModel();
      $Tag = $TagModel->GetID($Tagid, DATASET_TYPE_ARRAY);
      $tagname = $Tag["Name"];
      
      // get discussion
      $DiscussionID = $Sender->Request->Get('discussionid');
      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
      if (!$Discussion)
         throw NotFoundException('Discussion');
      $DiscussionTags = $Discussion->Tags;
      $DTags = TagModel::SplitTags($DiscussionTags);

      $Px = Gdn::Database()->DatabasePrefix;
      // Delete from TagDiscussion
      $SQL = Gdn::SQL();
      $SQL->Delete('TagDiscussion', array('DiscussionID' =>$DiscussionID,'TagID' => $Tagid));

      // update the counts
      $Sql = "update {$Px}Tag t
         set CountDiscussions = (
            select count(DiscussionID)
            from {$Px}TagDiscussion td
            where td.TagID = t.TagID)
          where t.TagID = :ToID";
      Gdn::Database()->Query($Sql, array(':ToID' => $Tagid));

      // delete tag in array
      $key = array_search($tagname,$DTags);
      if($key!==false){
         unset($DTags[$key]);
      }

      // resave the Discussion's Tags field as serialized
      $SerializedTags = Gdn_Format::Serialize($DTags);

      $SQL->Update('Discussion')->Set('Tags', $SerializedTags)->Where('DiscussionID', $DiscussionID)->Put();

      // return to list of tags
      $encodedtag = urlencode($Tag['Name']);
      Redirect(("discussions/tagged/{$encodedtag}"));
   }

   public function Setup() { }
}

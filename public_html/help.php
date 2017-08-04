<?php
	include_once('header.php');
?>

<div class="content">

<h3>FAQ's</h3>

Visit the <b><a href="http://steamcommunity.com/groups/mvmlobby-com" target=_blank>mvmlobby.com Steam group</a></b> for the latest info.
<br/><p></p>
<hr />

<div class="faqHeader">DATA IS CACHED</div>
<div class="faqContent">
Summary data is cached for up to <?php echo SUMMARY_REFRESH_MINUTES; ?> minute(s).  This includes fields for Name, Online status, and if the player is currently in an MvM game.<br/>
Inventory data (where the MvM data is found) is cached up to <?php echo INVENTORY_REFRESH_MINUTES; ?> minutes.  Tours and missions will not be accurate for up to <?php echo INVENTORY_REFRESH_MINUTES; ?> minutes after a player completes a mission. 
There are options to force a refresh if desired.
</div>
<hr />

<div class="faqHeader">IF YOU SEE TOUR COUNTS ALL SET TO ZERO</div>
<div class="faqContent">
There are a few possibilities as to why tour counts may be missing:<br/>
1) The players backpack is private (you should see a message indicating that this is the issue).<br/>
2) The player deleted their Tour of Duty badges.  This resets tour counts and missions for the tour the badge represented.<br/>
3) The Steam API's are down.<br/>
</div>
<hr />

<div class="faqHeader">WHEN ARE TOURS UPDATED? / WHY AM I NOT ON THE HALL OF FAME?</div>
<div class="faqContent">
<div class="divider8"></div>
Your data is updated whenever:<br/>
1) You log in.<br/>
2) Someone searches for you.<br/>
3) Someone refreshes the Friends page and you are added as an MvM friend.<br/>
<div class="divider8"></div>
You data is <strong>automatically</strong> updated every 24 hours if:<br/>
1) You have over 25 tours total.<br/>
2) You have logged in through Steam within the past 2 days.<br/>
</div>
<hr />

<div class="faqHeader">FRIENDS</div>
<div class="faqContent">
<i><?php echo SITE_NAME; ?> friends are separate from Steam friends</i>.  This allows you to manage your MvM friends as a separate list and refresh summaries and inventories much faster.
On the "Friends" page, there is a "Manage Friends" button which allows you to add friends from Steam.<br/>
Players with zero tours will be removed from the database on a regular basis.<br/>
Friends page refresh buttons: "Refresh Status" will refresh Steam summary info, and will update tour data if it's older than <?php echo INVENTORY_REFRESH_MINUTES; ?> minutes.  The "Refresh Tours" button refreshes everything including tour (inventory) data, so it's always slow but more accurate.<br/>
</div>
<hr />

<div class="faqHeader">BROWSER INCOMPATIBILITIES</div>
<div class="faqContent">
Use the Steam browser, Chrome, Firefox, or Safari and keep it up to date.  It has not been tested on older browsers or IE.<br/>
</div>
<hr />

<div class="faqHeader">PRIVACY</div>
<div class="faqContent">
There are no ads or tracking of any kind.  Signing in through Steam provides your Steam64 ID and nothing more.
This site only collects MvM related data which would be useful for Mann Up players from your Steam summary and TF2 backpack. 
This information is publicy available through Steam API's and your Steam profile regardless of whether you log in or not.
You can prevent this site from gathering this info by setting your profile or inventory to private.<br/>
</div>
<div style="height: 40px;"></div>

</div>	<!-- content -->

<?php
	include_once('footer.php');
?>
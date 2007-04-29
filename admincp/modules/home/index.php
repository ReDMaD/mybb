<?php
/**
 * MyBB 1.2
 * Copyright � 2007 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybboard.net
 * License: http://www.mybboard.net/license.php
 *
 * $Id$
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if(!$mybb->input['action'])
{
	if($mybb->request_method == "post" && isset($mybb->input['adminnotes']))
	{
		// Update Admin Notes cache
		$update_cache = array(
			"adminmessage" => $mybb->input['adminnotes']
		);
		
		$cache->update("adminnotes", $update_cache);
	
		flash_message($lang->success_notes_updated, 'success');
		admin_redirect("index.php?".SID);
	}
	
	$page->add_breadcrumb_item($lang->dashboard);
	$page->output_header($lang->dashboard, array("stylesheets" => array("home.css")));
	
	$sub_tabs['dashboard'] = array(
		'title' => $lang->dashboard,
		'link' => "index.php?".SID,
		'description' => $lang->dashboard_description
	);

	$page->output_nav_tabs($sub_tabs, 'dashboard');
	
	$serverload = get_server_load();
	if(!$serverload)
	{
		$serverload = $lang->unknown;
	}
	// Get the number of users
	$query = $db->simple_select("users", "COUNT(*) AS numusers");
	$users = $db->fetch_field($query, "numusers");

	// Get the number of users awaiting validation
	$query = $db->simple_select("users", "COUNT(*) AS awaitingusers", "usergroup='5'");
	$awaitingusers = $db->fetch_field($query, "awaitingusers");

	// Get the number of new users for today
	$timecut = time() - 86400;
	$query = $db->simple_select("users", "COUNT(*) AS newusers", "regdate > '$timecut'");
	$newusers = $db->fetch_field($query, "newusers");

	// Get the number of active users today
	$query = $db->simple_select("users", "COUNT(*) AS activeusers", "lastvisit > '$timecut'");
	$activeusers = $db->fetch_field($query, "activeusers");

	// Get the number of threads
	$query = $db->simple_select("threads", "COUNT(*) AS numthreads", "visible='1' AND closed NOT LIKE 'moved|%'");
	$threads = $db->fetch_field($query, "numthreads");

	// Get the number of unapproved threads
	$query = $db->simple_select("threads", "COUNT(*) AS numthreads", "visible='0' AND closed NOT LIKE 'moved|%'");
	$unapproved_threads = $db->fetch_field($query, "numthreads");

	// Get the number of new threads for today
	$query = $db->simple_select("threads", "COUNT(*) AS newthreads", "dateline > '$timecut' AND visible='1' AND closed NOT LIKE 'moved|%'");
	$newthreads = $db->fetch_field($query, "newthreads");

	// Get the number of posts
	$query = $db->simple_select("posts", "COUNT(*) AS numposts", "visible='1'");
	$posts = $db->fetch_field($query, "numposts");

	// Get the number of unapproved posts
	$query = $db->simple_select("posts", "COUNT(*) AS numposts", "visible='0'");
	$unapproved_posts = $db->fetch_field($query, "numposts");

	// Get the number of new posts for today
	$query = $db->simple_select("posts", "COUNT(*) AS newposts", "dateline > '$timecut' AND visible='1'");
	$newposts = $db->fetch_field($query, "newposts");

	// Get the number and total file size of attachments
	$query = $db->simple_select("attachments", "COUNT(*) AS numattachs, SUM(filesize) as spaceused", "visible='1' AND pid > '0'");
	$attachs = $db->fetch_array($query);
	$attachs['spaceused'] = get_friendly_size($attachs['spaceused']);

	// Get the number of unapproved attachments
	$query = $db->simple_select("attachments", "COUNT(*) AS numattachs", "visible='0' AND pid > '0'");
	$unapproved_attachs = $db->fetch_field($query, "numattachs");

	/*
	// Fetch the last time an update check was run
	$update_check = $cache->read("update_check");

	// If last update check was greater than two weeks ago (14 days) show an alert
	if($update_check['last_check'] <= time()-60*60*24*14)
	{
		$lang->last_update_check_two_weeks = sprintf($lang->last_update_check_two_weeks, "index.php?".SID."&amp;action=vercheck");
		makewarning($lang->last_update_check_two_weeks);
	}

	// If the update check contains information about a newer version, show an alert
	if($update_check['latest_version_code'] > $mybb->version_code)
	{
		$lang->new_version_available = sprintf($lang->new_version_available, "MyBB {$mybb->version}", "<a href=\"http://www.mybboard.net/?fwlink=release_{$update_check['latest_version_code']}\" target=\"_new\">MyBB {$update_check['latest_version']}</a>");
		makewarning($lang->new_version_available);
	}*/
	
	$adminmessage = $cache->read("adminnotes");

	$table = new Table;
	$table->construct_header($lang->mybb_server_stats, array("colspan" => 2));
	$table->construct_header($lang->forum_stats, array("colspan" => 2));
	
	$table->construct_cell("<strong>{$lang->mybb_version}</strong>", array('width' => '25%'));
	$table->construct_cell($mybb->version, array('width' => '25%'));
	$table->construct_cell("<strong>{$lang->threads}</strong>", array('width' => '25%'));
	$table->construct_cell("<strong>{$threads['numthreads']}</strong> {$lang->threads}<br /><strong>{$newthreads}</strong> {$lang->new_today}<br /><a href=\"\"><strong>{$unapproved_threads}</strong> {$lang->unapproved}</a>", array('width' => '25%'));
	$table->construct_row();
	
	$table->construct_cell("<strong>{$lang->php_version}</strong>", array('width' => '25%'));
	$table->construct_cell(phpversion(), array('width' => '25%'));
	$table->construct_cell("<strong>{$lang->posts}</strong>", array('width' => '25%'));
	$table->construct_cell("<strong>{$posts['numposts']}</strong> {$lang->posts}<br /><strong>{$newposts}</strong> {$lang->new_today}<br /><a href=\"\"><strong>{$unapproved_posts}</strong> {$lang->unapproved}</a>", array('width' => '25%'));
	$table->construct_row();
	
	$table->construct_cell("<strong>{$lang->sql_engine}</strong>", array('width' => '25%'));
	$table->construct_cell($db->title." ".$db->get_version(), array('width' => '25%'));
	$table->construct_cell("<strong>{$lang->users}</strong>", array('width' => '25%'));
	$table->construct_cell("<a href=\"\"><strong>{$users}</strong> {$lang->registered_users}</a><br /><strong>{$activeusers}</strong> {$lang->active_users}<br /><strong>{$newusers}</strong> {$lang->registrations_today}<br /><a href=\"\"><strong>{$awaitingusers}</strong> {$lang->awaiting_activation}</a>", array('width' => '25%'));
	$table->construct_row();
	
	$table->construct_cell("<strong>{$lang->server_load}</strong>", array('width' => '25%'));
	$table->construct_cell($serverload, array('width' => '25%'));
	$table->construct_cell("<strong>{$lang->attachments}</strong>", array('width' => '25%'));
	$table->construct_cell("<strong>{$attachs['numattachs']}</strong> {$lang->attachments}<br /><a href=\"\"><strong>{$unapproved_attachs}</strong> {$lang->unapproved}</a><br /><strong>{$attachs['spaceused']}</strong> {$lang->used}", array('width' => '25%'));
	$table->construct_row();
	
	$table->output($lang->dashboard);
	
	$table->construct_header($lang->admin_notes_public);
	
	$form = new Form("index.php?".SID, "post");
	$table->construct_cell($form->generate_text_area("adminnotes", $adminmessage['adminmessage'], array('style' => 'width: 99%; height: 200px;')));
	$table->construct_row();
	
	$table->output($lang->admin_notes);	
	
	$buttons[] = $form->generate_submit_button($lang->save_notes);
	$form->output_submit_wrapper($buttons);
	
	$form->end();
	
	$page->output_footer();
}
?>
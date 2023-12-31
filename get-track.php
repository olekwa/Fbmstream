<?php
require "assets/init.php";
require 'assets/libs/smartRead.php';
set_time_limit(0);
error_reporting(0);

if (empty($_GET['id'])) {
	exit('Empty ID, which track though?');
}

if (empty($_SERVER['HTTP_RANGE']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false) {
	if (empty($_COOKIE['session_hash'])) {
		exit('Access denied.');
	}

	if ($_COOKIE['session_hash'] != $_GET['hash']) {
		exit('Access denied.');
	}
}

$getSong = $db->where('audio_id', secure($_GET['id']))->getOne(T_SONGS);

if (empty($_SERVER['HTTP_REFERER'])) {
	exit('Access denied.');
} else {
	$site_url = str_replace('http://', '', $site_url);
	$site_url = str_replace('https://', '', $site_url);
	$site_url = rtrim($site_url, '/');
	if (((!strpos($_SERVER['HTTP_REFERER'], $site_url)) || (empty($_SERVER['HTTP_RANGE']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === false)) && !empty($getSong->dark_wave)) {
		exit('Access denied.');
	}
}

unset($_COOKIE['session_hash']);
setcookie('session_hash', null, -1, '/'); 
$purchase = false;
if ($getSong->price > 0) {
	if (!isTrackPurchased($getSong->id)) {
		$purchase = true;
		if (IS_LOGGED == true) {
			if ($user->id == $getSong->user_id) {
				$purchase = false;
			}
		}
	}
}

session_write_close();
$songOutput = $getSong->audio_location;

if (!empty($getSong->demo_track) && $purchase == true && $music->config->ffmpeg_system == 'on') {
	$songOutput = $getSong->demo_track;
	$purchase = false;
}

RecordUserActivities('listen_to_song', array('track_user_id' => $getSong->user_id, 'audio_id' => $getSong->audio_id));

smartReadFile($songOutput, str_replace(',', '-', $getSong->title) , 'audio/mpeg', $purchase);

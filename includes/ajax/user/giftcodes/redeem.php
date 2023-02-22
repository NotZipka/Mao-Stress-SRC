<?php

	ob_start(); 
	require_once '../../../app/config.php';
	require_once '../../../app/init.php'; 

	if (!empty($maintaince)) {
		die($maintaince);
	}

	if (!($user->LoggedIn()) || !($user->notBanned($odb)) || !(isset($_SERVER['HTTP_REFERER']))) {
		die();
	}

	$userID = $_GET['user'];
	$code = $_GET['code'];
	
	if (empty($code) || empty($userID)){
		die(error('Fill in all fields'));
	}

	
		$SQL = $odb -> prepare("SELECT `claimedby` FROM `giftcards` WHERE `code` = :code");
		$SQL -> execute(array(':code' => $code));
		$status = $SQL -> fetchColumn(0);
		if (!($status == 0)){
			die(error('Gift code has already been claimed!'));
		}
	
	// Update Status of GC
	$SQLUpdate = $odb -> prepare("UPDATE `giftcards` SET `claimedBy` = :userID, `dateClaimed` = UNIX_TIMESTAMP() WHERE `code` = :code");
	$SQLUpdate -> execute(array(':userID' => $userID, ':code' => $code));
	
	// Update User Account with new Plan
	$SQL = $odb -> prepare("SELECT `planID` FROM `giftcards` WHERE `code` = :code");
	$SQL -> execute(array(':code' => $code));
	$planID = $SQL -> fetchColumn(0);
	
	$SQL = $odb -> prepare("SELECT * FROM `plans` WHERE `ID` = :id");
	$SQL -> execute(array(':id' => $planID));
	$plan = $SQL -> fetch();
	
	$planName = $plan['name'];
	$unit = $plan['unit'];
	$length = $plan['length'];
	
	$newExpire = strtotime("+{$length} {$unit}");
	$updateSQL = $odb -> prepare("UPDATE `users` SET `membership` = :plan, `expire` = :expire WHERE `ID` = :id");
	$updateSQL -> execute(array(':plan' => (int)$planID, ':expire' => $newExpire, ':id' => (int)$userID));
	
	echo success('Gift code has been redeem. Plan ('.$planName.') has been added to your account!');
	
?>
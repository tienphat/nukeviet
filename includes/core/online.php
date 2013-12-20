<?php

/**
 * @Project NUKEVIET 3.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2012 VINADES.,JSC. All rights reserved
 * @createdate 12/29/2009 15:33
 */

if( ! defined( 'NV_MAINFILE' ) ) die( 'Stop!!!' );

/**
 * nv_online_upd()
 *
 * @return void
 */
function nv_online_upd()
{
	global $db, $client_info, $user_info;
	$userid = 0;
	$username = 'guest';
	if( isset( $user_info['userid'] ) and $user_info['userid'] > 0 )
	{
		$userid = $user_info['userid'];
		$username = $user_info['username'];
	}
	elseif( $client_info['is_bot'] )
	{
		$username = 'bot:' . $client_info['bot_info']['name'];
	}
	$sth = $db->prepare( 'REPLACE INTO `' . NV_SESSIONS_GLOBALTABLE . '` VALUES ( :session_id, ' . $userid . ', :username, ' . NV_CURRENTTIME . ')' );
	$sth->bindParam( ':session_id', $client_info['session_id'], PDO::PARAM_STR );
	$sth->bindParam( ':username', $username, PDO::PARAM_STR );
	$sth->execute();
}

nv_online_upd();

?>
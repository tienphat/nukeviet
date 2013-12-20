<?php

/**
 * @Project NUKEVIET 3.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2012 VINADES.,JSC. All rights reserved
 * @Createdate 2-11-2010 0:44
 */

if( ! defined( 'NV_IS_FILE_MODULES' ) ) die( 'Stop!!!' );

$contents = array();

$mod = $nv_Request->get_title( 'mod', 'get' );

if( empty( $mod ) or ! preg_match( $global_config['check_module'], $mod ) )
{
	Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name );
	die();
}

$sth = $db->prepare( 'SELECT * FROM `' . NV_MODULES_TABLE . '` WHERE `title`= :title');
$sth->bindParam( ':title', $mod, PDO::PARAM_STR );
$sth->execute();
$row = $sth->fetch();
if( empty($row) )
{
	Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name );
	die();
}

$theme_site_array = $theme_mobile_array = array();
$theme_array = scandir( NV_ROOTDIR . '/themes' );

foreach( $theme_array as $dir )
{
	if( preg_match( $global_config['check_theme'], $dir ) )
	{
		if( file_exists( NV_ROOTDIR . '/themes/' . $dir . '/config.ini' ) )
		{
			$theme_site_array[] = $dir;
		}
	}
	elseif( preg_match( $global_config['check_theme_mobile'], $dir ) )
	{
		if( file_exists( NV_ROOTDIR . '/themes/' . $dir . '/config.ini' ) )
		{
			$theme_mobile_array[] = $dir;
		}
	}
}

$theme_list = $theme_mobile_list = $array_theme = array();

// Chi nhung giao dien da duoc thiet lap layout moi duoc them
$result = $db->query( 'SELECT DISTINCT `theme` FROM `' . NV_PREFIXLANG . '_modthemes` WHERE `func_id`=0' );
while( list( $theme ) = $result->fetch( 3 ) )
{
	if( in_array( $theme, $theme_site_array ) )
	{
		$array_theme[] = $theme;
		$theme_list[] = $theme;
	}
	elseif( in_array( $theme, $theme_mobile_array ) )
	{
		$array_theme[] = $theme;
		$theme_mobile_list[] = $theme;
	}
}

$groups_list = nv_groups_list();

if( $nv_Request->get_int( 'save', 'post' ) == '1' )
{
	$custom_title = $nv_Request->get_title( 'custom_title', 'post', 1 );
	$admin_title = $nv_Request->get_title( 'admin_title', 'post', 1 );
	$theme = $nv_Request->get_title( 'theme', 'post', '', 1 );
	$mobile = $nv_Request->get_title( 'mobile', 'post', '', 1 );
	$description = $nv_Request->get_title( 'description', 'post', '', 1 );
	$description = nv_substr( $description, 0, 255 );
	$keywords = $nv_Request->get_title( 'keywords', 'post', '', 1 );
	$act = $nv_Request->get_int( 'act', 'post', 0 );
	$rss = $nv_Request->get_int( 'rss', 'post', 0 );

	if( ! empty( $theme ) and ! in_array( $theme, $theme_list ) ) $theme = '';

	if( ! empty( $mobile ) and ! in_array( $mobile, $theme_mobile_list ) ) $mobile = '';

	if( ! empty( $keywords ) )
	{
		$keywords = explode( ',', $keywords );
		$keywords = array_map( 'trim', $keywords );
		$keywords = implode( ', ', $keywords );
	}

	if( $mod != $global_config['site_home_module'] )
	{
		$who_view = $nv_Request->get_int( 'who_view', 'post', 0 );

		if( $who_view < 0 or $who_view > 3 ) $who_view = 0;

		$groups_view = '';

		if( $who_view == 3 )
		{
			$groups_view = $nv_Request->get_array( 'groups_view', 'post', array() );
			$groups_view = ! empty( $groups_view ) ? implode( ',', array_map( 'intval', $groups_view ) ) : '';
		}
		else
		{
			$groups_view = ( string )$who_view;
		}
	}
	else
	{
		$act = 1;
		$who_view = 0;
		$groups_view = '0';
	}

	if( $groups_view != '' and $custom_title != '' )
	{
		$array_layoutdefault = array();

		foreach( $array_theme as $_theme )
		{
			$xml = simplexml_load_file( NV_ROOTDIR . '/themes/' . $_theme . '/config.ini' );
			$layoutdefault = ( string )$xml->layoutdefault;

			if( ! empty( $layoutdefault ) and file_exists( NV_ROOTDIR . '/themes/' . $_theme . '/layout/layout.' . $layoutdefault . '.tpl' ) )
			{
				$array_layoutdefault[$_theme] = $layoutdefault;
			}
			else
			{
				$data['error'][] = $_theme;
			}
		}

		if( empty( $data['error'] ) )
		{
			foreach( $array_layoutdefault as $selectthemes => $layoutdefault )
			{
				$array_func_id = array();
				$sth = $db->prepare( 'SELECT `func_id` FROM `' . NV_PREFIXLANG . '_modthemes` WHERE `theme`= :theme' );
				$sth->bindParam( ':theme', $selectthemes, PDO::PARAM_STR );
				$sth->execute();
				while( list( $func_id ) = $sth->fetch( 3 ) )
				{
					$array_func_id[] = $func_id;
				}

				$sth = $db->prepare( 'SELECT `func_id` FROM `' . NV_MODFUNCS_TABLE . '` WHERE `in_module`= :in_module AND `show_func`=1 ORDER BY `subweight` ASC' );
				$sth->bindParam( ':in_module', $mod, PDO::PARAM_STR );
				$sth->execute();
				while( list( $func_id ) = $sth->fetch( 3 ) )
				{
					if( ! in_array( $func_id, $array_func_id ) )
					{
						$sth2 = $db->prepare( 'INSERT INTO `' . NV_PREFIXLANG . '_modthemes` (`func_id`, `layout`, `theme`) VALUES (' . $func_id . ', :layout, :theme)' );
						$sth2->bindParam( ':layout', $layoutdefault, PDO::PARAM_STR );
						$sth2->bindParam( ':theme', $selectthemes, PDO::PARAM_STR );
						$sth2->execute();
					}
				}
			}

			$sth = $db->prepare( 'UPDATE `' . NV_MODULES_TABLE . '` SET `custom_title`=:custom_title, `admin_title`=:admin_title, `theme`= :theme, `mobile`= :mobile, `description`= :description, `keywords`= :keywords, `groups_view`= :groups_view, `act`=' . $act . ', `rss`=' . $rss . ' WHERE `title`= :title' );
			$sth->bindParam( ':custom_title', $custom_title, PDO::PARAM_STR );
			$sth->bindParam( ':admin_title', $admin_title, PDO::PARAM_STR );
			$sth->bindParam( ':theme', $theme, PDO::PARAM_STR );
			$sth->bindParam( ':mobile', $mobile, PDO::PARAM_STR );
			$sth->bindParam( ':description', $description, PDO::PARAM_STR );
			$sth->bindParam( ':keywords', $keywords, PDO::PARAM_STR );
			$sth->bindParam( ':groups_view', $groups_view, PDO::PARAM_STR );
			$sth->bindParam( ':title', $mod, PDO::PARAM_STR );
			$sth->execute();

			$mod_name = change_alias( $nv_Request->get_title( 'mod_name', 'post' ) );
			if( $mod_name != $mod AND preg_match( $global_config['check_module'], $mod_name ) )
			{
				$sth = $db->prepare( 'UPDATE `' . NV_MODULES_TABLE . '` SET `title`= :mod_name  WHERE `title`= :mod' );
				$sth->bindParam( ':mod_name', $mod_name, PDO::PARAM_STR );
				$sth->bindParam( ':mod', $mod, PDO::PARAM_STR );
				if( $sth->execute() )
				{
					$sth = $db->prepare( 'UPDATE `' . NV_MODFUNCS_TABLE . '` SET `in_module`= :mod_name  WHERE `in_module`= :mod' );
					$sth->bindParam( ':mod_name', $mod_name, PDO::PARAM_STR );
					$sth->bindParam( ':mod', $mod, PDO::PARAM_STR );
					$sth->execute();
				}
			}
			nv_delete_all_cache();
			nv_insert_logs( NV_LANG_DATA, $module_name, sprintf( $lang_module['edit'], $mod ), '', $admin_info['userid'] );

			Header( 'Location: ' . NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name );
			exit();
		}
		else
		{
			$data['error'] = sprintf( $lang_module['edit_error_update_theme'], implode( ', ', $data['error'] ) );
		}
	}
	elseif( $groups_view != '' )
	{
		$row['groups_view'] = $groups_view;
	}
}
else
{
	$custom_title = $row['custom_title'];
	$admin_title = $row['admin_title'];
	$theme = $row['theme'];
	$mobile = $row['mobile'];
	$act = $row['act'];
	$description = $row['description'];
	$keywords = $row['keywords'];
	$rss = $row['rss'];
}

$who_view = 3;
$groups_view = array();

if( $row['groups_view'] == '0' or $row['groups_view'] == '1' or $row['groups_view'] == '2' )
{
	$who_view = intval( $row['groups_view'] );
}
else
{
	$groups_view = array_map( 'intval', explode( ',', $row['groups_view'] ) );
}

if( empty( $custom_title ) ) $custom_title = $mod;

$page_title = sprintf( $lang_module['edit'], $mod );

if( file_exists( NV_ROOTDIR . '/modules/' . $row['module_file'] . '/funcs/rss.php' ) )
{
	$data['rss'] = array( $lang_module['activate_rss'], $rss );
}

$data['action'] = NV_BASE_ADMINURL . 'index.php?' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit&amp;mod=' . $mod;
$data['custom_title'] = $custom_title;
$data['admin_title'] = $admin_title;
$data['theme'] = array( $lang_module['theme'], $lang_module['theme_default'], $theme_list, $theme );
$data['mobile'] = array( $lang_module['mobile'], $lang_module['theme_default'], $theme_mobile_list, $mobile );
$data['description'] = $description;
$data['keywords'] = $keywords;
$data['mod_name'] = $mod;

if( $mod != $global_config['site_home_module'] )
{
	$data['who_view'] = array( $lang_global['who_view'], array( $lang_global['who_view0'], $lang_global['who_view1'], $lang_global['who_view2'], $lang_global['who_view3'] ), $who_view );
	$data['groups_view'] = array( $lang_global['groups_view'], $groups_list, $groups_view );
}
$data['submit'] = $lang_global['submit'];

$xtpl = new XTemplate( 'edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file );
$xtpl->assign( 'GLANG', $lang_global );
$xtpl->assign( 'LANG', $lang_module );
$xtpl->assign( 'DATA', $data );

if( ! empty( $data['error'] ) )
{
	$xtpl->parse( 'main.error' );
}

foreach( $data['theme'][2] as $tm )
{
	$xtpl->assign( 'THEME', array( 'key' => $tm, 'selected' => $tm == $data['theme'][3] ? ' selected=\'selected\'' : '' ) );
	$xtpl->parse( 'main.theme' );
}

if( ! empty( $data['mobile'][2] ) )
{
	foreach( $data['mobile'][2] as $tm )
	{
		$xtpl->assign( 'MOBILE', array( 'key' => $tm, 'selected' => $tm == $data['mobile'][3] ? ' selected=\'selected\'' : '' ) );
		$xtpl->parse( 'main.mobile.loop' );
	}

	$xtpl->parse( 'main.mobile' );
}

if( isset( $data['who_view'] ) )
{
	foreach( $data['who_view'][1] as $k => $w )
	{
		$xtpl->assign( 'WHO_VIEW', array(
			'key' => $k,
			'selected' => $k == $data['who_view'][2] ? ' selected=\'selected\'' : '',
			'title' => $w
		) );
		$xtpl->parse( 'main.who_view.loop' );
	}

	$xtpl->assign( 'DISPLAY', $data['who_view'][2] == 3 ? 'visibility:visible;display:block;' : 'visibility:hidden;display:none;' );

	foreach( $data['groups_view'][1] as $group_id => $grtl )
	{
		$xtpl->assign( 'GROUPS_VIEW', array(
			'key' => $group_id,
			'checked' => in_array( $group_id, $data['groups_view'][2] ) ? ' checked="checked"' : '',
			'title' => $grtl
		) );

		$xtpl->parse( 'main.who_view.groups_view' );
	}

	$xtpl->parse( 'main.who_view' );
}

$xtpl->assign( 'ACTIVE', ( $act == 1 ) ? ' checked="checked"' : '' );

if( isset( $data['rss'] ) )
{
	$xtpl->assign( 'RSS', ( $data['rss'][1] == 1 ) ? ' checked="checked"' : '' );
	$xtpl->parse( 'main.rss' );
}

$xtpl->parse( 'main' );
$contents = $xtpl->text( 'main' );

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme( $contents );
include NV_ROOTDIR . '/includes/footer.php';

?>
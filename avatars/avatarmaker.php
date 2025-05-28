<?php
/**
 * AvatarMaker 3.x By InochiTeam
 *
 * @updated       17/10/2018 (3.2.1 Elsie)
 */

require_once("avatarmaker.class.php");


$avatarMaker = new HT_AvatarMaker();


/*
 * Send the configuration file in case of a GET request
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if( isset( $_GET['md5'] ) )
    {
        $avatarMaker->setAvatarMd5( $_GET['md5'] );
        $avatarMaker->renderAvatar( );
        die();
    }

    if( isset( $_GET['avm_items'] ) )
    {
        $avatarMaker->setAvatarGET( );
        $avatarMaker->renderAvatar( );
        die();
    }

	$avatarMaker->outAppConfig( );
}


/*
 * Load the avatar structure sent as json payload and render the avatar accordingly
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avatarMaker->setAvatar( );
    $avatarMaker->renderAvatar( );
}

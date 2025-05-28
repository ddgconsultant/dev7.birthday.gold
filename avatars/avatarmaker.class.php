<?php
/**
 * AvatarMaker 3.x By InochiTeam
 *
 * @updated       17/10/2018 (3.2.1 Elsie)
 */

class HT_AvatarMaker
{

	private $config;

	private $image;

	private $avatar;


    /**
     * On instantiation, load the configuration file and the appropriate renderer
     */
	public function __construct( )
	{
	    require_once "avatarmaker.config.php";
		$this->config = new HT_AvatarMaker_config();


		include "avatarmaker.graphics.php";

		try
		{
			$this->image = new HT_AvatarMaker_graphics( $this->config->renderer_driver );
		}
		catch (\Exception $e)
		{
			$this->outError($e->getMessage(), true);
		}


	}



    /**
     *  Exit from the application setting the appropriate http code (404 o r 500) and output an error message
     *
     * @param string $text A description of the error
     */
    private function outError( $text = "An unknown error has occurred!", $serverError = false )
	{

		if( !$serverError )
		{
	        header("HTTP/1.0 404 Not Found");
	        header("content-type: text/plain; charset=UTF-8");

	        die( "The avatarMaker API has rejected your request. " . $text );
		}
		else
		{
			header("HTTP/1.0 500 Internal Server Error");
		    header("content-type: text/plain; charset=UTF-8");

		   die( "Server Error: " . $text );
		}

	}



    /**
     * Output the configuration file for the app either by generating it or by reading a cached copy
     */
	public function outAppConfig( )
	{

		$configHash = md5( file_get_contents( "avatarmaker.config.php" )  );

			/* If available, load a cached copy of the configuration */
			if( $cache = @file_get_contents( $this->config->folder_cache."cacheData.htCache" ) )
			{
				$cache = json_decode($cache, true );

				/* Test if the cached copy is valid */
				if( $cache['configHash'] == $configHash )
				{

								$appConfig = $cache['configData'];

					header( 'Content-Type: application/json' );
							exit( json_encode( $appConfig ) );

				}

			}


		/* Generate the configuration */
		$appConfig = $this->generateAppConfig( );

		$cache = array(

			"configHash" => $configHash,
			"configData" => $appConfig

		);


        /* Save the app configuration to the cache and output it */
        file_put_contents($this->config->folder_cache . "cacheData.htCache", json_encode($cache) );


        header( 'Content-Type: application/json' );
        exit( json_encode( $appConfig ) );
    }



	/**
	 * Generate the configuration file for the app
	 *
	 * @return array
	 */
	private function generateAppConfig( )
	{

		$appConfig = array();

		/* Interface configuration */
		$appConfig['app'] = array();
		$appConfig['app']['outputFormat'] = (strpos($this->config->renderer_format, "_saved") !== false) ? "saved" : $this->config->renderer_format;

		$appConfig['app']['brand']                 = $this->config->app_brand;
		$appConfig['app']['displayCredits']        = $this->config->app_displayCredits;
		$appConfig['app']['compactUi']        	   = $this->config->app_compactUi;
		$appConfig['app']['cacheSizes']        	   = $this->config->renderer_cacheSizes;
		$appConfig['app']['randStartup']           = $this->config->app_randStartup;
		$appConfig['app']['randUpdateColors']      = $this->config->app_randUpdateColors;
		$appConfig['app']['transparentBackground'] = $this->config->app_transparentBackground;
		$appConfig['app']['allowCustomColors'] = $this->config->app_allowCustomColors;



		$aX = $this->config->renderer_assetsSizes['x'];
		$aY = $this->config->renderer_assetsSizes['y'];
		$cX = $this->config->renderer_cacheSizes['x'];
		$appConfig['app']['cacheSizes']['y'] = ( $this->config->renderer_cacheSizes['y'] ) ? $this->config->renderer_cacheSizes['y'] : round( $aY * $cX / $aX );


		/* A list of all layers and items in them */
		$appConfig['layers'] = $this->cacheAssets();


		/* All the available color palettes */
		$appConfig['palettes'] = $this->config->avatar_palettes;


		/* The list of the featured items */
		$appConfig['featuredItems'] = $this->config->avatar_featured_items;


		/* Load the localization for the UI */
        $appConfig['local'] = include $this->config->folder_locals . $this->config->app_local . ".php";

		return $appConfig;
	}



    /**
     * Generate a cache image for all layers items and build a map with all items names
     *
     * @return array
     */
    private function cacheAssets( )
    {
        /* Create the cache folder if it does't exist or exit if we can't */
        if( !file_exists ( $this->config->folder_cache ) )
		{
            if( !mkdir( $this->config->folder_cache, 755 ) )
			{
				$this->outError( "Unable to create the cache folder! Do we have write permission?", true);
			}
		}

        $assets = array();

		/* Calculate the sizes for the cache images */
		$aX = $this->config->renderer_assetsSizes['x'];
		$aY = $this->config->renderer_assetsSizes['y'];

		$cX = $this->config->renderer_cacheSizes['x'];
		$cY = ( $this->config->renderer_cacheSizes['y'] ) ? $this->config->renderer_cacheSizes['y'] : round( $aY * $cX / $aX );

		/* Scan every item in every layer */
        foreach( $this->config->avatar_layers as $layer => $colors )
        {

			try
			{
	            $assets[$layer] = array();
	            $assets[$layer]["colors"] = $colors;
	            $assets[$layer]["items"] = array();

	            $items = glob( $this->config->folder_assets.$layer."/*.png" );

				/* Check if there is at least an item for this layer */
				if( count($items) == 0 )
					$this->outError( 'The layer "' . $layer .'" is empty!', true);

	            $assetsCache = $this->image->create( $cX * count($items), $cY, true );

	            $i = 0;
	            foreach( $items as $item )
	            {
	                $this->image->compose($assetsCache, $item, $i * $cX, 0, $cX, $cY);

	                $item = str_replace( $this->config->folder_assets.$layer."/", "", $item );
	                $item = str_replace( ".png", "", $item );
	                array_push($assets[$layer]["items"], $item);

	                $i++;
	            }

	            $this->image->render( $assetsCache, "png", $this->config->folder_cache.$layer.".png" );

			}
			catch (\Exception $e)
			{
				$this->outError($e->getMessage(), true);
			}

        }

        return $assets;

    }



    /**
     * Parse a json string to load the structure for the next avatar to render.
     * If no json string is passed it will try to read it from the input stream
     *
     * @param bool $json
     * @return bool
     */
	public function setAvatar( $json = false )
	{
		if( !$json )
			$json = file_get_contents( 'php://input' );

		$this->avatar = @json_decode( $json, true );

		if( !is_array($this->avatar) )
            $this->outError("Invalid or missing JSON.");

        $this->avatar['size'] = @( is_numeric($this->avatar['size']) && $this->avatar['size'] > 0 && $this->avatar['size'] < 1024) ? $this->avatar['size'] : 1024;

		return true;
	}



    /**
     * Create an avatar structure based on the given md5 hash
     *
     * @param string $md5 The hash to use to create the avatar
     */
    public function setAvatarMd5( $md5 )
    {
        // Check if we have a valid hash
        if( !preg_match('/^[a-f0-9]{32}$/', $md5) )
            $this->outError("Invalid hash.");

        $couplesNumber = ( count( $this->config->avatar_layers ) + count( $this->config->avatar_palettes ) ) ;

        // If we need more data than what we have, we need to expand the hash
        if( $couplesNumber*2 > 32 ) {
            $newHash = $md5;

            for ( $i = 0; $i<($couplesNumber*2-32); $i++ )
            {
                // Pick a character in the position corresponding to the ascii value of the Nth character module 32
                // I Know, it is convoluted... but  trust me, it works. It extends the hash without repeating parts of it.
                $pos =  ord( substr($md5, $i, 1) ) % 32;
                $newHash .= substr($md5, $pos, 1);
            }


            $md5 = $newHash;
            unset($newHash);
        }


        $i = 0;
        $this->avatar = array();
        $this->avatar['size'] = @( is_numeric($_GET['size']) && $_GET['size'] > 0 && $_GET['size'] < 1024) ? $_GET['size'] : 1024;
        $this->avatar['layers'] = array();
        $this->avatar['colors'] = array();

        // Select an item in each category
        foreach( $this->config->avatar_layers as $layer => $palette )
        {
            $partsList = glob( $this->config->folder_assets.$layer."/*.png" );

            $partIndex = ord( substr( $md5, $i, 1 ) ) * ord( substr( $md5, $i+1, 1 ) );
            $partIndex = $partIndex % count( $partsList );

            $partName = str_replace( '.png', '', basename($partsList[$partIndex]) );

            $this->avatar['layers'][$layer] = $partName;

            $i = $i+2;
        }

        // Select a color in each palette
        foreach( $this->config->avatar_palettes as $palette => $colors )
        {

            $colorIndex = ord( substr( $md5, $i, 1 ) ) * ord( substr( $md5, $i+1, 1 ) );
            $colorIndex = $colorIndex % count( $colors );

            $this->avatar['colors'][$palette] = $colors[$colorIndex];

            $i = $i+2;
        }

		return true;
    }



    /**
     * Create an avatar structure based on the given parameters via GET
     */
    public function setAvatarGET( )
    {
        $this->avatar = array();
        $this->avatar['forcewn'] = @( $_GET['avm_forcewn'] == "true" ) ? true : false;
        $this->avatar['size'] = @( is_numeric($_GET['size']) && $_GET['size'] > 0 && $_GET['size'] < 1024) ? $_GET['size'] : 1024;
        $this->avatar['layers'] = array();
        $this->avatar['colors'] = array();

        // Parse the get values
        $items = $this->explodeGetString( $_GET['avm_items'] );
        $colors = $this->explodeGetString( $_GET['avm_colors'], true );

        // If there were no valid key/value pairs for the item, we have nothing to draw
        if( count($items) == 0 )
            $this->outError("No valid item to draw.");

        // Load all items in the avatar structure
        foreach( $this->config->avatar_layers as $layer => $palette )
        {
            $this->avatar['layers'][$layer] = @$items[$layer];
        }

        // Load all colors in the avatar structure
        foreach( $this->config->avatar_palettes as $palette => $list )
        {
            $this->avatar['colors'][$palette] = @$colors[$palette];
        }

    }



    /**
     * Generate an associative array from a get string
     *
     * @param $string
     * @param bool $convertColors
     * @return array
     */
    private function explodeGetString( $string, $convertColors = false )
    {
        $chunks = explode( '|', trim($string, '|') );
        $list = array();

        foreach($chunks as $chunk)
        {
            $chunk = explode( ':', $chunk );

            if( count( $chunk ) != 2 )
                continue;

            if( $convertColors )
                $list[$chunk[0]] = str_replace( '0x', '#', $chunk[1] );
            else
                $list[$chunk[0]] = $chunk[1];
        }

        return $list;
    }



    /**
     * Generate the avatar based on the parameter in the avatar array
     *
     * @param bool $avatarName
     */
	public function renderAvatar( $savePath = false )
	{
		try
		{

			/* Create the base image for the avatar */
			$avatar = $this->image->create($this->config->renderer_assetsSizes['x'], $this->config->renderer_assetsSizes['y'], true);

			/* Cycle cycle through each layer */
			foreach( $this->config->avatar_layers as $layer => $palette )
			{
			    if( !$this->avatar['layers'][$layer] )
			        continue;

				$source = $this->image->create( $this->config->folder_assets.$layer."/".$this->avatar['layers'][$layer].".png" );

				if( @$this->avatar['colors'][$palette] )
					$this->image->filter($source, "tint", $this->avatar['colors'][$palette]);

				$this->image->compose( $avatar, $source, 0, 0, $this->config->renderer_assetsSizes['x'], $this->config->renderer_assetsSizes['y'] );
			}

			/* If active add the appropriate overlay to the avatar */
			if( is_string( $this->config->renderer_overlay ) )
			    $this->image->overlay( $avatar, false, $this->config->renderer_overlay );

			elseif( $this->config->renderer_overlay === true )
	            $this->image->overlay( $avatar, "assets/overlay.png" );

			/* If requested resize the avatar */
			if( $this->avatar['size'] != 1024 )
				$avatar = $this->image->resize( $avatar, $this->avatar['size'] );


			if( strpos($this->config->renderer_format, "_saved") !== false )
			{
			    $outFormat = str_replace("_saved", "", $this->config->renderer_format);
	            $outPath = $savePath;
	        }
	        else
	        {
	            $outFormat =  $this->config->renderer_format;
	            $outPath = null;
	        }

	        if( @$this->avatar['forcewn'] )
	        {
	            header('Content-Type: application/octet-stream');
	            header('Content-Disposition: attachment; filename="AvatarMaker_'.substr(md5(microtime()),0,5).'.png"');
	        }

			/* Output the avatar */
			$this->image->render( $avatar, $outFormat, $outPath, $this->config->renderer_quality, @$this->avatar['encoding'] );

		}
		catch (\Exception $e)
		{
			$this->outError($e->getMessage(), true);
		}

    }

}

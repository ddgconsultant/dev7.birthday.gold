<?php
/**
 * AvatarMaker 3.x By InochiTeam
 *
 * @updated       17/10/2018 (3.2.1 Elsie)
 */


 class HT_AvatarMaker_graphics
 {

    const LIB_GD = 1,
          LIB_IM = 2,

          ERR_NO_LIB = 1,
          ERR_NO_GD = 2,
          ERR_NO_IMAGIC = 3,
          ERR_NO_FILE = 4,
          ERR_UNSUPPORTED_EXT = 5,
          ERR_UNSUPPORTED_FILTER = 6;


    private $library;


    /**
     * Check if the requested library is available on this system and instrat the class to use it
     *
     * @param string  $library  The library to use for rendering
     */
 	public function __construct( $library = 'gd' )
 	{

        if ( $library == "gd" )
        {
            /*
             * PHP GD
             */

            if ( !extension_loaded('gd') )
                throw new Exception("GD is not supported by the server!", self::ERR_NO_GD);

            $this->library = self::LIB_GD;

        }
        elseif ( $library == "magick" )
        {
            /*
             * IMAGICK
             */

            if ( !extension_loaded('imagick') )
                throw new Exception("Imagick is not supported by the server!", self::ERR_NO_IMAGIC);

            $this->library = self::LIB_IM;

        }
        else
            throw new Exception("Unknown library!", self::ERR_NO_LIB);

 	}



    /**
     * Crate a new image or load it from a path
     *
     * @return object
     */
 	public function create( )
 	{

        /* If we have only one parameter we assume it is the path of an image on the drive */
        if( func_num_args() == 1 )
            return $this->load( func_get_arg(0) );


        /* With multiple parameters we need to create a blank image */
        $sizeX = func_get_arg(0);
        $sizeY = func_get_arg(1);
        $transparent = func_get_arg(2) || false;


        if ( $this->library == self::LIB_GD )
        {
            /*
             * PHP GD
             */

            $image = imagecreatetruecolor( $sizeX, $sizeY );

    		if( $transparent )
    		{
    			$transparency = imagecolorallocatealpha( $image, 0, 0, 0, 127 );
    			imagefill( $image, 0, 0, $transparency );
    			imagesavealpha( $image, true );
    			imagealphablending( $image, true );
    		}
        }
        else
        {
            /*
             * IMAGICK
             */

            $background = ( $transparent ) ? new ImagickPixel('transparent') : new ImagickPixel('black');

            $image = new Imagick();
            $image->newImage( $sizeX , $sizeY, $background );
        }


        return $image;

 	}



    /**
     * Create an object from an existing image
     *
     * @param string  $sourcePath   The path of the image to load
     *
     * @return object
     */
 	private function load( $sourcePath )
 	{

        /* Check if the file exists */
        if ( !file_exists( $sourcePath ) )
            throw new Exception("Unable to load file!", self::ERR_NO_FILE);


        if ( $this->library == self::LIB_GD )
        {
            /*
             * PHP GD
             */

             switch( exif_imagetype( $sourcePath ) )
             {
                 case IMAGETYPE_GIF:
                     return imagecreatefromgif( $sourcePath );
                     break;

                 case IMAGETYPE_JPEG:
                     return imagecreatefromjpeg( $sourcePath );
                     break;

                 case IMAGETYPE_PNG:
                     return imagecreatefrompng( $sourcePath );
                     break;
             }

              throw new Exception("Unsupported file extension!", self::ERR_UNSUPPORTED_EXT);

        }
        else
        {
            /*
             * IMAGICK
             */

             return new Imagick( $sourcePath );

        }

 	}



    /**
     * Copy an image over an other.
     * If a path to an image is supplied it will be loaded into an object
     *
     * @param     $image    An ImageMagick object onto witch the image will be copied
     * @param     $source   The path of the image to copy or an image object
     * @param int $posX     X coordinate where to copy the image
     * @param int $posY     Y coordinate where to copy the image
     */
    public function compose( $image, $source, $posX = 0, $posY = 0, $sX = false, $sY = false )
    {
        if( is_string( $source ) )
            $source = $this->load( $source );


        if ( $this->library == self::LIB_GD )
        {
            /*
             * PHP GD
             */

             if( imagesx($source) == $sX && imagesy($source) == $sY )
                 imagecopy( $image, $source, $posX, $posY, 0, 0, $sX, $sY );
             else
                 imagecopyresampled( $image, $source, $posX, $posY, 0, 0, $sX, $sY, imagesx($source), imagesy($source) );

        }
        else
        {
            /*
             * IMAGICK
             */

             $sizeSource = $source->getImageGeometry();

             if( $sizeSource['width']  != $sX || $sizeSource['height']  != $sY )
                $source->resizeImage( $sX, $sY, Imagick::FILTER_LANCZOS, 1 );


             $image->setImageVirtualPixelMethod( Imagick::VIRTUALPIXELMETHOD_TRANSPARENT );
             $image->setImageArtifact( 'compose:args', "1,0,-0.5,0.5" );
             $image->compositeImage( $source, $source->getImageCompose(), $posX, $posY );

        }


    }



    /**
     * Apply a filter over an image
     *
     * @param      $image  The object to filter
     * @param      $filter The name fo the filter to apply
     * @param bool $p      Variables to pass to the filter
     */
    public function filter($image, $filter, $p = false )
	{

        if ( $this->library == self::LIB_GD )
        {
            /*
            * PHP GD
            */

            switch ( $filter )
            {

                case "tint":
                    $p = $this->hex2rgb( $p );

                    /* Tint filter */
                    if($filter == "tint")
                    {
                        imagefilter( $image, IMG_FILTER_NEGATE );
                        imagefilter( $image, IMG_FILTER_COLORIZE, 255-$p[0], 255-$p[1], 255-$p[2] );
                        imagefilter( $image, IMG_FILTER_NEGATE );
                    }
                break;

                default:
                    throw new Exception("This filter is not supported on this platform!", self::ERR_UNSUPPORTED_FILTER);

            }

        }
        else
        {
            /*
            * IMAGICK
            */

            switch ( $filter )
            {

                case "tint":
                    $p = $this->hex2rgb($p);

                    $clut = new Imagick();
                    $clut->newPseudoImage(255, 1, "gradient:black-rgb(" . ($p[0]) . "," . ($p[1]) . "," . ($p[2]) . ")");

                    $image->clutImage($clut);
                break;

                default:
                    throw new Exception("This filter is not supported on this platform!", ERR_UNSUPPORTED_FILTER);

            }

        }

        return;
	}




    /**
     * Resize an image to the given size
     *
     * @param     $image  The object to resize
     * @param int $size   The size of the final image
     *
     * @return object
     */
    public function resize($image, $size = 512)
	{

        if ( $this->library == self::LIB_GD )
        {
            /*
            * PHP GD
            */

            $resized = $this->create($size, $size, true);
            imagecopyresampled( $resized, $image, 0, 0, 0, 0, $size, $size, 1024, 1024 );

            return $resized;

        }
        else
        {
            /*
            * IMAGICK
            */

            $image->resizeImage ($size, $size,Imagick::FILTER_LANCZOS,1 );

            return $image;

        }

	}



    /**
     * Apply an overlay (Text or image) over an image
     *
     * @param        $image    The base object
     * @param bool   $overlay  The path of the overlay image. If false apply a text
     * @param string $text     The string for the text overlay
     */
    public function overlay($image, $overlay = false, $text = "InochiTeam")
	{

        if($overlay)
            $source = $this->load( $overlay );

        if ( $this->library == self::LIB_GD )
        {
            /*
            * PHP GD
            */

            if($overlay)
    		{
    			imagecopy( $image, $source, 0, 0, 0, 0, 1024,1024 );

    			return;
    		}

    		$white = imagecolorallocatealpha($image, 255, 255, 255, 50);
    		$grey = imagecolorallocate($image, 34, 34, 34);

    		imagefilledrectangle($image, 0 , 908 , 1024 , 974, $white);
    		Imagettftext($image, 26, 0, 50, 954, $grey, 'fonts/Lato-Semibold.ttf', $text);

        }
        else
        {
            /*
            * IMAGICK
            */

    		if($overlay)
    		{
                $image->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
                $image->setImageArtifact('compose:args', "1,0,-0.5,0.5");
                $image->compositeImage($source, $source->getImageCompose(), 0, 0);

    			return;
    		}

    		/* Define colors */
            $white = new ImagickPixel("rgb(255, 255, 255)");
            $grey = new ImagickPixel("rgb(34, 34, 34");


            /* Draw the rectangle */
            $draw = new ImagickDraw();
            $draw->setFillColor($white);
            $draw->setFillOpacity(0.5);
            $draw->rectangle(0, 908, 1024, 974);
            $image->drawImage($draw);


            /* Write the text */
            $draw = new ImagickDraw();
            $draw->setFillColor($grey);
            $draw->setFont('fonts/Lato-Semibold.ttf');
            $draw->setFontSize( 30 );
            $image->annotateImage($draw, 50, 954, 0, $text);

        }

	}



    /**
     * Convert an hex color in to a rgb array
     *
     * @param $color An hex color with starting #
     *
     * @return array
     */
    public function hex2rgb($color )
    {
        return sscanf($color, "#%02x%02x%02x");
    }



    /**
     * Output the image into the desired format
     *
     * @param       $image    The object to render
     * @param       $ext      The extension of the final image
     * @param mixed $dest     If a valid destination path is provided the image will be saved there. Set to false for live output
     * @param int   $quality  Quality setting for the jpg format
     * @param bool  $encoding If tru the image will be encoded in base64
     */
    public function render( $image, $ext, $dest = false, $quality = 90 )
	{
        if ( $this->library == self::LIB_GD )
        {
            /*
            * PHP GD
            */

            $renderer = "image".( ( $ext == 'jpg' ) ? "jpeg" : $ext );

            // To compress png images we need to check if zlib is installed
            if( $ext == "png" )
            {
               if ( function_exists("gzcompress") )
                    $quality = 9 ;
               else
                  $quality = null;
            }

            // No compression for gif images
            if( $ext == "gif" )
                $quality = null;

            if( $dest )
            {
                $renderer($image, $dest, $quality);
            }
            else
            {
                header('Content-Type: image/' . $ext );
                $renderer($image, null, $quality);
            }
        }
        else
        {
            /*
            * IMAGICK
            */

            if( $ext == "jpg")
                $image->setImageCompressionQuality($quality);

            if( !$dest )
            {

                $image->setImageFormat($ext);
                header("Content-type: image/" . $ext );
                echo $image->getImageBlob();

                return;
            }

            $image->writeImage($dest);
        }

	}

 }

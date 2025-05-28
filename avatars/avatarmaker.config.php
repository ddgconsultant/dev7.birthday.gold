<?php
/**
 * AvatarMaker 3.x By InochiTeam
 *
 * @updated       17/10/2018 (3.2.1 Elsie)
 */



class HT_AvatarMaker_config
{

    //-----------------------------------------------------------------------//
    // UI SETTINGS
    //-----------------------------------------------------------------------//

    /**
     * The name of your brand to display in the app
     *
     * @var string
     */
    public $app_brand = "birthday.gold AvatarMaker!";


    /**
     * The name of the language file for the app
     *
     * @var string
     */
    public $app_local = "en-us";


    /**
     * Display "Made by InochiTeam" in the app ( This is appreciated but completely optional )
     *
     * @var boolean
     */
    public $app_displayCredits = true;


    /**
     * If true, the mobile UI will display the item in line rather than in column,
     * this makes the interface more compact but the user has to scroll sideways
     *
     * @var boolean
     */
    public $app_compactUi = false;


    /**
     * Generate a random avatar once the app is loaded
     *
     * @var boolean
     */
    public $app_randStartup = true;


    /**
     * Update the items previews when generating a random avatar
     *
     * @var boolean
     */
    public $app_randUpdateColors = true;


    /**
     * Remove the background options and output avatars with a transparent background
     * This is available only when the output is set to PNG
     *
     * @var boolean
     */
    public $app_transparentBackground = false;


    /**
     * Display the color picker and let the users choose any color they want for any palette
     *
     * @var boolean
     */
    public $app_allowCustomColors = true;



    //-----------------------------------------------------------------------//
    // FOLDERS PATHS
    //-----------------------------------------------------------------------//

    /**
     * Relative or absolute path to the assets folder
     *
     * @var string
     */
    public $folder_assets = "assets/";


    /**
     * Relative or absolute path to the local files folder
     *
     * @var string
     */
    public $folder_locals = "local/";


    /**
     * Relative or absolute path to the cache folder
     *
     * @var string
     */
    public $folder_cache = "cache/";



    //-----------------------------------------------------------------------//
    // RENDERER SETTINGS
    //-----------------------------------------------------------------------//

    /**
     * The rendering library to use for the final image either "gd" for phpGD or "magick" for imagemagick
     *
     * If your server supports imagemagick, use it. It is marginally slower but its memory is not shared with PHP
     * and it may offers better results.
     *
     * @var string
     */
    public $renderer_driver = "gd";


    /**
     * The output format for the final image
     *
     * png, jpg, gif - for live images
     * png_saved, jpg_saved, gif_saved - if you want to save the avatar on the server instead of letting the user download it
     *
     * @var string
     */
    public $renderer_format = "png";


    /**
     * If the output format is jpg or jpg_saved, this will set the quality of the final image
     *
     * @var integer
     */
    public $renderer_quality = 90;


    /**
     * This control the overlay applied to the rendered images
     *
     * false - No overlay
     * true - Image overlay found in the assets folder named overlay.png
     * string - A text that will be written in the bottom left corner of the image
     *
     * @var mixed
     */
    public $renderer_overlay = false;


    /**
     * The sizes in pixels of the base images
     *
     * @var integer
     */
    public $renderer_assetsSizes = array(
                                            'x' => 1024,
                                            'y' => 1024
                                     );


    /**
     * The size in pixels of the cache images
     * Leave the Y value to false and it will be calculated automatically
     *
     * @var integer
     */
    public $renderer_cacheSizes = array(
                                            'x' => 400,
                                            'y' => false
                                     );


    //-----------------------------------------------------------------------//
    // AVATAR SETTINGS
    //-----------------------------------------------------------------------//

    /**
     * The layers and the color palettes they will use
     *
     * The order here will be followed when rendering the avatars. The first in the list will be the lowestmost layer.
     *
     * @var array
     */
    public $avatar_layers = array(
        "background" => "background",
        "ears"       => "skin",
        "head"       => "skin",
        "eyes"       => false,
        "eyebrows"   => "hair",
        "nose"       => false,
        "mouth"      => false,
        "hair"       => "hair",
        "objects"    => "objects"
    );

    /**
     * The list of all fetured items by layer
     *
     * @var array
     */
    public $avatar_featured_items = array(
        "head"       => ["face_1","face_6","face_10"],
        "eyes"       => ["eyes_5"],
    );


    /**
     * The color palettes with their IDs
     *
     * @var array
     */
    public $avatar_palettes = array(
        "background" => array(
                            "#ffffff",
                            "#A2B4EE",
                            "#9DC6A6",
                            "#B3D6C0",
                            "#F1DAF5",
                            "#F5B9AC",
                            "#AC9FF9",
                            "#FBEAC3",
                            "#7FBEB2",
                            "#333333"
                        ),
        "skin"       => array(
                            "#F4F4F4",
                            "#f6d9cb",
                            "#FCD8C5",
                            "#efc0a4",
                            "#d68d6a",
                            "#c98558",
                            "#edb886",
                            "#FFE6B1",
                            "#5b3c28",
                            "#9A8479",
                            "#73635A",
                            "#47484A",
                            "#ab47bc",
                            "#29b6f6",
                            "#ff7043",
                            "#9ccc65"
                        ),
        "hair"       => array(
                            "#84532a",
                            "#653521",
                            "#272b2a",
                            "#ffd900",
                            "#990000",
                            "#e1cb9a",
                            "#a94631",
                            "#3399ff",
                            "#ff99f4",
                            "#006766"
                        ),
        "objects"    => array(
                            "#F4F4F4",
                            "#333333",
                            "#FFDE00",
                            "#0069B1",
                            "#26A9E0",
                            "#BC2026",
                            "#E90A8A",
                            "#009345",
                            "#8BC53F"
                        )
    );


}

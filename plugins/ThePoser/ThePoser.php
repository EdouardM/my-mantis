<?php

require_once dirname( dirname( dirname(__FILE__) ) ).DIRECTORY_SEPARATOR.'core/http_api.php'; ///

class ThePoserPlugin extends MantisPlugin {
    function register() {
        $this->name = 'The Poser';    # Proper name of plugin
        $this->description = 'So you can explain to your boss why Mantis is better. (Look matters after all)';    # Short description of the plugin
        $this->page = 'config';           # Default plugin page

        $this->version = '1.1';     # Plugin version string
        $this->requires = array(    # Plugin dependencies, array of basename => version pairs
            'MantisCore' => '1.2.0',  #   Should always depend on an appropriate version of MantisBT
            );

        $this->author = 'Agave Storm Inc.';         # Author/team name
        $this->contact = 'agavestorm@gmail.com';        # Author/team e-mail address
        $this->url = 'http://agavestorm.com/the-poser-for-mantis/';            # Support webpage
    }
    
    function hooks() {
        return array(
            'EVENT_LAYOUT_RESOURCES' => 'initlook',
	    'EVENT_MENU_MAIN_FRONT' => 'beforeMenu',
	    'EVENT_LAYOUT_BODY_BEGIN' => 'bodyBegin',
	    'EVENT_LAYOUT_PAGE_HEADER' => 'afterMaintisLogo',
	    'EVENT_PLUGIN_INIT' => 'setupHeaders',
        );
    }
    
    function setupHeaders($p_event) {
	    global $g_bypass_headers;
	    if ( !$g_bypass_headers && !headers_sent() ) {
			http_content_headers();
			http_caching_headers();
			header( 'X-Frame-Options: DENY' );
		$t_avatar_img_allow = '';
		if ( config_get_global( 'show_avatar' ) ) {
			if ( $_SERVER['REQUEST_SCHEME'] == 'https' ) {
				$t_avatar_img_allow = "; img-src 'self' https://secure.gravatar.com:443";
			} else {
				$t_avatar_img_allow = "; img-src 'self' http://www.gravatar.com:80";
			}
		}
		header( "X-Content-Security-Policy: allow 'self'; img-src *; options inline-script eval-script$t_avatar_img_allow; frame-ancestors 'none'" );
			http_custom_headers();
		}
	    $g_bypass_headers = true;
    }
    
    function bodyBegin($p_event) {
	$classes ='';
	if(plugin_config_get('skin') == 2) {
	    $this->theCity();
	}
	if(plugin_config_get('headerHeight') != '2' && plugin_config_get('showCompanyLogo')) {
		if(!auth_is_user_authenticated()) {
			$classes .= ' poserNoAuth';
		}
		if(plugin_config_get('headerHeight') == '1') {
			$classes .=' poserSmallHeader';
		}
		if(plugin_config_get('headerHeight') == '2') {
			$classes .=' poserTinyHeader';
		}
	?>
	<div class="poserHeader <?php echo $classes; ?>">
		<a href="<?php echo plugin_config_get('companyUrl');?>" title="<?php echo plugin_config_get('companyName'); ?>" target="_blank">
			<?php 
			$imgdata = plugin_config_get('companyLogo');
			if(!empty($imgdata)) {
				?><img src="<?php echo $imgdata;?>" alt="<?php echo plugin_config_get('companyName'); ?>"/><?php
			} else {
				echo plugin_config_get('companyName'); 
			}
			?>
		</a>
	</div>
	<?php } ?>
	<div class="mantisLogo <?php echo $classes; ?>">
	<?php
    }
    
    function afterMaintisLogo($p_event) {
	    ?></div><?php
    }
    
    function beforeMenu($p_event) {
	    if(plugin_config_get('headerHeight') != '2') {
		    return;
	    }
	    $favicon = helper_mantis_url(config_get( 'favicon_image' ));
	    $companyName = plugin_config_get('companyName');
	    $imgdata = plugin_config_get('companyTinyLogo');
	    ?>
	    <span  class="tinyheader">
		<a href="<?php echo helper_mantis_url('my_view_page.php'); ?>">
			<img src="<?php echo $favicon; ?>"/>
		</a>
		
	    </span>
		<?php if(plugin_config_get('showCompanyLogo')) { ?>
			<span class="tinyheader-right">
				<a href="<?php echo plugin_config_get('companyUrl');?>" title="<?php echo plugin_config_get('companyName'); ?>" target="_blank">
					<img src="<?php echo $imgdata;?>" alt="<?php echo plugin_config_get('companyName'); ?>"/>
				</a>
			</span>
		<?php 
		}
    }
    
    function initlook($p_event) {
	    $header = plugin_config_get('headerHeight');
	    $skin = plugin_config_get('skin');
        ?>
        <link rel="stylesheet" type="text/css" href="<?php echo plugin_file( 'main.css' ); ?>"/>
	<link rel="stylesheet" type="text/css" href="<?php echo plugin_file( 'header-'.$header.'.css' ); ?>"/>
	<link rel="stylesheet" type="text/css" href="<?php echo plugin_file( 'skin-'.$skin.'.css' ); ?>"/>
	<link rel="stylesheet" type="text/css" href="<?php echo plugin_page( 'css' ); ?>"/>
        <?php
    }
    
    function config() {
        return array(
            'customLogo' => '',
	    'headerHeight' => 0,// default=0, small=1, tiny=2
	    'companyName' => 'setup you company name and logo',
	    'companyUrl' => plugin_page('config'),
	    'companyLogo' => '',
	    'companyTinyLogo' => '',
	    'customCss' => '',
	    'skin'=>0,
	    'showCompanyLogo' => true,
        );
    }
    
    function showImagickWarning() {
	    if(!class_exists('Imagick')) {
		    ?>
	<div class="poserWarning">
		Image Magick not found, auto resize for uploaded images won't work. Please, install and activate php-imagick or php-pecl-imagick extension.
	</div>
		<?php
	    }
    }
    
    function getImageForSaving($file, $size) {
		if(empty($file['tmp_name'])) {
			throw new Exception('no file');
		}
		$uploaded = $file['tmp_name'];
		$filecontent = 'data:'.$file['type'].';base64,'.base64_encode(self::resizeImage($uploaded,$size,$file['type']));
		return $filecontent;
    }
    
    function resizeImage($filename, $size, $mime) {
	    if(!class_exists('Imagick')) {
		return file_get_contents($filename);
	    }
	    $format = str_replace('image/','',$type);
	    $width = $size[0];
	    $height = $size[1];
	    $image = file_get_contents($filename);
	    $canvas = new Imagick();
		$canvas->readImageBlob($image);
		$canvas->setImageFormat(str_replace('image/','',$mime));
		if($canvas->getImageHeight() > $height) {
			$canvas->thumbnailImage($width, $height);
		}
		return $canvas->getImageBlob();
    }
    
    function theCity() {
	    $classes = '';
	    if(plugin_config_get('headerHeight') == '2') {
			$classes .=' poserTinyHeader';
		}
	    $buildings = $_SESSION['buildings'];
	    if(empty($buildings)) {
		$countBuildings = 1500;
		for($i=0;$i<$countBuildings;$i++) {
			$buildings[] = array(
			    'height' => rand(10,100),
			    'width' => rand(5,50),
			);
		}
		$_SESSION['buildings'] = $buildings;
	    }
	    
	    $stars = $_SESSION['stars'];
	    if(empty($stars)) {
		    $stars = array();
		$countStars = 1000;
		for($i=0;$i<$countStars;$i++) {
			$stars[] = array(
			    'x' => rand(1,3000),
			    'y' => rand(1,200),
			    'r'=>rand(200,255),
			    'g'=>rand(200,255),
			    'b'=>rand(200,255),
			    'opacity'=>rand(10,100)/100,
			);
		}
		$_SESSION['stars'] = $stars;
	    }
	    
	    ?>
	<div class="stars-container">
		<?php foreach($stars as $star) {
			?>
		<div class="star" style=" 
		     left:<?php echo $star['x'];?>px;
		     top:<?php echo $star['y'];?>px;
		     background: rgba(<?php echo $star['r'].','.$star['g'].','.$star['b'].','.$star['opacity'];?>);
		     "></div>
			<?php
		}?>
		<div class="star-hider"></div>
	</div>
	<div class="building-container <?php echo $classes;?>">
		<?php foreach($buildings as $building) {
			?>
		<div class="building buildingGradient" style="
		     height: <?php echo $building['height'];?>px; 
		     margin-top: <?php echo 100-$building['height'];?>px;
		     width: <?php echo $building['width'];?>px;"></div>
			<?php
		}?>
	</div>
		<?php
    }
}

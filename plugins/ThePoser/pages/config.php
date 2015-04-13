<?php
auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

$headerHeightOptions = array('Default', 'Small', 'Tiny');
$skinOptions = array('poser Default', 'Flat','MantisMan');
$currentHeader = plugin_config_get('headerHeight');
$currentSkin = plugin_config_get('skin');
$customCss = plugin_config_get('customCss');
$showCompanyLogo = plugin_config_get('showCompanyLogo');

html_page_top();
ThePoserPlugin::showImagickWarning();
?>
<div class="poserConfig">
<form action="<?php echo plugin_page( 'config_update' ) ?>" method="post" enctype="multipart/form-data">
<?php echo form_security_field( 'plugin_Example_config_update' ) ?>

	<h2>Header style</h2>
	<select name="headerHeight">
		<?php foreach($headerHeightOptions as $key=>$value) {
			?>
		<option value="<?php echo $key; ?>"
			<?php if($key == $currentHeader) {
				?> selected="yes"<?php
			} ?>
			><?php echo $value; ?></option>
			<?php
		}?>
	</select>
	<br/>
	
	<h2>Skin</h2>
	
	<div class="skins">
		<?php foreach($skinOptions as $key=>$value) { ?>
			<div class="skin">
				<h3>
				<input type="radio" name="skin" value="<?php echo $key; ?>"
				       <?php if($key == $currentSkin) {
						?> checked="yes"<?php
					} ?>
				       />
				<?php echo $value; ?>
				</h3>
				<div class="skin-thumb">
					<img src="<?php echo plugin_file( 'img/skin-'.$key.'.png' );?>"/>
				</div>
			</div>
		<?php  } ?>
		<div class="clear"></div>
	</div>
	<br/>
	
	<h2>Show company logo</h2>
	<input type="checkbox" name="showCompanyLogo" 
	       <?php
	       if($showCompanyLogo) {
		       ?> checked="yes"<?php
	       }
	       ?>
	       /><br/>
	
	<h2>Your company name</h2>
	<input type="text" name="companyName" value="<?php echo plugin_config_get('companyName'); ?>"/><br/>
	
	<h2>Your company website</h2>
	<input type="text" name="companyUrl" value="<?php echo plugin_config_get('companyUrl');?>"/><br/>
	
	<h2>Custom logo</h2>
	<?php
	$imgdata = plugin_config_get('companyLogo');
	if(!empty($imgdata)) {
		?><br/><img src="<?php echo $imgdata;?>" alt="<?php echo plugin_config_get('companyName'); ?>"/><br/><?php
	}
	?>
	<input type="file" name="customLogo"/><br/>
	
	<h2>Custom logo for tiny view (16px*16px)</h2>
	<?php
	$imgdata = plugin_config_get('companyTinyLogo');
	if(!empty($imgdata)) {
		?><br/><img src="<?php echo $imgdata;?>" alt="<?php echo plugin_config_get('companyName'); ?>"/><br/><?php
	}
	?>
	<input type="file" name="customTinyLogo"/><br/>
	
	<h2 for="customCss">Custom CSS rules</h2><br/>
	<textarea name="customCss"><?php echo $customCss; ?></textarea><br/>
	
	<label><input type="checkbox" name="reset_logo"/> Remove Logo</label><br/>
	<label><input type="checkbox" name="reset_tiny_logo"/> Remove Tiny Logo</label><br/>
<label><input type="checkbox" name="reset"/> Reset</label>
<br/>
<input type="submit" value="Apply Changes"/>

</form>
</div>
<?php

html_page_bottom();


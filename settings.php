<div id="sidelines-settings" class="wrap pure-skin-sidelines">
   <div class="icon32" id="icon-tools"> <br /> </div>
   <h2>Sidelines Widget Settings</h2>
   <?php settings_errors(); ?>
   <div style="margin-top: 1em"></div>
   <form method="post" action="options.php" class="pure-form pure-form-stacked">

<?php
settings_fields('sidelines_options');
do_settings_sections('sidelines_options', true);
?>
    <fieldset>
      <legend>Basic Settings</legend>

      <label for="publisher_code">
         <strong>Publisher Code</strong>
      </label>
      <input id="publisher_code" type="text" name="sidelines_publisher_code" value="<?php echo get_option('sidelines_publisher_code'); ?>" size="25">
      <aside class="pure-form-message">If you don't have one, <a href="http://sidelinesapp.com/publisher">sign up here</a></aside>

      <div style="margin-top: 1em"></div>
      <label for="display-mode">
         <strong>Plugin Display Mode</strong>
      <select id="display-mode" name="sidelines_mode" style="height: 50px;">
         <option value="content" <?php selected(get_option('sidelines_mode'), "content", true)?> >Right After Main Content</option>
         <option value="comments" <?php selected(get_option('sidelines_mode'), "comments", true)?> >Replace Commenting System</option>
      </select>
      </label>

      <div style="margin-top: 1em"></div>
      <label for="sidelines-enabled" class="pure-checkbox">
         <input id="sidelines-enabled" type="checkbox" name="sidelines_enabled" value="true"
            <?php if (get_option('sidelines_enabled', true) == "true") echo "checked"; ?> >
         Sidelines Enabled 
      </label>

      <div style="margin-top: 2em"></div>
      <input type="submit" name="submit" id="submit" class="pure-button pure-button-primary" value="Save Changes">
      <a href="http://sidelinesapp.com/publisher/settings" class="pure-button"  target="_blank">Advanced Settings</a>
      </fieldset>

    </form>
</div>



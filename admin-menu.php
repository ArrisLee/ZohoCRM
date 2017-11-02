<?php

/*
 * Administration menu for Contact Form 7: Zoho Leads
 */

function cf7zl_admin_notices() {
  echo "
    <div id='notice' class='updated fade'>
      <p>" . CF7ZL_PLUGIN_NAME . " is not configured yet</p>
    </div>";
}

function cf7zl_remove_admin_notices() {
  remove_action('admin_notices', 'cf7zl_admin_notices');
}

function cf7zl_options_page() {
  if( !current_user_can('manage_options') )
    wp_die(__('You do not have sufficient permissions to access this page.'));
  // Check if the form has been POSTed
  if( isset($_POST[CF7ZL_POST_HIDDEN_SUBMIT]) &&
      $_POST[CF7ZL_POST_HIDDEN_SUBMIT] == 'Y' ) {
    check_admin_referer('cf7zl-admin-options');
    update_option(CF7ZL_OPT_AUTHTOKEN, $_POST[CF7ZL_OPT_AUTHTOKEN]);
    echo "<div class='updated'><p><strong>Settings updated</strong></p></div>";
  }
  // Display the settings page form
  ?>
  <h1><?php echo CF7ZL_PLUGIN_NAME; ?></h1>
  <form name="form1" method="post" action="">
    <?php wp_nonce_field('cf7zl-admin-options'); ?>
    <input type="hidden"
           name="<?php echo CF7ZL_POST_HIDDEN_SUBMIT; ?>"
           value="Y" />
    <p style="padding-top: 20px; padding-bottom: 20px;">
      <strong>API Authentication Token</strong><br />
      <input type="password"
             name="<?php echo CF7ZL_OPT_AUTHTOKEN; ?>"
             placeholder="Authentication Token"
             value="<?php echo get_option(CF7ZL_OPT_AUTHTOKEN, ''); ?>"
             size="32" />
      <br />
      <span>
        Click
        <a target="_blank" href="<?php echo CF7ZL_AUTHTOKEN_URL; ?>">here</a>
        to generate and display a new token.
      </span>
    </p>
    <p class="submit">
      <input type="submit"
             name="Submit"
             class="button-primary"
             value="<?php esc_attr_e('Update') ?>" />
    </p>
  </form>
  <br />
  <div>
    <h2>Shortcode <em>[cf7lead]</em> Guide</h2>
    <h3>Options</h3>
    <table class="widefat fixed" cellspacing="0">
      <thead>
        <tr>
          <th class="manage-column column-columnname">
            Option name
          </th>
          <th class="manage-column column-columnname">
            Required
          </th>
          <th class="manage-column column-columnname">
            Default
          </th>
          <th class="manage-column column-columnname">
            Description
          </th>
        </tr>
      </thead>

      <tbody>
        <tr>
          <td>cf7_id</td>
          <td>Yes</td>
          <td></td>
          <td>ID of the Contact Form 7 form</td>
        </tr>
        <tr class="alternate">
          <td>debug</td>
          <td>No</td>
          <td>false</td>
          <td>If "true", the plugin will display error messages on screen</td>
        </tr>
        <tr>
          <td>fields</td>
          <td>No</td>
          <td>''</td>
          <td>
            This is a '|' (pipe) separated list of '=' (equal) separated strings.
            For instance, 'Company=company|First Name=first-name|Last Name=' is a
            valid fields string to use. It says to map the Zoho Leads field "Company"
            to the CF7 field "company" (everything here is case sensitive), the Zoho Leads
            field "First Name" to "first-name", and to not map "Last Name" to anything
            (which means that the Zoho Lead field "Last Name" will have no value).
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php
}

function cf7zl_admin_menu() {
  $hook_suffix = add_options_page(
    'Contact Form 7: Zoho Leads options',
    'Contact Form 7: Zoho Leads',
    'manage_options',
    'cf7_zoho_leads_options',
    'cf7zl_options_page'
  );

  if( !get_option(CF7ZL_OPT_AUTHTOKEN, null) )
     add_action('admin_notices', 'cf7zl_admin_notices');
  add_action('load-' . $hook_suffix, 'cf7zl_remove_admin_notices');
}

add_action('admin_menu', 'cf7zl_admin_menu');

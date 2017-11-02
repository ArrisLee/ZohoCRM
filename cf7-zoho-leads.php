<?php
/*
Plugin Name: Contact Form 7: Zoho Leads
Plugin URI: https://developer.wordpress.org/plugins/cf7-zoho-leads
Description: Zoho Leads integration for Contact Form 7 forms
Version: 0.0.5
Author: Joshua Cornutt
Author URI: https://joscor.com
License: MIT
*/

const CF7ZL_PLUGIN_NAME = 'Contact Form 7: Zoho Leads integration';
const CF7ZL_AUTHTOKEN_URL = 'https://accounts.zoho.com/apiauthtoken/create?SCOPE=ZohoCRM/crmapi';
const CF7ZL_POST_HIDDEN_SUBMIT = 'cf7zl_submit';
const CF7ZL_POST_HIDDEN_FIELDS = 'cf7zl_user_fields';
const CF7ZL_OPT_AUTHTOKEN = 'cf7zl_authtoken';

include_once( plugin_dir_path(__FILE__) . 'libs/logger.php');
include_once( plugin_dir_path(__FILE__) . 'admin-menu.php');
include_once( plugin_dir_path(__FILE__) . 'libs/zoho-crm-api.php');

global $LOG;
$LOG = new SimpleLogger(SimpleLogger::DEBUG);


function cf7zl_init_scripts() {
  global $LOG;
  $LOG->debug('Entered function: %s:%s()', __FILE__, __FUNCTION__);
  $LOG->info('Enqueuing jQuery script');
  wp_enqueue_script('jquery');
  $LOG->debug('Exiting function: %s:%s()', __FILE__, __FUNCTION__);
}

/**
 * Callback for the wpcf7_before_send_mail action.
 *
 * Captures submitted data from a specific Contact Form 7
 * form and uses the information to insert a record (lead)
 * to Zoho Leads.
 *
 * @param WPCF7_ContactForm $contact_form
 *
 * @return void
 */
function cf7zl_cf7_email_hook($contact_form) {
  global $LOG;
  $LOG->debug('Entered function: %s:%s()', __FILE__, __FUNCTION__);
  $zoho_authtoken = get_option(CF7ZL_OPT_AUTHTOKEN, null);
  // Bail if there's no token
  if( !$zoho_authtoken )
    return;
  // Get the CF7 posted data and title
  $cf7_data = WPCF7_Submission::get_instance()->get_posted_data();
  $cf7_title = $contact_form->title();
  $zoho = new Zoho($zoho_authtoken);
  $leads = array();
  // Get the user-defined overrides
  $cf7zl_fields = $cf7_data[CF7ZL_POST_HIDDEN_FIELDS];
  // Build the default map
  $map = array(
    "Company" => "company",
    "First Name" => "first-name",
    "Last Name" => "last-name",
    "Email" => "email",
    "Phone" => "phone",
    "Title" => "title"
  );
  
  // Build the user-defined map
  $overrides = array();
  foreach (explode('|', $cf7zl_fields) as $chunk) {
      list($key, $val) = explode('=', $chunk);
      if( empty($key) ) continue;
      if( empty($val) ) $val = null;
      $overrides[$key] = $val;
  }
  // Map default CF7 fields to Zoho Leads fields
  foreach( $map as $k => $v )
    $lead[$k] = $cf7_data[$v] ?: null;
  // Map user-defined CF7 fields to Zoho Leads fields
  foreach( $overrides as $k => $v )
    $lead[$k] = (is_null($v) ? null : ($cf7_data[$v] ?: null));
  // Assign the CF7 title as the Lead Source
  $lead["Lead Source"] = $cf7_title ?: null;
  $LOG->info('Inserting record in Zoho Leads');
  $LOG->debug('Zoho API request: %s', print_r($leads, true));
  $response = $zoho->insert_record(array($leads));
  $LOG->debug('Zoho API response: %s', print_r($response, true));
  $LOG->debug('Exiting function: %s:%s()', __FILE__, __FUNCTION__);
}

/**
 * Callback for the wpcf7_form_hidden_fields filter.
 *
 * Injects custom hidden fields into the Contact Form 7
 * form. In this case, the "fields" attribute from the
 * Zoho Leads shortcode (string) is saved for recall
 * by cf7_email_hook().
 *
 * @param Array $array Key/value pairs of hidden field names and values
 *
 * @return Array
 */
function cf7zl_filter_wpcf7_form_hidden_fields($array) {
  global $cf7zl_fields;
  $array[CF7ZL_POST_HIDDEN_FIELDS] = $cf7zl_fields;
  return $array;
}

/**
 * Callback for using the "cf7lead" shortcode.
 *
 * Collects shortcode attributes, prepares the user-defined
 * "fields" string (mapping overrides) to be consumed by
 * filter_wpcf7_form_hidden_fields(), and displays the
 * Contact Form 7 form.
 *
 * @param Array $atts Key/value pairs of shortcode attributes
 *
 * @return String Contact Form 7 HTML form
 */
function cf7zl_build_shortcode($atts) {
  global $LOG;
  $LOG->debug('Entered function: %s:%s()', __FILE__, __FUNCTION__);
  $LOG->info('Building shortcode');
  $LOG->debug('Shortcode options(pre): %s', print_r($atts, true));
  $scopts = shortcode_atts(array(
    'cf7_id' => '',
    'debug' => false,
    'fields' => ''), $atts);
  $LOG->debug('Shortcode options(post): %s', print_r($scopts, true));
  // Check for the Zoho API authentication token (admin settings)
  if( !get_option(CF7ZL_OPT_AUTHTOKEN, null) ) {
    $LOG->error('Zoho API authentication token not found!');
    return ($scopts['debug'] ? 'Authentication token must be set before use' : '');
  }
  // Save the shortcode fields for later
  global $cf7zl_fields;
  $cf7zl_fields = $scopts['fields'];
  // Send the user-defined fields as a hidden input
  add_filter('wpcf7_form_hidden_fields', 'cf7zl_filter_wpcf7_form_hidden_fields', 10, 1);
  // Check that the CF7 form exists (by ID)
  global $wpcf7_contact_form;
  if( !($wpcf7_contact_form = wpcf7_contact_form($scopts['cf7_id'])) ) {
    $LOG->error('Could not find CF7 form with ID "%s"', $scopts['cf7_id']);
    return ($scopts['debug'] ? "Could not find Contact Form 7 by ID" : '');
  }
  $LOG->info('Building CF7 form HTML');
  return $wpcf7_contact_form->form_html();
}

add_action('wp_enqueue_scripts', 'cf7zl_init_scripts');
add_shortcode('cf7lead', 'cf7zl_build_shortcode');
add_action('wpcf7_before_send_mail', 'cf7zl_cf7_email_hook', 10, 1);

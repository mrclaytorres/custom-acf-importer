<?php

/*
Plugin Name:  FAQ ACF Importer
Plugin URI:   https://github.com/mrclaytorres/custom-acf-importer
Description:  Plugin to Turn csv data into ACF Repeater
Author:       mrclaytorres
Version:      1.0
*/

// Referenced from https://medium.com/@alexjeffers/importing-data-from-a-csv-file-into-a-wordpress-acf-repeater-field-c411c642a54c

function faq_acf_menu() {
  add_management_page( __( 'FAQ Import Dashboard', 'textdomain' ), __( 'FAQ Import Plugin', 'textdomain' ), 'read', 'faq-import-acf', 'show_custom_meta_box_2' );
}

add_action('admin_menu', 'faq_acf_menu');

//showing custom form fields
function show_custom_meta_box_2()
{
    global $post;

    // Use nonce for verification to secure data sending
    wp_nonce_field(basename(__FILE__), 'wpse_our_nonce');

    // Display the "Import" button and a hidden field to trigger the import
    ?>
    <form id="import-form" method="post" enctype="multipart/form-data">
      <!-- my custom value input -->
      <p>Upload CSV File to create faqs</p>
      <input type="file" name="faq_data" value="">
      <?php //submit_button('Import', 'primary', 'import_faq_data', false); ?>
      <input type="submit" class="button-primary" value="Import">
      <?php wp_nonce_field('import_action', 'import_nonce'); // Add nonce for security ?>
      <input type="hidden" name="action" value="custom_import_action">
    </form>

    <div id="csv-output"></div>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- JavaScript to handle the form submission -->
    <script>
        jQuery(document).ready(function($) {
            $('#import-form').submit(function(e) {
              e.preventDefault(); // Prevent default form submission
              
              var formData = new FormData(this); // Get form data
              console.log('formData', formData);

              // AJAX request
              $.ajax({
                  url: ajaxurl, // WordPress AJAX URL
                  type: 'POST',
                  data: formData,
                  processData: false,
                  contentType: false,
                  success: function(response) {
                    console.log('response', response);
                    // Display the echoed CSV data on the page
                    $('#csv-output').html(response);
                  }
              });
              
              // Reset the file input value
              $('#faq_data').val('');

            });
        });
    </script>
    
    <?php
}

// Add your AJAX handler in your plugin or theme
add_action('wp_ajax_custom_import_action', 'custom_import_action');
add_action('wp_ajax_nopriv_custom_import_action', 'custom_import_action'); // For non-logged-in users

//now we are saving the data
function custom_import_action() {

  check_ajax_referer('import_action', 'import_nonce'); // Verify nonce for security
  
  // Check if the import action is triggered
  // Handle CSV import logic
  if (!empty($_FILES['faq_data']['name'])) {
    // $csv_data = file_get_contents($_FILES['faq_data']['tmp_name']);

    $upload = wp_upload_bits($_FILES['faq_data']['name'], null, file_get_contents($_FILES['faq_data']['tmp_name']));

    if (($file = fopen($upload['url'], 'r')) !== false ) {
      $header_skipped = false;

      while (($line = fgetcsv($file)) !== false) {
        // Skip the header row
        if (!$header_skipped) {
          $header_skipped = true;
          continue;
        }

        // // Process the CSV data (you can customize this part)
        // // For now, we'll simply echo the CSV data back
        // echo '<pre>' . htmlspecialchars($line[1]) . '</pre>';

        // Process the CSV data for each row
        // $csv_row_data = implode(', ', $line); // Example: convert array to comma-separated string
        $csv_row_data = $line[0];
        $csv_row_data2 = $line[1];
        
        // echo $csv_row_data . ' | ' . $csv_row_data2 . '<br>';

        // Starts uploading data at row in index 2
        $i = 0;
        $x = 2;
        $y = 3;
        while (!empty($line[$x])) {
          echo 'Importing ' . $line[$x] . '</br>';
          //$line is an array of the csv elements
          update_post_meta($line[0], 'question_and_answer_' . $i . '_question', $line[$x] );
          update_post_meta($line[0], '_question_and_answer_'. $i . '_question', '<QUESTION SUB-FIELD KEY>');
          update_post_meta($line[0], 'question_and_answer_'. $i .'_answer', $line[$y] );
          update_post_meta($line[0], '_question_and_answer_'. $i .'_answer', '<ANSWER SUB-FIELD KEY>');
          $x += 2;
          $y += 2;
          $i++;
          
        }
        update_post_meta($line[0], 'question_and_answer', $i);
        update_post_meta($line[0], '_question_and_answer', '<QUESTION_AND_ANSWER FIELD KEY>');
      }
      fclose($file);

      
    }

  } else {
      echo 'empty file';
  }
  // Return a response (you can return JSON or any other content)
  echo '<br>Import successful!';

  // return; // Stop further execution of the function after import

  // Always exit at the end of an AJAX function
  wp_die();
    
}
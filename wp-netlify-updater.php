<?php
/*
Plugin Name: Wp Netlify Updater
Plugin URI: http://www.example.com/plugin
Description: WordPress plugins Netlify build hook when updating posts
Author: yahsan2
Version: 0.1
Author URI: https://github.com/yahsan2
*/
class WpNetlifyUpdater {
    function __construct() {
      $this->version = '0.1';
      $this->name = 'Wp Netlify Updater';
      $this->slug = 'wp-netlify-updater';
      $this->prefix = 'wpnu_';

      $this->set_options();

      if(is_admin()){
        add_action('save_post', array($this, 'wp_save_post'));
        add_action('edit_term', array($this, 'wp_edit_term'));
        add_action('admin_menu', array($this, 'add_menu'));
      }
    }

    function wp_save_post( $post_id ) {
      $title = get_the_title($post_id);
      $modified_user = $this->get_modified_author($post_id);
      $this->netlify_webhooks( $title . ' updated by ' . $modified_user->display_name , 'Triggered by wp hook: save_post');
    }

    function wp_edit_term( $term_id, $tt_id, $taxonomy ) {
      $term = get_term($term_id, $taxonomy);
      $this->netlify_webhooks( $term->name, 'Triggered by wp hook: edit_term' );
    }

    function get_modified_author($post_id) {
      $last_id = get_post_meta( $post_id, '_edit_last', true );
      if ( $last_id ) {
        return get_userdata( $last_id );
      }
    }

    function netlify_webhooks($title, $hook = 'Triggered by hook: WordPress') {
      if(isset($this->webhook_url) && $this->webhook_url){
        $parsed_url = parse_url($this->webhook_url);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        $title = $title ? $title : get_bloginfo('name');
        $output['trigger_title'] = $hook. ' | ' . $title;
        $url = $base_url . '?' . http_build_query($output);

        $output = array();
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query( $output ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
      }
    }

    function add_menu() {
      add_submenu_page('options-general.php', $this->name, $this->name,  'level_8', __FILE__, array($this,'option_page'), '');
    }

    function set_options(){
      $opt = get_option( $this->prefix.'option');
      if(isset($opt)){
        $this->webhook_url = $opt['webhook_url'];
      }else{
        $this->webhook_url = null;
      }
    }

    function option_page() {
      if ( isset($_POST[ $this->prefix.'option'])): ?>
      <?php
          check_admin_referer( $this->slug );
          $opt = $_POST[ $this->prefix.'option'];
          update_option( $this->prefix.'option', $opt);
      ?>
      <div class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div>
      <?php endif; ?>
      <div class="wrap">
        <h2><?php echo $this->name ?></h2>
        <form action="" method="post">
            <?php
            wp_nonce_field( $this->slug );
            $this->set_options();
            ?>
            <table class="form-table">
                <tr valign="top">
                    <td scope="row">
                      <label for="input_url">Netlify Incoming Webhooks</label>
                      <p>Docs: <a href="https://www.netlify.com/docs/webhooks/" target="_blank">https://www.netlify.com/docs/webhooks/</a></p>
                    </td>
                    <td>
                      <p>URL:</p>
                      <input name="<?php echo $this->prefix; ?>option[webhook_url]" type="text" id="input_url" value="<?php  echo $this->webhook_url ?>" class="regular-text" placeholder="https://api.netlify.com/build_hooks/xxxxxxxxxxxxxxxxxxxxxxxx" />
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
        </form>
      </div>
      <!-- /.wrap -->
      <?php
    }
}

global $wp_netlify_updater;
$wp_netlify_updater = new WpNetlifyUpdater();
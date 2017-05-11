<?php

class Polylang_SDL_Admin_Actions {

    private $verbose = false;
    private $API;
    public $messages;

    public function __construct(){
        $this->API = new Polylang_SDL_API(true);
        $this->messages = array();
        if(isset($_GET['override'])) {
            $_POST['action'] = $_GET['action'];
        }
        if(isset($_POST['action'])) {
            $this->process_action();
        }
    }
    public function verbose($msg, $array = null) {
        if($this->verbose === true) {
            echo '<b>Console: </b>'. $msg .'<br />';
            if($array != null) {
                var_dump($array);
            }
        }
    }

    private function process_action(){
        switch ($_POST['action']) {
            case 'sdl_update_account_details':
                $this->action_update_account_options();
                break;
            case 'sdl_update_generalsettings':
                $this->action_update_general_settings();
                break;
            case 'sdl_create_project_quick':
                $this->action_create_project_quick($_POST['id']);
                break;
            case 'sdl_create_project':
                $this->action_create_project();
                break;
            case 'sdl_update_single':
                $this->action_update_single();
                break;
            case 'sdl_update_all':
                $this->action_update_all();
                break;
            case 'sdl_admin_updateoptions':
                $blog_id = intval( $_POST['blog_id'] );
                $id = $_POST['sdl_settings_projectoption'];
                $pairs = get_site_option('sdl_settings_projectoptions_pairs');
                update_blog_option($blog_id, 'sdl_settings_projectoption', $id);
                update_blog_option($blog_id, 'sdl_settings_projectoptions_sourcelang', strtolower($pairs[$id]['Source'][0]));
                break;
            case 'sdl_admin_refreshoptions' :
                update_site_option('sdl_settings_projectoptions_all', null);
                update_site_option('sdl_settings_projectoptions_pairs', null);
                break;
            case 'sdl_admin_togglenetwork' :
                $network_toggle = get_site_option('sdl_settings_networktoggle');
                if($network_toggle === true || $network_toggle == 1) {
                    update_site_option('sdl_settings_networktoggle', false);
                } else {
                    update_site_option('sdl_settings_networktoggle', true);
                }
                break;
        }
    }

    private function setup_page(){
        if(isset($_GET['override'])) {
            $this->tabs = array(
                $_GET['tab'] => ucfirst(str_replace('_', ' ', $_GET['tab']))
            );
            $this->current_tab = $_GET['tab'];
        } else {
            $this->register_tabs();
            $this->set_default();
        }
        $this->display_page();
    }
    private function is_SDL_manager(){
        if(is_network_admin() ||  get_site_option('sdl_settings_networktoggle') == 1 || !is_multisite() ) {
            $this->verbose('We are SDL manager');
            return true;
        } else {
            $this->verbose('We are not SDL manager');
            return false;
        }
    }
    private function action_update_single(){
        $args = array(
            'ProjectOptionsID' => $_GET['project_options'],
            'SrcLang' => $_GET['src_lang'],
            'Targets' => array($_GET['target_lang'])
        );
        $response = $this->API->translation_create($_GET['src_id'], $args);
        if($response['key'] == 'translation_success') {
            $reply = array('update_success' => 1);
        } else {
            $reply = array('update_error' => $response['translation_error']);
        }
        if(isset($_GET['redirect_to'])) {
            wp_redirect(
                add_query_arg(
                    $reply,
                    $_GET['redirect_to']
                )
            );
        }
    }
    private function action_update_all(){
        $post_model = new Polylang_SDL_Model;
        $map = $post_model->get_source_map($_GET['src_id']);
        $success = $fail = $total = 0;
        foreach($map['children'] as $lang) {
            if($lang != null) {
                $args = array(
                    'ProjectOptionsID' => $lang['produced_by'],
                    'SrcLang' => $_GET['src_lang'],
                    'Targets' => array($lang['locale'])
                );
                $response = $this->API->translation_create($_GET['src_id'], $args);
                if($response['key'] == 'translation_success') {
                    $success++;
                } else {
                    $fail++;
                }
                $total++;
            }
        }
        if($success == $total) {
            $reply = array('update_success_total' => $total);
        } elseif($success > 0) {
            $reply = array('update_success_partial' => array($success, $total));
        } else {
            $reply = array('update_error_total' => $response['translation_error']);
        }
        if(isset($_GET['redirect_to'])) {
            wp_redirect(
                add_query_arg(
                    $reply,
                    $_GET['redirect_to']
                )
            );
        }
    }
    private function action_create_project_quick($id){
        $args = array(
            'ProjectOptionsID' => get_option('sdl_settings_projectoption'),
            'SrcLang' => strtolower(get_option('sdl_settings_projectoptions_sourcelang')),
            'Targets' => array(sdl_get_post_language($id, 'locale'))
        );
        $response = $this->API->translation_create($id, $args);
    }
    private function action_create_project(){
        $args = array(
            'Name' => $_POST['name'],
            'Description' => $_POST['description'],
            'ProjectOptionsID' => $_POST['ProjectOptionsID'],
            'SrcLang' => $_POST['SrcLang'],
            'Due date' => date('Y-m-d\TH:i:s.Z\Z', strtotime($_POST['Due_date'])),
            'Targets' => $_POST['TargetLangs'],
        );
        /*
        if($_POST['TmSequenceId'] != null) {
            $args['TmSequenceId'] = $_POST['TmSequenceId'];
        };
        if($_POST['Vendors'] != null) {
            $args['Vendors'] = $_POST['Vendors'];
        } */
        $response = $this->API->translation_create($_POST['id'], $args);
        wp_redirect(
            add_query_arg(
                $response,
                admin_url('edit.php')
            )
        );
    }

    private function action_update_account_options() {
      if($this->API->testCredentials($_POST['sdl_settings_account_username'], $_POST['sdl_settings_account_password'])){
        $options = array('sdl_settings_account_username', 'sdl_settings_account_password');
        foreach ($options as $option) {
            if (isset($_POST[$option]) && $option === 'sdl_settings_account_password') {
                $output = openssl_encrypt($_POST[$option], 'AES-256-CBC', hash('sha256', wp_salt()), 0, substr(hash('sha256', 'managedtranslation'), 0, 16));
                $output = base64_encode($output);
                update_site_option($option, $output);
            } else if (isset($_POST[$option])) {
                update_site_option($option, $_POST[$option]);
            } else {
                delete_site_option($option);
            }
        }
        $this->messages['success'] = __('Authentication successful.', 'managedtranslation');
      } else {
        $this->messages['error'] = __('Unable to login using credentials provided.', 'managedtranslation');
      }
    }
    private function action_update_general_settings() {
        $options = array('sdl_settings_projectoption');
        foreach ($options as $option) {
            if (isset($_POST[$option])) {
                update_site_option($option, $_POST[$option]);
            } else {
                delete_site_option($option);
            }
        }
        $pairs = get_site_option('sdl_settings_projectoptions_pairs');
        update_option('sdl_settings_projectoptions_sourcelang', strtolower($pairs[$_POST['sdl_settings_projectoption']]['Source'][0]));
    }
}
?>

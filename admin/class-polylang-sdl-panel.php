<?php  

class Polylang_SDL_Admin_Panel {
    private $current_tab;
    private $tabs;
    private $verbose = false;
    private $API;

    public function __construct(){
        $this->API = new Polylang_SDL_API(true);
        if(isset($_POST['action'])) {
            $this->process_action();
        }
        $this->setup_page();
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
            case 'sdl_create_project':
                $this->action_create_project();
                break;
            case 'sdl_admin_updateoptions':
                $blog_id = intval( $_POST['blog_id'] );
                $id = $_POST['options_set'];
                $pairs = get_site_option('sdl_settings_projectoptions_pairs');
                update_blog_option($blog_id, 'sdl_projectoptions', $id);
                update_blog_option($blog_id, 'sdl_projectoptions_sourcelang', strtolower($pairs[$id]['Source'][0]));
                break;
            case 'sdl_admin_refreshoptions' :
                update_site_option('sdl_settings_projectoptions', null);
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
    private function register_tabs(){
        $tabs = array();
        if($this->is_SDL_manager()){
            $tabs['account'] = 'Account Details';
        }
        if($this->API->test_loggedIn()){
            if(is_network_admin()) {
                $tabs['network'] = 'Network Management';
            }
        }
        $this->tabs = $tabs;
    }
    private function set_default(){
        if(isset( $_GET['tab'] ) ) {  
            $this->current_tab = $_GET[ 'tab' ];  
        } else if(isset( $this->tabs['network'] )) {
            $this->current_tab = 'network';
        } else if(isset( $this->tabs['account'] )) {
            $this->current_tab = 'account';
        } else {
            if($this->API->test_loggedIn()){
                $this->error_code = 403;
            } else {
                $this->error_code = 401;
            }
            $this->current_tab = false;
        }
    }
    public function display_page(){
        echo '<div id="sdl_settings" class="wrap">';
        echo '<h2>SDL Managed Translation for Polylang</h2>';
        settings_errors();
        if($this->current_tab != false) {
            echo '<div class="wp-filter">';
            echo '<ul class="filter-links">';
            foreach($this->tabs as $name => $display) {
                $this->output_menu($name, $display);
            }
            echo '</ul>';
            echo '</div>';
            $this->output_panel();
        } else {
            echo $this->output_panel();
        }
        echo '</div>';
        return $output;
    }
    private function output_menu($name, $display){
        $output .= '<li><a href="?page=managedtranslation&tab='. $name .'"';
        if($this->current_tab == $name) {
            $output .= 'class="current"';
        }
        $output .= '>' . $display . '</a></li>';
        echo $output;
    }
    private function output_panel(){
        switch($this->current_tab) {
            case 'network':
                if( ! class_exists( 'SDL_Sites_Table' ) ) {
                    require_once('class-polylang-sdl-admin–network–sites.php' );
                }
                $sitesTable = new SDL_Sites_Table();
                $sitesTable->prepare_items();
                $sitesTable->display();
                break;
            case 'account':
                echo "<form id='account_details' action='admin.php?page=managedtranslation' method='post' class='buttonform'>";
                    echo '<input type="hidden" name="action" value="sdl_update_account_details" />';
                    echo '<h2>Account details</h2>';
                    echo '<table class="form-table">';
                    echo '<tr>';
                        echo '<th><label for="sdl_settings_account_username">Username</label></th>';
                            echo '<td><input type="text" name="sdl_settings_account_username" value="'. get_site_option('sdl_settings_account_username') . '" /></td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<th><label for="sdl_settings_account_password">Password</label></th>';
                            echo '<td><input type="password" name="sdl_settings_account_password" /></td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<th><button type="submit" form="account_details" class="button button-primary">Login to Managed Translation</button></th>';
                    echo '</tr>';
                echo '</form>';
                break;
            case false:
                $url = network_admin_url('admin.php?page=managedtranslation&tab=account');
                $output .= '<form>';
                $this->print_error();
                $output .= '</form>';
                echo $output;
                break;
            case 'create_project':
                $this->build_create_project_panel();
                break;
            default:
                break;
        }
    }
    private function print_error(){
        switch ($this->error_code) {
            case 401:
                $output .= '<h2>Unable to connect to SDL Managed Translation</h2>';
                $output .= '<p>Please ask network administrator to visit the <a href="'. $url .'">Network Settings page</a> to complete setup</p>';
                break;
            case 403:
                $output .= '<h2>Permission denied</h2>';
                $output .= '<p>SDL Managed Translation settings are being managed by network administrator. Please contact them for support.</p>';
                break;
        }
        echo $output;
    }
    public function build_create_project_panel(){
        $ids = explode(',', $_GET['posts']);
        if(sizeof($ids) > 1) {
            $name = 'Bulk translation – ' . date('H:i jS M');
            $description = 'Features '. sizeof($ids) .' posts, including:&#013; &#010;';
            $SrcLang = array();
            foreach($ids as $id) {
                $description .= '- ' . get_the_title($id) . '&#013;';
                $lang = pll_get_post_language($id);
                if($lang != false && !array_key_exists($lang, $SrcLang)) {
                    $SrcLang[] = $lang;
                }
            }
        } else {
            $name = get_the_title($ids[0]);
            $description = 'Project created ' . date('d/m/y');
            $SrcLang = pll_get_post_language($ids[0]);
        }
        if($this->is_SDL_manager()) {
            $PIDs = get_site_option('sdl_settings_projectoptions');
        } else {
            $PID = get_option('sdl_projectoptions');
            $PIDs = get_site_option('sdl_settings_projectoptions');
            $offset = array_search($PID, array_column($PIDs, 'Id'));
        }
        $date = date('Y-m-d', strtotime("+1 week")); 
        echo "<form id='create_project' action='admin.php?page=managedtranslation' method='post' class='buttonform'>";
            echo '<input type="hidden" name="action" value="sdl_create_project" />';
            echo '<input type="hidden" name="id" value="'. $_GET['posts'] .'" />';
            echo '<table class="form-table">';
            echo '<tr>';
                echo '<th><label for="name">Project name</label></th>';
                    echo '<td><input name="name" type="text" class="regular-text" value="'. $name .'"></input></td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="description">Project description</label></th>';
                    echo '<td><textarea name="description" type="text" cols="45" rows="6">'. $description .'</textarea></td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="ProjectOptionsID">Project options set</label></th>';
                    echo '<td>';
                    if($this->is_SDL_manager()) {
                        echo '<select name="ProjectOptionsID" class="regular-text" form="create_project">';
                        foreach($PIDs as $option) {
                            echo '<option value="'. $option['Id'] .'">'. $option['Name'] .'</option>';
                        }
                        echo '</select>';
                    } else {
                        echo '<select name="ProjectOptionsID" class="regular-text" form="create_project">';
                            echo '<option value="'. $PID .'" selected>'. $PIDs[$offset]['Name'] .'</option>';
                        echo '</select>';
                        echo '<p class="description">Project Options assigned by network administrator</p>';
                    }
                    echo '</td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="SrcLang">Source language</label></th>';
                    echo '<td>';                        
                    if($this->is_SDL_manager()) {
                    } else {
                        $langs = get_option('sdl_projectoptions_sourcelang');
                        if(is_array($langs)) {
                            echo '<select name="SrcLang" form="create_project">';
                            foreach($langs as $lang) {
                                echo '<option value="'. $lang .'">'. $lang .'</option>';
                            } 
                            echo '</select>';
                        } else {
                            echo '<select name="SrcLang" form="create_project">';
                            echo '<option value="'. $langs .'" selected>'. $langs .'</option>';
                            echo '</select>';
                        }
                    }
                        echo '<p class="description">Each project may only translate from <strong>one</strong> source language</p>';
                    echo '</td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="TargetLangs">Target languages</label></th>';
                    echo '<td>';                        
                        if($this->is_SDL_manager()) {
                        } else {
                            $language_sets = get_site_option('sdl_settings_projectoptions_pairs')[$PID];
                            echo '<select name="TargetLangs[]" form="create_project" multiple="true">';
                            foreach($language_sets['Target'] as $language) {
                                echo '<option value="'. $language .'">'. $language .'</option>';
                            } 
                            echo '</select>';
                        }
                        echo '<p class="description">Each project may only translate from <strong>one</strong> source language</p>';
                    echo '</td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="date">Due date</label></th>';
                    echo '<td><input name="Due date" type="date" inputmode="numeric" value="'. $date .'"></input></td>';
            echo '</tr>';
            if($this->is_SDL_manager()) {
            } elseif(sizeof($PIDs[$offset]['TmSequences']) > 1){
                echo '<tr>';
                    echo '<th><label for="TmSequenceId">TM Sequence</label></th>';
                    echo '<td>';
                        echo '<select name="TmSequenceId" form="create_project">';
                        foreach($PIDs[$offset]['TmSequences'] as $sequence) {
                            echo '<option value="'. $sequence['Id'] .'">'. $sequence['Name'] .'</option>';
                        } 
                        echo '</select>';
                    echo '</td>';
                echo '</tr>';
            }
            if($this->is_SDL_manager()) {
            } elseif(sizeof($PIDs[$offset]['Vendors']) > 1){            
                echo '<tr>';
                    echo '<th><label for="Vendors">Vendors</label></th>';
                    echo '<td>';    
                        echo '<select name="Vendors" form="create_project">';
                        foreach($PIDs[$offset]['Vendors'] as $vendor) {
                            echo '<option value="'. $vendor .'">'. $vendor .'</option>';
                        } 
                        echo '</select>';
                    echo '</td>';
                echo '</tr>';
            }
            echo '<tr>';
                echo '<th><button type="submit" form="create_project" class="button button-primary">Create project</button></th>';
            echo '</tr>';
            echo '</table>';
        echo '</form>';
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
        if($_POST['TmSequenceId'] != null) {
            $args['TmSequenceId'] = $_POST['TmSequenceId'];
        };
        if($_POST['Vendors'] != null) {
            $args['Vendors'] = $_POST['Vendors'];
        }
        $id = explode(',', urldecode($_POST['id']));
        $convertor = new Polylang_SDL_Create_XLIFF();
        $args['Files'] = $convertor->package_post($id, $args);
        $response = $this->API->project_create($args);
        if(is_array($response)) {
            $inprogress = get_option('sdl_translations_inprogress');
            if($inprogress == null) {
                $inprogress = array();
            }
            $inprogress[] = $response['ProjectId'];
            update_option('sdl_translations_inprogress', $inprogress);
            wp_redirect(
                add_query_arg(
                    array(
                        'page' => 'edit.php',
                        'translation_success' => count( $id ),
                        ), 
                    admin_url('admin.php')
                )
            );
        }
    }

    private function action_update_account_options() {
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
    }
}
?> 
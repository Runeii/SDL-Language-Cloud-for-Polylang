<?php  

class Polylang_SDL_Admin_Panel {
    private $current_tab;
    private $tabs;
    private $verbose = true;
    private $API;

    public function __construct(){
        $this->API = new Polylang_SDL_API(true);
        $this->register_tabs();
        $this->set_default();

        $this->display_page();
    }
    public function verbose($msg, $array = null) {
        if($this->verbose === true) {
            echo '<b>Console: </b>'. $msg .'<br />';
            if($array != null) {
                var_dump($array);
            }
        }
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
        print '<div id="sdl_settings" class="wrap">';
        print '<h2>SDL Managed Translation for Polylang</h2>';
        settings_errors();
        if($this->current_tab != false) {
            print '<div class="wp-filter">';
            print '<ul class="filter-links">';
            foreach($this->tabs as $name => $display) {
                $this->output_menu($name, $display);
            }
            print '</ul>';
            print '</div>';
            $this->output_panel();
        } else {
            print $this->output_panel();
        }
        print '</div>';
        return $output;
    }
    private function output_menu($name, $display){
        $output .= '<li><a href="?page=managedtranslation&tab='. $name .'"';
        if($this->current_tab == $name) {
            $output .= 'class="current"';
        }
        $output .= '>' . $display . '</a></li>';
        print $output;
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
                echo "<form action='edit.php?action=sdl_settings_update_network_options' method='post'>";
                    echo '<h2>Account details</h2>';
                    settings_fields( 'sdl_settings_account_page' );
                    do_settings_sections( 'sdl_settings_account_page' );
                    submit_button('Login to Managed Translation');
                echo '</form>';
                break;
            case false:
                $url = network_admin_url('admin.php?page=managedtranslation&tab=account');
                $output .= '<form>';
                $this->print_error_code();
                $output .= '</form>';
                print $output;
                break;
            default:
                break;
        }
    }
    private function print_error_code(){
        switch ($this->error_code) {
            case 401:
                $output .= '<h2>Unable to connect to SDL Managed Translation</h2>';
                $output .= '<p>Please ask network administrator to visit the <a href="'. $url .'">Network Settings page</a> to complete setup</p>';
                break;
            case 403:
                $output .= '<h2>Permission denied</h2>';
                $output .= '<p>SDL Managed Translation settings are being managed by network administrator</p>';
                break;
        }
        print $output;
    }

}
?> 
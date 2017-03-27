<?php  

class Polylang_SDL_Admin_Panel {
    private $current_tab;
    private $tabs;
    private $verbose = false;
    private $API;

    public function __construct(){
        $this->API = new Polylang_SDL_API(true);
        if(isset($_GET['override'])) {
            //Add tab to array, tidy up user facing display name
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
        /* $args should be an array(
            'ProjectOptionsID' => 'ID of the options set for this project !required',
            'SrcLang' => 'Source language !required', <!---- we should check this
            'TmSequenceId' => TM sequence identifier,
            'Vendors' => Sets the vendor ID for this project,
            'Due date' => When the project is due
        ) */
    public function build_create_project_panel(){
        $ids = explode(',', $_GET['posts']);
        if(sizeof($ids) > 1) {
            $name = 'Bulk translation – ' . date('H:i jS M');
            $description = 'A group of posts, including:&#013; &#010;';
            foreach($ids as $id) {
                $description .= '- ' . get_the_title($id) . '&#013;';
            }
        } else {
            $name = get_the_title($ids[0]);
            $description = '';
        }
        $date = '31.08.1989';
        var_dump($description);
        echo "<form action='edit.php?action=create_project' method='post'>";
            echo '<table class="form-table">';
            echo '<tr>';
                echo '<th><label for="name">Project name</label></th>';
                    echo '<td><input name="name" type="text" class="regular-text" value="'. $name .'"></input></td>';
            echo '<tr>';
            echo '</tr>';
                echo '<th><label for="description">Project description</label></th>';
                    echo '<td><textarea name="description" type="text" cols="45" row="5">'. $description .'</textarea></td>';
            echo '<tr>';
            echo '</tr>';
                echo '<th><label for="date">Due date</label></th>';
                    echo '<td><input name="Due date" type="date" inputmode="numeric" value="'. $date .'"></input></td>';
                echo '<submit>Create project</submit>';
            echo '</tr>';
            echo '</table>';
        echo '</form>';
    }
}
?> 
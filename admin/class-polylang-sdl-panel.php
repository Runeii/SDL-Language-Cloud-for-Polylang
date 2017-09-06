<?php

class Polylang_SDL_Admin_Panel {
    private $current_tab;
    private $tabs;
    private $verbose = false;
    private $API;
    private $parent;
    private $loggedin;

    public function __construct($parent){
        $this->parent = $parent;
        $this->API = new Polylang_SDL_API(true);
        if($this->API->test_loggedIn()){
          $this->loggedin = true;
        } else {
          $this->loggedin = false;
        }
        $this->setup_page();
    }
    public function verbose($msg, $array = null) {
    	if($this->verbose === true) {
    		var_dump('Error: ' . $msg, $array);
    	}
    }
    private function setup_page(){
        $this->register_tabs();
        $this->set_default();
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
        $tabs['welcome'] = 'Welcome';
        if(($this->is_SDL_manager() && is_network_admin()) || !is_multisite()){
            $tabs['account'] = 'Account Details';
        }
        if($this->loggedin == true){
            if(is_network_admin()) {
                $tabs['network'] = 'Network Management';
            } elseif($this->is_SDL_manager()){
                $tabs['settings'] = 'General settings';
            }
        }
        $this->tabs = $tabs;
    }
    private function set_default(){
        if(isset( $_GET['tab'] ) ) {
            $this->current_tab = $_GET[ 'tab' ];
        } else if(isset( $this->tabs['network'] )) {
            $this->current_tab = 'network';
          } else if(isset( $this->tabs['settings'] )) {
              $this->current_tab = 'settings';
          } else if(isset( $this->tabs['account'] )) {
            $this->current_tab = 'welcome';
        } else {
            if($this->loggedin == true){
              $this->error_code = 403;
            } else {
              $this->error_code = 401;
            }
            $this->current_tab = false;
        }
    }
    public function display_page(){
      $output = '';
      echo '<div id="sdl_settings" class="wrap">';
      echo '<h2>SDL Managed Translation for Polylang</h2>';
      settings_errors();
      if(sizeof($this->parent->admin_actions->messages) > 0) {
        echo '<div class="notice notice-'. key($this->parent->admin_actions->messages) .'">';
          echo '<h2>' . ucwords(key($this->parent->admin_actions->messages)) . '</h2>';
          echo '<p>'. reset($this->parent->admin_actions->messages) .'</p>';
        echo '</div>';
      }
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
        $output = '';
        $output .= '<li><a href="?page=managedtranslation&tab='. $name .'"';
        if($this->current_tab == $name) {
            $output .= 'class="current"';
        }
        $output .= '>' . $display . '</a></li>';
        echo $output;
    }
    private function output_panel(){
        switch($this->current_tab) {
            case 'welcome':
              $this->build_welcome();
              break;
            case 'network':
                if( ! class_exists( 'SDL_Sites_Table' ) ) {
                    require_once('class-polylang-sdl-admin–network–sites.php' );
                }
                $sitesTable = new SDL_Sites_Table($this->parent);
                $sitesTable->prepare_items();
                $sitesTable->display();
                break;
            case 'settings':
                echo "<form id='general_settings' action='admin.php?page=managedtranslation' method='post' class='buttonform'>";
                    echo '<input type="hidden" name="action" value="sdl_update_generalsettings" />';
                    echo '<h2>General settings</h2>';
                    echo '<table class="form-table">';
                    echo '<tr>';
                        echo '<th><label for="sdl_settings_projectoptions">Default project options</label></th>';
                            echo '<td>'.$this->parent->filter_project_options().'</td>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<th><button type="submit" form="general_settings" class="button button-primary">Save</button></th>';
                    echo '</tr>';
                echo '</form>';
                break;
            case 'account':
                echo "<form id='account_details' action='admin.php?page=managedtranslation' method='post' class='buttonform'>";
                  if($this->loggedin == true) {
                    echo '<div class="updated notice notice-success">';
                      echo '<h2>' . __( 'Logged in', 'managedtranslation' ) . '</h2>';
                      echo '<p>'. __( 'Currently connected to SDL Managed Translation service as ', 'managedtranslation' ) . '<strong>' . get_site_option('sdl_settings_account_username', true) . '</strong></p>';
                    echo '</div>';
                  }
                  echo '<h2>Account details</h2>';
                  echo '<table class="form-table">';
                  if($this->loggedin !== true) {
                    echo '<input type="hidden" name="action" value="sdl_update_account_details" />';
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
                  } else {
                    echo '<tr>';
                      echo '<input type="hidden" name="action" value="sdl_logout" />';
                      echo '<td><button type="submit" form="account_details" class="button button-primary">Logout</button></td>';
                    echo '</tr>';
                  }
                  echo '</table>';
                echo '</form>';
                break;
            case false:
                $output = '';
                $output .= '<form>';
                $output .= $this->print_error();
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
      $output = '';
      switch ($this->error_code) {
        case 401:
          $url = network_admin_url('admin.php?page=managedtranslation&tab=account');
          $output .= '<h2>Unable to connect to SDL Managed Translation</h2>';
          $output .= '<p>Please ask network administrator to visit the <a href="'. $url .'">Network Settings page</a> to complete setup</p>';
          break;
        case 403:
          $output .= '<h2>Permission denied</h2>';
          $output .= '<p>SDL Managed Translation settings are being managed by network administrator. Please contact them for support.</p>';
          break;
      }
      return $output;
    }
    public function build_create_project_panel(){
        $ids = explode(',', $_GET['posts']);
        if(sizeof($ids) > 1) {
            $name = 'Bulk translation – ' . date('H:i jS M');
            $description = 'Features '. sizeof($ids) .' posts, including:&#013; &#010;';
            $SrcLang = array();
            foreach($ids as $id) {
                $description .= '- ' . get_the_title($id) . '&#013;';
                $lang = sdl_get_post_language($id);
                if($lang != false && !array_key_exists($lang, $SrcLang)) {
                    $SrcLang[] = $lang;
                }
            }
        } else {
            $name = get_the_title($ids[0]);
            $description = 'Project created ' . date('d/m/y');
            $SrcLang = sdl_get_post_language($ids[0]);
        }
        if($this->is_SDL_manager()) {
            $PIDs = get_site_option('sdl_settings_projectoptions_all');
        } else {
            $PID = get_option('sdl_settings_projectoption');
            $PIDs = get_site_option('sdl_settings_projectoptions_all');
            $offset = array_search($PID, array_column($PIDs, 'Id'));
        }
        $availableLangs = array();
        foreach(pll_languages_list(array('fields' => 'locale')) as $lang) {
          $availableLangs[] = strtolower(format_locale($lang));
        };
        $date = date('Y-m-d', strtotime("+1 week"));
        echo "<form id='create_project' action='admin.php?page=managedtranslation' method='post' class='buttonform'>";
            echo '<input type="hidden" name="action" value="sdl_create_project" />';
            echo '<input type="hidden" name="id" value="'. $_GET['posts'] .'" />';
            echo "<input type='hidden' id='available_languages' name='languages' data-langs='". json_encode($availableLangs) ."' />";
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
                        echo '<select name="ProjectOptionsID" class="regular-text" id="PID_dropdown" form="create_project">';
                            echo '<option value="blank" selected>– Select project options set –</option>';
                        foreach($PIDs as $option) {
                            echo '<option value="'. $option['Id'] .'">'. $option['Name'] .'</option>';
                        }
                        echo '</select>';
                    } else {
                        echo '<select name="ProjectOptionsID" class="regular-text" form="create_project">';
                            echo '<option value="'. $PID .'" selected>'. $PIDs[$offset]['Name'] .'</option>';
                        echo '</select>';
                        echo '<p class="description">Project Options set has been assigned by network administrator</p>';
                    }
                    echo '</td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="SrcLang">Source language</label></th>';
                    echo '<td>';
                    if($this->is_SDL_manager()) {
                        //In this situation, we dynamically update form using jQuery
                        echo '<select name="SrcLang" form="create_project" id="src_langs">';
                        echo '<option value="blank" selected>Please first select a Project Options set</option>';
                        echo '</select>';
                    } else {
                        $langs = get_option('sdl_settings_projectoptions_sourcelang');
                        if(is_array($langs)) {
                            echo '<select name="SrcLang" form="create_project">';
                            echo '<option value="blank" selected>Available source languages</option>';
                            foreach($langs as $lang) {
                              if(in_array(strtolower($lang), $availableLangs)) {
                                echo '<option value="'. $lang .'">'. $lang .'</option>';
                              } else {
                                echo '<option value="'. $lang .'" disabled>'. $lang .'</option>';
                              }
                            }
                            echo '</select>';
                        } else {
                            echo '<select name="SrcLang" form="create_project">';
                            echo '<option value="blank" selected>Available source languages</option>';
                            if(in_array(strtolower($langs), $availableLangs)) {
                              echo '<option value="'. $langs .'">'. $langs .'</option>';
                            } else {
                              echo '<option value="'. $langs .'" disabled>'. $langs .'</option>';
                            }
                            echo '</select>';
                        }
                    }
                        echo '<p class="description">Each project may only translate from <strong>one</strong> source language</p>';
                    echo '</td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="TargetLangs">Target languages</label></th>';
                    echo '<td>';
                        echo "<div id='TargetLangs'>";
                        if($this->is_SDL_manager()) {
                            //In this situation, we dynamically update form using jQuery
                        } else {
                            $language_sets = get_site_option('sdl_settings_projectoptions_pairs')[$PID];
                            foreach($language_sets['Target'] as $language) {
                              if(in_array(strtolower($language), $availableLangs)) {
                                echo '<input type="checkbox" name="TargetLangs[]" value="' . $language .'">';
                              } else {
                                echo '<input type="checkbox" name="TargetLangs[]" value="' . $language .'" disabled>';
                              }
                              echo '<label for="' . $language .'">' . $language .'</label>';
                            }
                        }
                        echo '</div>';
                    echo '</td>';
            echo '</tr>';
            echo '<tr>';
                echo '<th><label for="date">Due date</label></th>';
                    echo '<td><input name="Due date" type="date" inputmode="numeric" value="'. $date .'"></input></td>';
            echo '</tr>';
            /*
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
            } */
            echo '<tr>';
                echo '<th><button type="submit" id="create_button" form="create_project" class="button button-primary" disabled>Create project</button></th>';
            echo '</tr>';
            echo '</table>';
        echo '</form>';
    }
  function build_welcome(){ ?>
    <div class="wrap about-wrap">
        <img src="<?php echo plugin_dir_url( __FILE__ ); ?>/img/sdl_header_image.jpg" />
		    <h1>Welcome to SDL Managed Translation for Polylang</h1>
		    <div class="about-text">Thank you for installing the SDL Managed Translation plugin for WordPress</div>
				<div id="welcome-panel" class="welcome-panel sdl-welcome">
					<div class="welcome-panel-content">
						<h3>To start translating your site, simply follow these 3 steps</h3>
						<div class="welcome-panel-column-container">
							<div class="welcome-panel-column">
                <span class="dashicons dashicons-admin-users"></span>
								<p>Register your free SDL Managed Translation account</p>
                <a href="https://languagecloud.sdl.com/en/managed-translation/register" class="button button-primary">Register</a>
							</div>
							<div class="welcome-panel-column">
                <span class="dashicons dashicons-admin-network"></span>
								<p>Login from the plugin Account Details tab to link your new Managed Translation account to your WordPress site</p>
                <a href="<?php echo network_admin_url('admin.php?page=managedtranslation&tab=account'); ?>" class="button button-primary">Visit tab</a>
							</div>
							<div class="welcome-panel-column welcome-panel-last">
                <span class="dashicons dashicons-translation"></span>
								<p>Start managing your multilingual web content and translation projects from within WordPress</p>
							</div>
						</div>
					</div>
				</div>
        <hr class="sdl-divider"></hr>
        <div class="sdl-blurb">
          <h2>Key benefits of the SDL Managed Translation plugin for WordPress</h2>
          <p class="about-text">Remember, the SDL Managed Translation plugin for WordPress is your hassle-free way to improve translation quality, accelerate speed to market of translated content and enhance the performance of your WordPress sites.</p>
          <div class="feature">
            <h4>Deliver quicker</h4>
            <p>Our simple but powerful platform is proven to cut the time to market for translated content</p>
          </div>
          <div class="feature">
            <h4>Save money</h4>
            <p>Our smart Translation Memory means you’ll never pay to translate the same phrase twice as you build your translation memory and control terminology</p>
          </div>
          <div class="feature">
            <h4>Improve quality and consistency</h4>
            <p>Our expert ISO certified translation service is delivered through a robust and secure cloud platform</p>
          </div>
          <div class="feature">
            <h4>Gain control</h4>
            <p>Use your SDL interface to track project progress, manage spend and identify savings</p>
          </div>
        </div>
    <?php
  }
}
?>

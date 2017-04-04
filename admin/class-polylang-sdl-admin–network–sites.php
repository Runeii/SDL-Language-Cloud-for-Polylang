<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SDL_Sites_Table extends WP_List_Table
{
    private $options;
    private $pairs;
    private $Polylang;
    private $network_toggle;

    public function prepare_items()
    {
        $this->network_toggle = get_site_option('sdl_settings_networktoggle');
        echo '<form method="post" action="admin.php?page=managedtranslation" class="buttonform">';
        echo '<input type="hidden" name="action" value="sdl_admin_togglenetwork" />';
        if($this->network_toggle === true || $this->network_toggle == 1) { 
            echo '<button class="button button-secondary">Enable Network-level management</button>';
        } else {
            echo '<button class="button button-primary">Disable Network-level management</button>';
        }
        echo '</form>';
        if($this->network_toggle === true || $this->network_toggle == 1) { 
            die();
        };
        echo '<form method="post" action="admin.php?page=managedtranslation" class="buttonform">';
        echo '<input type="hidden" name="action" value="sdl_admin_refreshoptions" />';
        echo '<button class="button button-primary">Refresh project options</button>';
        echo '</form>';
        $this->options = get_site_option('sdl_settings_projectoptions_all');
        if($this->options === null || $this->options === false) {
            $API = new Polylang_SDL_API();
            $this->options = $API->user_options();
        }
        $this->pairs = get_site_option('sdl_settings_projectoptions_pairs');
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );
        $perPage = 5;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'blog_id'     => 'ID',
            'name'       => 'Name',
            'url' => 'Location',
            'lang' => 'Language',
            'src_lang' => 'Source languages',
            'target_lang' => 'Target languages',
            'options'       => 'Options set',
        );
        return $columns;
    }

    public function get_hidden_columns()
    {
        return array();
    }

    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }

    private function table_data()
    {
        $sites = get_sites();
        $data = array();
        foreach($sites as $site) {
            $details = get_blog_details($site->blog_id, true);
            $optionsid = get_blog_option($site->blog_id, 'sdl_settings_projectoption', '0');
            $lang = get_formatted_locale($site->blog_id);
            if($lang == '' || $lang == null) {
                $lang = get_locale();
            }
            $data[] = array(
                'blog_id' => $site->blog_id,
                'name' => $details->blogname,
                'url' => $details->path,
                'lang' => $lang,
                'src_lang' => $this->print_flags($this->pairs[$optionsid]['Source']),
                'target_lang' => $this->print_flags($this->pairs[$optionsid]['Target']),
                );
        }
        return $data;
    }
    public function print_flags($langs){
        $translations = pll_languages_list();
        if($this->Polylang === null || $this->Polylang === false) {
            $plugins = get_plugins(); 
            foreach($plugins as $key => $plugin) {
                if($plugin['Name'] == 'Polylang') {
                    $folder = explode('/', $key);
                    $plugins = plugins_url();
                    $this->Polylang = $plugins . '/'. $folder[0];
                }
            }
        }
        $output = '';
        foreach($langs as $lang) {
            $stub = explode('-', $lang);
            if($stub[1] == null) {
                $output .= '<img src="'. $this->Polylang . '/flags/' . $stub[0] . '.png" />';
            } else {
                $output .= '<img src="'. $this->Polylang . '/flags/' . $stub[1] . '.png" />';
            }
        }
        return $output;
    }
    private function options_set($id){
        $selector = '<form method="post" action="admin.php?page=managedtranslation">';
        $selector .= '<input type="hidden" name="action" value="sdl_admin_updateoptions" />';
        $selector .= '<input type="hidden" name="blog_id" value="'. $id . '" />';
        $selector .= $this->parent->filter_project_options($site->blog_id);
        $selector .= '<button class="button button-primary">Update</button>';
        $selector .= '</form>';
        return $selector;
    }
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'blog_id':
            case 'name':
            case 'url':
            case 'lang':
            case 'src_lang':
            case 'target_lang':
                return $item[ $column_name ];
            case 'options':
                return $this->options_set($item['blog_id']);
            default:
                return print_r( $item, true ) ;
        }
    }
}
?>
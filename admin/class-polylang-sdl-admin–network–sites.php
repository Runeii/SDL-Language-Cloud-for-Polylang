<?php
if( $_POST['action'] == 'sdl_admin_updateoptions' ) {
    $blog_id = intval( $_POST['blog_id'] );
    $id = $_POST['options_set'];
    $pairs = get_site_option('sdl_settings_projectoptions_pairs');
    update_blog_option($blog_id, 'sdl_projectoptions', $id);
    update_blog_option($blog_id, 'sdl_projectoptions_sourcelang', $pairs[$id]['Source'][0]);
}

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SDL_Sites_Table extends WP_List_Table
{
    private $options;
    private $pairs;

    public function prepare_items()
    {
        $this->options = get_site_option('sdl_settings_projectoptions');
        if($this->options === null || $this->options === false) {
            $API = new Polylang_SDL_API();
            $this->options = $API->user_options();
        }
        $this->pairs = get_site_option('sdl_settings_projectoptions_pairs');

        if($this->options === null || $this->options === false){
            echo 'break here';
            die();
        }
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
            'options_id' => 'Options id',
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
        $network_lang = get_site_option('WPLANG' );
        foreach($sites as $site) {
            $details = get_blog_details($site->blog_id, true);
            $optionsid = get_blog_option($site->blog_id, 'sdl_projectoptions', '0');
            $data[] = array(
                'blog_id' => $site->blog_id,
                'name' => $details->blogname,
                'options_id' => $optionsid,
                'url' => $details->path,
                'lang' => get_blog_option($site->blog_id, 'WPLANG', $network_lang),
                'src_lang' => implode(', ', $this->pairs[$optionsid]['Source']),
                'target_lang' => implode(', ', $this->pairs[$optionsid]['Target']),
                );
        }
        return $data;
    }
    private function options_set($id){
        $selector = '<form method="post" action="admin.php?page=languagecloud">';
        $selector .= '<input type="hidden" name="action" value="sdl_admin_updateoptions" />';
        $selector .= '<input type="hidden" name="blog_id" value="'. $id . '" />';
        $selector .= '<select name="options_set">';
        $selector .= '<option>– Select project options set –</option>';
        foreach($this->options as $option){
            $selector .= '<option value="'. $option['Id'] . '">' . $option['Name'] . '</option>';
        }
        $selector .= '</select>';
        $selector .= '<button>Update</button>';
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
            case 'options_id':
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
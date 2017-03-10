<?php

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
/**
 * Create a new table class that will extend the WP_List_Table
 */
class SDL_Sites_Table extends WP_List_Table
{
    private $options;

    public function prepare_items()
    {
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
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $sites = get_sites();
        $data = array();
        $network_lang = get_site_option('WPLANG' );
        foreach($sites as $site) {
            $details = get_blog_details($site->blog_id, true);
            $data[] = array(
                'blog_id' => $site->blog_id,
                'name' => $details->blogname,
                'url' => $details->path,
                'lang' => get_blog_option($site->blog_id, 'WPLANG', $network_lang),
                'src_lang' => '',
                'target_lang' => ''
                );
        }
        return $data;
    }
    private function options_set($id){
        $options = $this->options ?: get_site_option('sdl_settings_projectoptions');
        if($options === null || $options === false) {
            $API = new Polylang_SDL_API;
            $options = $API->user_options();
            $this->options = $options;
        }
        $selector = '<select>';
        foreach($options as $option){
            $selector .= '<option>' . $option['Name'] . '</option>';
        }
        $selector .= '</select>';
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
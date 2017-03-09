<?php  
if( isset( $_GET[ 'tab' ] ) ) {  
    $active_tab = $_GET[ 'tab' ];  
} else if(is_network_admin()) {
    $active_tab = 'networksettings';
} else {
    $active_tab = 'overview';
}
?>  
<div id="sdl_settings" class="wrap">
    <h2>SDL Language Cloud for Polylang</h2>
    <?php settings_errors(); ?> 
    <?php
        $API = new Polylang_SDL_API('andrewhill', 'Sdl2017', true);
        if($API->test_loggedIn()){
            ?>
            <div class="wp-filter">
                <ul class="filter-links">
                <?php if(is_network_admin()) { ?>
                    <li>
                        <a href="?page=languagecloud&tab=network" class="<?php echo $active_tab == 'network' ? 'current' : ''; ?>">Network settings</a>
                    </li>
                <?php } ?>
                    <li>
                        <a href="?page=languagecloud&tab=overview" class="<?php echo $active_tab == 'overview' ? 'current' : ''; ?>">Overview</a>  
                    </li>
                <?php if(is_network_admin()) { ?>
                    <li>
                        <a href="?page=languagecloud&tab=account class="<?php echo $active_tab == 'account' ? 'current' : ''; ?>">Account Details</a>  
                    </li>
                <?php } ?>
                </ul>
            </div>
            <form action='options.php' method='post'>
                <?php 
                if( $active_tab == 'network' ) {  
                    settings_fields( 'sdl_settings_network_page' );
                    do_settings_sections( 'sdl_settings_network_page' );
                    submit_button();
                } else if( $active_tab == 'overview' ) {
                    echo '<h2>Overview</h2>';
                    settings_fields( 'sdl_settings_overview_page' );
                    do_settings_sections( 'sdl_settings_overview_page' );
                    submit_button();
                } else if( $active_tab == 'account' ) {
                    echo '<h2>Account details</h2>';
                    settings_fields( 'sdl_settings_account_page' );
                    do_settings_sections( 'sdl_settings_account_page' );
                    submit_button('Login to Language Cloud');
                }
                ?>
            </form>
        <?php
        } else { ?>
            <div class="wp-filter">
                <ul class="filter-links">
                    <li>
                        <a href="?page=languagecloud&tab=account" class="current">Account Details</a>
                    </li>
                </ul>  
            </div>
            <?php
            if(is_network_admin()) {
            ?>
                <form action='options.php' method='post'>
                    <?php 
                    if( $active_tab == 'account' ) {  
                        settings_fields( 'sdl_settings_account_page' );
                        do_settings_sections( 'sdl_settings_account_page' );
                        submit_button('Login to Language Cloud');
                    }
                    ?>
                </form>
            <?php 
            } else { 
                $url = network_admin_url('admin.php?page=languagecloud&tab=account');
                ?>
                <form>
                    <h2>Setup not completed</h2>
                    <p>Please ask network administrator to visit the <a href="<?php echo $url; ?>">Network Settings page</a> to complete setup</p>
                </form>
            <? }
        } ?>
</div>
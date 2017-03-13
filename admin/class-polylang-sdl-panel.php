<?php  
if( isset( $_GET[ 'tab' ] ) ) {  
    $active_tab = $_GET[ 'tab' ];  
} else if(is_network_admin()) {
    $active_tab = 'sites';
} else {
    $active_tab = 'dashboard';
}
?>  
<div id="sdl_settings" class="wrap">
    <h2>SDL Language Cloud for Polylang</h2>
    <?php settings_errors(); ?> 
    <?php
        $API = new Polylang_SDL_API(true);
        if($API->test_loggedIn()){
            ?>
            <div class="wp-filter">
                <ul class="filter-links">
                <?php if(is_network_admin()) { ?>
                    <li>
                        <a href="?page=languagecloud&tab=sites" class="<?php echo $active_tab == 'sites' ? 'current' : ''; ?>">Network setup</a>
                    </li>
                <?php } ?>
                    <li>
                        <a href="?page=languagecloud&tab=dashboard" class="<?php echo $active_tab == 'dashboard' ? 'current' : ''; ?>">Dashboard</a>  
                    </li>
                <?php if(is_network_admin()) { ?>
                    <li>
                        <a href="?page=languagecloud&tab=account" class="<?php echo $active_tab == 'account' ? 'current' : ''; ?>">Account Details</a>  
                    </li>
                <?php } ?>
                </ul>
            </div>
                <?php 
                if( $active_tab == 'sites' ) {  
                    if( ! class_exists( 'SDL_Sites_Table' ) ) {
                        require_once('class-polylang-sdl-admin–network–sites.php' );
                    }
                    $sitesTable = new SDL_Sites_Table();
                    $sitesTable->prepare_items();
                    $sitesTable->display();
                } else if( $active_tab == 'dashboard' ) {
                    echo "<form action='options.php' method='post'>";
                    echo '<h2>Our statistics, reports and overview will go here.</h2>';
                    echo '</form>';
                } else if( $active_tab == 'account' ) {
                    echo "<form action='edit.php?action=sdl_settings_update_network_options' method='post'>";
                        echo '<h2>Account details</h2>';
                        settings_fields( 'sdl_settings_account_page' );
                        do_settings_sections( 'sdl_settings_account_page' );
                        submit_button('Login to Language Cloud');
                    echo '</form>';
                }
                ?>
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
            if(is_network_admin() && $active_tab == 'account') {
                echo "<form action='edit.php?action=sdl_settings_update_network_options' method='post'>";
                    settings_fields( 'sdl_settings_account_page' );
                    do_settings_sections( 'sdl_settings_account_page' ); 
                    submit_button('Login to Language Cloud');
                echo "</form>";
            } else if(is_network_admin()) {
                echo "
                <form>
                    <h2>Setup not completed</h2>
                    <p>Please visit the Account Details tab to complete setup</p>
                </form>
                ";
            } else { 
                $url = network_admin_url('admin.php?page=languagecloud&tab=account');
                echo "
                <form>
                    <h2>Setup not completed</h2>
                    <p>Please ask network administrator to visit the <a href='". $url ."'>Network Settings page</a> to complete setup</p>
                </form>
                ";
            }
        } ?>
</div>
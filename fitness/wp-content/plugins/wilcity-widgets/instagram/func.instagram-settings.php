<?php
use \WilokeListingTools\Framework\Store\Session;

if (!function_exists('wilokeInstagramSettings')) {
    function wilokeInstargamSettings()
    {
        $instagramKey = 'wiloke_instagram_settings';
        if (current_user_can('edit_theme_options') && isset($_POST['instagram'])) {
            $aOldVal = get_option($instagramKey);
            $aData   = !empty($aOldVal) ? array_merge($aOldVal, $_POST['instagram']) : $_POST['instagram'];
            update_option($instagramKey, $aData);
        }

        $aInstagram = get_option($instagramKey);
        $aInstagram = $aInstagram ? $aInstagram : [
          'userid'        => '',
          'username'      => '',
          'access_token'  => '',
          'refresh_token' => '',
          'app_id'        => '',
          'redirect_uri'  => '',
        ];

        $token = time();
        Session::setSession('request-instagram-token', $token);
        $instagramRedirectUri = add_query_arg(
          [
            'client_id'     => $aInstagram['app_id'],
            'redirect_uri'  => $aInstagram['redirect_uri'],
            'scope'         => 'user_profile,user_media',
            'response_type' => 'code',
            'state'         => $token
          ], 'http://instagram.com/oauth/authorize'
        );
        ?>
        <form action="<?php echo admin_url('options-general.php?page=wiloke-instagram'); ?>" method="POST">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="sign-in-with-instagram"><?php esc_html_e('Sign in with Instagram',
                              'wiloke'); ?></label></th>
                    <td>
                        <a id="sign-in-with-instagram" class="button button-primary"
                           href="<?php echo esc_url($instagramRedirectUri); ?>"><?php esc_html_e('Execute',
                              'wiloke'); ?></a>
                    </td>
                </tr>

                <tr>
                    <th scope="row"></th>
                    <td>
                        <p>How can I configure it? => <a href="https://documentation.wilcity.com/knowledgebase/setting-up-instagram-api/" target="_blank">Click on me to get an instruction</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <?php
                        if (!empty($aInstagram['profile_picture'])) {
                            ?>
                            <img style="width: 50px; height: 50px; border-radius: 100%;"
                                 src="<?php echo esc_url($aInstagram['profile_picture']); ?>" alt="Profile Picture"/>
                            <?php
                        }
                        ?>
                        <input id="profilepicture" type="hidden" name="instagram[profile_picture]"
                               value="<?php echo esc_url($aInstagram['profile_picture']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="userid"><?php esc_html_e('User ID', 'wiloke'); ?></label></th>
                    <td>
                        <input id="userid" type="text" name="instagram[userid]" readonly
                               value="<?php echo esc_attr($aInstagram['userid']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="username"><?php esc_html_e('User Name', 'wiloke'); ?></label></th>
                    <td>
                        <input id="username" type="text" name="instagram[username]" readonly
                               value="<?php echo esc_attr($aInstagram['username']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="access_token"><?php esc_html_e('Access Token', 'wiloke'); ?></label>
                    </th>
                    <td>
                        <input id="access_token" type="password" name="instagram[access_token]" readonly
                               value="<?php echo esc_attr($aInstagram['access_token']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="refresh_token"><?php esc_html_e('Refresh Token', 'wiloke'); ?></label>
                    </th>
                    <td>
                        <input id="refresh_token" type="password" name="instagram[refresh_token]" readonly
                               value="<?php echo esc_attr($aInstagram['refresh_token']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="app_id"><?php esc_html_e('App ID', 'wiloke');
                            ?></label>
                    </th>
                    <td>
                        <input id="app_id" type="text" name="instagram[app_id]"
                               value="<?php echo esc_attr($aInstagram['app_id']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="app_secret"><?php esc_html_e('App Secret', 'wiloke');
                            ?></label>
                    </th>
                    <td>
                        <input id="app_secret" type="text" name="instagram[app_secret]"
                               value="<?php echo esc_attr($aInstagram['app_secret']); ?>"/>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="redirect_uri"><?php esc_html_e('Redirect Uri', 'wiloke');
                            ?></label>
                    </th>
                    <td>
                        <input id="redirect_uri" type="text" name="instagram[redirect_uri]"
                               value="<?php echo esc_attr($aInstagram['redirect_uri']); ?>"/>
                    </td>
                </tr>
                </tbody>
                <tr>
                    <td scope="2"><input type="submit" class="button button-primary" value="Save Changes"></td>
                </tr>
            </table>
        </form>
        <?php
    }
}

if (!function_exists('wilokeInstagramMenu')) {
    function wilokeInstagramMenu()
    {
        add_options_page('Wiloke Instagram Settings', 'Wiloke Instagram Settings', 'edit_theme_options',
          'wiloke-instagram', 'wilokeInstargamSettings');
    }

    add_action('admin_menu', 'wilokeInstagramMenu');
}

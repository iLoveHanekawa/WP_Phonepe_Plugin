<?php 
namespace _29kPayments;
use WP_Error;
use WP_REST_Request;
use WP_User;
class BasePlugin {
    public function __construct() {}
    public function addShortCode(string $key, string $path, array $attsArr = []) {
        add_action('init', function () use(&$key, &$path, &$attsArr) {
            add_shortcode($key, function ($atts) use(&$path, &$attsArr) {
                $shortcode_atts = shortcode_atts($attsArr, $atts);
                ob_start();
                include($path);
                $content = ob_get_clean();
                return $content;
            });
        });
    }

    public function addScript(string $path, array $dependencies = array(), bool $inFooter = \false, string|bool $version = \false) {
        add_action('wp_enqueue_scripts', function () use(&$path, &$dependencies, &$version, &$inFooter) {
            wp_enqueue_script(md5($path), $path, $dependencies, $version, $inFooter);
        });
    }

    public function addStyle(string $path) {
        add_action('wp_enqueue_scripts', function () use(&$path) {
            wp_enqueue_style(md5($path), $path);
        });
    }

    public function addRestRoute(string $method, string $route, string $endpoint, callable $callback, callable $permissionCallback = null) {
        if(!$permissionCallback) $permissionCallback = function() { return '__return_true'; };
        add_action('rest_api_init', function () use(&$method, &$callback, $route, &$endpoint, $permissionCallback){
            register_rest_route($route, '/' . $endpoint, array(
                'methods' => $method,
                'callback' => function (WP_REST_Request $request) use(&$callback) {
                    $callback($request);
                },
                'permission_callback' => $permissionCallback
            ));
        });
    }

    public function addPluginAdminSettings(string $filePath, string $pageTitle, string $menuTitle, string $slugForAdminLink, array $HTMLFieldNames,string $HTMLSubmitButtonName, string $HTMLUiPath, string $wordpressIcon = 'dashicons-admin-generic') {
        add_action('admin_menu', function () use($pageTitle, $menuTitle, $slugForAdminLink, $HTMLFieldNames, $HTMLSubmitButtonName, $HTMLUiPath, $wordpressIcon) {
            add_menu_page(
                $pageTitle,
                $menuTitle,
                'manage_options', // Set the required capability to access this menu item
                $slugForAdminLink, // Unique menu slug for the settings page
                function () use($HTMLFieldNames, $HTMLSubmitButtonName, $HTMLUiPath) {
                    // Check if the form is submitted and process the data
                    $updateFields = [];
                    if (isset($_POST[$HTMLSubmitButtonName])) {
                        foreach($HTMLFieldNames as $fieldName) {
                            $updateFields[] = sanitize_text_field($_POST[$fieldName]);
                        }
                        foreach($updateFields as $index => $updateField) {
                            update_option($HTMLFieldNames[$index], $updateField);
                        }
                        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
                    }    
                    include($HTMLUiPath);
                }, 
                $wordpressIcon, // Icon for the menu item (optional)
                85 // Position of the menu item in the sidebar
            );
        });
        
        add_filter('plugin_action_links_' . $filePath, function ($links) use($slugForAdminLink) {
            $settings_link = '<a href="' . admin_url('admin.php?page=' . $slugForAdminLink) . '">Settings</a>';
            array_push($links, $settings_link);
            return $links;
        });
    }
    public function setResetPasswordEmail(callable $callback) {
        add_filter( 'retrieve_password_message', 
            function (string $message, string $key, string $user_login, WP_User $user_data) use($callback) {
                return $callback($message, $key, $user_login, $user_data);
            }, 
        10, 4 );
    }

    public function addMailFrom(string $email) {
        add_filter( 'wp_mail_from', function () use(&$email) {
            return $email;
        } );
    }

    public function addMailFailedCallback(callable $callback) {
        add_action( 'wp_mail_failed', function (WP_Error $error) use(&$callback) {
            $callback($error);
        });
    }

    public function addMailFromName(string $name) {
        add_filter( 'wp_mail_from_name', function () use(&$name) {
            return $name;
        });
    }

    public function addCustomPostType(string $postTypeNameSingular, string $postTypeNamePlural, string $menuIcon) {
        add_action( 'init', function () use($postTypeNamePlural, $postTypeNameSingular, $menuIcon) {
            $labels = [
                'name'               => $postTypeNamePlural,
                'singular_name'      => $postTypeNameSingular,
                'menu_name'          => $postTypeNamePlural,
                'name_admin_bar'     => $postTypeNameSingular,
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New ' . $postTypeNameSingular,
                'new_item'           => 'New ' . $postTypeNameSingular,
                'edit_item'          => 'Edit ' . $postTypeNameSingular,
                'view_item'          => 'View ' . $postTypeNameSingular,
                'all_items'          => 'All ' . $postTypeNamePlural,
                'search_items'       => 'Search ' . $postTypeNamePlural,
                'parent_item_colon'  => 'Parent ' . $postTypeNamePlural . ':',
                'not_found'          => 'No ' . $postTypeNamePlural . ' found.',
                'not_found_in_trash' => 'No ' . $postTypeNamePlural . ' found in Trash.'
            ];
        
            $args = [
                'labels'             => $labels,
                'public'             => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => [ 'slug' => $postTypeNamePlural ],
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_icon'          => $menuIcon,
                'menu_position'      => null,
                'supports'           => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ],
                'show_in_rest'       => true
            ];  
            register_post_type($postTypeNamePlural, $args );
        });
    }
}
?>
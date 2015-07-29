<?php
namespace TypeRocket;

class Core
{

    /**
     * Core
     *
     * Only initialize core once
     *
     * @param bool|true $init initialize core
     */
    public function __construct($init = false) {
        if($init) {
            $this->initCore();
        }
    }

    /**
     * Core Init
     */
    public function initCore()
    {
        $this->initAdmin();
        $this->loadPlugins( new Plugin\PluginCollection() );

        $comments = new Http\Responders\CommentsResponder();
        $users = new Http\Responders\UsersResponder();
        $this->loadResponders( $comments, $users );
    }

    /**
     * Admin Init
     */
    public function initAdmin()
    {
        add_action( 'post_updated_messages', array( $this, 'setMessages' ) );
        add_action( 'edit_user_profile', array( $this, 'userContent' ) );
        add_action( 'show_user_profile', array( $this, 'userContent' ) );
        add_action( 'admin_init', array( $this, 'addCss' ) );
        add_action( 'admin_init', array( $this, 'addJs' ) );
        add_action( 'admin_footer', array( $this, 'addBottomJs' ) );
    }

    /**
     * Front End Init
     */
    public function initFrontEnd()
    {
        add_action( 'wp_enqueue_scripts', array( $this, 'addCss' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'addJs' ) );
        add_action( 'wp_footer', array( $this, 'addBottomJs' ) );
    }

    /**
     * @param Plugin\PluginCollection $collection
     */
    public function loadPlugins( Plugin\PluginCollection $collection )
    {
        if (Config::getPlugins()) {
            $plugins = Config::getPlugins();

            foreach ($plugins as $plugin) {
                $collection->append( $plugin );
            }

            $loader = new Plugin\Loader( $collection );
            $loader->load();
        }
    }

    /**
     * Init Responders
     *
     * Add hook into WordPress to give the main functionality needed for
     * TypeRocket to work.
     */
    public function loadResponders() {
        add_action( 'save_post', 'TypeRocket\Http\Responders\Hook::posts' );
        add_action( 'wp_insert_comment', 'TypeRocket\Http\Responders\Hook::comments' );
        add_action( 'edit_comment', 'TypeRocket\Http\Responders\Hook::comments' );
        add_action( 'profile_update', 'TypeRocket\Http\Responders\Hook::users' );
        add_action( 'user_register', 'TypeRocket\Http\Responders\Hook::users' );
    }

    /**
     * Set custom post type messages to make more since.
     *
     * @param $messages
     *
     * @return mixed
     */
    public function setMessages( $messages )
    {
        global $post;

        $pt = get_post_type( $post->ID );

        if ($pt != 'attachment' && $pt != 'page' && $pt != 'post') :

            $obj      = get_post_type_object( $pt );
            $singular = $obj->labels->singular_name;

            if ($obj->public == true) :
                $view    = sprintf( __( '<a href="%s">View %s</a>' ), esc_url( get_permalink( $post->ID ) ),
                    $singular );
                $preview = sprintf( __( '<a target="_blank" href="%s">Preview %s</a>' ),
                    esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ), $singular );
            else :
                $view = $preview = '';
            endif;

            $messages[$pt] = array(
                1  => sprintf( __( '%s updated. %s' ), $singular, $view ),
                2  => __( 'Custom field updated.' ),
                3  => __( 'Custom field deleted.' ),
                4  => sprintf( __( '%s updated.' ), $singular ),
                5  => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s' ), $singular,
                    wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
                6  => sprintf( __( '%s published. %s' ), $singular, $view ),
                7  => sprintf( __( '%s saved.' ), $singular ),
                8  => sprintf( __( '%s submitted. %s' ), $singular, $preview ),
                9  => sprintf( __( '%s scheduled for: <strong>%1$s</strong>. %s' ), $singular,
                    date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ), $preview ),
                10 => sprintf( __( '%s draft updated. ' ), $singular ),
            );

        endif;

        return $messages;
    }

    /**
     * Add CSS
     */
    public function addCss()
    {
        $paths = Config::getPaths();
        wp_enqueue_style( 'typerocket-icons', $paths['urls']['assets'] . '/css/icons.css' );
        wp_enqueue_style( 'typerocket-styles', $paths['urls']['assets'] . '/css/typerocket.css' );
        wp_enqueue_style( 'typerocket-date-picker', $paths['urls']['assets'] . '/css/date-picker.css' );
        wp_enqueue_style( 'typerocket-editor', $paths['urls']['assets'] . '/css/redactor.css' );

        if (is_admin()) {
            wp_enqueue_style( 'typerocket-tabs', $paths['urls']['assets'] . '/css/tabs.css' );
        }
    }

    /**
     * Add JavaScript
     */
    public function addJs()
    {

        $paths = Config::getPaths();

        wp_enqueue_script( 'typerocket-scripts-global', $paths['urls']['assets'] . '/js/global.js', array(),
            '1.0' );
    }

    /**
     * Add JavaScript to very bottom
     */
    public function addBottomJs()
    {

        $paths = Config::getPaths();

        if (TR_DEBUG === true) {
            wp_enqueue_script( 'typerocket-dev', $paths['urls']['assets'] . '/js/dev.js', array( 'jquery' ), '1.0',
                true );
        }
        wp_enqueue_script( 'typerocket-scripts', $paths['urls']['assets'] . '/js/typerocket.js', array( 'jquery' ),
            '1.0', true );

    }

    /**
     * Hook for both user edit and add pages
     *
     * @param $user_obj
     */
    public function userContent( $user_obj )
    {
        echo '<div class="typerocket-container typerocket-wp-style-guide">';
        do_action( 'tr_user_profile', $user_obj );
        echo '</div>';
    }

}

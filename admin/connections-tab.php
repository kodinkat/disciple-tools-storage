<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Storage_Tab_Connections
 */
class Disciple_Tools_Storage_Tab_Connections {

    public function __construct() {

        // Handle update submissions
        $this->process_updates();

        // Load scripts and styles
        $this->process_scripts();
    }

    private function final_post_param_sanitization( $str ) {
        return str_replace( [ '&lt;', '&gt;' ], [ '<', '>' ], $str );
    }

    public function process_updates() {

        if ( isset( $_POST['m_main_col_update_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['m_main_col_update_form_nonce'] ) ), 'm_main_col_update_form_nonce' ) ) {
            if ( isset( $_POST['m_main_col_update_form_connection_obj'] ) ){

                // Fetch newly updated link object.
                $sanitized_input   = filter_var( wp_unslash( $_POST['m_main_col_update_form_connection_obj'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );
                $updating_connection_obj = json_decode( $this->final_post_param_sanitization( $sanitized_input ) );

                // Ensure we have something to work with.
                if ( !empty( $updating_connection_obj ) && isset( $updating_connection_obj->id ) ) {
                    Disciple_Tools_Storage_API::update_option_connection_obj( $updating_connection_obj );
                }
            }
        }

        if ( isset( $_POST['m_main_col_delete_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['m_main_col_delete_form_nonce'] ) ), 'm_main_col_delete_form_nonce' ) ) {
            if ( isset( $_POST['m_main_col_delete_form_connection_obj_id'] ) ) {

                // Fetch connection object id to be deleted.
                $connection_obj_id = filter_var( wp_unslash( $_POST['m_main_col_delete_form_connection_obj_id'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );

                // Ensure we have something to work with.
                if ( ! empty( $connection_obj_id ) ) {
                    Disciple_Tools_Storage_API::delete_option_connection_obj( $connection_obj_id );
                }
            }
        }
    }

    private function fetch_previous_updated_connection_obj() {
        if ( isset( $_POST['m_main_col_update_form_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['m_main_col_update_form_nonce'] ) ), 'm_main_col_update_form_nonce' ) ) {
            if ( isset( $_POST['m_main_col_update_form_connection_obj'] ) ) {
                $sanitized_input = filter_var( wp_unslash( $_POST['m_main_col_update_form_connection_obj'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES );

                return json_decode( $this->final_post_param_sanitization( $sanitized_input ) );
            }
        }

        return null;
    }

    public function process_scripts() {
        dt_theme_enqueue_style( 'material-font-icons-local', 'dt-core/dependencies/mdi/css/materialdesignicons.min.css', array() );
        wp_enqueue_style( 'material-font-icons', 'https://cdn.jsdelivr.net/npm/@mdi/font@6.6.96/css/materialdesignicons.min.css', array(), '6.6.96' );

        wp_enqueue_script( 'dt_storage_script', plugin_dir_url( __FILE__ ) . 'js/connections-tab.js', [
            'jquery'
        ], filemtime( dirname( __FILE__ ) . '/js/connections-tab.js' ), true );

        wp_localize_script(
            'dt_storage_script', 'dt_storage', array(
                'dt_endpoint_validate_connection' => Disciple_Tools_Storage_API::fetch_endpoint_validate_connection(),
                'connection_types' => Disciple_Tools_Storage_API::list_supported_connection_types(),
                'connection_objs' => Disciple_Tools_Storage_API::fetch_option_connection_objs(),
                'previous_updated_connection_obj'  => $this->fetch_previous_updated_connection_obj(),
                'is_multisite' => is_multisite() ? '1' : '0'
            )
        );
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped" id="m_main_col_available_connections">
            <thead>
            <tr>
                <th>Available Connections</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_available_connections(); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->

        <!-- Connection Deletion -->
        <span style="float:right; margin-bottom: 15px;">
            <button style="display: none;" type="submit" id="m_main_col_delete_but"
                    class="button float-right"><?php esc_html_e( 'Delete', 'disciple_tools' ) ?></button>
        </span>
        <form method="post" id="m_main_col_delete_form">
            <input type="hidden" id="m_main_col_delete_form_nonce" name="m_main_col_delete_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'm_main_col_delete_form_nonce' ) ) ?>"/>

            <input type="hidden" id="m_main_col_delete_form_connection_obj_id"
                   name="m_main_col_delete_form_connection_obj_id" value=""/>
        </form>
        <!-- Connection Deletion -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="m_main_col_connection_manage">
            <thead>
            <tr>
                <th>Connection Management</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_connection_manage(); ?>
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td>
                    <span style="float:right;">
                        <button type="submit" id="m_main_col_connection_manage_update_but"
                                class="button float-right m-connection-update-but"><?php esc_html_e( 'Update', 'disciple_tools' ) ?></button>
                    </span>
                </td>
            </tr>
            </tfoot>
        </table>
        <br>
        <!-- End Box -->

        <!-- Box -->
        <table style="display: none;" class="widefat striped" id="m_main_col_connection_type_details">
            <thead>
            <tr>
                <th>Connection Type Details</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <?php $this->main_column_connection_type_details(); ?>
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td>
                    <span style="float:right;">
                        <button type="submit" id="m_main_col_connection_type_details_update_but"
                                class="button float-right m-connection-update-but"><?php esc_html_e( 'Update', 'disciple_tools' ) ?></button>
                    </span>
                </td>
            </tr>
            </tfoot>
        </table>
        <br>
        <!-- End Box -->

        <!-- [Submission Form] -->
        <form method="post" id="m_main_col_update_form">
            <input type="hidden" id="m_main_col_update_form_nonce" name="m_main_col_update_form_nonce"
                   value="<?php echo esc_attr( wp_create_nonce( 'm_main_col_update_form_nonce' ) ) ?>"/>

            <input type="hidden" id="m_main_col_update_form_connection_obj"
                   name="m_main_col_update_form_connection_obj" value=""/>
        </form>

        <?php
    }

    public function right_column() {
        ?>
        <!-- Box -->

        <!-- End Box -->
        <?php
    }

    private function main_column_available_connections(): void {
        ?>
        <select style="min-width: 80%;" id="m_main_col_available_connections_select"></select>
        <span style="float:right;">
            <button id="m_main_col_available_connections_new" type="submit"
                    class="button float-right"><?php esc_html_e( 'New', 'disciple_tools' ) ?></button>
        </span>
        <?php
    }

    private function main_column_connection_manage(): void {
        ?>
        <table class="widefat striped">
            <tr>
                <td style="vertical-align: middle;">Enabled</td>
                <td>
                    <input type="checkbox" id="m_main_col_connection_manage_enabled"/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Name</td>
                <td>
                    <input type="hidden" id="m_main_col_connection_manage_id" value=""/>
                    <input style="min-width: 100%;" type="text" id="m_main_col_connection_manage_name" value=""/>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: middle;">Connection Type [<a href="#" class="m-connections-docs"
                                                                        data-title="m_connections_right_docs_connection_type_title"
                                                                        data-content="m_connections_right_docs_connection_type_content">&#63;</a>]
                </td>
                <td>
                    <select style="min-width: 100%;" id="m_main_col_connection_manage_type">
                        <option disabled selected value="">-- select connection type --</option>
                        <?php
                        foreach ( Disciple_Tools_Storage_API::list_supported_connection_types() ?? [] as $key => $type ) {
                            if ( $type['enabled'] ) {
                                ?>
                                <option
                                    value="<?php echo esc_attr( $key ) ?>"><?php echo esc_attr( $type['label'] ) ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    private function main_column_connection_type_details(): void {
        ?>
        <div id="m_main_col_connection_type_details_content"></div>
        <?php
    }
}

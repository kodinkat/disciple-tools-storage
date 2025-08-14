<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

/**
 * Class Disciple_Tools_Storage_API
 */
class Disciple_Tools_Storage_API {

    public static $option_dt_storage_connection_objects = 'dt_storage_connection_objects';

    public static function fetch_endpoint_validate_connection(): string {
        return trailingslashit( site_url() ) . 'wp-json/disciple_tools_storage/v1/validate_connection';
    }

    public static function list_supported_connection_types(): array {
        return [
            'aws' => [
                'key' => 'aws',
                'api' => 's3',
                'label' => 'AWS S3',
                'prefix_bucket_name_to_obj_key' => false,
                'enabled' => true
            ],
            'backblaze' => [
                'key' => 'backblaze',
                'api' => 's3',
                'label' => 'Backblaze',
                'prefix_bucket_name_to_obj_key' => false,
                'enabled' => true
            ],
            'minio' => [
                'key' => 'minio',
                'api' => 's3',
                'label' => 'MinIO',
                'prefix_bucket_name_to_obj_key' => true,
                'enabled' => true
            ]
        ];
    }

    public static function generate_random_string( $length = 112 ): string {
        $random_string = '';
        $keys = array_merge( range( 0, 9 ), range( 'a', 'z' ), range( 'A', 'Z' ) );
        for ( $i = 0; $i < $length; $i++ ){
            $random_string .= $keys[mt_rand( 0, count( $keys ) - 1 )];
        }

        return $random_string;
    }

    public static function generate_image_thumbnail( $src, $content_type, $desired_width ) {
        $thumbnail = null;
        try {

            // Read the original source image, by respective content type.
            switch ( strtolower( trim( $content_type ) ) ) {
                case 'image/gif':
                    $source_image = imagecreatefromgif( $src );
                    break;
                case 'image/jpeg':
                    $source_image = imagecreatefromjpeg( $src );
                    break;
                case 'image/png':
                    $source_image = imagecreatefrompng( $src );
                    break;
                default:
                    $source_image = null;
                    break;
            }

            if ( !empty( $source_image ) ) {

                // Determine sourced image dimensions.
                $width = imagesx( $source_image );
                $height = imagesy( $source_image );

                // Find the "desired height" of this thumbnail, relative to the desired width.
                $desired_height = floor( $height * ( $desired_width / $width ) );

                // Create a new, "virtual" image.
                $virtual_image = imagecreatetruecolor( $desired_width, $desired_height );

                // Support background transparency.
                $black = imagecolorallocate( $virtual_image, 0, 0, 0 );
                imagecolortransparent( $virtual_image, $black );

                // Copy source image at a resized size.
                imagecopyresampled( $virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height );

                // Ensure there is a valid virtual image to be processed.
                if ( !empty( $virtual_image ) ) {

                    // Next, capture virtual image to be returned.
                    $thumbnail = $virtual_image;
                }
            }
        } catch ( Exception $e ) {
            $thumbnail = null;
        }

        return $thumbnail;
    }

    public static function validate_url( $url ): string {
        if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
            $http = 'http://';
            $https = 'https://';
            if ( ( substr( $url, 0, strlen( $http ) ) !== $http ) && ( substr( $url, 0, strlen( $https ) ) !== $https ) ) {
                $url = $https . trim( $url );
            }
        }

        return $url;
    }

    public static function fetch_option_connection_objs(): object {
        $option = get_option( self::$option_dt_storage_connection_objects );

        // If multisite, then ensure specified connection configuration is always included.
        if ( is_multisite() && !empty( get_site_option( 'dt_storage_multisite_connection_object', [] ) ) ) {
            $multisite_connection_objects = get_site_option( 'dt_storage_multisite_connection_object', [] );

            // Check if there is a valid, specified multisite storage connection.
            if ( isset( $multisite_connection_objects['id'], $multisite_connection_objects['enabled'], $multisite_connection_objects['name'], $multisite_connection_objects['type'] ) && boolval( $multisite_connection_objects['enabled'] ) ) {
                $type = $multisite_connection_objects['type'];

                // Ensure identified connection contains a valid type configuration.
                if ( isset( $multisite_connection_objects[ $type ]['access_key'], $multisite_connection_objects[ $type ]['secret_access_key'], $multisite_connection_objects[ $type ]['region'], $multisite_connection_objects[ $type ]['endpoint'], $multisite_connection_objects[ $type ]['bucket'] ) ) {

                    // Indicate to downstream users, the origins of connection object.
                    $multisite_connection_objects['source'] = 'multisite';

                    // Finally, update option, with identified multisite connection.
                    $decoded_option = json_decode( !empty( $option ) ? $option : '{}' );
                    $decoded_option->{$multisite_connection_objects['id']} = (object) $multisite_connection_objects;
                    $option = json_encode( $decoded_option );
                }
            }
        }

        if ( ! empty( $option ) ) {

            $connection_objs = [];
            foreach ( json_decode( $option ) as $id => $connection_obj ) {
                if ( isset( $connection_obj->id ) ){
                    $connection_objs[ $connection_obj->id ] = $connection_obj;
                }
            }

            return (object) $connection_objs;
        }

        return (object) [];
    }

    public static function update_option_connection_obj( $connection_obj ): void {
        $option_connection_objs = self::fetch_option_connection_objs();

        $option_connection_objs->{$connection_obj->id} = $connection_obj;

        // Save changes.
        update_option( self::$option_dt_storage_connection_objects, json_encode( $option_connection_objs ) );
    }

    public static function fetch_option_connection_obj( $connection_obj_id ) {
        $option_connection_objs = self::fetch_option_connection_objs();

        return ( isset( $option_connection_objs->{$connection_obj_id} ) ) ? $option_connection_objs->{$connection_obj_id} : (object) [];
    }

    public static function delete_option_connection_obj( $connection_obj_id ): void {
        $option_connection_objs = self::fetch_option_connection_objs();

        // Do we have a match?
        if ( isset( $option_connection_objs->{$connection_obj_id} ) ) {

            // Remove connection object from options.
            unset( $option_connection_objs->{$connection_obj_id} );

            // Save changes
            update_option( self::$option_dt_storage_connection_objects, json_encode( $option_connection_objs ) );
        }
    }
}

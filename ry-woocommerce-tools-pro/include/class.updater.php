<?php
final class RY_WTP_updater
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'transient_update_plugins']);
        }
    }

    public static function check_update()
    {
        $update_plugins = get_site_transient('update_plugins');
        set_site_transient('update_plugins', $update_plugins);
    }

    public static function transient_update_plugins($transient)
    {
        $json = RY_WTP_link_server::check_version();

        if (is_array($json) && isset($json['version'])) {
            set_site_transient(RY_WTP::$option_prefix . 'checktime', time());

            $item = (object) array(
                'slug' => 'ry-woocommerce-tools-pro',
                'plugin' => RY_WTP_PLUGIN_BASENAME,
                'new_version'=> $json['version'],
                'package'=> $json['url']
            );
            if (version_compare(RY_WTP_VERSION, $json['version'], '<')) {
                if (empty($transient)) {
                    $transient = new stdClass;
                }
                $transient->last_checked = time();
                $transient->response[RY_WTP_PLUGIN_BASENAME] = (object) $item;
            } else {
                if (isset($transient->response)) {
                    unset($transient->response[RY_WTP_PLUGIN_BASENAME]);
                }
            }
        }

        return $transient;
    }
}

RY_WTP_updater::init();

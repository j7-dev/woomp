<?php
final class RY_WTP_cron
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            self::set_event();
        }
    }

    protected static function set_event()
    {
        add_action(RY_WTP::$option_prefix . 'check_update', ['RY_WTP_updater', 'check_update']);
        if (!wp_next_scheduled(RY_WTP::$option_prefix . 'check_update')) {
            $time = wp_next_scheduled('wp_update_plugins');
            if ($time == false) {
                $time = time();
            }
            wp_schedule_event($time + MINUTE_IN_SECONDS, 'twicedaily', RY_WTP::$option_prefix . 'check_update');
        }

        add_action(RY_WTP::$option_prefix . 'check_expire', ['RY_WTP', 'check_expire']);
        if (!wp_next_scheduled(RY_WTP::$option_prefix . 'check_expire')) {
            wp_schedule_event(time(), 'daily', RY_WTP::$option_prefix . 'check_expire');
        }
    }
}

RY_WTP_cron::init();

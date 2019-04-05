<?php
/**
 * Plugin Name: BC Imperial Date
 * Plugin URI:  https://github.com/bug-c/bc-imperial-date
 * Description: This plugin allows you to customize your wordpress to use Warhammer 40k pre-great rift Imperial Dating System instead of the default wordpress date. Date and time format options can be configured from Settings -> General screen.
 * Version:     1.0.1
 * Author: 		Bugariu Catalin
 * Author URI:  https://www.bugariu-catalin.com
 * License:     GPL3
 *
 * @package BC_Imperial_Date
 */
 
if (!defined('ABSPATH')) {
    die('The Emperor protects!');
}

class BC_Imperial_Date {

    CONST PREFIX = 'bc_imperial_date_';
    CONST MAKR_CONSTANT = 0.11407955263862231501532129004257;

    /**
     * Class instance.
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Get new class instance or return an existing instance.
     *
     * @return \BC_Imperial_Date
     */
    public static function getInstance() {

        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        $this->init();
    }

    /**
     * Add actions and filters.
     * 
     */
    public function init() {
        add_filter('date_formats', [$this, 'filterDateFormat'], 10, 1);
        add_filter('time_formats', [$this, 'filterTimeFormats'], 10, 1);
        add_filter('date_i18n', [$this, 'filterDatei18n'], 10, 4);
        add_action('admin_init', [$this, 'adminInit']);
    }

    public function adminInit() {
        $this->addConfigOptions();
    }

    public function getDefaultCheckNumber() {
        return get_option(self::PREFIX . 'check_number', '0');
    }

    public function selectCheckNumber() {
        $value = get_option(self::PREFIX . 'check_number', '0');

        $check_numbers = [
            'Location Based' => [
                '0' => 'Means that the event occurred on Terra.',
                '1' => 'Means that the event occurred within the Sol system.',
            ],
            'Contact Based' => [
                '2' => 'Means that the event occurred while someone present for the event was in direct psychic contact with Terra or the Sol system.',
                '3' => 'Means that an individual or organization present was in psychic contact with a 2 source while the event occurred.',
                '4' => 'Means that the individual or organization was in contact with a 3 OR 2 source.',
                '5' => 'Means that the individual or organization was in contact with a 4 source.',
                '6' => 'Means that the individual or organization was in contact with a 5 source.',
            ],
            'Estimation Based' => [
                '7' => 'Means that the event in question occurred within 10 years of the date listed in the rest of the Imperial date.',
                '8' => 'Means that the event occurred within 20 years of the date.',
                '9' => 'Class source is special. A 9-class source is an approximated date, and is usually used when recording a date within Warp travel or while on a planet that does not use the Imperial system.',
            ],
        ];

        echo '<select tid="' . self::PREFIX . 'check_number' . '" name="' . self::PREFIX . 'check_number' . '">';
        foreach ($check_numbers as $group => $options) {
            echo '<optgroup label="' . $group . '">';
            foreach ($options as $digit => $label) {
                $selected = $value == $digit ? 'selected' : '';
                echo "<option value=\"{$digit}\" {$selected}>{$digit}  - {$label}";
            }
            echo '</optgroup>';
        }
        echo '</select>';
    }

    public function getDefaultUseDat() {
        return get_option(self::PREFIX . 'use_dot', '1');
    }

    public function selectUseDot() {
        $checked = get_option(self::PREFIX . 'use_dot', '1') == 1 ? 'checked' : '';


        echo '<input type="checkbox" id="' . self::PREFIX . 'use_dot' . '" name="' . self::PREFIX . 'use_dot' . '" value="1" ' . $checked . ' >';
    }

    public function addConfigOptions() {
        register_setting('general', self::PREFIX . 'check_number', 'esc_attr');
        register_setting('general', self::PREFIX . 'use_dot', 'esc_attr');

        add_settings_field(self::PREFIX . 'check_number', '<label for="' . self::PREFIX . 'check_number">' . __('Imperial Date Check Number', 'check_number') . '</label>', [$this, 'selectCheckNumber'], 'general');
        add_settings_field(self::PREFIX . 'use_dot', '<label for="' . self::PREFIX . 'use_dot">' . __('Imperial Date Use Dot', 'use_dot') . '</label>', [$this, 'selectUseDot'], 'general');
    }

    /**
     * Filters the default date formats.
     *
     * @param array $default_date_formats Array of default date formats.
     */
    public function filterDateFormat($default_date_formats) {

        $default_date_formats[] = 'Imperial Date';

        return $default_date_formats;
    }

    /**
     * Filters the date formatted based on the locale.
     *
     * @param string $j          Formatted date string.
     * @param string $req_format Format to display the date.
     * @param int    $i          Unix timestamp.
     * @param bool   $gmt        Whether to convert to GMT for time. Default false.
     */
    public function filterDatei18n($j, $req_format, $i, $gmt) {

        if ($req_format == 'Imperial Date') {
            return $this->getDate($i);
        } elseif ($req_format == 'Imperial Time') {
            return $this->getTime($i);
        }

        return $j;
    }

    /**
     * Filters the default time formats.
     *
     * @param array $default_time_formats Array of default time formats.
     */
    public function filterTimeFormats($default_time_formats) {

        $default_time_formats[] = 'Imperial Time';

        return $default_time_formats;
    }

    public function getFormatedDate($check_number, $year_fraction, $year, $millennium, $use_dot) {
        $dot = $use_dot ? '.' : '';

        return sprintf("%d%d%03d%sM%d", $check_number, $year_fraction, $year, $dot, $millennium);
    }

    public function getYearFraction($timestamp) {
        $day_of_year = date('z', $timestamp) + 1;
        $h = date('G', $timestamp);

        return floor(( $day_of_year * 24 + $h) * self::MAKR_CONSTANT);
    }

    public function getYear($timestamp) {
        $year = date('Y', $timestamp);

        return $year - 2000;
    }

    public function getMillennium($year) {
        return ceil($year / 1000);
    }

    public function getDate($timestamp = '') {
        $ts = !empty($timestamp) ? $timestamp : time();

        $wp_year = date('Y', $ts);
        $check_number = $this->getDefaultCheckNumber();
        $year_fraction = $this->getYearFraction($ts);
        $year = $this->getYear($ts);
        $millennium = $this->getMillennium($wp_year);
        $use_dot = $this->getDefaultUseDat();

        return $this->getFormatedDate($check_number, $year_fraction, $year, $millennium, $use_dot);
    }

    public function getTime($timestamp = '') {
        $ts = !empty($timestamp) ? $timestamp : time();

        $year_fraction = $this->getYearFraction($ts);

        return $year_fraction;
    }

}

BC_Imperial_Date::getInstance();


<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   theme_boost_bld_const
 * @copyright 2016 Ryan Wyllie
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_bld_const_admin_settingspage_tabs('themesettingboost_bld_const', get_string('configtitle', 'theme_boost_bld_const'));
    $page = new admin_settingpage('theme_boost_bld_const_general', get_string('generalsettings', 'theme_boost_bld_const'));

    // Preset.
    $name = 'theme_boost_bld_const/preset';
    $title = get_string('preset', 'theme_boost_bld_const');
    $description = get_string('preset_desc', 'theme_boost_bld_const');
    $default = 'default.scss';

    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'theme_boost_bld_const', 'preset', 0, 'itemid, filepath, filename', false);

    $choices = [];
    foreach ($files as $file) {
        $choices[$file->get_filename()] = $file->get_filename();
    }
    // These are the built in presets.
    $choices['default.scss'] = 'default.scss';
    $choices['unitec-00.scss'] = 'unitec-00.scss';
    $choices['unitec-01.scss'] = 'unitec-01.scss';
    $choices['unitec-02.scss'] = 'unitec-02.scss';
    $choices['unitec-03.scss'] = 'unitec-03.scss';
    $choices['unitec-04.scss'] = 'unitec-04.scss';
    $choices['unitec-05.scss'] = 'unitec-05.scss';
    $choices['unitec-06.scss'] = 'unitec-06.scss';
    $choices['unitec-07.scss'] = 'unitec-07.scss';
    $choices['unitec-08.scss'] = 'unitec-08.scss';
    $choices['unitec-09.scss'] = 'unitec-09.scss';
    $choices['unitec-10.scss'] = 'unitec-10.scss';
    $choices['police.scss'] = 'police.scss';
    $choices['hawkins.scss'] = 'hawkins.scss';
    $choices['swift.scss'] = 'swift.scss';
    $choices['plain.scss'] = 'plain.scss';

    $setting = new admin_setting_configthemepreset($name, $title, $description, $default, $choices, 'boost_bld_const');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Preset files setting.
    $name = 'theme_boost_bld_const/presetfiles';
    $title = get_string('presetfiles','theme_boost_bld_const');
    $description = get_string('presetfiles_desc', 'theme_boost_bld_const');

    $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
        array('maxfiles' => 20, 'accepted_types' => array('.scss')));
    $page->add($setting);

    // Background image setting.
    $name = 'theme_boost_bld_const/backgroundimage';
    $title = get_string('backgroundimage', 'theme_boost_bld_const');
    $description = get_string('backgroundimage_desc', 'theme_boost_bld_const');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Variable $body-color.
    // We use an empty default value because the default colour should come from the preset.
    $name = 'theme_boost_bld_const/brandcolor';
    $title = get_string('brandcolor', 'theme_boost_bld_const');
    $description = get_string('brandcolor_desc', 'theme_boost_bld_const');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Must add the page after definiting all the settings!
    $settings->add($page);

    // Advanced settings.
    $page = new admin_settingpage('theme_boost_bld_const_advanced', get_string('advancedsettings', 'theme_boost_bld_const'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_scsscode('theme_boost_bld_const/scsspre',
        get_string('rawscsspre', 'theme_boost_bld_const'), get_string('rawscsspre_desc', 'theme_boost_bld_const'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_scsscode('theme_boost_bld_const/scss', get_string('rawscss', 'theme_boost_bld_const'),
        get_string('rawscss_desc', 'theme_boost_bld_const'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}

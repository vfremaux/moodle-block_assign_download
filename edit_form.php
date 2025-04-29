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
 * Block instance editing form.
 *
 * @package     block_assign_download
 * @author      Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_assign_download_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $COURSE;

        $config = get_config('block_assign_download');

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $assigns = $DB->get_records_menu('assign', array('course' => $COURSE->id), 'id,name', 'id,name');

        if (!empty($assigns)) {
            $select = &$mform->addElement('select', 'config_assignid', get_string('assigns', 'block_assign_download'), $assigns);
            $select->setMultiple(true);
        } else {
            $mform->addElement('static', 'noassigns', get_string('noassigns', 'block_assign_download'));
        }

        $mform->addElement('advcheckbox', 'usersasfolders', get_string('usersasfolders', 'block_assign_download'));

    }

    public function set_data($defaults) {

        // Put back into array for a multiple selector.
        $defaults->assignid = explode(',', @$defaults->assignid);
        parent::set_data($defaults);
    }
}

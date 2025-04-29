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
 * @package     block_assign_download
 * @category    blocks
 * @autor       Valery Fremaux <valery.Fremaux@gmail.com>
 * @copyright   2017 onwards Valery Fremaux (http://mylearningfactory.com)
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/assign_download/locallib.php');

$blockid = required_param('blockid', PARAM_INT);
$scope = required_param('scope', PARAM_TEXT);
$assignid = required_param('assignid', PARAM_TEXT);

if (!$blockrec = $DB->get_record('block_instances', array('id' => $blockid))) {
    print_error('badblockid', 'block_assign_download');
}

if (!$assign = $DB->get_record('assign', array('id' => $assignid))) {
    print_error('invalidassign', 'block_assign_download');
}

$theblock = block_instance('assign_download', $blockrec);

$params = array('blockid' => $blockrec->id, 'scope' => $scope, 'assignid' => $assignid);
$url = new moodle_url('/block/assign_download/download.php', $params);
$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_instance('assign', $assign->id)) {
    print_error('invalidmodule');
}

if (!$course = $DB->get_record('course', array('id' => $assign->course))) {
    print_error('coursemisconf');
}

$context = context_module::instance($cm->id);
require_login($course);
require_capability('mod/assign:grade', $context);

$func = 'get_'.$scope;

$userids = $theblock->$func($assignid);
print_object($userids);
$assign = new download_assign($context, $cm, $course);

$assign->set_block($theblock);
$result = $assign->download_submissions($userids);

if (!empty($result)) {
    echo $result;
}
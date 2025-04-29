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

defined('MOODLE_INTERNAL') || die();

class block_assign_download extends block_base {

    function init() {
        global $PAGE;
        $this->title = get_string('pluginname', 'block_assign_download');
    }

    /**
     * is the bloc configurable ?
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     * do we have local config
     */
    function instance_allow_config() {
        return true;
    }

    /**
     *
     */
    public function applicable_formats() {
        return array('all' => false, 'course' => true, 'admin' => false, 'my' => false);
    }

    /**
     * check conditions for visibility
     */
    function is_empty(){
        $this->get_content();
        return(empty($this->content->text) && empty($this->content->footer));
    }

    /**
     * Produce content for the bloc
     */
    function get_content() {
        global $PAGE, $OUTPUT, $DB;

        $renderer = $PAGE->get_renderer('block_assign_download');

        $blockcontext = context_block::instance($this->instance->id);

        if (!has_capability('block/assign_download:view', $blockcontext)) {
            $this->content = new StdClass;
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        $template = new StdClass;

        $this->content = new StdClass;

        if (empty($this->config) || empty($this->config->assignid)) {
            $template->notassigned = true;
            $template->notassignedlbl = $OUTPUT->notification(get_string('notassigned', 'block_assign_download'));
        } else {

            $this->content->text = '';
            $assignids = explode(',', $this->config->assignid);

            foreach ($assignids as $assignid) {

                $assignname = $DB->get_field('assign', 'name', array('id' => $assignid));

                $template->assignlbl = get_string('assign', 'block_assign_download', format_string($assignname));

                $cm = get_coursemodule_from_instance('assign', $assignid);
                if (!$cm) {
                    continue;
                }
                $modcontext = context_module::instance($cm->id);
                if (!has_capability('mod/assign:grade', $modcontext)) {
                    $this->content = new StdClass;
                    $this->content->text = '';
                    $this->content->footer = '';
                    return $this->content;
                }

                $template->hasnew = $this->has_new($assignid);
                $template->hasold = $this->has_old($assignid);

                if ($template->hasnew + $template->hasold == 0) {
                    $template->nosubmissions = true;
                    $template->nosubmissionslbl = $OUTPUT->notification(get_string('nosubmissions', 'block_assign_download'));
                }

                $params = array('blockid' => $this->instance->id, 'scope' => 'new', 'assignid' => $assignid);
                $template->getnewurl = new moodle_url('/blocks/assign_download/download.php', $params);
                $params = array('blockid' => $this->instance->id, 'scope' => 'all', 'assignid' => $assignid);
                $template->getallurl = new moodle_url('/blocks/assign_download/download.php', $params);
                $template->getnewlbl = get_string('getnew', 'block_assign_download', $template->hasnew);
                $template->getalllbl = get_string('getall', 'block_assign_download', $template->hasold + $template->hasnew);

                $lastvisitdate = $DB->get_field('block_assign_download', 'MAX(timeretrieved)', array('assignid' => $assignid));
                if ($lastvisitdate) {
                    $lastvisit = userdate($lastvisitdate);
                } else {
                    $lastvisit = '-';
                }
                $template->lastvisit = get_string('lastvisit', 'block_assign_download', $lastvisit);
                $this->content->text .= $renderer->render_content($template);
            }
        }

        $this->content->footer = '';


        return $this->content;
    }

    protected function has_new($assignid) {
        global $DB;

        $sql = "
            SELECT
                COUNT(*)
            FROM
                {assign_submission} ass
            LEFT JOIN
                {block_assign_download} ad
            ON
                ass.assignment = ad.assignid AND
                ass.id = ad.submissionid
            WHERE
                ((ad.assignid IS NULL) OR
                (ad.timeretrieved < ass.timemodified)) AND
                status = 'submitted' AND
                ass.assignment = ?
        ";
        $new = $DB->count_records_sql($sql, array($assignid));

        return $new;
    }

    public function get_new($assignid) {
        global $DB;

        $sql = "
            SELECT DISTINCT
                ass.userid,
                ass.userid
            FROM
                {assign_submission} ass
            LEFT JOIN
                {block_assign_download} ad
            ON
                ass.assignment = ad.assignid AND
                ass.id = ad.submissionid
            WHERE
                ((ad.assignid IS NULL) OR
                (ad.timeretrieved < ass.timemodified)) AND
                status = 'submitted' AND
                ass.assignment = ? AND
                latest = 1
        ";

        if ($newsubs = $DB->get_records_sql($sql, array($assignid))) {
            return array_keys($newsubs);
        }

        return array();
    }

    protected function has_old($assignid) {
        global $DB;

        $sql = "
            SELECT
                COUNT(*)
            FROM
                {assign_submission} ass
            LEFT JOIN
                {block_assign_download} ad
            ON
                ass.assignment = ad.assignid AND
                ass.id = ad.submissionid
            WHERE
                ad.assignid IS NOT NULL AND
                ass.assignment = ? AND
                status = 'submitted' AND
                latest = 1 AND
                ad.timeretrieved >= ass.timemodified
        ";

        $old = $DB->count_records_sql($sql, array($assignid));

        return $old;
    }

    public function get_old($assignid) {
        global $DB;

        $sql = "
            SELECT DISTINCT
                ass.userid,
                ass.userid
            FROM
                {assign_submission} ass,
                {block_assign_download} ad
            WHERE
                ass.assignment = ad.assignid AND
                ass.id = ad.submissionid AND
                ass.assignment = ? AND
                status = 'submitted' AND
                latest = 1 AND
                ad.timeretrieved >= ass.timemodified
        ";

        if ($oldsubs = $DB->get_records_sql($sql, array($assignid))) {
            return array_keys($oldsubs);
        }

        return array();
    }

    public function get_all($assignid) {
        global $DB;

        $sql = "
            SELECT DISTINCT
                ass.userid,
                ass.userid
            FROM
                {assign_submission} ass
            WHERE
                ass.assignment = ? AND
                status = 'submitted' AND
                latest = 1
        ";

        if ($oldsubs = $DB->get_records_sql($sql, array($assignid))) {
            return array_keys($oldsubs);
        }

        return array();
    }

    public function instance_config_save($data, $nolongerused = false) {

        $data->assignid = implode(',', $data->assignid);

        parent::instance_config_save($data);
    }

}


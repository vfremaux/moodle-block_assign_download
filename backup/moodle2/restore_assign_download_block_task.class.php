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
 * @subpackage  backup-moodle2
 * @copyright   2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Specialised restore task for the assign_download block
 * (has own DB structures to backup)
 *
 * TODO: Finish phpdocs
 */
class restore_assign_download_block_task extends restore_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
    }

    public function get_fileareas() {
        // No associated fileareas.
        return array();
    }

    public function get_configdata_encoded_attributes() {
        // No special handling of configdata.
        return array();
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }

    // Each block will be responsible for his own remapping in is associated pageid.
    public function after_restore() {
        global $DB;

        $courseid = $this->get_courseid();
        $blockid = $this->get_blockid();
        $oldblockid = $this->get_old_blockid();

        // These are fake blocks that can be cached in backup.
        if (!$blockid) {
            return;
        }

        // Adjust the serialized configdata->assignid to the actualized restored assign ids.
        // Get the configdata.
        $configdata = $DB->get_field('block_instances', 'configdata', array('id' => $blockid));
        // Extract configdata.
        $config = unserialize(base64_decode($configdata));
        // Set array of used rss feeds.
        // TODO check this, not sure course modules are stored in backup mapping tables as this.
        if ($config && !empty($config->assignid)) {

            $assignids = explode(',', $this->config->assignid);

            $newids = array();
            foreach ($assignids as $assignid) {
                $newid = $this->get_mappingid('assign', $oldassignid);
                if ($newid) {
                    $newids[] = $newid;
                }
            }

            $config->assignid = implode(',', $newids);
            // Serialize back the configdata
            $configdata = base64_encode(serialize($config));
            // Set the configdata back.
            $DB->set_field('block_instances', 'configdata', $configdata, array('id' => $blockid));
        }
    }

    /**
     * Return the new id of a mapping for the given itemname
     *
     * @param string $itemname the type of item
     * @param int $oldid the item ID from the backup
     * @param mixed $ifnotfound what to return if $oldid wasnt found. Defaults to false
     */
    public function get_mappingid($itemname, $oldid, $ifnotfound = false) {
        $mapping = $this->get_mapping($itemname, $oldid);
        return $mapping ? $mapping->newitemid : $ifnotfound;
    }

    /**
     * Return the complete mapping from the given itemname, itemid
     */
    public function get_mapping($itemname, $oldid) {
        return restore_dbops::get_backup_ids_record($this->plan->get_restoreid(), $itemname, $oldid);
    }
}

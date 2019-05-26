<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Hook class.
 */
class Hook_checklist_version
{
    /**
     * Find items to include on the staff checklist.
     *
     * @return array An array of tuples: The task row to show, the number of seconds until it is due (or null if not on a timer), the number of things to sort out (or null if not on a queue), The name of the config option that controls the schedule (or null if no option).
     */
    public function run()
    {
        require_code('version2');
        $version = get_future_version_information();
        $ve = $version->evaluate();
        $version_outdated = (strpos($ve, 'You are running the latest version') === false) && (strpos($ve, 'This version does not exist in our database') === false) && (strpos($ve, 'Cannot connect') === false) && (cms_version_minor() != 'custom');

        require_code('addons2');
        $num_addons_outdated = (cms_version_minor() == 'custom') ? 0 : count(find_updated_addons());

        if (($version_outdated) || ($num_addons_outdated > 0)) {
            $status = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_0', array('_GUID' => 'm578142633c6f3d37776e82a869deb91'));
        } else {
            $status = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_1', array('_GUID' => 'n578142633c6f3d37776e82a869deb91'));
        }

        if ($version_outdated) {
            $url = new Tempcode(); // Don't want to point people to upgrade addons if on an old version
        } else {
            $url = build_url(array('page' => 'admin_addons', 'type' => 'browse'), get_module_zone('admin_addons'));
        }

        require_lang('addons');

        $cnt = $num_addons_outdated + ($version_outdated ? 1 : 0);
        $tpl = do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM', array('_GUID' => 'bbcf866e2ea104ac41685a8756e182f8', 'URL' => $url, 'STATUS' => $status, 'TASK' => do_lang_tempcode('UPGRADE'), 'INFO' => do_lang_tempcode('NUM_QUEUE', escape_html(integer_format($cnt)))));
        return array(array($tpl, null, $cnt, null));
    }
}

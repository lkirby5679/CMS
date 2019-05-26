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
 * @package    core_comcode_pages
 */

/**
 * Hook class.
 */
class Hook_cleanup_comcode_pages
{
    /**
     * Find details about this cleanup hook.
     *
     * @return ?array Map of cleanup hook info (null: hook is disabled).
     */
    public function info()
    {
        require_lang('zones');

        $info = array();
        $info['title'] = do_lang_tempcode('COMCODE_PAGES');
        $info['description'] = do_lang_tempcode('DESCRIPTION_COMCODE_PAGE_CACHE');
        $info['type'] = 'cache';

        return $info;
    }

    /**
     * Run the cleanup hook action.
     *
     * @return Tempcode Results
     */
    public function run()
    {
        require_code('caches3');

        erase_comcode_page_cache();

        return new Tempcode();
    }
}
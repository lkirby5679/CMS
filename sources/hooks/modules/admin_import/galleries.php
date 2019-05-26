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
 * @package    galleries
 */

/**
 * Hook class.
 */
class Hook_galleries
{
    /**
     * Standard importer hook info function.
     *
     * @return ?array Importer handling details (null: importer is disabled).
     */
    public function info()
    {
        $info = array();
        $info['product'] = 'Gallery media';
        $info['hook_type'] = 'redirect';
        $info['import_module'] = 'cms_galleries';
        $info['import_method_name'] = 'import';
        return $info;
    }
}
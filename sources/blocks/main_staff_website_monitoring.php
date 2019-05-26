<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*NO_API_CHECK*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_adminzone_dashboard
 */

/**
 * Block class.
 */
class Block_main_staff_website_monitoring
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Jack Franklin';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 3;
        $info['locked'] = false;
        $info['parameters'] = array();
        $info['update_require_upgrade'] = true;
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled).
     */
    public function caching_environment()
    {
        $info = array();
        $info['cache_on'] = '(count($_POST)>0)?null:array()'; // No cache on POST as this is when we save text data
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60 * 5;
        return $info;
    }

    /**
     * Uninstall the block.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('staff_website_monitoring');
    }

    /**
     * Install the block.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        if ((is_null($upgrade_from)) || ($upgrade_from < 2)) {
            $GLOBALS['SITE_DB']->create_table('staff_website_monitoring', array(
                'id' => '*AUTO',
                'site_url' => 'URLPATH',
                'site_name' => 'SHORT_TEXT',
            ));

            $GLOBALS['SITE_DB']->query_insert('staff_website_monitoring', array(
                'site_url' => get_base_url(),
                'site_name' => get_site_name(),
            ));
        }
    }

    /**
     * Function to find Alexa details of the site.
     *
     * @param  string $url The URL of the site which you want to find out information on.)
     * @return array Returns a triple array with the rank, the amount of links, and the speed of the site.
     */
    public function getAlexaRank($url)
    {
        require_lang('staff_checklist');

        require_code('files');
        $p = array();
        $_url = 'https://www.alexa.com/minisiteinfo/' . urlencode($url);
        $result = http_download_file($_url, null, false, false, 'Composr', null, null, null, null, null, null, null, null, 1.0);
        if (preg_match('#([\d,]+)\s*</a>\s*</div>\s*<div class="label">Alexa Traffic Rank#s', $result, $p) != 0) {
            $rank = integer_format(intval($p[1]));
        } else {
            $rank = do_lang('NA');
        }
        if (preg_match('#([\d,]+)\s*</a>\s*</div>\s*<div class="label">Sites Linking In#s', $result, $p) != 0) {
            $links = integer_format(intval($p[1]));
        } else {
            $links = '0';
        }
        $speed = '?';

        // we would like, but cannot get (without an API key)...
        /*
            time on site
            reach (as a percentage)
            page views
            audience (i.e. what country views the site most)
         */

        return array($rank, $links, $speed);
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters.
     * @return Tempcode The result of execution.
     */
    public function run($map)
    {
        $links = post_param_string('website_monitoring_list_edit', null);
        if (!is_null($links)) {
            $GLOBALS['SITE_DB']->query_delete('staff_website_monitoring');
            $items = explode("\n", $links);
            foreach ($items as $i) {
                $q = trim($i);
                if (!empty($q)) {
                    $bits = explode('=', $q);
                    if (count($bits) >= 2) {
                        $last_bit = array_pop($bits);
                        $bits = array(implode('=', $bits), $last_bit);
                        $link = $bits[0];
                        $site_name = $bits[1];
                    } else {
                        $link = $q;

                        require_code('files2');
                        $meta_details = get_webpage_meta_details($link);
                        $site_name = $meta_details['t_title'];
                        if ($site_name == '') {
                            $site_name = $link;
                        }
                    }
                    $GLOBALS['SITE_DB']->query_insert('staff_website_monitoring', array('site_name' => $site_name, 'site_url' => fixup_protocolless_urls($link)));
                }
            }

            decache('main_staff_website_monitoring');

            log_it('SITE_WATCHLIST');
        }

        $rows = $GLOBALS['SITE_DB']->query_select('staff_website_monitoring');

        $sites_being_watched = array();
        $grid_data = array();
        if (count($rows) > 0) {
            foreach ($rows as $r) {
                $alex = $this->getAlexaRank(($r['site_url']));
                $sites_being_watched[$r['site_url']] = $r['site_name'];
                $alexa_ranking = $alex[0];
                $alexa_traffic = $alex[1];

                $grid_data[] = array(
                    'URL' => $r['site_url'],
                    'ALEXA_RANKING' => $alexa_ranking,
                    'ALEXA_TRAFFIC' => $alexa_traffic,
                    'SITE_NAME' => $r['site_name'],
                );
            }
        }

        $map_comcode = get_block_ajax_submit_map($map);
        return do_template('BLOCK_MAIN_STAFF_WEBSITE_MONITORING', array('_GUID' => '0abf65878c508bf133836589a8cc45da', 'URL' => get_self_url(), 'BLOCK_NAME' => 'main_staff_website_monitoring', 'MAP' => $map_comcode, 'SITES_BEING_WATCHED' => $sites_being_watched, 'GRID_DATA' => $grid_data));
    }
}
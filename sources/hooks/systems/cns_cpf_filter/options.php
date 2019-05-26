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
class Hook_cns_cpf_filter_options
{
    /**
     * Find which special CPFs to enable.
     *
     * @return array A list of CPFs to enable
     */
    public function to_enable()
    {
        $cpf = array();

        if (get_option('cpf_enable_phone') == '1') {
            $cpf = array_merge($cpf, array('mobile_phone_number' => true));
        }

        if (get_option('cpf_enable_street_address') == '1') {
            $cpf = array_merge($cpf, array('street_address' => true));
        }

        if (get_option('cpf_enable_state') == '1') {
            $cpf = array_merge($cpf, array('state' => true));
        }

        if (get_option('cpf_enable_county') == '1') {
            $cpf = array_merge($cpf, array('county' => true));
        }

        if (get_option('cpf_enable_country') == '1') {
            $cpf = array_merge($cpf, array('country' => true));
        }

        if (get_option('cpf_enable_post_code') == '1') {
            $cpf = array_merge($cpf, array('post_code' => true));
        }

        if (get_option('cpf_enable_city') == '1') {
            $cpf = array_merge($cpf, array('city' => true));
        }

        if (get_option('cpf_enable_name') == '1') {
            $cpf = array_merge($cpf, array('firstname' => true, 'lastname' => true));
        }

        return $cpf;
    }
}
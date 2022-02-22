<?php

/*
MyBB-Thread-Starter Plugin for MyBB
Copyright (C) 2015-> SvePu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

if (defined('IN_ADMINCP'))
{
    $plugins->add_hook("admin_config_settings_begin", 'threadstarter_settings');
    $plugins->add_hook("admin_page_output_footer", 'threadstarter_settings_peeker');
}
else
{
    $plugins->add_hook('postbit', 'threadstarter_postbit');
}

function threadstarter_info()
{
    global $plugins_cache, $db, $lang;
    $lang->load('config_threadstarter');
    $info = array(
        "name" => $db->escape_string($lang->threadstarter),
        "description" => $db->escape_string($lang->threadstarter_desc),
        "website" => "https://github.com/SvePu/MyBB-Thread-Starter",
        "author" => "SvePu",
        "authorsite" => "https://github.com/SvePu",
        "codename" => "threadstarter",
        "version" => "1.5",
        "compatibility" => "18*"
    );

    $info_desc = '';
    $gid_result = $db->simple_select('settinggroups', 'gid', "name = 'threadstarter'", array('limit' => 1));
    $settings_group = $db->fetch_array($gid_result);
    if (!empty($settings_group['gid']))
    {
        $info_desc .= "<span style=\"font-size: 0.9em;\">(~<a href=\"index.php?module=config-settings&action=change&gid=" . $settings_group['gid'] . "\"> " . $db->escape_string($lang->setting_group_threadstarter) . " </a>~)</span>";
    }

    if (is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['threadstarter'])
    {
        $info_desc .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float: right;" target="_blank" />
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="VGQ4ZDT8M7WS2" />
<input type="image" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
</form>';
    }

    if ($info_desc != '')
    {
        $info['description'] = $info_desc . '<br />' . $info['description'];
    }

    return $info;
}

function threadstarter_install()
{
    global $db, $lang;
    $lang->load('config_threadstarter');

    $group = array(
        'name' => 'threadstarter',
        'title' => $db->escape_string($lang->setting_group_threadstarter),
        'description' => $db->escape_string($lang->setting_group_threadstarter_desc),
        'isdefault' => 0
    );

    $query = $db->simple_select('settinggroups', 'gid', "name='threadstarter'");

    if ($gid = (int)$db->fetch_field($query, 'gid'))
    {
        $db->update_query('settinggroups', $group, "gid='{$gid}'");
    }
    else
    {
        $query = $db->simple_select('settinggroups', 'MAX(disporder) AS disporder');
        $disporder = (int)$db->fetch_field($query, 'disporder');

        $group['disporder'] = ++$disporder;

        $gid = (int)$db->insert_query('settinggroups', $group);
    }

    $settings = array(
        'enable' => array(
            'optionscode' => 'yesno',
            'value' => 1
        ),
        'choise' => array(
            'optionscode' => 'radio \n 1=' . $db->escape_string($lang->setting_threadstarter_choise_1) . ' \n 2=' . $db->escape_string($lang->setting_threadstarter_choise_2),
            'value' => '1'
        ),
        'text' => array(
            'optionscode' => 'text',
            'value' => ''
        ),
        'image' => array(
            'optionscode' => 'text',
            'value' => '',
        ),
        'firstpostlink' => array(
            'optionscode' => 'yesno',
            'value' => 1,
        )
    );

    $disporder = 0;

    foreach ($settings as $key => $setting)
    {
        $key = "threadstarter_{$key}";

        $setting['name'] = $db->escape_string($key);

        $lang_var_title = "setting_{$key}";
        $lang_var_description = "setting_{$key}_desc";

        $setting['title'] = $db->escape_string($lang->{$lang_var_title});
        $setting['description'] = $db->escape_string($lang->{$lang_var_description});
        $setting['disporder'] = $disporder;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
        ++$disporder;
    }

    rebuild_settings();
}

function threadstarter_is_installed()
{
    global $mybb;
    if (isset($mybb->settings['threadstarter_enable']))
    {
        return true;
    }
    return false;
}

function threadstarter_uninstall()
{
    global $db;

    $result = $db->simple_select('settinggroups', 'gid', "name = 'threadstarter'", array('limit' => 1));
    $group = $db->fetch_array($result);

    if (!empty($group['gid']))
    {
        $db->delete_query('settinggroups', "gid='{$group['gid']}'");
        $db->delete_query('settings', "gid='{$group['gid']}'");
        rebuild_settings();
    }
}

function threadstarter_activate()
{
    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'userstars\']}') . "#i", "{\$post['threadstarter']}\n{\$post['userstars']}");
    find_replace_templatesets("postbit_classic", "#" . preg_quote('{$post[\'userstars\']}') . "#i", "{\$post['threadstarter']}\n{\$post['userstars']}");
}

function threadstarter_deactivate()
{
    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'threadstarter\']}') . "(\r?)\n#", '', 0);
    find_replace_templatesets("postbit_classic", "#" . preg_quote('{$post[\'threadstarter\']}') . "(\r?)\n#", '', 0);
}

function threadstarter_settings()
{
    global $lang, $db, $mybb, $ts_settings_peeker;
    $lang->load('config_threadstarter');
    $query = $db->simple_select("settinggroups", "gid as gid", "name='threadstarter'", array('limit' => 1));
    $gid = $db->fetch_field($query, 'gid');
    $ts_settings_peeker = ($mybb->input["gid"] == $gid) && ($mybb->request_method != "post");
}

function threadstarter_settings_peeker()
{
    global $ts_settings_peeker;
    if ($ts_settings_peeker)
    {
        echo '<script type="text/javascript">
        $(document).ready(function(){
            new Peeker($("#setting_threadstarter_choise_1"), $("#row_setting_threadstarter_text"), 1, true),
            new Peeker($("#setting_threadstarter_choise_2"), $("#row_setting_threadstarter_text"), 1, false),
            new Peeker($("#setting_threadstarter_choise_1"), $("#row_setting_threadstarter_image"), 2, false),
            new Peeker($("#setting_threadstarter_choise_2"), $("#row_setting_threadstarter_image"), 2, true);
        });
        </script>';
    }
}

function threadstarter_postbit(&$post)
{
    global $thread, $mybb, $postcounter, $theme;

    if ($mybb->settings['threadstarter_enable'] == 0 || $thread['uid'] == 0)
    {
        return;
    }

    $threadstarter = "";

    switch ($mybb->settings['threadstarter_choise'])
    {
        case 1:
            $tstext = "ThreadStarter";

            if (!empty($mybb->settings['threadstarter_text']))
            {
                $tstext = htmlspecialchars_uni($mybb->settings['threadstarter_text']);
            }

            $threadstarter = "<span class=\"ts_text\">" .  $tstext . "</span>";
            break;
        case 2:
            $tsimage = $mybb->settings['bburl'] . "/images/default_ts_image.png";

            if (!empty($mybb->settings['threadstarter_image']))
            {
                $tsimage_check = trim($mybb->settings['threadstarter_image']);

                if (strpos($tsimage_check, '{theme}') !== false)
                {
                    $tsimage_check = preg_replace('/\{theme\}/i', $theme['imgdir'], $tsimage_check);
                }

                if (@getimagesize($tsimage_check))
                {
                    $tsimage = $tsimage_check;
                }
            }

            if ($imgdim = @getimagesize($tsimage))
            {
                list($width, $height, $type, $width_height) = $imgdim;
                $threadstarter = "<img class=\"ts_image\" " . $width_height . " src=\"" . $tsimage . "\" alt=\"threadstarter\" />";
            }
            break;
    }

    $post['threadstarter'] = "";
    if ($post['uid'] == $thread['uid'] && $postcounter > 1 && !empty($threadstarter))
    {
        $post['threadstarter'] = $threadstarter . "<br />";

        if ($mybb->settings['threadstarter_firstpostlink'] != 0)
        {
            $post['threadstarter'] = "<a href=\"" . $mybb->settings['bburl'] . "/" . get_post_link($thread['firstpost'], $thread['tid']) . "#pid" . $thread['firstpost'] . "\">" . $threadstarter . "</a><br />";
        }
    }
}

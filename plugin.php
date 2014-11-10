<?php

class gitlab_issues extends SlackServicePlugin {

    public $name = "GitLab Issues";
    public $desc = "Source control and issue management.";

    public $cfg = array(
        'has_token' => true,
    );

    function onInit() {
        $channels = $this->getChannelsList();

        foreach ($channels as $k => $v) {
            if ($v == '#general') {
                $this->icfg['channel']      = $k;
                $this->icfg['channel_name'] = $v;
            }
        }

        $this->icfg['botname'] = 'gitlab';
    }

    function onView() {
        return $this->smarty->fetch('view.tpl');
    }

    function onEdit() {
        $channels = $this->getChannelsList();

        if ($_GET['save']) {
            $this->icfg['channel']      = $_POST['channel'];
            $this->icfg['channel_name'] = $channels[$_POST['channel']];
            $this->icfg['botname']      = $_POST['botname'];
            $this->saveConfig();

            header("location: {$this->getViewUrl()}&saved=1");
            exit;
        }

        $this->smarty->assign('channels', $channels);

        return $this->smarty->fetch('edit.tpl');
    }

    function onHook($req) {
        global $cfg;

        if (!$this->icfg['channel']) {
            return array(
                'ok'    => false,
                'error' => "No channel configured",
            );
        }

        $gitlab_payload = json_decode($req['post_body']);

        if (!$gitlab_payload || !is_object($gitlab_payload) || $gitlab_payload->object_kind != "issue") {
            return array(
                'ok'    => false,
                'error' => "No payload received from gitlab",
            );
        }

        $message = sprintf(
            '*Issue #%s* - %s - *[%s]*',
            $gitlab_payload->object_attributes->iid,
            $gitlab_payload->object_attributes->title,
            strtoupper($gitlab_payload->object_attributes->action)
        );

        if (count($fields) > 0) {
            $this->postToChannel($message, array(
                'channel'     => $this->icfg['channel'],
                'username'    => $this->icfg['botname'],
                'icon_url'    => 'https://cdn.pancentric.com/cdn/libs/icons/gitlab.png'
            ));
        }

        return array(
            'ok'     => true,
            'status' => "Nothing found to report",
        );
    }

    function getLabel() {
        return "Post issue updates to {$this->icfg['channel_name']} as {$this->icfg['botname']}";
    }

}

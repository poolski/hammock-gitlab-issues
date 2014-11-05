<?php

class gitlab_commits extends SlackServicePlugin {

    public $name = "GitLab Commits";
    public $desc = "Source control and code management.";

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

        if (!$gitlab_payload || !is_object($gitlab_payload) || !isset($gitlab_payload->object_attributes)) {
            return array(
                'ok'    => false,
                'error' => "No payload received from gitlab",
            );
        }

        $fields = array();
        foreach ($gitlab_payload->object_attributes as $issue) {
            $fields[] = array(
                'text' => sprintf(
                    '<%s|%s> - %s',
                    $issue->url,
                    substr($issue->id, 0, 9),
                    $issue->title
                ),
                'color' => 'good',
            );
        }

        $message = sprintf(
            'New/updated issue on <%s|%s>',
            $gitlab_payload->object_attributes->url,
            $gitlab_payload->object_attributes->name,
            $gitlab_payload->object_attributes->state
        );

        if (count($fields) > 0) {
            $this->postToChannel($message, array(
                'channel'     => $this->icfg['channel'],
                'username'    => $this->icfg['botname'],
                'attachments' => $fields,
                'icon_url'    => $cfg['root_url'] . 'plugins/gitlab_commits/icon_128.png'
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

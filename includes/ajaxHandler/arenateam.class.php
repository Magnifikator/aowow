<?php

if (!defined('AOWOW_REVISION'))
    die('invalid access');

class AjaxArenaTeam extends AjaxHandler
{
    protected $validParams = ['resync', 'status'];
    protected $_get        = array(
        'id'     => [FILTER_CALLBACK, ['options' => 'AjaxHandler::checkIdList']],
    );

    public function __construct(array $params)
    {
        parent::__construct($params);

        if (!$this->params)
            return;

        switch ($this->params[0])
        {
            case 'status':
                $this->handler = 'handleStatus';            // returns status object
                break;
            case 'resync':
                $this->handler = 'handleResync';
                break;
        }
    }

    /*  params
            id: <prId1,prId2,..,prIdN>
            user: <string> [optional, not used]
        return: 1
    */
    protected function handleResync()
    {
        if ($teams = DB::Aowow()->select('SELECT realm, realmGUID FROM ?_profiler_arena_team WHERE id IN (?a)', $this->_get['id']))
            foreach ($teams as $t)
                Profiler::scheduleResync(TYPE_ARENA_TEAM, $t['realm'], $t['realmGUID']);

        return '1';
    }

    /*  params
            id: <prId1,prId2,..,prIdN>
        return
            <status object>
            [
                nQueueProcesses,
                [statusCode, timeToRefresh, curQueuePos, errorCode, nResyncTries],
                [<anotherStatus>]
                ...
            ]

            not all fields are required, if zero they are omitted
            statusCode:
                0: end the request
                1: waiting
                2: working...
                3: ready; click to view
                4: error / retry
            errorCode:
                0: unk error
                1: char does not exist
                2: armory gone
    */
    protected function handleStatus()
    {
        $response = Profiler::resyncStatus(TYPE_ARENA_TEAM, $this->_get['id']);
        return Util::toJSON($response);
    }
}

?>

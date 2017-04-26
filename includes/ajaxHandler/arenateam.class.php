<?php

if (!defined('AOWOW_REVISION'))
    die('invalid access');

class AjaxArenaTeam extends AjaxHandler
{
    protected $validParams = ['resync', 'status'];
    protected $_post       = array(
        'id' => [FILTER_CALLBACK, ['options' => 'AjaxGuild::checkId']],
    );

    public function __construct(array $params)
    {
        parent::__construct($params);

        if (!$this->params)
            return;

        switch ($this->params[0])
        {
            case 'resync':
            case 'status':
                $this->handler = 'handleResync';
                break;
        }
    }

    protected function handleResync()                       // resync init and status requests
    {
        /*  params
                id: <prId1,prId2,..,prIdN>
                user: <string> [optional]
            return
                null            [onOK]
                int or str      [onError]
        */

        if ($this->params[0] == 'resync')
            return '1';
        else // $this->params[0] == 'status'
        {
            /*
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

                [
                    processId,
                    [StatusCode, timeToRefresh, iCount, errorCode, iNResyncs],
                    [<anotherStatus>]...
                ]
            */
            return '[0, [4, 10000, 1, 2]]';
        }
    }

    protected function checkId($val)
    {
        // expecting id-list
        if (preg_match('/\d+(,\d+)*/', $val))
            return array_map('intVal', explode(', ', $val));

        return null;
    }
}

?>

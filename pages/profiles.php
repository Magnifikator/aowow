<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


// menuId 5: Profiler g_initPath()
//  tabId 1: Tools    g_initHeader()
class ProfilesPage extends GenericPage
{
    use TrProfiler;

    protected $roster   = 0;                                // $_GET['roster'] = 1|2|3|4 .. 2,3,4 arenateam-size (4 => 5-man), 1 guild .. it puts a resync button on the lv...

    protected $tabId    = 1;
    protected $path     = [1, 5, 0];
    protected $tpl      = 'profiles';
    protected $js       = ['filters.js', 'profile_all.js', 'profile.js'];
    protected $css      = [['path' => 'Profiler.css']];

    public function __construct($pageCall, $pageParam)
    {
        $this->getSubjectFromUrl($pageParam);

        $this->filterObj = new ProfileListFilter();

        foreach (Profiler::getRealms() as $idx => $r)
        {
            if ($this->region && $r['region'] != $this->region)
                continue;

            if ($this->realm && $r['name'] != $this->realm)
                continue;

            $this->sumSubjects += DB::Characters($idx)->selectCell('SELECT count(*) FROM characters WHERE deleteInfos_Name IS NULL');
        }

        parent::__construct($pageCall, $pageParam);

        $this->name   = Util::ucFirst(Lang::game('profiles'));
        $this->subCat = $pageParam ? '='.$pageParam : '';
    }

    protected function generateTitle()
    {
        if ($this->realm)
            array_unshift($this->title, $this->realm,/* CFG_BATTLEGROUP,*/ Lang::profiler('regions', $this->region), Lang::game('profiles'));
        else if ($this->region)
            array_unshift($this->title, Lang::profiler('regions', $this->region), Lang::game('profiles'));
        else
            array_unshift($this->title, Lang::game('profiles'));
    }

    protected function generateContent()
    {
        $this->addJS('?data=weight-presets.realms&locale='.User::$localeId.'&t='.$_SESSION['dataKey']);

        $conditions = array(
            ['deleteInfos_Name', null],
            ['level', MAX_LEVEL, '<='],                     // prevents JS errors
            [['extra_flags', 0x7D, '&'], 0]                 // not a staff char
        );

        if ($_ = $this->filterObj->getConditions())
            $conditions[] = $_;

        // recreate form selection
        $this->filter             = $this->filterObj->getForm();
        $this->filter['query']    = isset($_GET['filter']) ? $_GET['filter'] : null;
        $this->filter['initData'] = ['init' => 'profiles'];

        if ($x = $this->filterObj->getSetCriteria())
        {
            $this->filter['initData']['sc'] = $x;

            if ($r = array_intersect([9, 12, 15, 18], $x['cr']))
                if (count($r) == 1)
                    $this->roster = (reset($r) - 6) / 3;        // 1, 2, 3, or 4
        }

        $tabData = array(
            'id'          => 'characters',
            'hideCount'   => 1,
            'visibleCols' => ['race', 'classs', 'level', 'talents', 'achievementpoints', 'gearscore'],
            'onBeforeCreate' => '$pr_initRosterListview'        // puts a resync button on the lv
        );

        $skillCols = $this->filterObj->getExtraCols();
        if ($skillCols)
        {
            $xc = [];
            foreach ($skillCols as $skill => $__)
                $xc[] = "\$Listview.funcBox.createSimpleCol('Skill' + ".$skill.", g_spell_skills[".$skill."], '7%', 'skill' + ".$skill.")";

            $tabData['extraCols'] = $xc;
        }

        $miscParams = [];
        if ($this->realm)
            $miscParams['sv'] = $this->realm;
        if ($this->region)
            $miscParams['rg'] = $this->region;
        if ($_ = $this->filterObj->extraOpts)
            $miscParams['extraOpts'] = $_;

        $profiles = new RemoteProfileList($conditions, $miscParams);
        if (!$profiles->error)
        {
            // init these chars on our side and get local ids
            $profiles->initializeLocalEntries();

            $addInfoMask = PROFILEINFO_CHARACTER;

            // init roster-listview
            // $_GET['roster'] = 1|2|3|4 originally supplemented this somehow .. 2,3,4 arenateam-size (4 => 5-man), 1 guild
            if ($this->roster == 1 && !$profiles->hasDiffFields(['guild']) && $profiles->getField('guild'))
            {
                $tabData['roster']        = $this->roster;
                $tabData['visibleCols'][] = 'guildrank';
                $tabData['hiddenCols'][]  = 'guild';

                $this->roster  = Lang::profiler('guildRoster', [$profiles->getField('guildname')]);
            }
            else if ($this->roster && !$profiles->hasDiffFields(['arenateam']) && $profiles->getField('arenateam'))
            {
                $tabData['roster']        = $this->roster;
                $tabData['visibleCols'][] = 'rating';

                $addInfoMask |= PROFILEINFO_ARENA;
                $this->roster = Lang::profiler('arenaRoster', [$profiles->getField('arenateam')]);
            }
            else
                $this->roster = 0;

            $tabData['data'] = array_values($profiles->getListviewData($addInfoMask, $skillCols));

            // create note if search limit was exceeded
            if ($this->filter['query'] && $profiles->getMatches() > CFG_SQL_LIMIT_DEFAULT)
            {
                $tabData['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_charactersfound2', $this->sumSubjects, $profiles->getMatches());
                $tabData['_truncated'] = 1;
            }
            else if ($profiles->getMatches() > CFG_SQL_LIMIT_DEFAULT)
                $tabData['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_charactersfound', $this->sumSubjects, 0);

            if ($this->filterObj->error)
                $tabData['_errors'] = '$1';
        }
        else
            $this->roster = 0;


        $this->lvTabs[] = ['profile', $tabData];

        Lang::sort('game', 'cl');
        Lang::sort('game', 'ra');
    }
}

?>

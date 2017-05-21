<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');

// !do not cache!
/* older version
new Listview({
    template: 'profile',
    id: 'characters',
    name: LANG.tab_characters,
    parent: 'lkljbjkb574',
    visibleCols: ['race','classs','level','talents','gearscore','achievementpoints','rating'],
    sort: [-15],
    hiddenCols: ['arenateam','guild','location'],
    onBeforeCreate: pr_initRosterListview,
    data: [
        {id:30577430,name:'Çircus',achievementpoints:0,guild:'swaggin',guildrank:5,arenateam:{2:{name:'the bird knows the word',rating:1845}},realm:'maiev',realmname:'Maiev',battlegroup:'whirlwind',battlegroupname:'Whirlwind',region:'us',roster:2,row:1},
        {id:10602015,name:'Gremiss',achievementpoints:3130,guild:'Team Discovery Channel',guildrank:3,arenateam:{2:{name:'the bird knows the word',rating:1376}},realm:'maiev',realmname:'Maiev',battlegroup:'whirlwind',battlegroupname:'Whirlwind',region:'us',level:80,race:5,gender:1,classs:9,faction:1,gearscore:2838,talenttree1:54,talenttree2:17,talenttree3:0,talentspec:1,roster:2,row:2}
    ]
});
*/

// menuId 5: Profiler g_initPath()
//  tabId 1: Tools    g_initHeader()
class ProfilesPage extends GenericPage
{
    use TrProfiler;

    protected $tpl      = 'profiles';
    protected $js       = ['filters.js', 'profile_all.js', 'profile.js'];
    protected $css      = [['path' => 'Profiler.css']];
    protected $tabId    = 1;
    protected $path     = [1, 5, 0];
    protected $region   = '';                               // seconded..
    protected $realm    = '';                               // not sure about the use
    protected $roster   = 0;                                // $_GET['roster'] = 1|2|3|4 .. 2,3,4 arenateam-size (4 => 5-man), 1 guild .. it puts a resync button on the lv...

    protected $sumChars = 0;

    public function __construct($pageCall, $pageParam)
    {
        $cat = explode('.', $pageParam);
        if ($cat[0] && count($cat) < 3 && $cat[0] === 'eu' || $cat[0] === 'us')
        {
            $this->region = $cat[0];

            // if ($cat[1] == Profiler::urlize(CFG_BATTLEGROUP))
                // $this->realm = CFG_BATTLEGROUP;

            if (isset($cat[1]))
            {
                foreach (Profiler::getRealms() as $r)
                {
                    if (Profiler::urlize($r['name']) == $cat[1])
                    {
                        $this->realm = $r['name'];
                        break;
                    }
                }
            }
        }

        $this->filterObj = new ProfileListFilter();

        foreach (Profiler::getRealms() as $idx => $r)
        {
            if ($this->region && $r['region'] != $this->region)
                continue;

            if ($this->realm && $r['name'] != $this->realm)
                continue;

            $this->sumChars += DB::Characters($idx)->selectCell('SELECT count(*) FROM characters WHERE deleteInfos_Name IS NULL');
        }

        parent::__construct($pageCall, $pageParam);

        $this->name   = Util::ucFirst(Lang::game('profiles'));
        $this->subCat = $pageParam ? '='.$pageParam : '';
    }

    protected function generateTitle()
    {
        // -> battlegroup
        // -> server
        // -> region
        // Alonsus - Cruelty / Crueldad - Europe - Profiles - World of Warcraft
        // Norgannon - German - Europe - Profile - World of Warcraft

        array_unshift($this->title, Util::ucFirst(Lang::game('profiles')));

        if ($this->region)
            array_unshift($this->title, Lang::profiler('regions', $this->region));

        if ($this->realm)
            array_unshift($this->title, $this->realm);
    }

    protected function generatePath()
    {
        if ($this->region)
        {
            $this->path[] = $this->region;

            if ($this->realm)
            {
                $this->path[] = Profiler::urlize(CFG_BATTLEGROUP);
                if ($this->realm != CFG_BATTLEGROUP)
                    $this->path[] = Profiler::urlize($this->realm);
            }
        }
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
            'name'        => '$LANG.tab_characters',
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

                $this->roster  = Lang::profiler('guildRoster', [$profiles->getField('guild')]);
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
                $tabData['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_charactersfound2', $this->sumChars, $profiles->getMatches());
                $tabData['_truncated'] = 1;
            }
            else if ($profiles->getMatches() > CFG_SQL_LIMIT_DEFAULT)
                $tabData['note'] = sprintf(Util::$tryFilteringString, 'LANG.lvnote_charactersfound', $this->sumChars, 0);

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

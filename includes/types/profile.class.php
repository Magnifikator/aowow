<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


class ProfileList extends BaseType
{
    use profilerHelper, listviewHelper;

    public function getListviewData($addInfo = 0, array $reqCols = [])
    {
        $data = [];
        foreach ($this->iterate() as $__)
        {
            if ($this->getField('user') && User::$id != $this->getField('user') && !($this->getField('cuFlags') & PROFILER_CU_PUBLISHED))
                continue;

            if (($addInfo & PROFILEINFO_PROFILE) && !$this->isCustom())
                continue;

            if (($addInfo & PROFILEINFO_CHARACTER) && $this->isCustom())
                continue;

            $data[$this->id] = array(
                'id'                => $this->getField('id'),
                'name'              => $this->getField('name'),
                'race'              => $this->getField('race'),
                'classs'            => $this->getField('class'),
                'gender'            => $this->getField('gender'),
                'level'             => $this->getField('level'),
                'faction'           => (1 << ($this->getField('race') - 1)) & RACE_MASK_ALLIANCE ? 0 : 1,
                'talenttree1'       => $this->getField('talenttree1'),
                'talenttree2'       => $this->getField('talenttree2'),
                'talenttree3'       => $this->getField('talenttree3'),
                'talentspec'        => $this->getField('activespec') + 1,                   // 0 => 1; 1 => 2
                'achievementpoints' => $this->getField('achievementpoints'),
                'guild'             => '$"'.$this->getField('guildname').'"',               // force this to be a string
                'guildrank'         => $this->getField('guildrank'),
                'realm'             => Profiler::urlize($this->getField('realmName')),
                'realmname'         => $this->getField('realmName'),
             // 'battlegroup'       => Profiler::urlize($this->getField('battlegroup')),    // was renamed to subregion somewhere around cata release
             // 'battlegroupname'   => $this->getField('battlegroup'),
                'gearscore'         => $this->getField('gearscore')
            );


            // for the lv this determins if the link is profile=<id> or profile=<region>.<realm>.<name>
            if ($this->isCustom())
                $data[$this->id]['published'] = (int)!!($this->getField('cuFlags') & PROFILER_CU_PUBLISHED);
            else
                $data[$this->id]['region']    = Profiler::urlize($this->getField('region'));

            if ($addInfo & PROFILEINFO_ARENA)
            {
                $data[$this->id]['rating']  = $this->getField('rating');
                $data[$this->id]['captain'] = $this->getField('captain');
            }
            // else
                // $data[$this->id]['arenateams'] = $this->getField('arenateams');

            // Filter asked for skills - add them
            foreach ($reqCols as $col)
                $data[$this->id][$col] = $this->getField($col);

            if ($addInfo & PROFILEINFO_PROFILE)
                if ($_ = $this->getField('description'))
                    $data[$this->id]['description'] = $_;

            if ($addInfo & PROFILEINFO_PROFILE)
                if ($_ = $this->getField('icon'))
                    $data[$this->id]['icon'] = $_;

            if ($this->getField('cuFlags') & PROFILER_CU_PINNED)
                $data[$this->id]['pinned'] = 1;

            if ($this->getField('cuFlags') & PROFILER_CU_DELETED)
                $data[$this->id]['deleted'] = 1;
        }

        return array_values($data);
    }

    public function renderTooltip($interactive = false)
    {
        if (!$this->curTpl)
            return [];

        $title = '';
        $name  = $this->getField('name');
        if ($_ = $this->getField('chosenTitle'))
            $title = (new TitleList(array(['bitIdx', $_])))->getField($this->getField('gender') ? 'female' : 'male', true);

        if ($this->isCustom())
            $name .= ' (Custom Profile)';
        else if ($title)
            $name = sprintf($title, $name);

        $x  = '<table>';
        $x .= '<tr><td><b class="q">'.$name.'</b></td></tr>';
        if ($g = $this->getField('guildname'))
            $x .= '<tr><td>&lt;'.$g.'&gt;</td></tr>';
        else if ($d = $this->getField('description'))
            $x .= '<tr><td>'.$d.'</td></tr>';
        $x .= '<tr><td>'.Lang::game('level').' '.$this->getField('level').' '.Lang::game('ra', $this->getField('race')).' '.Lang::game('cl', $this->getField('class')).'</td></tr>';
        $x .= '</table>';

        return $x;
    }

    public function getJSGlobals($addMask = 0)
    {
        $data   = [];
        $realms = Profiler::getRealms();

        foreach ($this->iterate() as $id => $__)
        {
            if (($addMask & PROFILEINFO_PROFILE) && ($this->getField('cuFlags') & PROFILER_CU_PROFILE))
            {
                $profile = array(
                    'id'     => $this->getField('id'),
                    'name'   => $this->getField('name'),
                    'race'   => $this->getField('race'),
                    'classs' => $this->getField('class'),
                    'level'  => $this->getField('level'),
                    'gender' => $this->getField('gender')
                );

                if ($_ = $this->getField('icon'))
                    $profile['icon'] = $_;

                $data[] = $profile;

                continue;
            }

            if ($addMask & PROFILEINFO_CHARACTER && !($this->getField('cuFlags') & PROFILER_CU_PROFILE))
            {
                if (!isset($realms[$this->getField('realm')]))
                    continue;

                $data[] = array(
                    'id'        => $this->getField('id'),
                    'name'      => $this->getField('name'),
                    'realmname' => $realms[$this->getField('realm')]['name'],
                    'region'    => $realms[$this->getField('realm')]['region'],
                    'realm'     => Profiler::urlize($realms[$this->getField('realm')]['name']),
                    'race'      => $this->getField('race'),
                    'classs'    => $this->getField('class'),
                    'level'     => $this->getField('level'),
                    'gender'    => $this->getField('gender'),
                    'pinned'    => $this->getField('cuFlags') & PROFILER_CU_PINNED ? 1 : 0
                );
            }
        }

        return $data;
    }

    public function isCustom()
    {
        return $this->getField('cuFlags') & PROFILER_CU_PROFILE;
    }
}


class ProfileListFilter extends Filter
{
    public    $extraOpts     = [];
    protected $enums         = array(
        -1 => array(                                        // arena team sizes
        //  by name     by rating   by contrib
            12 => 2,    13 => 2,    14 => 2,
            15 => 3,    16 => 3,    17 => 3,
            18 => 5,    19 => 5,    20 => 5
        ),
        -2 => array(                                        // professions (by setting key #24, the next elements are increments of it)
            24 => null, 171, 164, 333, 202, 182, 773, 755, 165, 186, 393, 197
        ),
    );

    protected $genericFilter = array(                       // misc (bool): _NUMERIC => useFloat; _STRING => localized; _FLAG => match Value; _BOOLEAN => stringSet
        // { id: 2,   name: 'gearscore',               type: 'num' },
        // { id: 3,   name: 'achievementpoints',       type: 'num' },
        // { id: 21,  name: 'wearingitem',             type: 'str-small' },
        23 => [FILTER_CR_STRING,   'ca.achievement', STR_MATCH_EXACT | STR_ALLOW_SHORT ],  // completedachievement
        // { id: 5,   name: 'talenttree1',         type: 'num' },
        // { id: 6,   name: 'talenttree2',         type: 'num' },
        // { id: 7,   name: 'talenttree3',         type: 'num' },
         9 => [FILTER_CR_STRING,   'g.name',                         ], // guildname
        10 => [FILTER_CR_NUMERIC,  'gm.rank',      NUM_CAST_INT      ], // guildrank
        13 => [FILTER_CR_CALLBACK, 'cbTeamRating', null,        null ], // teamrtng2v2
        16 => [FILTER_CR_CALLBACK, 'cbTeamRating', null,        null ], // teamrtng3v3
        19 => [FILTER_CR_CALLBACK, 'cbTeamRating', null,        null ], // teamrtng5v5
        12 => [FILTER_CR_CALLBACK, 'cbTeamName',   null,        null ], // teamname2v2
        15 => [FILTER_CR_CALLBACK, 'cbTeamName',   null,        null ], // teamname3v3
        18 => [FILTER_CR_CALLBACK, 'cbTeamName',   null,        null ], // teamname5v5
        36 => [FILTER_CR_CALLBACK, 'cbHasGuild',   null,        null ], // hasguild [yn]
    );

    // fieldId => [checkType, checkValue[, fieldIsArray]]
    protected $inputFields = array(
        'cr'     => [FILTER_V_RANGE,    [1, 36],                                        true ], // criteria ids
        'crs'    => [FILTER_V_LIST,     [FILTER_ENUM_NONE, FILTER_ENUM_ANY, [0, 5000]], true ], // criteria operators
        'crv'    => [FILTER_V_REGEX,    '/[\p{C};]/ui',                                 true ], // criteria values
        'na'     => [FILTER_V_REGEX,    '/[\p{C};]/ui',                                 false], // name - only printable chars, no delimiter
        'ma'     => [FILTER_V_EQUAL,    1,                                              false], // match any / all filter
        'ex'     => [FILTER_V_EQUAL,    'on',                                           false], // only match exact
        'si'     => [FILTER_V_LIST,     [1, 2],                                         false], // side
        'ra'     => [FILTER_V_LIST,     [[1, 8], 10, 11],                               true ], // race
        'cl'     => [FILTER_V_LIST,     [[1, 9], 11],                                   true ], // class
        'minle'  => [FILTER_V_RANGE,    [1, MAX_LEVEL],                                 false], // min level
        'maxle'  => [FILTER_V_RANGE,    [1, MAX_LEVEL],                                 false], // max level
        'rg'     => [FILTER_V_CALLBACK, 'cbRegionCheck',                                false], // region
        'sv'     => [FILTER_V_CALLBACK, 'cbServerCheck',                                false], // server
    );

    protected function createSQLForCriterium(&$cr)
    {
        if (in_array($cr[0], array_keys($this->genericFilter)))
        {
            if ($genCR = $this->genericCriterion($cr))
                return $genCR;

            unset($cr);
            $this->error = true;
            return [1];
        }

        $skillId = 0;
        switch ($cr[0])
        {
            case 14:                                        // teamcontrib2v2
            case 17:                                        // teamcontrib3v3
            case 20:                                        // teamcontrib5v5
                break;

            //  F I X   M E ! ! !

            case 25:                                        // alchemy [num]
            case 26:                                        // blacksmithing [num]
            case 27:                                        // enchanting [num]
            case 28:                                        // engineering [num]
            case 29:                                        // herbalism [num]
            case 30:                                        // inscription [num]
            case 31:                                        // jewelcrafting [num]
            case 32:                                        // leatherworking [num]
            case 33:                                        // mining [num]
            case 34:                                        // skinning [num]
            case 35:                                        // tailoring [num]
                if (!Util::checkNumeric($cr[2], NUM_CAST_INT) || !$this->int2Op($cr[1]) || empty($this->enums[-2][$cr[0]]))
                    break;
                $skill = $this->enums[-2][$cr[0]];
                $this->extraOpts['sk']['s'][] = ', sk.value AS skill'.$skill;
                $this->formData['extraCols'][$skill] = 'skill'.$skill;
                return ['AND', ['sk.skill', $skill], ['sk.value', $cr[2], $cr[1]]];
        }

        unset($cr);
        $this->error = 1;
        return [1];
    }

    protected function createSQLForValues()
    {
        $parts = [];
        $_v    = $this->fiData['v'];

        // region (rg), battlegroup (bg) and server (sv) are passed to ArenaTeamList as miscData and handled there

        // name [str] - the table is case sensitive. Since i down't want to destroy indizes, lets alter the search terms
        if (!empty($_v['na']))
        {
            $lower  = $this->modularizeString(['c.name'], Util::lower($_v['na']),   !empty($_v['ex']) && $_v['ex'] == 'on');
            $proper = $this->modularizeString(['c.name'], Util::ucWords($_v['na']), !empty($_v['ex']) && $_v['ex'] == 'on');

            $parts[] = ['OR', $lower, $proper];
        }

        // side [list]
        if (!empty($_v['si']))
        {
            if ($_v['si'] == 1)
                $parts[] = ['c.race', [1, 3, 4, 7, 11]];
            else if ($_v['si'] == 2)
                $parts[] = ['c.race', [2, 5, 6, 8, 10]];
        }

        // race [list]
        if (!empty($_v['ra']))
            $parts[] = ['c.race', $_v['ra']];

        // class [list]
        if (!empty($_v['cl']))
            $parts[] = ['c.class', $_v['cl']];

        // min level [int]
        if (isset($_v['minle']))
            $parts[] = ['c.level', $_v['minle'], '>='];

        // max level [int]
        if (isset($_v['maxle']))
            $parts[] = ['c.level', $_v['maxle'], '<='];

        return $parts;
    }

    protected function cbRegionCheck(&$v)
    {
        if ($v == 'eu' || $v == 'us')
        {
            $this->parentCats[0] = $v;                      // directly redirect onto this region
            $v = '';                                        // remove from filter

            return true;
        }

        return false;
    }

    protected function cbServerCheck(&$v)
    {
        foreach (Profiler::getRealms() as $realm)
            if ($realm['name'] == $v)
            {
                $this->parentCats[1] = Profiler::urlize($v);// directly redirect onto this server
                $v = '';                                    // remove from filter

                return true;
            }

        return false;
    }

    protected function cbHasGuild($cr)
    {
        if ($this->int2Bool($cr[1]))
            return ['gm.guildId', null, $cr[1] ? '!' : null];

        return false;
    }

    protected function cbTeamName($cr)
    {
        if ($_ = $this->modularizeString(['at.name'], $cr[2]))
        {
            // $this->formData['extraCols'][] = XXX something something teamname

            return ['AND', ['at.type', $this->enums[-1][$cr[0]]], $_];
        }

        return false;
    }

    protected function cbTeamRating($cr)
    {
        if (!Util::checkNumeric($cr[2], NUM_CAST_INT) || !$this->int2Op($cr[1]))
            return false;

        // $this->formData['extraCols'][] = XXX something something teamname

        return ['AND', ['at.type', $this->enums[-1][$cr[0]]], ['at.rating', $cr[2], $cr[1]]];
    }
}


class RemoteProfileList extends ProfileList
{
    protected   $queryBase = 'SELECT `c`.*, `c`.`guid` AS ARRAY_KEY FROM characters c';
    protected   $queryOpts = array(
                    'c'   => [['gm', 'g', 'ca', 'ct'], 'g' => 'ARRAY_KEY', 'o' => 'level DESC, name ASC'],
                    'gm'  => ['j' => ['guild_member gm ON gm.guid = c.guid', true], 's' => ', gm.rank AS guildrank'],
                    'g'   => ['j' => ['guild g ON g.guildid = gm.guildid', true], 's' => ', g.guildid AS guild, g.name AS guildname'],
                    'ca'  => ['j' => ['character_achievement ca ON ca.guid = c.guid', true], 's' => ', GROUP_CONCAT(DISTINCT ca.achievement SEPARATOR " ") AS _acvs'],
                    'ct'  => ['j' => ['character_talent ct ON ct.guid = c.guid AND ct.spec = c.activespec', true], 's' => ', GROUP_CONCAT(DISTINCT ct.spell SEPARATOR " ") AS _talents'],
                    // 'atm' => ['j' => ['arena_team_member atm ON atm.guid = c.guid', true], 's' => ', GROUP_CONCAT(DISTINCT CONCAT(atm.arenaTeamId, ":", atm.personalRating) SEPARATOR " ") AS _teamData'],
                    'atm' => ['j' => ['arena_team_member atm ON atm.guid = c.guid', true], 's' => ', atm.personalRating AS rating'],
                    'at'  => [['atm'], 'j' => 'arena_team at ON atm.arenaTeamId = at.arenaTeamId', 's' => ', at.name AS arenateam, IF(at.captainGuid = c.guid, 1, 0) AS captain'],
                    'sk'  => ['j' => 'character_skills sk ON sk.guid = c.guid'/*, 's' => ', sk.value AS skillValue'*/]
                );

    public function __construct($conditions = [], $miscData = null)
    {
        // select DB by realm
        if (!$this->selectRealms($miscData))
        {
            trigger_error('no access to auth-db or table realmlist is empty', E_USER_WARNING);
            return;
        }

        parent::__construct($conditions, $miscData);

        if ($this->error)
            return;

        reset($this->dbNames);                              // only use when querying single realm
        $realmId     = key($this->dbNames);
        $realms      = Profiler::getRealms();
        $acvCache    = [];
        $talentCache = [];
        $atCache     = [];
        $distrib     = [];
        $talentData  = [];

        // post processing
        foreach ($this->iterate() as $guid => &$curTpl)
        {
            // battlegroup
            $curTpl['battlegroup'] = CFG_BATTLEGROUP;

            // realm
            $r = explode(':', $guid)[0];
            if (!empty($realms[$r]))
            {
                $curTpl['realm']     = $r;
                $curTpl['realmName'] = $realms[$r]['name'];
                $curTpl['region']    = $realms[$r]['region'];
            }
            else
            {
                trigger_error('character "'.$curTpl['name'].'" belongs to nonexistant realm #'.$r, E_USER_WARNING);
                unset($this->templates[$guid]);
                continue;
            }

            // temp id
            $curTpl['id'] = 0;

            // achievement points pre
            if ($acvs = explode(' ', $curTpl['_acvs']))
                foreach ($acvs as $a)
                    if ($a && !isset($acvCache[$a]))
                        $acvCache[$a] = $a;

            // talent points pre
            if ($talents = explode(' ', $curTpl['_talents']))
                foreach ($talents as $t)
                    if ($t && !isset($talentCache[$t]))
                        $talentCache[$t] = $t;

            // equalize distribution
            if (empty($distrib[$curTpl['realm']]))
                $distrib[$curTpl['realm']] = 1;
            else
                $distrib[$curTpl['realm']]++;

            $curTpl['cuFlags'] = 0;
        }

        if ($talentCache)
            $talentData = DB::Aowow()->select('SELECT spell AS ARRAY_KEY, tab, rank FROM ?_talents WHERE spell IN (?a)', $talentCache);

        $limit = CFG_SQL_LIMIT_DEFAULT;
        foreach ($conditions as $c)
            if (is_int($c))
                $limit = $c;

        $total = array_sum($distrib);
        foreach ($distrib as &$d)
            $d = ceil($limit * $d / $total);

        if ($acvCache)
            $acvCache = DB::Aowow()->selectCol('SELECT id AS ARRAY_KEY, points FROM ?_achievement WHERE id IN (?a)', $acvCache);

        foreach ($this->iterate() as $guid => &$curTpl)
        {
            if ($limit <= 0 || $distrib[$curTpl['realm']] <= 0)
            {
                unset($this->templates[$guid]);
                continue;
            }

            $distrib[$curTpl['realm']]--;
            $limit--;

            $a  = explode(' ', $curTpl['_acvs']);
            $t  = explode(' ', $curTpl['_talents']);
            unset($curTpl['_acvs']);
            unset($curTpl['_talents']);

            // achievement points post
            $curTpl['achievementpoints'] = array_sum(array_intersect_key($acvCache, array_combine($a, $a)));

            // talent points post
            $curTpl['talenttree1'] = 0;
            $curTpl['talenttree2'] = 0;
            $curTpl['talenttree3'] = 0;
            foreach ($talentData as $spell => $data)
                if (in_array($spell, $t))
                    $curTpl['talenttree'.($data['tab'] + 1)] += $data['rank'];
        }
    }

    public function getListviewData($addInfoMask = 0, array $reqCols = [])
    {
        $data = parent::getListviewData($addInfoMask, $reqCols);

        // not wanted on server list
        foreach ($data as &$d)
            unset($d['published']);

        return $data;
    }

    public function initializeLocalEntries()
    {
        $baseData = $guildData = [];
        foreach ($this->iterate() as $guid => $__)
        {
            $baseData[$guid] = array(
                'realm'     => $this->getField('realm'),
                'realmGUID' => $this->getField('guid'),
                'name'      => $this->getField('name'),
                'race'      => $this->getField('race'),
                'class'     => $this->getField('class'),
                'level'     => $this->getField('level'),
                'gender'    => $this->getField('gender'),
                'guild'     => $this->getField('guild') ?: null,
                'guildrank' => $this->getField('guild') ? $this->getField('guildrank') : null,
                'cuFlags'   => PROFILER_CU_NEEDS_RESYNC
            );

            if ($this->getField('guild'))
                $guildData[] = array(
                    'realm'     => $this->getField('realm'),
                    'realmGUID' => $this->getField('guild'),
                    'name'      => $this->getField('guildname'),
                    'nameUrl'   => Profiler::urlize($this->getField('guildname')),
                    'cuFlags'   => PROFILER_CU_NEEDS_RESYNC
                );
        }

        // basic guild data (satisfying table constraints)
        if ($guildData)
        {
            foreach (Util::createSqlBatchInsert($guildData) as $ins)
                DB::Aowow()->query('INSERT IGNORE INTO ?_profiler_guild (?#) VALUES '.$ins, array_keys(reset($guildData)));

            // merge back local ids
            $localGuilds = DB::Aowow()->selectCol('SELECT realm AS ARRAY_KEY, realmGUID AS ARRAY_KEY2, id FROM ?_profiler_guild WHERE realm IN (?a) AND realmGUID IN (?a)',
                array_column($guildData, 'realm'), array_column($guildData, 'realmGUID')
            );

            foreach ($baseData as &$bd)
                if ($bd['guild'])
                    $bd['guild'] = $localGuilds[$bd['realm']][$bd['guild']];
        }

        // basic char data (enough for tooltips)
        foreach (Util::createSqlBatchInsert($baseData) as $ins)
            DB::Aowow()->query('INSERT IGNORE INTO ?_profiler_profiles (?#) VALUES '.$ins, array_keys(reset($baseData)));

        // merge back local ids
        $localIds = DB::Aowow()->select(
            'SELECT CONCAT(realm, ":", realmGUID) AS ARRAY_KEY, id, gearscore FROM ?_profiler_profiles WHERE (cuFlags & ?d) = 0 AND realm IN (?a) AND realmGUID IN (?a)',
            PROFILER_CU_PROFILE,
            array_column($baseData, 'realm'),
            array_column($baseData, 'realmGUID')
        );

        foreach ($this->iterate() as $guid => &$_curTpl)
            if (isset($localIds[$guid]))
                $_curTpl = array_merge($_curTpl, $localIds[$guid]);
    }
}


class LocalProfileList extends ProfileList
{
    protected       $queryBase = 'SELECT p.*, p.id AS ARRAY_KEY FROM ?_profiler_profiles p';
    protected       $queryOpts = array(
                        'p'  => [['pg']],
                        'ap'   => ['j' => ['?_account_profiles ap ON ap.profileId = p.id AND ap.profileId = %d', true], 's' => ', (IFNULL(ap.ExtraFlags, 0) | p.cuFlags) AS cuFlags'],
                        'patm' => ['j' => ['?_profiler_arena_team_member patm ON patm.profileId = p.id', true], 's' => ', patm.captain, patm.personalRating AS rating'],
                        'pat'  => ['?_profiler_arena_team pat ON pat.id = patm.arenaTeamId', 's' => ', pat.mode, pat.name'],
                        'pg'   => ['j' => ['?_profiler_guild pg ON pg.id = p.guild', true], 's' => ', pg.name AS guildname']
                    );

    public function __construct($conditions = [], $miscData = null)
    {
        // todo (med): beautify this shit
        $this->queryOpts['ap']['j'][0] = sprintf($this->queryOpts['ap']['j'][0], User::$id);

        parent::__construct($conditions, $miscData);

        if ($this->error)
            return;

        $realms = Profiler::getRealms();

        // post processing
        $acvPoints = DB::Aowow()->selectCol('SELECT pc.id AS ARRAY_KEY, SUM(a.points) FROM ?_profiler_completion pc LEFT JOIN ?_achievement a ON a.id = pc.typeId WHERE pc.`type` = ?d AND pc.id IN (?a) GROUP BY pc.id', TYPE_ACHIEVEMENT, $this->getFoundIDs());

        foreach ($this->iterate() as $id => &$curTpl)
        {
            if ($curTpl['realm'] && !isset($realms[$curTpl['realm']]))
                continue;

            if (isset($realms[$curTpl['realm']]))
            {
                $curTpl['realmName'] = $realms[$curTpl['realm']]['name'];
                $curTpl['region']    = $realms[$curTpl['realm']]['region'];
            }

            // battlegroup
            $curTpl['battlegroup'] = CFG_BATTLEGROUP;

            $curTpl['achievementpoints'] = isset($acvPoints[$id]) ? $acvPoints[$id] : 0;
        }
    }

    public function getProfileUrl()
    {
        $url = '?profile=';

        if ($this->isCustom())
            return $url.$this->getField('id');

        return $url.implode('.', array(
            Profiler::urlize($this->getField('region')),
            Profiler::urlize($this->getField('realmName')),
            urlencode($this->getField('name'))
        ));
    }
}


?>

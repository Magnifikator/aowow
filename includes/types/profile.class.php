<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


// class CharacterList extends BaseType                     // new profiler-related parent: ProfilerType?; maybe a trait is enough => use ProfileHelper;
// class GuildList extends BaseType
// class ArenaTeamList extends BaseType

class ProfileList extends BaseType
{
    use profilerHelper;

    public function getListviewData($addInfo = 0, array $reqCols = [])
    {
        $data = [];
        foreach ($this->iterate() as $__)
        {
            if ($this->getField('user') && User::$id != $this->getField('user') && !($this->getField('cuFlags') & PROFILER_CU_PUBLISHED))
                continue;

            if (($addInfo & PROFILEINFO_PROFILE) && !($this->getField('cuFlags') & PROFILER_CU_PROFILE))
                continue;

            if (($addInfo & PROFILEINFO_CHARACTER) && ($this->getField('cuFlags') & PROFILER_CU_PROFILE))
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
                'talentspec'        => $this->getField('activespec') + 1,             // 0 => 1; 1 => 2
                'achievementpoints' => $this->getField('achievementpoints'),
                'guild'             => '$"'.$this->getField('guild').'"',       // force this to be a string
                'guildrank'         => $this->getField('guildRank'),
                'realm'             => Profiler::urlize($this->getField('realmName')),
                'realmname'         => $this->getField('realmName'),
             // 'battlegroup'       => Profiler::urlize($this->getField('battlegroup')),  // was renamed to subregion somewhere around cata release
             // 'battlegroupname'   => $this->getField('battlegroup'),
                'published'         => (int)!!($this->getField('cuFlags') & PROFILER_CU_PUBLISHED),
                'gearscore'         => $this->getField('gearscore')
            );

            // for the lv this determins if the link is profile=<id> or profile=<region>.<realm>.<name>
            if (!($this->getField('cuFlags') & PROFILER_CU_PROFILE))
                $data[$this->id]['region'] = Profiler::urlize($this->getField('region'));

            // if ($addInfo == PROFILEINFO_ARENA_2S)
                // $data[$this->id]['rating'] = $this->getField('arenateams')[2]['rating'];
            // else if ($addInfo == PROFILEINFO_ARENA_3S)
                // $data[$this->id]['rating'] = $this->getField('arenateams')[3]['rating'];
            // else if ($addInfo == PROFILEINFO_ARENA_5S)
                // $data[$this->id]['rating'] = $this->getField('arenateams')[5]['rating'];
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
        if ($g = $this->getField('guild'))
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
            12 => 2,
            15 => 3,
            18 => 5
        ),
        -2 => array(                                        // professions (by setting key #24, the next elements are increments of it)
            24 => null, 171, 164, 333, 202, 182, 773, 755, 165, 186, 393, 197
        ),
    );

    protected $genericFilter = array(                       // misc (bool): _NUMERIC => useFloat; _STRING => localized; _FLAG => match Value; _BOOLEAN => stringSet
        // { id: 2,   name: 'gearscore',               type: 'num' },
        // { id: 3,   name: 'achievementpoints',       type: 'num' },
        // { id: 21,  name: 'wearingitem',             type: 'str-small' },
        // { id: 23,  name: 'completedachievement',    type: 'str-small' },
        // { id: 5,   name: 'talenttree1',         type: 'num' },
        // { id: 6,   name: 'talenttree2',         type: 'num' },
        // { id: 7,   name: 'talenttree3',         type: 'num' },
         9 => [FILTER_CR_STRING,    'g.name',                ], // guildname
        10 => [FILTER_CR_NUMERIC,   'gm.rank',  NUM_CAST_INT ], // guildrank
    );

    // fieldId => [checkType, checkValue[, fieldIsArray]]
    protected $inputFields = array(
        'cr'     => [FILTER_V_RANGE,  [1, 36],                                       true ], // criteria ids
        'crs'    => [FILTER_V_LIST,  [FILTER_ENUM_NONE, FILTER_ENUM_ANY, [0, 5000]], true ], // criteria operators
        'crv'    => [FILTER_V_REGEX, '/[\p{C};]/ui',                                 true ], // criteria values - only numeric input values expected
        'na'     => [FILTER_V_REGEX, '/[\p{C};]/ui',                                 false], // name - only printable chars, no delimiter
        'ma'     => [FILTER_V_EQUAL, 1,                                              false], // match any / all filter
        'ex'     => [FILTER_V_EQUAL, 'on',                                           false], // only match exact
        'si'     => [FILTER_V_LIST, [1, 2],                                          false], // side
        'ra'     => [FILTER_V_LIST, [[1, 8], 10, 11],                                true ], // race
        'cl'     => [FILTER_V_LIST, [[1, 9], 11],                                    true ], // class
        'minle'  => [FILTER_V_RANGE, [1, MAX_LEVEL],                                 false], // min level
        'maxle'  => [FILTER_V_RANGE, [1, MAX_LEVEL],                                 false]  // max level
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
            case 36:                                        // hasguild [yn]
                if ($this->int2Bool($cr[1]))
                    return ['gm.guildId', null, $cr[1] ? '!' : null];
                break;
            case 12:                                        // teamname2v2
            case 15:                                        // teamname3v3
            case 18:                                        // teamname5v5
                if ($_ = $this->modularizeString(['at.name'], $cr[2]))
                    return ['AND', ['at.type', $this->enums[-1][$cr[0]]], $_];

                break;
            case 13:                                        // teamrtng2v2
            case 16:                                        // teamrtng3v3
            case 19:                                        // teamrtng5v5
            case 14:                                        // teamcontrib2v2
            case 17:                                        // teamcontrib3v3
            case 20:                                        // teamcontrib5v5
                break;
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
}

class RemoteProfileList extends ProfileList
{
    protected   $queryBase = 'SELECT `c`.*, `c`.`guid` AS ARRAY_KEY FROM characters c';
    protected   $queryOpts = array(
                    'c'   => [['gm', 'g', 'ca', 'ct', 'atm'], 'g' => 'ARRAY_KEY', 'o' => 'level DESC, name ASC'],
                    'gm'  => ['j' => ['guild_member gm ON gm.guid = c.guid', true], 's' => ', gm.rank AS guildRank'],
                    'g'   => ['j' => ['guild g ON g.guildid = gm.guildid', true], 's' => ', g.name AS guild'],
                    'ca'  => ['j' => ['character_achievement ca ON ca.guid = c.guid', true], 's' => ', GROUP_CONCAT(DISTINCT ca.achievement SEPARATOR " ") AS _acvs'],
                    'ct'  => ['j' => ['character_talent ct ON ct.guid = c.guid AND ct.spec = c.activespec', true], 's' => ', GROUP_CONCAT(DISTINCT ct.spell SEPARATOR " ") AS _talents'],
                    'atm' => ['j' => ['arena_team_member atm ON atm.guid = c.guid', true], 's' => ', GROUP_CONCAT(DISTINCT CONCAT(atm.arenaTeamId, ":", atm.personalRating) SEPARATOR " ") AS _teamData'],
                    'at'  => ['j' => 'arena_team at ON atm.arenaTeamId = at.arenaTeamId'],
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

            // battlegroup
            $curTpl['battlegroup'] = CFG_BATTLEGROUP;

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

            // arenateam membership
            if ($arenaTeams = explode(' ', $curTpl['_teamData']))
                foreach ($arenaTeams as $at)
                    if ($_ = explode(':', $at))
                        if (!isset($atCache[$_[0]]))
                            $atCache[$_[0]] = $_[0];

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

        if ($atCache)
            $atCache = new ArenaTeamList(array(['at.arenaTeamId', array_values($atCache)]), $miscData);

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
            $td = explode(' ', $curTpl['_teamData']);
            unset($curTpl['_acvs']);
            unset($curTpl['_talents']);
            unset($curTpl['_teamData']);

            // achievement points post
            $curTpl['achievementpoints'] = array_sum(array_intersect_key($acvCache, array_combine($a, $a)));

            // talent points post
            $curTpl['talenttree1'] = 0;
            $curTpl['talenttree2'] = 0;
            $curTpl['talenttree3'] = 0;
            foreach ($talentData as $spell => $data)
                if (in_array($spell, $t))
                    $curTpl['talenttree'.($data['tab'] + 1)] += $data['rank'];

            // arenateams
            $curTpl['arenateams'] = [];
            foreach ($td as $data)
            {
                $d = explode(':', $data);
                if ($atCache->getEntry($d[0]))
                {
                    $curTpl['arenateams'][$atCache->getField('type')] = array(
                        'name'   => $atCache->getField('name'),
                        'rating' => $d[1]
                    );
                }
            }
        }
    }

    public function getListviewData($addInfoMask = 0, array $reqCols = [])
    {
        $data = parent::getListviewData($addInfoMask, $reqCols);

        // not wanted on eserver list
        foreach ($data as &$d)
            unset($d['published']);

        return $data;
    }

    public function initializeLocalEntries()
    {
        // absolute basic data (enough for tooltips)
        $data = [];
        foreach ($this->iterate() as $guid => $__)
            $data[$guid] = array(
                'realm'     => $this->getField('realm'),
                'realmGUID' => $this->getField('guid'),
                'name'      => $this->getField('name'),
                'race'      => $this->getField('race'),
                'class'     => $this->getField('class'),
                'level'     => $this->getField('level'),
                'gender'    => $this->getField('gender'),
                'guild'     => $this->getField('guild'),
                'guildrank' => $this->getField('guild') ? $this->getField('guildRank') : null,
                'cuFlags'   => PROFILER_CU_NEEDS_RESYNC
            );

        foreach (Util::createSqlBatchInsert($data) as $ins)
            DB::Aowow()->query('INSERT IGNORE INTO ?_profiler_profiles (?#) VALUES '.$ins, array_keys(reset($data)));

        // merge back local ids
        $localIds = DB::Aowow()->select(
            'SELECT CONCAT(realm, ":", realmGUID) AS ARRAY_KEY, id, gearscore FROM ?_profiler_profiles WHERE (cuFlags & ?d) = 0 AND realm IN (?a) AND realmGUID IN (?a)',
            PROFILER_CU_PROFILE,
            array_column($data, 'realm'),
            array_column($data, 'realmGUID')
        );

        foreach ($this->iterate() as $guid => &$_curTpl)
            if (isset($localIds[$guid]))
                $_curTpl = array_merge($_curTpl, $localIds[$guid]);
    }

    public function selectRealms($fi)
    {
        $this->dbNames = [];

        foreach(Profiler::getRealms() as $idx => $r)
        {
            if (!empty($fi['sv']) && Profiler::urlize($r['name']) != Profiler::urlize($fi['sv']))
                continue;

            if (!empty($fi['rg']) && Profiler::urlize($r['region']) != Profiler::urlize($fi['rg']))
                continue;

            $this->dbNames[$idx] = 'Characters';
        }

        return !!$this->dbNames;
    }
}


class LocalProfileList extends ProfileList
{
    protected       $queryBase = 'SELECT p.*, p.id AS ARRAY_KEY FROM ?_profiler_profiles p';
    protected       $queryOpts = array(
                        // 'p'  => [['ap']],
                        'ap' => ['j' => ['?_account_profiles ap ON ap.profileId = p.id', true], 's' => ', (IFNULL(ap.ExtraFlags, 0) | p.cuFlags) AS cuFlags'],
                        // 'pam' => [['?_profiles_arenateam_member pam ON pam.memberId = p.id', true], 's' => ', pam.status'],
                        // 'pa'  => ['?_profiles_arenateam pa ON pa.id = pam.teamId', 's' => ', pa.mode, pa.name'],
                        // 'pgm' => [['?_profiles_guid_member pgm ON pgm.memberId = p.Id', true], 's' => ', pgm.rankId'],
                        // 'pg'  => ['?_profiles_guild pg ON pg.if = pgm.guildId', 's' => ', pg.name']
                    );

    public function __construct($conditions = [], $miscData = null)
    {
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
            Profiler::urlize($this->getField('name'))
        ));
    }
}


?>

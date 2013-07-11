<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


class FactionList extends BaseType
{
    public static $type = TYPE_FACTION;

    public static function getName($id)
    {
        $n = DB::Aowow()->SelectRow('
            SELECT
                name_loc0,
                name_loc2,
                name_loc3,
                name_loc6,
                name_loc8
            FROM
                ?_factions
            WHERE
                factionID = ?d',
            $id
        );
        return Util::localizedString($n, 'name');
    }

    public function reactsAgainst($faction)
    {
        // see factionTemplate
        /*
            1: friendly
            0: neutral
            -1: hostile
        */
    }

    public function getListviewData() { }
    public function addGlobalsToJScript(&$template, $addMask = 0) { }
    public function renderTooltip() { }

}

?>

            <script type="text/javascript">//<![CDATA[
<?php if (isset($this->region) && isset($this->realm)): ?>
                pr_setRegionRealm($WH.ge('fi').firstChild, '<?=$this->region; ?>', '<?=$this->realm; ?>');
                pr_onChangeRace();
<?php endif; ?>
                fi_init('<?=$fi['init'];?>');
<?php
if (!empty($fi['sc'])):
    echo '                fi_setCriteria('.Util::toJSON($fi['sc']['cr'] ?: []).', '.Util::toJSON($fi['sc']['crs'] ?: []).', '.Util::toJSON($fi['sc']['crv'] ?: []).");\n";
endif;
if (!empty($fi['sw'])):
    echo '                fi_setWeights('.Util::toJSON($fi['sw']).", 0, 1, 1);\n";
endif;
if (!empty($fi['ec'])):
    echo '                fi_extraCols = '.Util::toJSON($fi['ec']).";\n";
endif;
?>
            //]]></script>

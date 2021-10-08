{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Perikles implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    perikles_perikles.tpl
-->


<div id="periklesboard">
    <div id="perikles_map">
        <div id="influence_tiles">
            <!-- BEGIN INFLUENCE_TILES_BLOCK -->
            <div id="influence_slot_{i}" class="per_influence_slot" style="top: {T}px; left: {L}px;"></div>
            <!-- END INFLUENCE_TILES_BLOCK -->
        </div>
        <div id="corinth">
            <!-- BEGIN CORINTH_DEFEAT_BLOCK -->
            <div id="corinth_defeat_{i}" class="per_defeat_slot" style="top: {T}px; left: {L}px;"></div>
            <!-- END CORINTH_DEFEAT_BLOCK -->
        </div>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

const jstpl_influence_tile = '<div id="${city}_${id}" class="per_influence_tile" style="background-position: ${x}px ${y}px;"></div>';

const jstpl_influence_back = '<div id="cardback_${id}" class="per_influence_tile" style="background-position: ${x}px ${y}px; position: absolute; margin: -${m}px ${m}px;"></div>';

const jstpl_special_tile = '<div id="${special}_special_tile" class="per_special_tile ${special}"></div>';

const jstpl_special_back = '<div id="special_${id}" class="per_special_tile_back"></div>';

</script>  

{OVERALL_GAME_FOOTER}

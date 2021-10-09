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
        <!-- BEGIN CITY_BLOCK -->
        <div id="{CITY}">
            <div id="{CITY}_leader" class="per_counter_slot" style="top: {LEADERY}px; left: {LEADERX}px;"></div>
            <div id="{CITY}_statues" class="per_statue_zone" style="top: {STATUEY}px; left: {STATUEX}px;"></div>
            <div id="{CITY}_military" class="per_military_zone" style="top: {MILY}px; left: {MILX}px;"></div>
            <!-- BEGIN DEFEAT_BLOCK -->
            <div id="{CITY}_defeat_slot_{i}" class="per_counter_slot" style="top: {T}px; left: {L}px;"></div>
            <!-- END DEFEAT_BLOCK -->
        </div>
        <!-- END CITY_BLOCK -->
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

const jstpl_influence_tile = '<div id="${city}_${id}" class="per_influence_tile" style="background-position: ${x}px ${y}px;"></div>';

const jstpl_influence_back = '<div id="cardback_${id}" class="per_influence_tile" style="background-position: ${x}px ${y}px; position: absolute; margin: -${m}px ${m}px;"></div>';

const jstpl_special_tile = '<div id="${special}_special_tile" class="per_special_tile ${special}"></div>';

const jstpl_special_back = '<div id="special_${id}" class="per_special_tile_back"></div>';

const jstpl_defeat = '<div id="${city}_defeat_${num}" class="per_defeat_counter"></div>';

const jstpl_leader = '<div id="${city}_${type}_${num}" class="per_counter per_statue per_${type}_${color}"></div>';

</script>  

{OVERALL_GAME_FOOTER}

{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Perikles implementation : © <David Edelstein> <david.edelstein@gmail.com>
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
        <div id="location_area">
            <!-- BEGIN LOCATION_BLOCK -->
            <div id="location_{LOC}" class="per_location_slot" style="top: {T}px; left: {L}px;"></div>
            <!-- END LOCATION_BLOCK -->
        </div>

        <div id="persia_military" class="per_military_zone" style="top: {PERSIA_Y}px; left: {PERSIA_X}px;"></div>

        <!-- BEGIN CITY_BLOCK -->
            <div id="{CITY}" class="per_city" style="top: {CITYY}px; left: {CITYX}px;"></div>
            <div id="{CITY}_leader" class="per_counter_slot" style="top: {LEADERY}px; left: {LEADERX}px;"></div>
            <div id="{CITY}_statues" class="per_statue_zone" style="top: {STATUEY}px; left: {STATUEX}px;"></div>
            <div id="{CITY}_a" class="per_candidate_space" style="top: {ALPHAY}px; left: {ALPHAX}px;"></div>
            <div id="{CITY}_b" class="per_candidate_space" style="top: {BETAY}px; left: {BETAX}px;"></div>
            <!-- BEGIN DEFEAT_BLOCK -->
            <div id="{CITY}_defeat_slot_{i}" class="per_counter_slot" style="top: {T}px; left: {L}px;"></div>
            <!-- END DEFEAT_BLOCK -->
            <!-- BEGIN CUBES_BLOCK -->
            <div id="{CITY}_cubes_{player_id}" class="per_city_cubes" style="top: {T}px; left: {L}px;"></div>
            <!-- END CUBES_BLOCK -->
            <div id="{CITY}_military" class="per_military_zone" style="top: {MILY}px; left: {MILX}px;"></div>
        <!-- END CITY_BLOCK -->

    </div>
    <div id="deadpool">
        <h1>{DEADPOOL}</h1>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

const jstpl_influence_tile = '<div id="${city}_${id}" class="per_influence_tile" style="background-position: ${x}px ${y}px; margin: ${margin};"></div>';

const jstpl_influence_back = '<div id="cardback_${id}" class="per_influence_tile" style="background-position: ${x}px ${y}px; position: absolute; margin: -${m}px ${m}px;"></div>';

const jstpl_special_tile = '<div id="${special}_special_tile" class="per_special_tile ${special}" style="--scale: ${scale}; margin: ${margin};"></div>';

const jstpl_special_back = '<div id="special_${id}" class="per_special_tile_back" style="--scale: ${scale};"></div>';

const jstpl_defeat = '<div id="${city}_defeat_${num}" class="per_defeat_counter"></div>';

const jstpl_leader = '<div id="${city}_${type}_${num}" class="per_counter per_statue per_${type}_${color}"></div>';

const jstpl_cube = '<div id="${id}"" class="per_cube" style="background-color: #${color};"></div>';

const jstpl_location_tile = '<div id="${id}_tile" class="per_location_tile" style="background-position: ${x}px ${y}px;"></div>';

const jstpl_military = '<div id="${city}_${type}_${s}_${id}" class="per_military per_${type}" style="background-position: ${x}px ${y}px; margin: ${m}px; top: ${t}px;"></div>';

const jstpl_special_tt = '<div id="{$special}_special_tt" style="display: flex; flex-direction: row;">\
                            <div style="flex: 1;">\
                                <h1 style="font-family: ThrumsSerifRegular;">${header}</h1>\
                                <hr\>\
                                ${text}\
                            </div>\
                            <div class="per_special_tile_tt ${special}" style="--scale: ${scale};"></div>\
                        </div>';

const jstpl_influence_tt = '<div style="display: flex; flex-direction: row;">\
                                <div class="per_influence_tile" style="background-position: ${x}px ${y}px; margin: 5px;"></div>\
                                <div style="flex: 1;">\
                                    <h1 style="font-family: ThrumsSerifRegular;">${city}</h1>\
                                    <h2>${label}</h2>\
                                    <span>${text}</span>\
                                </div>\
                            </div>';

const jstpl_influence_cards = '<div id="${id}_player_cards" class="per_player_infl" style="--scale: ${scale};"></div>';

</script>  

{OVERALL_GAME_FOOTER}

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
    <div id="military_board">
        <h2 class="prk_hdr">{MILITARY}</h2>
        <div id="mymilitary">
        </div>
    </div>
    
    <div id="perikles_map">
        <div id="influence_tiles">
            <!-- BEGIN INFLUENCE_TILES_BLOCK -->
            <div id="influence_slot_{i}" class="prk_influence_slot" style="top: {T}px; left: {L}px;"></div>
            <!-- END INFLUENCE_TILES_BLOCK -->
        </div>
        <div id="attacker_dicebox-1" class="prk_dice_box" style="left: 832px; top: 515px;"></div>
        <div id="attacker_dicebox-2" class="prk_dice_box" style="left: 902px; top: 515px;"></div>
        <div id="defender_dicebox-1" class="prk_dice_box" style="left: 1110px; top: 515px;"></div>
        <div id="defender_dicebox-2" class="prk_dice_box" style="left: 1180px; top: 515px;"></div>
        <div id="attacker_battle_tokens" class="prk_token_box" style="width: 188px; left: 802px; top: 586px;"></div>
        <div id="battle_tokens" class="prk_token_box" style="width: 70px; left: 1001px; top: 586px;"></div>
        <div id="defender_battle_tokens" class="prk_token_box" style="width: 188px; left: 1080px; top: 586px;"></div>
        <div id="location_area">
            <!-- BEGIN LOCATION_BLOCK -->
            <div id="battle_zone_{LOC}">
                <div id="location_{LOC}" class="prk_location_slot" style="top: {T}px; left: {L}px;"></div>
                <div id="battle_{LOC}_hoplite_att" class="prk_battle" style="top: {T}px; left: {LA}px;" data-side="attacker" data-unit="hoplite" data-role="main"></div>
                <div id="battle_{LOC}_hoplite_att_ally" class="prk_battle" style="top: {T}px; left: {LAA}px;" data-side="attacker" data-unit="hoplite" data-role="ally"></div>
                <div id="battle_{LOC}_trireme_att" class="prk_battle" style="top: {TT}px; left: {LA}px;" data-side="attacker" data-unit="trireme" data-role="main"></div>
                <div id="battle_{LOC}_trireme_att_ally" class="prk_battle" style="top: {TT}px; left: {LAA}px;" data-side="attacker" data-unit="trireme" data-role="ally"></div>
                <div id="battle_{LOC}_hoplite_def" class="prk_battle" style="top: {T}px; left: {LD}px;" data-side="defender" data-unit="hoplite" data-role="main"></div>
                <div id="battle_{LOC}_hoplite_def_ally" class="prk_battle" style="top: {T}px; left: {LDA}px;" data-side="defender" data-unit="hoplite" data-role="ally"></div>
                <div id="battle_{LOC}_trireme_def" class="prk_battle" style="top: {TT}px; left: {LD}px;" data-side="defender" data-unit="trireme" data-role="main"></div>
                <div id="battle_{LOC}_trireme_def_ally" class="prk_battle" style="top: {TT}px; left: {LDA}px;" data-side="defender" data-unit="trireme" data-role="ally"></div>
            </div>
            <!-- END LOCATION_BLOCK -->
        </div>

        <div id="persia_military" class="prk_military_zone" style="top: {PERSIA_Y}px; left: {PERSIA_X}px;"></div>

        <!-- BEGIN CITY_BLOCK -->
            <div id="{CITY}" class="prk_city" style="top: {CITYY}px; left: {CITYX}px;">
                <!-- BEGIN CUBES_BLOCK -->
                <div id="{CITY}_cubes_{player_id}" class="prk_city_cubes" style="top: {T}px; left: {L}px;"></div>
                <!-- END CUBES_BLOCK -->
            </div>
            <div id="{CITY}_leader" class="prk_counter_slot" style="top: {LEADERY}px; left: {LEADERX}px;"></div>
            <div id="{CITY}_statues" class="prk_statue_zone" style="top: {STATUEY}px; left: {STATUEX}px;"></div>
            <div id="{CITY}_a" class="prk_candidate_space" style="top: {ALPHAY}px; left: {ALPHAX}px;"></div>
            <div id="{CITY}_b" class="prk_candidate_space" style="top: {BETAY}px; left: {BETAX}px;"></div>
            <!-- BEGIN DEFEAT_BLOCK -->
            <div id="{CITY}_defeat_slot_{i}" class="prk_counter_slot" style="top: {T}px; left: {L}px;"></div>
            <!-- END DEFEAT_BLOCK -->
            <div id="{CITY}_military" class="prk_military_zone" style="top: {MILY}px; left: {MILX}px;"></div>
        <!-- END CITY_BLOCK -->

        <!-- BEGIN CRT_BLOCK -->
            <div id="crt_{COL}" class="prk_crt" style="top: {CRTY}px; left: {CRTX}px;"></div>
        <!-- END CRT_BLOCK -->
    </div>
    <div id="deadpool">
        <h2 class="prk_hdr">{DEADPOOL}</h2>
        <div id="deadpool_ctnr"></div>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

const jstpl_influence_tile = '<div id="${city}_${id}" class="prk_influence_tile" style="background-position: ${x}px ${y}px; margin: 2px; outline: 2px groove var(--color_${city});"></div>';

const jstpl_influence_back = '<div id="cardback_${id}" class="prk_influence_tile" style="background-position: ${x}px ${y}px; position: absolute; margin: -${m}px ${m}px; outline: 1px gray ridge;"></div>';

const jstpl_defeat = '<div id="${city}_defeat_${num}" class="prk_defeat" title="${title}"></div>';

const jstpl_leader = '<div id="${city}_${type}_${num}" class="prk_player_counter prk_${type}" style="bottom: calc(${num}*22px); left: calc(${num}*6px);"></div>';

const jstpl_leader_log = '<div class="prk_player_counter prk_${type}" data-color="${color}"></div>';

const jstpl_cube = '<div id="${id}" class="prk_cube" style="background-color: ${color};"></div>';

const jstpl_city_btn = '<button id="${city}_commit_btn" type="button" class="prk_city_btn" style="background-color: var(--color_${city});">${city_name}</button>';

const jstpl_permission_btn = '<button id="${location}_${city}_btn" type="button" class="prk_city_btn" style="background-color: var(--color_${city});">${city_name}</button>';

const jstpl_permission_icon = '<button id="${rel}_btn" type="button" class="prk_city_btn" style="pointer-events: none; min-width: max-content;" data-status=${rel} data-defender=${defender}>${relationship}</button>';

const jstpl_plague_btn = '<button id="${city}_plague_btn" type="button" class="prk_plague_btn" style="background-color: var(--color_${city});">${city_name}</button>';

const jstpl_alkibiades_from_btn = '<div id="${city}_alkibiades_from_btn" class="prk_alkibiades_btn" style="background-color: var(--color_${city});">${city_name}</div>';

const jstpl_alkibiades_to_btn = '<div id="${city}_alkibiades_to_btn" class="prk_alkibiades_btn" style="background-color: white;">${city_name}</div>';

const jstpl_city_banner = '<button id="${city}_commit_btn" type="button" class="prk_city_banner" style="background-color: var(--color_${city});">${city_name}</button>';

const jstpl_military_area = '<div id="${city}_military_${id}" class="prk_mil_board">\
                                <h3 class="prk_hdr">${cityname}</h3>\
                                <div id="${city}_mil_ctnr_${id}" class="prk_mil_container">\
                                    <div id="${city}_hoplite_${id}" class="prk_units_column" data-unit="hoplites">\
                                        <div id="${city}_hoplite_1_${id}" class="prk_units_container"></div>\
                                        <div id="${city}_hoplite_2_${id}" class="prk_units_container"></div>\
                                        <div id="${city}_hoplite_3_${id}" class="prk_units_container"></div>\
                                        <div id="${city}_hoplite_4_${id}" class="prk_units_container"></div>\
                                    </div>\
                                    <div id="${city}_trireme_${id}" class="prk_units_column" data-unit="triremes">\
                                        <div id="${city}_trireme_1_${id}" class="prk_units_container"></div>\
                                        <div id="${city}_trireme_2_${id}" class="prk_units_container"></div>\
                                        <div id="${city}_trireme_3_${id}" class="prk_units_container"></div>\
                                        <div id="${city}_trireme_4_${id}" class="prk_units_container"></div>\
                                        </div>\
                                </div>\
                            </div>';

const jstpl_influence_tt = '<div style="display: flex; flex-direction: row;">\
                                <div class="prk_influence_tile" style="background-position: ${x}px ${y}px; margin: 5px;"></div>\
                                <div style="flex: 1;">\
                                    <h1 style="font-family: Bodoni Moda;">${city}</h1>\
                                    <h2>${label}</h2>\
                                    <span>${text}</span>\
                                </div>\
                            </div>';

const jstpl_influence_cards = '<div id="${id}_player_cards" class="prk_player_infl" style="--scale: ${scale};"></div>';

const jstpl_battle_token = '<div id="battle_token_${id}" class="prk_battle_token"></div>';

const jstpl_victory_tiles = '<div id="${id}_player_tiles" class="prk_player_tiles" style="--scale: ${scale};"></div>';

const jstpl_die = '<div class="prk_dice" style="background-position: ${x}px 0;" data-side="${side}"></div>';

const jstpl_permission_box = '<div id="${location}_permissions_wrapper" class="prk_permission_box" style="background-color: ${player_color};">'+
                                '<div id="${location}_permissions" class="prk_permission_row"></div>'+
                                '<span>Controlling Player: ${player_name}</span>'+
                            '</div>';

const jstpl_player_options = '<div id="player_options">'+
                                '<label class="prk_player_opt"><span>${text}</span>'+
                                    '<input id="autopass_special" type="checkbox">'+
                                    '<span class="prk_checkmark"></span>'+
                                '</label>'+
                                '<i id="autopass_help" class="fa fa-question-circle-o fa-lg"></i>'+
                            '</div>';

</script>  

{OVERALL_GAME_FOOTER}

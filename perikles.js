/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Perikles implementation : © <David Edelstein> <david.edelstein@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * perikles.js
 *
 * Perikles user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

const BOARD_SCALE = 2;
const INFLUENCE_SCALE = 0.5;

const CITIES = ['athens', 'sparta', 'argos', 'corinth', 'thebes', 'megara'];

const INFLUENCE_ROW = {'athens' : 0, 'sparta' : 1, 'argos' : 2, 'corinth' : 3, 'thebes' : 4, 'megara' : 5, 'any' : 6};
const INFLUENCE_COL = {'influence' : 0, 'candidate' : 1, 'assassin' : 2};

const INFLUENCE_PILE = "influence_slot_0";

const COMMIT_INFLUENCE_CUBES = "commit_influence_cubes";

// player preferences
const PREF_AUTO_PASS = 100;
const PREF_LOG_FONT = 101;
const PREF_CONFIRM_DIALOG = 102;
const PREF_COLORBLIND = 103;
const SCORING_ANIMATION = 2000;

// permission buttons get displayed here
const MILITARY_DISPLAY_STATES = ['spartanChoice', 'nextPlayerCommit', 'commitForces', 'permissionResponse',  'deadPool', 'takeDead', 'resolveTile', 'battle', 'rollcombat', 'specialBattleTile', 'takeLoss'];

const CANDIDATES = {
    "\u{003B1}" : "a",
    "\u{003B2}" : "b"
}

const WHITE_OUTLINE = 'text-shadow: 1px 1px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;';

const DEAD_POOL = "deadpool";

const LEFT_ARROW = '<span style="font-size:2em;">&#8678;</span>';
const RIGHT_ARROW = '<span style="font-size:2em;">&#8680;</span>';

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    g_gamethemeurl + "modules/specialtile.js",
    g_gamethemeurl + "modules/alkibiades.js",
    g_gamethemeurl + "modules/slaverevolt.js",
    g_gamethemeurl + "modules/stack.js",
    g_gamethemeurl + "modules/counter.js",
    g_gamethemeurl + "modules/locationtile.js",
    g_gamethemeurl + "modules/decorator.js",
    g_gamethemeurl + "modules/rolldice.js",
],
function (dojo, declare) {
    return declare("bgagame.perikles", [ebg.core.gamegui, perikles.alkibiades, perikles.slaverevolt, perikles.stack, perikles.counter, perikles.decorator, perikles.dice], {
        constructor: function(){
            this.influence_h = 199;
            this.influence_w = 128;

            this.stacks = new perikles.stack();
            this.slaverevolt = new perikles.slaverevolt();
            // this.currentState = null;
        },

        /* @Override */
        showMessage: function (msg, type) {
            if (type == "error") {
                // invalid commit, clear commits
                if (this.currentState == "commitForces") {
                    this.onCancelCommit();
                }
            }
            this.inherited(arguments);
        },

        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas ) {
            if (!this._notif_uid_to_log_id) {
                this._notif_uid_to_log_id = {};
            }

            this.decorator = new perikles.decorator(gamedatas.players);
            this.dice = new perikles.dice();

            this.setupSpecialTiles(gamedatas.players, gamedatas.specialtiles);
            this.setupInfluenceTiles(gamedatas.influencetiles, parseInt(gamedatas.decksize));
            this.setupInfluenceCubes(gamedatas.influencecubes);
            this.setupLocationTiles(gamedatas.locationtiles);
            this.setupCandidates(gamedatas.candidates);
            this.setupLeaders(gamedatas.leaders);
            this.setupStatues(gamedatas.statues);
            this.setupMilitary(gamedatas.military, gamedatas.persianleaders);
            this.setupTokens(gamedatas.battletokens);
            this.setupDefeats(gamedatas.defeats);
            this.setupCities();


            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            if (!this.isReadOnly()) {
                this.setupPlayerOptions(gamedatas.specialtiles[this.player_id]);
            }

            this.setupPreference();
        },

        /**
         * Allow setting autopass for Special Tile  if it hasn't been used yet.
         * @param {Object} myspecialtile
         */
        setupPlayerOptions: function(myspecialtile) {
            if (!myspecialtile['used']) {
                const panel_html = this.format_block('jstpl_player_options', {text: _('Special Tile: Automatically pass')});
                dojo.place(panel_html, $('player_board_'+this.player_id));
                const pass_pref = this.prefs[PREF_AUTO_PASS].value;

                const check = $("autopass_special");
                if (pass_pref == 1) {
                    check.setAttribute("checked", "checked");
                }
                dojo.connect(check, 'onchange', this, 'changePreference');
                const msg = _("If not checked, the game will pause whenever you are eligible to play your Special Tile. This might reveal information to other players about which Special Tile you have.");
                const tthtml = '<span class="prk_help">'+msg+'</span>';
                this.addTooltipHtml('autopass_help', tthtml, '');
            }
        },

        /**
         * Initialize preference values.
         */
         setupPreference: function() {
            // when refreshed, make sure doesn't change
            this.changeLogFontSize(this.prefs[PREF_LOG_FONT].value);

            // set preference for autoplay
            this.onPreferenceChanged(PREF_AUTO_PASS, this.prefs[PREF_AUTO_PASS].value);

            dojo.query('.preference_control').on('change', (e) => {
                const match = e.target.id.match(/^preference_control_(\d+)$/);
                if (match) {
                    const pref = toint(match[1]);
                    if ([PREF_AUTO_PASS, PREF_LOG_FONT, PREF_CONFIRM_DIALOG].includes(pref)) {
                        const newValue = e.target.value;
                        this.prefs[pref].value = newValue;
                        this.onPreferenceChanged(pref, newValue);
                    }
                }
            });
        },

        /**
         * Called by clicking preference checkboxes, sets player pref.
         * @param {Object} check
         */
         changePreference: function(check) {
            const newpref = check.target.checked ? 1 : 0;
            this.setPreferenceValue(PREF_AUTO_PASS, newpref);
        },

        /*
        * Preference polyfill. Called by both checkbox and the player preference menu.
        */
        setPreferenceValue: function(number, newValue) {
            var optionSel = 'option[value="' + newValue + '"]';
            dojo.query('#preference_control_' + number + ' > ' + optionSel + ', #preference_fontrol_' + number + ' > ' + optionSel).attr('selected', true);
            var select = $('preference_control_' + number);
            if (dojo.isIE) {
                select.fireEvent('onchange');
            } else {
                var event = document.createEvent('HTMLEvents');
                event.initEvent('change', false, true);
                select.dispatchEvent(event);
            }
        },

        /**
         * Set up special tiles
         * @param {Array} players 
         * @param {Array} specialtiles 
         */
        setupSpecialTiles: function(players, specialtiles) {
            const special_scale = 0.2;

            for (const player_id in players) {
                const spec = specialtiles[player_id];

                // add flex row for cards
                const player_cards = this.format_block('jstpl_influence_cards', {id: player_id, scale: special_scale});
                const player_cards_div = dojo.place(player_cards, $('player_board_'+player_id));

                const specname = spec['name']; // null for other players' unused tiles
                const used = !!spec['used'];
                const specialtile = new perikles.specialtile(player_id, specname, used);

                const specialhtml = specialtile.getDiv();

                const tile = dojo.place(specialhtml, player_cards_div);

                let ttext = "";
                if (specialtile.isFaceup()) {
                    ttext = specialtile.createSpecialTileTooltip();
                } else {
                    ttext = _("${player_name}'s Special tile");
                    const player_name = this.decorator.spanPlayerName(player_id, this.isColorblind());
                    ttext = ttext.replace('${player_name}', player_name);
                }
                this.addTooltip(tile.id, ttext, '');
            }
        },

        /**
         * Put influence tiles on board, create deck
         * @param {Array} influence
         * @param {int} decksize
         */
        setupInfluenceTiles: function(influence, decksize) {
            // tiles in slots
            for (const tile of influence) {
                const loc = tile['location'];
                if (loc == "board") {
                    this.placeInfluenceTileBoard(tile);
                } else {
                    this.placeInfluenceTilePlayerBoard(tile);
                }
            }
            // deck
            this.createInfluencePile(decksize);
        },

        /**
         * 
         * @param {Object} tile 
         */
        placeInfluenceTileBoard: function(tile) {
            const city = tile['city'];
            const s = tile['slot'];

            const card_div = this.createInfluenceCard(tile);
            const card = dojo.place(card_div,  $("influence_slot_"+s));

            this.decorateInfluenceCard(card, city, tile['type']);
        },

        /**
         * 
         * @param {Object} tile 
         */
        placeInfluenceTilePlayerBoard: function(tile) {
            const loc = tile['location'];
            const player_cards = loc+'_player_cards';
            const card_div = this.createInfluenceCard(tile);
            dojo.place(card_div, $(player_cards));
        },

        /**
         * 
         * @param {Object} tile 
         * @returns Div string
         */
        createInfluenceCard: function(tile) {
            const id = tile['id'];
            const city = tile['city'];
            const xoff = -1 * INFLUENCE_COL[tile['type']] * INFLUENCE_SCALE * this.influence_w;
            const yoff = -1 * INFLUENCE_ROW[city] * INFLUENCE_SCALE * this.influence_h;
            const card_div = this.format_block('jstpl_influence_tile', {id: id, city: city, x: xoff, y: yoff});
            return card_div;
        },

        /**
         * Deck pile of decksize cardbacks
         * @param {int} decksize 
         */
        createInfluencePile: function(decksize) {
            for (let c = 1; c <= decksize; c++) {
                const cardback = this.format_block('jstpl_influence_back', {id: c, x: -1 * INFLUENCE_SCALE * this.influence_w, y: -6 * INFLUENCE_SCALE * this.influence_h, m: c-1});
                dojo.place(cardback, $(INFLUENCE_PILE));
            }
            var pile_tt = _("Influence Deck: ${num} cards remaining");
            pile_tt = pile_tt.replace('${num}', decksize);
            this.addTooltip(INFLUENCE_PILE, pile_tt, '');
        },

        /**
         * Return translatable city name text
         * @param {string} city 
         * @returns translatable string
         */
        getCityNameTr: function(city) {
            const citynames = {
                "argos" : _("Argos"),
                "athens": _("Athens"),
                "corinth": _("Corinth"),
                "megara": _("Megara"),
                "sparta": _("Sparta"),
                "thebes": _("Thebes"),
                "any": _("Any City"),
                "persia": _("Persia"),
            };
            return citynames[city];
        },

        /**
         * Get the translation string for a special tile
         * @param {string} special 
         */
        getSpecialTileTr: function(special) {
            const special_names = {
                'perikles': _("Perikles"),
                'persianfleet': _("Persian Fleet"),
                'slaverevolt': _("Slave Revolt"),
                'brasidas': _("Brasidas"),
                'thessalanianallies': _("Thessalanian Allies"),
                'alkibiades': _("Alkibiades"),
                'phormio': _("Phormio"),
                'plague': _("Plague")
            };
            return special_names[special];
        },

        /**
         * For Influence cards on display, add Event listeners.
         * @param {Object} card
         * @param {string} city
         * @param {string} type
         */
         decorateInfluenceCard: function(card, city, type) {
            const helplbl = {
                "influence": "",
                "candidate": _("Candidate"),
                "assassin": _("Assassin"),
                "any": "",
            };
            const helptext = {
                "influence": _("Add 2 Influence cubes to ${city}"),
                "candidate": _("Add 1 Influence cube to ${city}, and propose a candidate in any city"),
                "assassin": _("Add 1 Influence cube to ${city}, and remove 1 cube from any city"),
                "any": _("Add 1 Influence cube to any city"),
            };

            const cityname = this.getCityNameTr(city);
            let ttext = "";
            if (city == "any") {
                ttext = helptext["any"];
            } else {
                ttext = helptext[type];
            }

            ttext = ttext.replace('${city}', cityname);
            const xoff = -1 * INFLUENCE_COL[type] * INFLUENCE_SCALE * this.influence_w;
            const yoff = -1 * INFLUENCE_ROW[city] * INFLUENCE_SCALE * this.influence_h;
            const tooltip = this.format_block('jstpl_influence_tt', {city: cityname, label: helplbl[type], text: ttext, x: xoff, y: yoff});
            this.addTooltipHtml(card.id, tooltip, '');

            card.addEventListener('click', () => {
                this.onInfluenceCardSelected(card.id);
            });
            card.addEventListener('mouseenter', () => {
                this.onInfluenceCardHover(card.id, true);
            });
            card.addEventListener('mouseleave', () => {
                this.onInfluenceCardHover(card.id, false);
            });
        },

        /**
         * Place influence cubes on cities.
         * @param {Object} influencecubes 
         */
        setupInfluenceCubes: function(influencecubes) {
            for (const player_id of Object.keys(influencecubes)) {
                for (const [city, cubes] of Object.entries(influencecubes[player_id])) {
                    const num = parseInt(cubes);
                    for (let n = 0; n < num; n++) {
                        const cube = this.createInfluenceCube(player_id, city, n);
                        const column = $(city+"_cubes_"+player_id);
                        const cube_div = dojo.place(cube, column);
                        cube_div.addEventListener('click', (event) => this.onSelectCube(event));
                    }
                    this.decorateInfluenceCubes(city, player_id);
                }
            }
        },

        /**
         * Add event listeners to the cubes areas for each player.
         * @param {string} city
         * @param {string} player_id
         */
        decorateInfluenceCubes: function(city, player_id) {
            const id = city+"_cubes_"+player_id;
            const cubes_div = $(id);

            cubes_div.addEventListener('mouseenter', (event) => {
                this.onInfluenceCubesHover(event, city, true);
            });
            cubes_div.addEventListener('mouseleave', (event) => {
                this.onInfluenceCubesHover(event, city, false);
            });
            cubes_div.addEventListener('click', (event) => {
                this.onInfluenceCubesClick(event, city, player_id);
            });
        },

        /**
         * Put all the Location tiles in their slots.
         * @param {array} players
         * @param {rray} locationtiles 
         */
        setupLocationTiles: function(locationtiles) {
            const tile_scale = 0.2;
            // create player area for victory tiles
            for (const player_id in this.gamedatas.players) {
                const player_tiles = this.format_block('jstpl_victory_tiles', {id: player_id, scale: tile_scale});
                dojo.place(player_tiles, $('player_board_'+player_id));
            }
            for (let loc of locationtiles) {
                const slot = loc['slot'];
                const location = loc['location'];
                const tile = new perikles.locationtile(location);
                const place = loc['loc'];
                const tile_div = tile.createTile();
                if (place == "board") {
                    const tileObj = dojo.place(tile_div, $("location_"+slot));
                    const lochtml = tile.createTooltip(this.getCityNameTr(tile.getCity()));
                    this.addTooltipHtml(tileObj.id, lochtml, '');
                } else if (place == "unclaimed") {
                    this.createUnclaimedTilesBox();
                    const tileObj = dojo.place(tile_div, $("unclaimed_tiles"));
                    tileObj.style.margin = null;
                    const tt = tile.createVictoryTileTooltip();
                    this.addTooltipHtml(tileObj.id, tt, '');
                } else if (place.startsWith("persia")) {
                    // this is the special case where a tile was claimed by multiple players sharing Persian control
                    const n = toint(place.slice(-1));
                    for (let i = 1; i <= n; i++) {
                        const persian_player = loc['persia'+i];
                        let tileObj = dojo.place(tile_div, $(persian_player+'_player_tiles'));
                        tileObj = this.makePersianVictoryTile(tileObj, i);
                        const tt = tile.createVictoryTileTooltip();
                        this.addTooltipHtml(tileObj.id, tt, '');
                    }
                } else {
                    // player claimed
                    const victoryTile = dojo.place(tile_div, $(place+'_player_tiles'));
                    const tt = tile.createVictoryTileTooltip();
                    this.addTooltipHtml(victoryTile.id, tt, '');
                }
            }
            this.displayPlayerVictoryTiles();
        },

        /**
         * Put cubes in candidate spaces.
         * @param {Object} candidates 
         */
        setupCandidates: function(candidates) {
            for (const [cand, player_id] of Object.entries(candidates)) {
                const cid = cand.split('_');
                const city = cid[0];
                const cube = this.createInfluenceCube(player_id, city, cid[1]);
                const candidate = dojo.place(cube, $(cand));
                candidate.addEventListener('click', (event) => this.onSelectCube(event));
            }
        },

        /**
         * Place Leader tokens on cities.
         * @param {Object} leaders 
         */
        setupLeaders: function(leaders) {
            for (const [city, player_id] of Object.entries(leaders)) {
                this.createLeaderCounter(player_id, city, "leader");
            }
        },

        /**
         * Place all statues in city statue areas.
         * @param {Object} statues 
         */
        setupStatues: function(statues) {
            for (const [player_id, civs] of Object.entries(statues)) {
                for (const [city, num] of Object.entries(civs)) {
                    for (let i = 0; i < toint(num); i++) {
                        this.createLeaderCounter(player_id, city, "statue");
                    }
                }
            }
        },

        /**
         * For creating Leader and Statue counters.
         * Places it in the zone.
         * @param {int} player_id 
         * @param {string} city 
         * @param {string} type "leader" or "statue"
         */
        createLeaderCounter: function(player_id, city, type) {
            let counter_zone = $(city+"_leader");
            let s = 0;
            let tt = _("${player_name} is Leader of ${city_name}");
            if (type == "statue") {
                counter_zone = $(city+"_statues");
                s = counter_zone.childElementCount;
                tt = _("Statue of ${player_name} in ${city_name}");
            }
            const leaderhtml = this.format_block('jstpl_leader', {city: city, type: type, num: s});
            const leader = dojo.place(leaderhtml, counter_zone);
            leader.dataset.color = this.decorator.playerColor(player_id);

            tt = tt.replace('${player_name}', this.decorator.spanPlayerName(player_id, this.isColorblind()));
            tt = tt.replace('${city_name}', this.getCityNameTr(city));
            this.addTooltip(leader.id, tt, '');
        },

        /**
         * Create a cube div in player's color.
         * @param {int} player_id
         * @param {string} city
         * @param {string} tag
         * @returns html for colored influence cube
         */
        createInfluenceCube: function(player_id, city, tag) {
            const color = this.getPlayerColor(player_id);
            const id = player_id+"_"+city+"_"+tag;
            const cube = this.format_block('jstpl_cube', {id: id, color: color});
            return cube;
        },

        /**
         * Set up battle tokens in their boxes given current state.
         * @param {Object} tokens {box id => num tokens}
         */
         setupTokens: function(tokens) {
            let i = 0;
            for (let [box, count] of Object.entries(tokens)) {
                for (let n = 0; n < count; n++) {
                    const token = this.format_block('jstpl_battle_token', {id: i++});
                    dojo.place(token, $(box));
                }
            }
        },

        /**
         * For start of a combat. If tokens are not already present, place four new tokens in the center battle_tokens box.
         */
        initializeBattleTokens: function() {
            const tokens = $('perikles_map').getElementsByClassName("prk_battle_token");
            if (tokens.length == 0) {
                for (let i = 0; i < 4; i++) {
                    const token = this.format_block('jstpl_battle_token', {id: i});
                    dojo.place(token, $('battle_tokens'));
                }
            }
        },

        /**
         * Move tokens from attacker/defender boxes back to center. Keep one if we have a victory from previous round.
         * @param {string} (optional) winner "attacker" or "defender" or null
         */
        returnBattleTokens(winner=null) {
            let keep1 = null;
            if (winner != null) {
                keep1 = winner;
            }

            const attacker_tokens = $('attacker_battle_tokens').getElementsByClassName("prk_battle_token");
            if (attacker_tokens.length == 0 && keep1 == "attacker") {
                // attacker gets a starting token but there isn't one already in the attacker box
                this.moveTokenToBattleSide(keep1);
            } else {
                [...attacker_tokens].forEach(t => {
                    if (keep1 == "attacker") {
                        keep1 = null;
                    } else {
                        this.slideToObjectRelative( t.id, 'battle_tokens', 1000, 500, null, "last" );
                    }
                });
            }
            const defender_tokens = $('defender_battle_tokens').getElementsByClassName("prk_battle_token");
            if (defender_tokens.length == 0 && keep1 == "defender") {
                // defender gets a starting token but there isn't one already in the attacker box
                this.moveTokenToBattleSide(keep1);
            } else {
                [...defender_tokens].forEach(t => {
                    if (keep1 == "defender") {
                        keep1 = null;
                    } else {
                        this.slideToObjectRelative( t.id, 'battle_tokens', 1000, 500, null, "last" );
                    }
                });
            }
        },

        /**
         * Move one Battle Token from center box to attacker or defender.
         * @param {string} side attacker or defender
         */
        moveTokenToBattleSide: function(side) {
            const token = $('battle_tokens').lastChild;
            this.slideToObjectRelative(token.id, side+'_battle_tokens', 1000, 500, null, "last");
        },

        /**
         * Place all military counters
         * @param {Object} military 
         */
        setupMilitary: function(military, persianleaders) {
            // first add Persia
            this.createStack("persia");
            // stack for each city
            for (const city of CITIES) {
                this.createStack(city);
            }

            for(const mil of military) {
                const counter = this.militaryToCounter(mil);
                if (counter.getLocation() == counter.getCity() && counter.getPosition() == 0) {
                    // in a city stack
                    counter.addToStack();
                } else if (counter.getLocation() == DEAD_POOL) {
                    // in the dead pool
                    this.createMilitaryArea(DEAD_POOL, counter.getCity());
                    const counterObj = counter.placeCounterInContainer(DEAD_POOL);
                    counterObj.setAttribute("title", this.counterText(counter));
                } else if (Object.keys(LOCATION_TILES).includes(counter.getLocation())) {
                    // sent to a battle
                    counter.placeBattle();
                } else {
                    // it's in a player pool
                    const player_id = counter.getLocation();
                    // "_persia_" is special flag for controlled persian units
                    if (player_id == this.player_id || (player_id == "_persia_" && persianleaders.includes(String(this.player_id)))) {
                        this.createMilitaryArea(player_id, counter.getCity());
                        const counterObj = counter.placeCounterInContainer(player_id);
                        counterObj.setAttribute("title", this.counterText(counter));
                    }
                }
            }
            // reinitialize tooltips
            this.setCityStackTooltip("persia");
            for (const city of CITIES) {
                this.setCityStackTooltip(city);
            }
        },

        /**
         * Factory method: create a counter from the PHP object in datas.
         * @param {Object} military 
         * @returns perikles.counter
         */
        militaryToCounter: function(military) {
            const city = military['city'];
            const unit = military['type'];
            const strength = military['strength'];
            const id = military['id'];
            const location = military['location'];
            const position = military['battlepos'];
            const counter = new perikles.counter(city, unit, strength, id, location, position);
            return counter;
        },

        /**
         * Create a military stack, add a tooltip.
         * @param {string} city 
         */
        createStack: function(city) {
            const stack = city+"_military";
            this.stacks.decorateMilitaryStack(stack);
        },

        /**
         * set tooltip for a city stack, depending on whether there is a stack there or not.
         * @param {string} city 
         */
        setCityStackTooltip: function(city) {
            const stack = city+"_military";
            // get rid of previous tooltip
            this.removeTooltip(stack);
            tt = this.stacks.showStartingForces(city, this.spanCityName(city));
            if ($(stack).childElementCount != 0) {
                tt += '<div style="margin-top: 0.5em;">';
                tt += _("Click to inspect stack");
                tt += '<div>'
            }
            this.addTooltip(stack, tt, '');
        },

        /**
         * Place Defeat counters on cities.
         * @param {Object} defeats 
         */
        setupDefeats: function(defeats) {
            for (let [city, num] of Object.entries(defeats)) {
                for (let d = 1; d <= num; d++) {
                    this.addDefeatCounter(city, d);
                }
            }
        },

        /**
         * Add event listeners to city divs.
         */
        setupCities: function() {
            for (const city of CITIES) {
                $(city).addEventListener('mouseenter', () => {
                    this.onCityTouch(city, true);
                });
                $(city).addEventListener('mouseleave', () => {
                    this.onCityTouch(city, false);
                });
                $(city).addEventListener('click', () => {
                    this.onCityClick(city);
                });
            }
        },

        /**
         * Show or hide the player board areas holding victory tiles.
         */
        displayPlayerVictoryTiles: function() {
            tilezones = document.getElementsByClassName("prk_player_tiles");
            [...tilezones].forEach(z => {
                z.style.display = (z.childElementCount == 0) ? 'none' : 'flex';
            });
       },

        ///////////////////////////////////////////////////
        //// Display methods

        /* @Override */
        format_string_recursive : function(log, args) {
            try {
                if (log && args && !args.processed) {
                    args.processed = true;

                    if (args.player_name && args.player_id && this.gamedatas.players[args.player_id]) {
                        args.player_name = this.decorator.spanPlayerName(args.player_id, this.isColorblind());
                    }
                    if (args.actplayer) {
                        args.actplayer = args.actplayer.replace('color:#FFF;', 'color:#FFF;'+WHITE_OUTLINE);
                    }
                    if (args.candidate_name && args.candidate_id) {
                        args.candidate_name  = this.decorator.spanPlayerName(args.candidate_id, this.isColorblind());
                    }
                    if (args.city_name && args.city) {
                        args.city_name = this.spanCityName(args.city);
                    }
                    if (args.city_name2 && args.city2) {
                        args.city_name2 = this.spanCityName(args.city2);
                    }
                    if (args.special_tile && args.icon) {
                        const specialtile = new perikles.specialtile(args.player_id, args.tile, false);
                        const specialhtml = specialtile.getLogDiv();
                        args.special_tile = '<span class="prk_special_log">'+args.special_tile+'</span>';
                        args.icon = specialhtml;
                    }
                    if (args.cubes && args.icon && !args.leader) {
                        let cubeicon = "";
                        for (let i = 0; i < args.cubes; i++) {
                            const cube = this.createInfluenceCube(args.player_id, args.city, "log");
                            cubeicon += cube;
                        }
                        args.icon = cubeicon;
                    }
                    if (args.token && args.icon) {
                        const token = this.format_block('jstpl_battle_token', {id: "log"});
                        args.icon = token;
                    }
                    if (args.location && args.icon && !args.casualty_log) {
                        const tile = new perikles.locationtile(args.location);
                        const tile_div = tile.createIcon();
                        let loc_msg = tile_div;
                        if (args.battlepos && args.type) {
                            loc_msg = '<div style="display: flex; flex-direction: row; align-items: center;">';
                            const counter = new perikles.counter(args.city, args.type, 0);
                            const mil_div = counter.toLogIcon();
                            if (toint(args.battlepos) > 2) {
                                // defender
                                loc_msg += tile_div + LEFT_ARROW + mil_div;
                            } else {
                                // attacker
                                loc_msg += mil_div + RIGHT_ARROW + tile_div;
                            }
                            loc_msg += '</div>';
                        }
                        args.icon = loc_msg;
                    }
                    // a battle
                    if (args.crt) {
                        args.att = '<span class="prk_special_log">'+args.att+'</span>';
                        args.def = '<span class="prk_special_log">'+args.def+'</span>';
                    }
                    if (args.crtroll) {
                        args.crtroll = '<br/>';
                        const hit = '<span class="prk_hit">'+_("Success")+'</span>';
                        const miss = '<span class="prk_miss">'+_("Failure")+'</span>';
                        args.attd1 = this.diceIcon(args.attd1, "attacker");
                        args.attd2 = this.diceIcon(args.attd2, "attacker");
                        args.defd1 = this.diceIcon(args.defd1, "defender");
                        args.defd2 = this.diceIcon(args.defd2, "defender");
                        args.atttotal = '<span class="prk_dicetotal">'+args.atttotal+'</span>';
                        args.deftotal = '<span class="prk_dicetotal">'+args.deftotal+'</span>';
                        args.atttarget = '<span class="prk_dicetotal">'+args.atttarget+'</span>';
                        args.deftarget = '<span class="prk_dicetotal">'+args.deftarget+'</span>';
                        args.atttotal = '<span>'+args.atttotal+'</span>';
                        args.atthit = args.atthit ? hit : miss;
                        args.defhit = args.defhit ? hit : miss;
                    }
                    // for slave revolt, show Hoplite counter
                    if (args.return_from && args.icon) {
                        const strength = args.strength;
                        counter = new perikles.counter("sparta", HOPLITE, strength, "slaverevolt_log");
                        const mil_html = counter.toRelativeDiv("inline-block");
                        args.icon = mil_html;
                    }
                    // choose unit from deadpool
                    if (args.deadpool) {
                        // for log message showing unit was chosen
                        if (args.deadpool === true && args.icon) {
                            const type = args.type;
                            const strength = args.strength;
                            const city = args.city;
                            counter = new perikles.counter(city, type, strength, "deadpool_log");
                            const mil_html = counter.toRelativeDiv("inline-block");
                            args.icon = mil_html;
                        } else {
                            // here it's displaying in the titlebar units to be chosen
                            log += '<br>';
                            for (const [id, unit] of Object.entries(args.deadpool)) {
                                const counter = new perikles.counter(unit.city, unit.type, unit.strength, "deadpool_select");
                                const mil_div = counter.toRelativeDiv("inline-block");
                                log += mil_div;
                            }
                        }
                    }
                    // assign casualties
                    if (args.casualty) {
                        const type = args.type;
                        const strength = args.strength;
                        const cities = args.cities;
                        log += '<br/>';
                        for (let c of cities) {
                            counter = new perikles.counter(c, type, strength, "casualty_select");
                            let mil_html = counter.toRelativeDiv("inline-block");
                            log += mil_html;
                        }
                    }
                    if (args.casualty_log && args.icon) {
                        const type = args.type;
                        const strength = args.strength;
                        const city = args.city;
                        counter = new perikles.counter(city, type, strength, "casualty_log");
                        const mil_html = counter.toRelativeDiv("inline-block");
                        args.icon = mil_html;
                    }
                    // defeat counter
                    if (args.defeats && args.icon) {
                        const city = args.city;
                        const def_ctr = this.createDefeatCounter(city, args.defeats+'_log');
                        args.icon = def_ctr;
                    }
                    // leader/statue counters
                    if (args.leader && args.icon) {
                        const leader = args.leader;
                        const player_id = args.player_id;
                        const city = args.city;
                        const color = this.decorator.playerColor(player_id);
                        const ldr_ctr = this.format_block('jstpl_leader_log', {city: city, type: leader, color: color});
                        args.icon = ldr_ctr;
                    }
                    if (!this.isSpectator) {
                        log = log.replaceAll("You", this.decorator.spanYou(this.player_id), this.isColorblind());

                        if (args.committed) {
                            const commit_log = this.createCommittedUnits(args.committed);
                            log = log.replace('committed_forces', commit_log);
                        }
                        if (args.plague) {
                            const plague_btns = this.createPlagueButtons();
                            log += plague_btns;
                        }
                        if (args.alkibiades) {
                            const alkibiades_btns = this.createAlkibiadesButtons();
                            log += alkibiades_btns;
                        }
                        if (args.slaverevolt) {
                            const slaverevolt_btns = this.createSlaveRevoltButtons();
                            log += slaverevolt_btns;
                        }
                    }
                }
            } catch (e) {
                console.error(log, args, "Exception thrown", e.stack);
            }
            return this.inherited(arguments);
        },

        /**
         * Show all the units that have been assigned to send to battles and display in commit dialog.
         * @param {Object} committed Object from args
         * @returns html div
         */
        createCommittedUnits: function(committed) {
            let commit_log = "";
            const attack_str = _("Send ${unit} to attack ${location}");
            const defend_str = _("Send ${unit} to defend ${location}");
            let counters = 0;
            // if spent cube from city for extra units
            const commit_city = committed.cube;
            let extra_forces = "";
            for (const[id, selected] of Object.entries(committed)) {
                if (id != "cube") {
                    let commit_str = (selected.side == "attack" ? attack_str : defend_str);
                    const counter = new perikles.counter(selected.city, selected.unit, selected.strength, id+"_dlg");
                    let mil_html = counter.toRelativeDiv("inline-block");
                    commit_str = commit_str.replace('${unit}', mil_html);
                    const tile = new perikles.locationtile(selected.location);
                    let loc_html = tile.createTile(0, "commit_"+counter.getId());
                    loc_html = this.decorator.prependStyle(loc_html, 'display: inline-block');
                    commit_str = commit_str.replace('${location}', loc_html);

                    if (selected.cube) {
                        extra_forces += commit_str+'<br/>';
                    } else {
                        commit_log += commit_str+'<br/>';
                    }
                    counters++;
                }
            }
            if (counters < 2) {
                let commit_string = _("${num}/2 units assigned");
                commit_string = commit_string.replace('${num}', counters);
                commit_log = commit_string+'<br/>'+commit_log;
            } else {
                // option to spend Influence cube
                commit_log += '<hr/>';
                if (commit_city) {
                    let city_commit = _("Additional unit(s) committed from ${city}");
                    city_commit = city_commit.replace('${city}', this.spanCityName(commit_city));
                    commit_log += city_commit + '<br/>';
                    commit_log += extra_forces;
                } else {
                    let spend_cubes_div = this.createSpendInfluenceDiv();
                    if (spend_cubes_div != null) {
                        let cubehtml = this.createInfluenceCube(this.player_id, 'commit', '');
                        cubehtml = this.decorator.prependStyle(cubehtml, "display: inline-block; margin-left: 5px;");

                        let msg = _("You may spend an Influence cube to send an additional 1 or 2 units from that city");
                        if (this.isPersianLeader(this.player_id)) {
                            msg = _("You may spend an Influence cube from any city to send an additional 1 or 2 units");
                        }
                        spend_cubes_div = msg+cubehtml+'<br/>'+spend_cubes_div;
                        commit_log += spend_cubes_div;
                    }
                }
            }
            commit_log += '<br/>';
            return commit_log;
        },

        /**
         * Create die icon.
         * @param {string} val 
         * @param {string} side "attacker" or "defender"
         * @returns html icon
         */
        diceIcon: function(val, side) {
            const roll = toint(val);
            const xoff = -33 * (roll-1);
            const die_icon = this.format_block('jstpl_die', {x: xoff, side: side});
            return die_icon;
        },

        /**
         * Take a city name and put it in colored text and translate the name.
         * @param {string} city 
         * @returns decorated HTML div
         */
        spanCityName: function(city) {
            let cityclass = "prk_city_name";
            if (city != "any") {
                cityclass += " prk_shadow"
            }
            const city_name = '<div class="'+cityclass+'" style="color:var(--color_'+city+');">'+this.getCityNameTr(city)+'</div>';
            return city_name;
        },

        /**
         * Puts top banner for active player.
         * @param {string} text
         * @param {Array} moreargs
         */
         setDescriptionOnMyTurn: function(text, moreargs) {
            const oldtext = this.isCurrentPlayerActive() ? this.gamedatas.gamestate.descriptionmyturn : this.gamedatas.gamestate.description;
            this.gamedatas.gamestate.olddescriptionmyturn = oldtext;
            this.gamedatas.gamestate.descriptionmyturn = text;
            this.gamedatas.gamestate.oldargs = this.gamedatas.gamestate.args;
            let tpl = Object.assign({}, this.gamedatas.gamestate.args);
            if (!tpl) {
                tpl = {};
            }
            if (typeof moreargs != 'undefined') {
                for ( const key in moreargs) {
                    if (moreargs.hasOwnProperty(key)) {
                        tpl[key]=moreargs[key];
                    }
                }
            }
 
            let title = "";
            if (this.isCurrentPlayerActive() && text !== null) {
                tpl.you = this.decorator.spanYou(this.player_id, this.isColorblind());
            }
            if (text !== null) {
                title = this.format_string_recursive(text, tpl);
            }
            if (title == "") {
                this.decorator.setMainTitle("&nbsp;");
            } else {
                this.decorator.setMainTitle(title);
            }
        },

        /**
         * Restore title text.
         */
        restoreDescriptionOnMyTurn: function() {
            const text = this.gamedatas.gamestate.olddescriptionmyturn;
            if (text) {
                const player_id = (this.gamedatas.gamestate.name == "specialBattleTile") ? this.getCurrentPlayerId() : this.getActivePlayerId();
                const acting = this.decorator.spanPlayerName(player_id, this.isColorblind());
                this.setDescriptionOnMyTurn(text, {actplayer: acting});
            }
        },

        /**
         * Create a military area for player, if it does not already exist.
         * Or for DEAD_POOL
         * @param {string} id
         * @param {strin} city 
         * @returns id of military div
         */
        createMilitaryArea: function(id, city) {
            const city_mil = [city, 'military', id].join("_");
            if (!document.getElementById(city_mil)) {
                const mil_div = this.format_block('jstpl_military_area', {city: city, id: id, cityname: this.getCityNameTr(city)});
                const zone_id = (id == DEAD_POOL) ? 'deadpool_ctnr' : 'mymilitary';
                const zone = $(zone_id);
                dojo.place(mil_div, zone);
            }
            return city_mil;
        },

        /**
         * Move a cube from one location to another.
         * @param {string} cube 
         * @param {DOMElement} from_div 
         * @param {DOMElement} to_div
         * @param {int} delay
         */
         moveCube: function(cube, from_div, to_div, delay) {
            const mobile = dojo.place(cube, from_div);
            mobile.addEventListener('click', (event) => this.onSelectCube(event));
            this.slideToObjectRelative(mobile, to_div, 1000, delay, this.decorator.visibilize, "last");
        },

        /**
         * Move a military token from the city to the player's board
         * @param {Object} military
         * @param {bool} fromDeadpool is it being moved from deadpool?
         */
        counterToPlayerBoard: function(military, fromDeadpool=false) {
            const counter = this.militaryToCounter(military);
            const player_id = military['location'];
            const id = counter.getCounterId();
            const counterObj = $(id);
            this.slideToObjectAndDestroy(counterObj, $('player_board_'+player_id), 500, 500);
            if (player_id == this.player_id) {
                this.createMilitaryArea(player_id, counter.getCity());
                const newCounterObj = counter.placeCounterInContainer(player_id);
                newCounterObj.setAttribute("title", this.counterText(counter));
            }
            if (fromDeadpool) {
                this.stacks.sortCounterStack(counterObj);
            }

        },

        /**
         * Remove deadpool if it's empty, or else remove empty deadpool columns.
         */
        updateDeadpoolDisplay: function() {
            // hide dead pool if no more units
            const deadunits = $(DEAD_POOL).getElementsByClassName("prk_military");
            $(DEAD_POOL).style['display'] = (deadunits.length == 0) ? 'none' : 'block';

            if (deadunits.length > 0) {
                // remove cities with no dead units
                const boards = $(DEAD_POOL).getElementsByClassName("prk_mil_board");
                [...boards].forEach(b => {
                    const bunits = b.getElementsByClassName("prk_military");
                    if (bunits.length == 0) {
                        b.remove();
                    }
                });
                // now remove containers that are empty
                this.stacks.hideEmptyUnitContainers(DEAD_POOL);
            }
        },

        /**
         * Move an object to a battle tile
         * @param {*} military 
         */
        moveToBattle: function(player_id, counter, slot) {
            // if it's my counter, remove it from my board
            let mycounter = false;
            if (player_id == this.player_id) {
                mycounter = true;
            } else {
                // also need to check for shared Persian counters
                if (counter.getCity() == "persia") {
                    if (this.isPersianLeader(player_id) && this.isPersianLeader(this.player_id)) {
                        mycounter = true;
                    }
                }
            }
            if (mycounter) {
                $(counter.getCity()+'_'+counter.getType()+'_'+counter.getStrength()+'_'+counter.getId()).remove();
            }
            // move from city to battle
            const battlepos = "battle_"+slot+"_"+counter.getType()+"_"+counter.getBattlePosition();
            const stackct = $(battlepos).childElementCount;
            const id = counter.getId();
            // for facedown counters
            if (id == 0) {
                counter.setId(stackct+"_"+counter.getLocation());
            }
            const counter_html = counter.toBattleDiv(stackct);
            const milzone = $(counter.getCity()+"_military");
            const counterObj = dojo.place(counter_html, milzone);
            this.slide(counterObj, battlepos, {from: milzone});
        },

        /**
         * Create the HTML div for a Defeat counter.
         * @param {string} city 
         * @param {int} num 
         * @returns 
         */
        createDefeatCounter: function(city, num) {
            let title_str = _("${city_name} Defeat");
            title_str = title_str.replace('${city_name}', this.getCityNameTr(city));
            const def_ctr = this.format_block('jstpl_defeat', {city: city, num: num, title: title_str} );
            return def_ctr;
        },

        /**
         * Add a defeat counter to a city. Checks to make sure not more than 4
         * (should only be possible with athens and sparta).
         * @param {string} city
         * @param {string} num
         */
        addDefeatCounter: function(city, num) {
            if (num <= 4) {
                const defeat_ctr = this.createDefeatCounter(city, num);
                const def_div = $(city+'_defeat_slot_'+num);
                dojo.place(defeat_ctr, def_div);
            }
        },

        /**
         * Moves a Location tile to a player board (or Unclaimed Tiles), scores it, removes permissions box.
         * @param {Element} tile 
         * @param {string} location 
         * @param {string} toDiv destination ID
         * @param {string} (optional) player_id or bull
         * @param {int} vp points
         */
         moveAndScoreTile: function(tile, location, toDiv, player_id=null, vp=0) {
            // clear margin before putting in box
            tile.style.margin = null;
            this.slideToObjectRelative(tile.id, toDiv, 500, 0);
            this.removeTooltip(tile.id);
            const mytile = new perikles.locationtile(location);
            const tt = mytile.createVictoryTileTooltip();
            this.addTooltipHtml(tile.id, tt, '');
            if (player_id) {
                this.scoreCtrl[ player_id ].incValue( vp );
            }
            // remove the permissions box if it wasn't already
            // (may be already moved with multiple Persian players claiming a tile)
            const perm_box = $(location+'_permissions');
            if (perm_box) {
                perm_box.remove();
            } 
        },

        /**
         * Clear effects after a tile has been resolved
         * @param {string} location 
         */
        postBattle: function(location) {
            this.dice.clearResultHighlights();
            $(location+'_permissions_wrapper').remove();
        },

        /**
         * When player unstages units they were preparing to commit, or cancels a defend request.
        1 */
        uncommitUnits: function() {
            const mils = $('mymilitary').getElementsByClassName('prk_military ');
            [...mils].forEach(m => {
                // redisplay counters that were hidden before
                delete m.dataset.selected;
                // reenable deselected counters
                if (m.dataset.selectable == "false") {
                    this.makeSelectable(m);
                }
            });
        },

        //////////////////////////////////////////////////////////////////////////////
        //// Borrowed from Tisaac
        //////////////////////////////////////////////////////////////////////////////

        /**
         * Tisaac's slide method.
         * @param {DOMElement} mobile 
         * @param {string} targetId 
         * @param {Object} options 
         * @returns a Promise
         */
         slide: function(mobile, targetId, options = {}) {
            let config = Object.assign(
              {
                duration: 800,
                delay: 0,
                destroy: false,
                attach: true,
                changeParent: true, // Change parent during sliding to avoid zIndex issue
                pos: null,
                className: 'moving',
                from: null,
                clearPos: true,
              },
              options,
            );
            const newParent = config.attach ? targetId : $(mobile).parentNode;
            mobile.style['z-index'] = 5000;
            mobile.classList.add(config.className);
            // if (config.changeParent) {
            //     this.changeParent(mobile, 'game_play_area');
            // }
            if (config.from != null) {
                this.placeOnObject(mobile, config.from);
            }
            return new Promise((resolve, reject) => {
              const animation =
                config.pos == null
                  ? this.slideToObject(mobile, targetId, config.duration, config.delay)
                  : this.slideToObjectPos(mobile, targetId, config.pos.x, config.pos.y, config.duration, config.delay);
      
              dojo.connect(animation, 'onEnd', () => {
                // mobile.style['z-index'] = null;
                mobile.classList.remove(config.className);
                if (config.changeParent) {
                    this.changeParent(mobile, newParent);
                }
                if (config.destroy) {
                    mobile.parentNode.removeChild(mobile);
                }
                if (config.clearPos && !config.destroy) {
                    Object.assign(mobile.style, { top: null, left: null, position: null, 'z-index': null });
                }
                resolve();
              });
              animation.play();
            });
          },

          /**
           * 
           * @param {DOMObject} mobile 
           * @param {string} parent_id 
           */
          changeParent: function(mobile, parent_id) {
            $(parent_id).appendChild(mobile);
          },

        /*
         * This method is similar to slideToObject but works on object which do not use inline style positioning. It also attaches object to
         * new parent immediately, so parent is correct during animation
         */
        slideToObjectRelative: function (token, finalPlace, duration, delay, onEnd, relation) {
            token = $(token);
            this.delayedExec(() => {
                token.style.transition = "none";
                token.classList.add('moving_token');
                var box = this.attachToNewParentNoDestroy(token, finalPlace, relation, 'static');
                token.offsetHeight; // re-flow
                token.style.transition = "all " + duration + "ms ease-in-out";
                token.style.left = box.l + "px";
                token.style.top = box.t + "px";
            }, () => {
                token.style.removeProperty("transition");
                this.decorator.stripPosition(token);
                token.classList.remove('moving_token');
                // Perikles additions
                if (token.classList.contains("prk_military")) {
                    this.adjustMilitaryCounter(token, finalPlace);
                }
                if (onEnd) onEnd(token);
            }, duration, delay);
        },

        /**
         * 
         * @param {DOM} token
         * @param {string} container
         */
        adjustMilitaryCounter: function(token, container_id) {
            const container = $(container_id);
            // is this on my military board?
            if (container.classList.contains("prk_units_column")) {


            } else {
                token.style.position = "relative";
                token.style.margin = "1px";
            }
        },

        /**
         * This method will attach mobile to a new_parent without destroying, unlike original attachToNewParent which destroys mobile and
         * all its connectors (onClick, etc)
         */
         attachToNewParentNoDestroy: function (mobile_in, new_parent_in, relation, place_position) {
            const mobile = $(mobile_in);
            const new_parent = $(new_parent_in);

            var src = dojo.position(mobile);
            if (place_position) {
                mobile.style.position = place_position;
            }
            dojo.place(mobile, new_parent, relation);
            mobile.offsetTop;//force re-flow
            var tgt = dojo.position(mobile);
            var box = dojo.marginBox(mobile);
            var cbox = dojo.contentBox(mobile);
            var left = box.l + src.x - tgt.x;
            var top = box.t + src.y - tgt.y;

            mobile.style.position = "absolute";
            mobile.style.left = left + "px";
            mobile.style.top = top + "px";
            box.l += box.w - cbox.w;
            box.t += box.h - cbox.h;
            mobile.offsetTop;//force re-flow
            return box;
        },

        /**
         * 
         * @param {*} onStart 
         * @param {*} onEnd 
         * @param {*} duration 
         * @param {*} delay 
         */
        delayedExec: function (onStart, onEnd, duration, delay) {
            if (typeof duration == "undefined") {
                duration = this.defaultAnimationDuration;
            }
            if (typeof delay == "undefined") {
                delay = 0;
            }
            if (this.instantaneousMode) {
                delay = Math.min(1, delay);
                duration = Math.min(1, duration);
            }
            if (delay) {
                setTimeout(function () {
                    onStart();
                    if (onEnd) {
                        setTimeout(onEnd, duration);
                    }
                }, delay);
            } else {
                onStart();
                if (onEnd) {
                    setTimeout(onEnd, duration);
                }
            }
        },
        
        ///////////////////////////////////////////////////
        //// Event listeners

        /**
         * Mouse entering City zone.
         * @param {string} city
         * @param {bool} enter true if touching, otherwise leaving
         */
        onCityTouch: function(city, enter) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("placeAnyCube", true)) {
                    const mycubes = $(city+"_cubes_"+this.player_id);
                    if (enter) {
                        this.decorator.highlight(mycubes);
                    } else {
                        this.decorator.unhighlight(mycubes);
                    }
                }
            }
        },

        /**
         * Mouse clicking City zone.
         * @param {string} city
         */
        onCityClick: function(city) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("placeAnyCube", true)) {
                    this.placeInfluenceCube(city);
                    this.decorator.removeAllHighlighted();
                }
            }
        },

        /**
         * Mouse entering or leaving player's Influence zone in city.
         * @param {Object} event 
         * @param {string} city
         * @param {bool} enter
         */
         onInfluenceCubesHover: function(event, city, enter) {
            if (this.isCurrentPlayerActive() && this.checkAction("proposeCandidate", true)) {
                const cube_div = event.target;
                // player must have a cube in the city
                if (enter && this.hasCubeInCity(city, true)) {
                    if (cube_div.hasChildNodes() && this.decorator.isHighlighted($(city))) {
                        this.decorator.highlight(cube_div);
                    }
                } else {
                    this.decorator.unhighlight(cube_div);
                }
            }
        },

        /**
         * Mouse clicking Influence zone in city.
         * @param {Object} event 
         * @param {string} city
         * @param {string} player_id
         */
         onInfluenceCubesClick: function(event, city, player_id) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("proposeCandidate", true)) {
                    const tgt = event.target;
                    // it's either the cube area or one of the cubes
                    if (this.decorator.isHighlighted($(city)) && this.hasCubeInCity(city, true)) {
                        if (tgt.classList.contains("prk_cube") || (tgt.classList.contains("prk_city_cubes") && tgt.hasChildNodes())) {
                            this.proposeCandidate(city, player_id);
                        }    
                    }
                }
            }
        },

        /**
         * When Influence card is clicked.
         * @param {string} id 
         */
         onInfluenceCardSelected: function(id) {
            if (this.checkAction("takeInfluence", true)) {
                this.takeInfluenceTile(id);
            }
       },

       /**
        * When mouse hovers or leaves Influence tile.
        * @param {string} id 
        * @param {bool} hover true if entering, false if leaving
        */
        onInfluenceCardHover: function(id, hover) {
            if (this.checkAction("takeInfluence", true)) {
                const card = $(id);
                if (hover) {
                    this.decorator.highlight(card);
                } else {
                    this.decorator.unhighlight(card);
                }
            }
        },

        /**
         * After clicking a cube during Assasassinate phase.
         */
        onSelectCube: function(event) {
            if (this.checkAction("chooseRemoveCube", true)) {
                const cube_id = event.target.id;
                const segs = cube_id.split("_");
                const player_id = segs[0];
                const city = segs[1];
                const c = segs[2];
                this.removeCube(player_id, city, c);
            }
        },

        /**
         * Send a unit to a battle location.
         * @param {string} id 
         * @param {string} city 
         * @param {string} unit HOPLITE or TRIREME
         * @param {int} strength 
         * @param {string} side attacker or defender
         * @param {string} battle location
         */
         onSendUnit: function(id, city, unit, strength, side, battle) {
            // is this an extra unit sent with a cube?
            const commit_city = this.gamedatas.gamestate.args.committed['cube'];
            const is_extra = (commit_city != null) && (commit_city == city || city == "persia");
            this.gamedatas.gamestate.args.committed[id] = {city: city, side: side, location: battle, strength: strength, unit: unit, cube: is_extra};
            this.setDescriptionOnMyTurn(_("You must commit forces")+'<br/>committed_forces');
            // add event listeners
            if ($(COMMIT_INFLUENCE_CUBES)) {
                const city_btns = $('commit_influence_cubes').getElementsByClassName("prk_city_btn");
                [...city_btns].forEach(btn => {
                    btn.addEventListener('click', this.onCommitExtraForces.bind(this));
                });
            }
            // hide unit on military board
            $(city+'_'+unit+'_'+strength+'_'+id).dataset.selected = "true";

            // don't forget the cube is one of the keys
            const len = Object.keys(this.gamedatas.gamestate.args.committed).length;
            // don't unselect anything if < 2
            if (len > 1) {
                let selectable_city = null;
                if (len > 2) {
                    if (commit_city) {
                        if (len == 4) {
                            if (city == "persia") {
                                selectable_city = "persia";
                            } else {
                                selectable_city = commit_city;
                            }
                        } else if (len > 5) {
                            // sanity check 2
                            throw new Error("More than 4 units selected!");
                        }
                    } else {
                        // sanity check
                        throw new Error("More than 2 units selected without a cube!");
                    }
                }
                const mymil = $(mymilitary).getElementsByClassName("prk_military");
                [...mymil].forEach(m => this.makeSelectable(m, false));
                if (selectable_city) {
                    const selunits = $(selectable_city+"_mil_ctnr_"+this.player_id).getElementsByClassName("prk_military");
                    [...selunits].forEach(s => this.makeSelectable(s));
                }
            }
        },

        /**
         * Player clicks "Commit Forces" button.
         */
        onCommitForces: function() {
            // existing units available?
            const myunits = $('mymilitary').getElementsByClassName("prk_military");
            const sz = Object.keys(this.gamedatas.gamestate.args.committed).length;
            if (sz == 0 && myunits.length > 0) {
                this.confirmationDialog( _("You have not selected any forces"), dojo.hitch( this, function() {
                    this.commitForces() 
                }));
            } else if (sz == 1 && myunits.length > 0) {
                this.confirmationDialog( _("You are only sending 1 unit; you may send up to 2"), dojo.hitch( this, function() {
                    this.commitForces() 
                }));
            } else {
                this.commitForces();
            }
        },

        /**
         * Player clicked a city button to spend a cube on sending extra counters.
         */
        onCommitExtraForces: function(evt) {
            const target = evt.currentTarget;
            const city = target.id.split('_')[0];
            this.gamedatas.gamestate.args.committed['cube'] = city;
            // hide other buttons
            $(COMMIT_INFLUENCE_CUBES).innerHTML = this.format_block('jstpl_city_banner', {city: city, city_name: this.getCityNameTr(city)});
            // readd listeners to chosen city
            // handle Persians
            let unit_city = city;
            if (this.isPersianLeader(this.player_id)) {
                unit_city = "persia";
            }
            const civ_mils = $(unit_city+'_military_'+this.player_id).getElementsByClassName('prk_military');
            [...civ_mils].forEach(ctr => this.makeSelectable(ctr));
        },

        /**
         * Player clicked "Cancel" while committing forces.
         * Clear "committed" gamedatas
         */
        onCancelCommit: function() {
            this.gamedatas.gamestate.args['committed'] = {};
            this.setDescriptionOnMyTurn(_("You must commit forces"));
            this.uncommitUnits();
            this.toggleAssignmentCancelButton(false);
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            this.currentState = stateName;
            console.log("Entering state: "+stateName, args);

            switch( stateName ) {
                case 'chooseInitialInfluence':
                case 'choosePlaceInfluence':
                    if( this.isCurrentPlayerActive() ) {
                        let cities = document.getElementsByClassName("prk_city");
                        [...cities].forEach(c => this.decorator.highlight(c));
                    }
                    break;
                case 'proposeCandidates':
                    if (this.isCurrentPlayerActive()) {
                        for (const city of CITIES) {
                            const candidate_space = this.openCandidateSpace(city);
                            if (candidate_space && this.hasCubeInCity(city, true) && this.existsOtherCubesInCity(city)) {
                                const city_div = $(city);
                                this.decorator.highlight(city_div);
                                this.decorator.highlight(candidate_space);
                            }
                        }
                    }
                    break;
                case 'assassinate':
                    if (this.isCurrentPlayerActive()) {
                        let cubes = $('perikles_map').getElementsByClassName("prk_cube");
                        [...cubes].forEach( c => c.dataset.action = "remove");
                    }
                    break;
                case 'commitForces':
                    if (this.isCurrentPlayerActive()) {
                            const mils = $('mymilitary').getElementsByClassName("prk_military");
                            [...mils].forEach(m => {
                                this.makeSelectable(m);
                            });
                            if (args.args._private) {
                                const committed = args.args._private.committed;
                                if (committed) {
                                    this.gamedatas.gamestate.args.committed['cube'] = committed.cube ?? "";
                                    if (committed.units) {
                                        // add these to gamestate committed
                                        for (const mil of committed.units) {
                                            const isSupportCube = (cube != "") && (mil.city == cube || mil.city == "persia");
                                            const id = mil.city+'_'+mil.unit+'_'+mil.strength+'_'+mil.id;
                                            this.gamedatas.gamestate.args.committed[id] = {city: mil.city, side: mil.side, location: mil.location, strength: mil.strength, unit: mil.unit, cube: isSupportCube};
                                        }
                                    }
                                } else {
                                this.gamedatas.gamestate.args = {};
                                this.gamedatas.gamestate.args.committed = {};
                            }
                        }
                        this.updateDeadpoolDisplay();
                    }
                    break;
                case 'nextPlayerCommit':
                    this.gamedatas.wars = args.args.wars;
                    break;
                case 'rollcombat':
                    this.dice.placeDice();
                    break;
                case 'endTurn':
                    this.resetWars();
                    this.gamedatas.permissions = {};
                    this.removePermissionButtons();
                    this.dice.removeDice();
                    break;
            }
            if (MILITARY_DISPLAY_STATES.includes(stateName)) {
                this.militaryPhaseDisplay();
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            switch( stateName ) {
                case 'chooseInitialInfluence':
                case 'choosePlaceInfluence':
                    this.decorator.removeAllHighlighted();
                    break;
                case 'proposeCandidates':
                    this.decorator.removeAllHighlighted();
                    this.last_cube = null;
                    break;
                case 'assassinate':
                    const cubes = document.querySelectorAll('[data-action="remove"]');
                    cubes.forEach(c => {
                        delete c.dataset.action;
                    });
                    break;
                case 'commitForces':
                    const mils = $('mymilitary').getElementsByClassName("prk_military");
                    [...mils].forEach(m => {
                        this.makeSelectable(m, false);
                    });
                    this.stacks.hideEmptyUnitContainers("mymilitary");
                    this.gamedatas.gamestate.args = {};
                    this.gamedatas.gamestate.args.committed = {};
                    break;
                case 'deadPool':
                case 'takeDead':
                    // hide dead pool if no more units
                    this.updateDeadpoolDisplay();
                    break;
                case 'dummmy':
                    break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log("onUpdateActionButtons: "+stateName, args);
            if( this.isCurrentPlayerActive() ) {
                switch( stateName ) {
                    case 'takeInfluence':
                        if (args._private.special) {
                            this.addSpecialTileButton();
                        }
                        break;
                    case 'spartanChoice':
                        for (player_id in this.gamedatas.players) {
                            this.addActionButton( 'choose_'+player_id, this.decorator.spanPlayerName(player_id, this.isColorblind()), 'choosePlayer', null, false, 'gray' );
                        }
                        break;
                    case 'commitForces':
                        this.addCommitForcesButton();
                        // add Cancel button if some units have already been assigned
                        this.addActionButton( "commit_cancel_btn", _('Cancel'), () => {
                            this.onCancelCommit();
                        }, null, null, 'red');
                        this.toggleAssignmentCancelButton(false);
                        // if we have the Slave Revolt tile
                        if (args._private.special) {
                            this.addSpecialTileButton();
                        }
                        break;
                    case 'specialTile':
                        if (args._private.special) {
                            this.addSpecialTileButton();
                            this.addSpecialPassButton();
                        }
                        break;
                    case 'specialBattleTile':
                        if (args._private.special) {
                            const location = args._private.location;
                            this.addSpecialTileButton(location);
                            this.addSpecialPassButton(true);
                        }
                        break;
                    case 'takeDead':
                        const deadpool = args.deadpool;
                        this.addDeadpoolButtons(deadpool);
                        break;
                    case 'takeLoss':
                        const type = args.type;
                        const cities = args.cities;
                        const strength = args.strength;
                        const location = args.location;
                        this.addCasualtyButtons(type, strength, cities, location);
                        break;
                    case 'permissionResponse':
                        // array of permission requests, each with {owner, owning_city, location, requesting_city}
                        const requesting_player = args.otherplayer_id;
                        const permission_requests = args.permission_requests;
                        let msg = '';
                        let multireqs = false;
                        
                        // create the status bar message
                        const isRequester = (this.player_id == requesting_player);
                        // array of mapped pairs, city-location, only if this player is the owner or requester
                        let requestargs = [];
                        for (let req of permission_requests) {
                            const location = req.location;
                            const locationName = new perikles.locationtile(location).getNameTr();
                            const requesting_city = req.requesting_city;
                            const owner = req.owner;
                            const isOwner = (this.player_id == owner);
                            if (isRequester || isOwner) {
                                requestargs.push({requesting_city, location});
                            }

                            const span_id = `${requesting_city}-${location}`;
                            let reqmsg = '';

                            if (isRequester) {
                                reqmsg = `<div class="prk_permrequest" id="req-${span_id}">` + _("You requested permission from ${player_name} for ${requesting_city} to defend ${location}") + '</div>';
                                reqmsg = reqmsg.replace('${player_name}', this.decorator.spanPlayerName(owner, this.isColorblind()));
                                reqmsg = reqmsg.replace('${requesting_city}', this.spanCityName(requesting_city));
                                reqmsg = reqmsg.replace('${location}', locationName);
                            } else if (isOwner) {
                                reqmsg = `<div class="prk_permrequest" id="req-${span_id}">` + _("${player_name} is requesting permission for ${requesting_city} to defend ${location}") + '</div>';
                                reqmsg = reqmsg.replace('${player_name}', this.decorator.spanPlayerName(requesting_player, this.isColorblind()));
                                reqmsg = reqmsg.replace('${requesting_city}', this.spanCityName(requesting_city));
                                reqmsg = reqmsg.replace('${location}', locationName);
                            }
                            if (multireqs) {
                                msg += reqmsg;
                            } else {
                                msg = reqmsg;
                                multireqs = true;
                            }
                        }
                        debugger;
                        if  (isRequester) {
                            this.addPermissionCancelButton(requestargs);
                            // const is_extra = (commit_city != null) && (commit_city == city || city == "persia");
                            //this.gamedatas.gamestate.args.committed[id] = {city: city, side: side, location: battle, strength: strength, unit: unit, cube: is_extra};
                            console.log("Added permission cancel button for "+requestargs);
                        }  else if (requestargs.length > 0) {
                            this.addPermissionRequestButtons(requestargs);
                            console.log("Added permission request buttons for "+requestargs);
                            msg += '<br/>' + _("(You may also grant or revoke permissions at any time on the Defender Permissions panel)");
                        }

                        // update description with status
                        this.setDescriptionOnMyTurn(msg, {});
                        break;
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Player must choose loss 
        ///////////////////////////////////////////////////

        /**
         * Creates buttons to click for a casualty to send to dead pool.
         * @param {string} type HOPLITE or TRIREME
         * @param {string} strength 
         * @param {array} cities list of choices
         * @param {string} location label
         */
        addCasualtyButtons: function(type, strength, cities, location) {
            const location_name = new perikles.locationtile(location).getNameTr();
            let msg = _("Choose counter from ${location} to send to dead pool");
            msg = msg.replace('${location}', location_name);
            this.setDescriptionOnMyTurn(msg, {'casualty': true, 'type': type, 'strength': strength, 'cities': cities});
            // add listeners to the buttons
            for (let c of cities ) {
                const id = [c, type, strength, 'casualty_select'].join("_");
                const button = $(id);
                button.dataset.casualty = "true";
                button.addEventListener('click', () => {
                    this.onSelectCasualty(c);
                });
            }
        },

        ///////////////////////////////////////////////////
        //// Player must choose which unit to retrieve from deadpool
        ///////////////////////////////////////////////////

        /**
         * Put buttons to select units to retrieve from deadpool.
         * @param {array} deadpoolunits 
         */
        addDeadpoolButtons: function(deadpoolunits) {
            const cities = new Set();
            Object.keys(deadpoolunits).forEach(u => {
                const unit = deadpoolunits[u];
                const city = unit['city'];
                cities.add(city);
            });
            let city_string = "";
            const numcities = cities.size;
            let i = 0;
            for (let city of cities) {
                const city_name = this.getCityNameTr(city);
                city_string += city_name;
                if (i < numcities-1) {
                    city_string += ", ";
                }
                i++;
            }
            let msg = _("You must retrieve a Hoplite or Trireme from the dead pool for ${cities}");
            msg = msg.replace('${cities}', city_string);
            this.setDescriptionOnMyTurn(msg, {});
            // add listeners to the buttons
            Object.keys(deadpoolunits).forEach(u => {
                const unit = deadpoolunits[u];
                const id = [unit['city'], unit['type'], unit['strength'], "deadpool_select"].join("_");
                const button = $(id);
                button.dataset.deadpool = "button";
                button.addEventListener('click', () => {
                    this.onSelectDeadpool(unit);
                });
            });
        },


        //////////////////////////////////////////////////
        //// Permission Requests
        //////////////////////////////////////////////////

        /**
         * Add buttons for the owning player to grant or deny a permission request.
         * @param {*} citylocation_pairs
         */
        addPermissionRequestButtons: function(citylocation_pairs) {
            this.addActionButton( 'grant_permission_btn', _("Allow"), () => {
                this.onPermissionRequest(citylocation_pairs, true);
            }, null, false, 'green' );
            this.addActionButton( 'deny_permission_btn', _("Deny"), () => {
                this.onPermissionRequest(citylocation_pairs, false);
            }, null, false, 'red' );
        },

        /**
         * Send permission response to server to allow or deny a request to defend.
         * Sets permission for all city/location pairs in one call.
         * @param {*} citylocation_pairs
         * @param {bool} allow
         */
        onPermissionRequest: function(citylocation_pairs, allow) {
            let cities = "";
            let locations = "";
            for (let pair of citylocation_pairs) {
                cities += pair.requesting_city + " ";
                locations += pair.location + " ";
            }
            cities = cities.trim();
            locations = locations.trim();

            this.ajaxcall( "/perikles/perikles/respondPermission.html", {
                requesting_cities: cities,
                locations: locations,
                allow: allow
            }, this, function(result) {});
        },

        /**
         * Button for requesting player to cancel request
         * @param {*} citylocation_pairs
         */
        addPermissionCancelButton: function(citylocation_pairs) {
            const label = (citylocation_pairs.length > 1) ? _("Cancel Requests") : _("Cancel Request");
            this.addActionButton( 'cancel_permission_btn', label, () => {
                this.cancelPermissionRequest();
            }, null, false, 'red' );
        },

        /**
         * Requesting player canceled request(s) to defend.
         */
        cancelPermissionRequest: function() {
            this.ajaxcall( "/perikles/perikles/cancelPermissionRequest.html", {
            }, this, function(result) {});

            this.uncommitUnits();
        },

        ///////////////////////////////////////////////////
        //// Handling Special Tiles
        ///////////////////////////////////////////////////

        /**
         * Get the translatable label for a Special Tile button
         * @param {string} player_id 
         * @returns "Use ${special} tile"
         */
         getSpecialButtonLabel: function(player_id) {
            let buttonlbl = _("Use ${special_name} tile");
            const speciallbl = this.getPlayerSpecial(player_id);
            const specialtr = this.getSpecialTileTr(speciallbl);
            buttonlbl = buttonlbl.replace('${special_name}', specialtr);
            return buttonlbl;
        },

        /**
         * Get the translated a player's Special Tile label.
         * @param {string} player_id 
         * @returns label or null
         */
        getPlayerSpecial: function(player_id) {
            const mycards = $(player_id+"_player_cards");
            const myspecial = mycards.getElementsByClassName("prk_special_tile")[0];
            const label = myspecial.id.split("_")[0];
            return label;
        },

        /**
         * Create an action button for this player that triggers their Special Tile.
         * Assumes we've checked they have a valid one.
         * @param {string} location only supplied for battle tiles
         */
         addSpecialTileButton: function(location=null) {
            const buttonlbl = this.getSpecialButtonLabel(this.player_id);
            this.addActionButton( 'play_special_btn', buttonlbl, () => {
                this.activateSpecialTile(location);
            }, null, false, 'blue' );
        },

        /**
         * Check cards before submitting to the specialTile function.
         * @param {string} location only supplied for battle tiles
         */
         activateSpecialTile: function(location=null) {
            const special = this.getPlayerSpecial(this.player_id);

            switch (special) {
                case PLAGUE:
                    this.addPlagueButtons();
                    break;
                case ALKIBIADES:
                    this.addAlkibiadesButtons();
                    break;
                case SLAVEREVOLT:
                    this.addSlaveRevoltButtons();
                    break;
                case PERSIANFLEET:
                    this.addSpecialBattleButtons(location, PERSIANFLEET);
                    break;
                case THESSALANIANALLIES:
                    this.addSpecialBattleButtons(location, THESSALANIANALLIES);
                    break;
                case PHORMIO:
                case BRASIDAS:
                    this.specialBattleTile(true);
                    break;
                case PERIKLES:
                    this.onPerikles();
            };
        },

        /**
         * When Special tile is canceled, re-add it.
         * Also add the Pass button if it's the Special Tile phase.
         * Readd Commit Forces in commit forces phase
         * @param {string} special
         * @param {string} location for battle tile buttons
         */
         addSpecialTileCancel: function(special, location=null) {
            this.addActionButton( special+"_cancel_btn", _("Cancel"), () => {
                const state = this.gamedatas.gamestate.name;
                this.restoreDescriptionOnMyTurn();
                this.removeActionButtons();
                if (state == "commitForces") {
                    this.addCommitForcesButton();
                }

                this.addActionButton( 'play_special_btn', this.getSpecialButtonLabel(this.player_id), () => {
                    this.activateSpecialTile(location);
                }, null, false, 'blue' );
                if (state == "specialTile") {
                    this.addSpecialPassButton();
                } else if (state == "specialBattleTile") {
                    this.addSpecialPassButton(true);
                }
            }, null, null, 'red');
        },

        /**
         * Add the "Commit Forces" action button.
         */
        addCommitForcesButton: function() {
            this.addActionButton( "commit_send_btn", _('Commit Forces'), () => {
                this.onCommitForces();
            });
        },

        /**
         * Add the 'Pass' button to pass on playing a Special tile.
         * @param {bool} isBattle
         */
        addSpecialPassButton: function(isBattle=false) {
            this.addActionButton( 'pass_btn', _("Pass"), () => {
                if (isBattle) {
                    this.specialBattleTile(false);
                } else {
                    this.specialTilePass();
                }
            }, null, false, 'red' );
        },

        /////////////////////// PLAGUE ///////////////////////

        /**
         * Player clicked "Use Plague" button.
         */
        addPlagueButtons: function() {
            this.setDescriptionOnMyTurn(_("Select a city to be struck with plague"), {'plague': true});
            this.removeActionButtons();
            const plaguebuttons = document.getElementsByClassName("prk_plague_btn");
            [...plaguebuttons].forEach( p => p.addEventListener('click', () => {
                const city = p.id.split("_")[0];
                this.onPlagueCity(city);
            }));

            this.addSpecialTileCancel(PLAGUE);
        },

        /////////////////////// ALKIBIADES ///////////////////////

        /**
         * Player clicked "Use Alkibiades" button.
         */
         addAlkibiadesButtons: function() {
            this.setDescriptionOnMyTurn(_("Select 2 cubes to move"), {'alkibiades': true});
            this.removeActionButtons();

            // add listeners to the cubes in the From cities
            const alkibiadescubes = $('alkibiades_from_cities').getElementsByClassName('prk_cube');
            [...alkibiadescubes].forEach( c => this.addAlkibiadesCubesEventListeners(c));

            // add listeners to the To-buttons
            const to_civs = $('alkibiades_to_cities').getElementsByClassName('prk_alkibiades_btn');
            [...to_civs].forEach(civ => this.addAlkibiadesToCivListeners(civ));

            this.addActionButton( 'alkibiades_move_btn', _("Confirm"), () => {
                this.onAlkibiadesMove();
            }, null, false, 'blue' );
            $('alkibiades_move_btn').classList.add('disabled');
            this.addSpecialTileCancel(ALKIBIADES);
        },

        /**
         * Add listeners to cubes in the Alkibiades banner.
         * Makes them light up and spin when hovered
         * @param {Object} cube 
         */
         addAlkibiadesCubesEventListeners: function(cube) {
            cube.dataset.action = "alkibiades";
            // spin and highlight
            cube.addEventListener('mouseenter', () => {
                const cubes = this.getAlkibiadesCubesToMove();
                if (cubes.length < 2) {
                    this.decorator.highlight(cube);
                }
            });
            // unhighlight
            cube.addEventListener('mouseleave', () => {
                this.decorator.unhighlight(cube);
            });
            cube.addEventListener('click', () => {
                const cubes = this.getAlkibiadesCubesToMove();
                if (cubes.length < 2) {
                    // unmark any previous cube
                    this.deselectAlkibiadesCube();
                    // mark the cube
                    cube.dataset.selected = "true";
                    // highlight the To box with the selected player's color
                    const [selected_pid, fromcity] = cube.id.split("_").splice(0, 2);
                    this.decorateAlkibiadesToDiv(selected_pid, fromcity);
                }
            });
        },

        /**
         * Add listeners to the To buttons for Alkibiades
         * @param {Object} civ 
         */
        addAlkibiadesToCivListeners: function(civ) {
            // colored when hovered
            civ.addEventListener('mouseenter', () => this.enterCivBtnAlkibiades(civ));
            // uncolor when left
            civ.addEventListener('mouseleave', () => this.leaveCivBtnAlkibiades(civ));
            // clicking a Civ to place cube there
            civ.addEventListener('click', () => this.clickCivBtnAlkibiades(civ));
        },

        /**
         * Highlight the Alkibiades "To" div with player cube color.
         * @param {string} player_id
         * @param {string} fromcity
         */
        decorateAlkibiadesToDiv: function(player_id, fromcity) {
            let pcolor = this.decorator.playerColor(player_id);
            if (pcolor == 'white') {
                pcolor = 'gray';
            }
            const to_city_container = $('alkibiades_to_cities');
            to_city_container.style['box-shadow'] = '2px 2px 15px 5px '+pcolor;
            const toButtons = to_city_container.getElementsByClassName('prk_alkibiades_btn ');
            // remove any previously disabled toCiv marks
            [...toButtons].forEach(tb => delete tb.dataset.noselect);
            $(fromcity+"_alkibiades_to_btn").dataset.noselect = "true";
        },

        /**
         * When entering one of the Alkibiades To buttons, color it if it's not the from city.
         * @param {element} tociv 
         */
        enterCivBtnAlkibiades: function(tociv) {
            // get the selected cube
            const selected = this.getAlkibiadesCubeSelected();
            if (selected) {
                const fromcity = selected.id.split("_")[1];
                const tocity = tociv.id.split("_")[0];
                if (fromcity != tocity) {
                    Object.assign(tociv.style, {
                        'background-color': 'var(--color_'+tocity+')',
                        'cursor': 'grab'
                    });
                }
            }
        },

        /**
         * Uncolor Alkibiades To button.
         * @param {element} tociv 
         */
         leaveCivBtnAlkibiades: function(tociv) {
             if (tociv.dataset.noselect != "true") {
                Object.assign(tociv.style, {
                    'background-color': 'white',
                    'cursor': 'default'
                });
             }
        },

        /**
         * Dropping a cube on a city to move it there.
         * @param {element} tociv
         */
         clickCivBtnAlkibiades: function(tociv) {
             if (tociv.dataset.noselect == "true") {
                 return;
             }
            const selected = this.getAlkibiadesCubeSelected();
            if (selected) {
                const [player_id, fromcity] = selected.id.split("_").splice(0,2);
                const tocity = tociv.id.split("_")[0];
                const previouscubes = this.getAlkibiadesCubesToMove();
                // how many have already been put down? Should be 0 or 1
                const movedcubes = previouscubes.length;
                if (movedcubes < 2) {
                    if (fromcity != tocity) {
                        const cubehtml = this.createInfluenceCube(player_id, fromcity, 'move'+(movedcubes+1));
                        dojo.place(cubehtml, tociv);
                        this.deselectAlkibiadesCube();
                    }
                    let movestr = _("Move one of ${player_name}'s cubes from ${from_city} to ${to_city}");
                    const from_city_name = this.spanCityName(fromcity);
                    const to_city_name = this.spanCityName(tocity);

                    movestr = movestr.replace('${player_name}', this.decorator.spanPlayerName(player_id, this.isColorblind()));
                    movestr = movestr.replace('${from_city}', from_city_name);
                    movestr = movestr.replace('${to_city}', to_city_name);

                    $('alkibiades_selections').innerHTML += (movedcubes == 0 ? '' : '<br/>') + movestr;
                    if (movedcubes == 0) {
                        // if this was the first cube, and it was the only cube that player had in the city, take it off the Alkibiades banner
                        const remainingcubes = $(fromcity+'_cubes_'+player_id).getElementsByClassName("prk_cube");
                        if (remainingcubes.length < 2) {
                            $(player_id+'_'+fromcity+'_alkibiades').remove();
                        }
                    } else {
                        // if this was the second, then activate submit button
                        $('alkibiades_move_btn').classList.remove('disabled');
                    }
                } else {
                    throw new Error("Too many cubes selected!");
                }
            }
        },

        /**
         * Get all the cubes currently on the To-Move box
         * @returns alkibades cube array
         */
        getAlkibiadesCubesToMove: function() {
            alkcubes = [];
            for (const city of CITIES) {
                const citybtn = $(city+'_alkibiades_to_btn');
                const cubes = citybtn.getElementsByClassName('prk_cube');
                [...cubes].forEach(c => {
                    const alk = new perikles.alkibiades();
                    alk.setToCity(city);
                    alk.setValues(c);
                    alkcubes.push(alk);
                });
            }
            return alkcubes;
        },

        /**
         * Get the Alkibiades cube that was clicked to be moved.
         * @return a selected cube or null
         */
        getAlkibiadesCubeSelected: function() {
            const selected = $('alkibiades_from_cities').querySelectorAll('[data-selected="true"]');
            if (selected.length > 1) {
                throw new Error("Multiple Alkibiades cubes have been marked as selected");
            }
            if (selected.length == 0) {
                return null;
            } else {
                return selected[0];
            }
        },

        /**
         * Cube selected for Alkibiades movement is unselected.
         */
        deselectAlkibiadesCube: function() {
            const to_city_container = $('alkibiades_to_cities');
            to_city_container.style['box-shadow'] = '';
            const toButtons = to_city_container.getElementsByClassName('prk_alkibiades_btn');
            // remove any previously disabled toCiv marks
            [...toButtons].forEach(tb => delete tb.dataset.noselect);
            const fromCubes = $('alkibiades_from_cities').querySelectorAll('[data-action="alkibiades"]');;
            [...fromCubes].forEach(c => delete c.dataset.selected);
        },

        /////////////////////// SLAVE REVOLT ///////////////////////

        /**
         * Player clicked "Use Slave Revolt" button.
         * This adds the buttons that select a slave revolt location.
         */
        addSlaveRevoltButtons: function() {
            this.setDescriptionOnMyTurn(_("Choose location for Slave Revolt (one Spartan Hoplite counter will be placed back in Sparta)"), {'slaverevolt': true});
            this.removeActionButtons();
            const srbtns = $('slaverevolt_div').getElementsByClassName("prk_slaverevolt_btn");
            [...srbtns].forEach(b => this.addSlaveRevoltListeners(b));

            this.addSpecialTileCancel(SLAVEREVOLT);
        },

        /**
         * 
         * @param {Object} button 
         */
        addSlaveRevoltListeners: function(button) {
            button.addEventListener('click', () => {
                // clear any commits we may have been in the middle of
                this.onCancelCommit();
                const id = button.id.split("_")[0];
                this.onSlaveRevolt(id);
            });
        },

        /////////////////////// PERSIAN FLEET + THESSALANIAN ALLIES ///////////////////////

        /**
         * 
         * @param {string} location 
         * @param {strin} special PERSIANFLEET or THESSALANIANALLIES
         */
        addSpecialBattleButtons: function(location, special) {
            const type = (special == PERSIANFLEET) ? TRIREME : HOPLITE;
            const msg = this.getChooseSidesMsg(location, type);
            this.setDescriptionOnMyTurn(msg, {special: true});
            this.removeActionButtons();
            // check whether a side already has a Victory Token: may not add to that side

            if ($('attacker_battle_tokens').childElementCount == 0) {
                this.addActionButton( "attacker_btn", _('Attackers'), () => {
                    this.specialBattleTile(true, "attacker");
                }, null, null, 'blue');
            }
            if ($('defender_battle_tokens').childElementCount == 0) {
                this.addActionButton( "defender_btn", _('Defenders'), () => {
                    this.specialBattleTile(true, "defender");
                }, null, null, 'blue');
            }

            this.addSpecialTileCancel(special, location);
        },

        /**
         * Message for Persian Fleet or Thessalanian Allies
         * @param {string} location 
         * @param {string} type HOPLITE or TRIREME
         * @returns 
         */
         getChooseSidesMsg: function(location, type) {
            let msg = _("Choose side to begin ${type} battle at ${location} with 1 Battle Token");
            const unit = this.getUnitTr(type);
            const locname = new perikles.locationtile(location).getNameTr();
            msg = msg.replace('${type}', unit);
            msg = msg.replace('${location}', locname);
            return msg;
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /**
         * For a city, get the current player who's leader.
         * @param {string} city 
         * @return player_id (null if no one controls that city)
         */
        getLeader: function(city) {
            for (const player_id in this.gamedatas.players) {
                if (this.isLeader(player_id, city)) {
                    return player_id;
                }
            }
            return null;
        },

        /**
         * Is this player leader of this city?
         * @param {string} player_id 
         * @param {string} city 
         */
        isLeader: function(player_id, city) {
            let isLeader = false;
            const leader = $(city+'_leader').firstChild;
            if (leader) {
                const color = this.decorator.playerColor(player_id);
                if (leader.dataset.color == color) {
                    isLeader = true;
                }
            }
            return isLeader;
        },

        /**
         * Is this player a Persian leader?
         * @param {string} player_id 
         * @return true if player_id controls Persians
         */
        isPersianLeader: function(player_id) {
            for (const city of CITIES) {
                if (this.isLeader(player_id, city)) {
                    return false;
                }
            }
            return true;
        },

        /**
         * Check whether the Persians are controlled by anyone.
         * @return {bool} true if at least one player controls the Persians
         */
        existsPersianLeader: function() {
            for (const player_id in this.gamedatas.players) {
                if (this.isPersianLeader(player_id)) {
                    return true;
                }
            }
            return false;
        },

        /**
         * Does the player have any uncommitted military units in that city? (or Persian)
         * @param {string} player_id 
         * @param {string} city 
         */
        hasAvailableUnits: function(player_id, city) {
            let avail = false;
            const city_zone = $(city+'_mil_ctnr_'+player_id);
            if (city_zone) {
                const units = city_zone.querySelectorAll('div.prk_military:not([data-selected="true"])');
                avail = units.length > 0;
            }
            return avail;
        },

        /**
         * Does current player have a cube in the city?
         * @param {string} city 
         * @param {bool} bCheckCandidates including candidates? default: false
         * @returns true if this player has a cube in city
         */
        hasCubeInCity: function(city, bCheckCandidates=false) {
            const player_id = this.player_id;
            if (bCheckCandidates) {
                if (document.getElementById(player_id+"_"+city+"_a")) {
                    return true;
                }
                if (document.getElementById(player_id+"_"+city+"_b")) {
                    return true;
                }
            }
            if ($(city+"_cubes_"+player_id).getElementsByClassName("prk_cube").length > 0) {
                return true;
            }
            // edge case: I might have just added a cube to this city but the element hasn't been placed yet
            if (this.isLastCubeAdded(player_id, city)) {
                return true;
            }
            return false;
        },

        /**
         * For checking the edge case where my cubes are the only ones in the city.
         * @param {string} city 
         * @return {bool} true if any other player has cubes here
         */
        existsOtherCubesInCity: function(city) {
            for (const player_id in this.gamedatas.players) {
                if (player_id != this.player_id) {
                    if ($(city+'_cubes_'+player_id).childElementCount > 0) {
                        return true;
                    }
                }
            }
            return false;
        },

        /**
         * For a city, returns the div for candidate a if it's empty, else b if it's empty, else null
         * @param {string} city 
         * @returns a DOM Element or else null
         */
         openCandidateSpace: function(city) {
            let candidate_space = null;
            const citya = $(city+"_a");
            if (!citya.hasChildNodes()) {
                candidate_space = citya;
            } else {
                const cityb = $(city+"_b");
                if (!cityb.hasChildNodes()) {
                    candidate_space = cityb; 
                }
            }
            return candidate_space;
        },

        /**
         * Remove the bottommost of a player's Influence cubes in a city
         * @param {string} player_id 
         * @param {string} city
         * @param {int} num
         */
        removeInfluenceCubes: function(player_id, city, num) {
            const from_div = $(city+'_cubes_'+player_id);
            const cubes = from_div.children;
            [...cubes].slice(0, num).forEach(c => {
                this.fadeOutAndDestroy(c, 500);
            });
        },

        /**
         * For military counters, makes them selectable/unselectable
         * @param {DOM} counter 
         * @param {bool} selectable (default true)
         */
        makeSelectable: function(counter, selectable=true) {
            counter.dataset.selectable = selectable;
            if (selectable) {
                this.connect(counter, 'click', this.assignUnit.bind(this));
            } else {
                this.disconnect(counter, 'click');
            }
        },

        /**
         * Add listeners to a location tile to allow splaying/diplaying counter stacks on it.
         * @param {DOM} battleslot 
         * @param {bool} splay 
         */
        makeSplayable: function(battleslot, splay=true) {
            if (splay) {
                this.connect(battleslot, 'click', this.stacks.splayUnits);
                this.connect(battleslot, 'mouseenter', this.stacks.splayUnits);
                this.connect(battleslot, 'mouseleave', this.stacks.unsplayUnits);
            } else {
                this.disconnect(battleslot, 'click', this.stacks.splayUnits);
                this.disconnect(battleslot, 'mouseenter', this.stacks.splayUnits);
                this.disconnect(battleslot, 'mouseleave', this.stacks.unsplayUnits);
            }
        },

        /**
         * Button to assign a unit to a battle.
         * @param {Object} evt event
         */
         assignUnit: function(evt) {
            const selectedUnit = evt.currentTarget;

            this.commitDlg = new ebg.popindialog();
            this.commitDlg.create( 'commitDlg' );
            const [city,unit,strength,id] = selectedUnit.id.split('_');
            const counter = new perikles.counter(city,unit,strength,id);

            const unit_str = this.counterSpan(counter);
            const unitc = counter.copy();

            this.commitDlg.setTitle( _("Commit Forces") );
            this.commitDlg.setMaxWidth( 720 );
            const html = '<div id="CommitDialogDiv">\
                            <div id="commit_dlg_cols">'
                                +unitc + this.createCommitDialogLocationTiles(city, unit)+
                            '</div>\
                            <div id="commit_text"></div>\
                            <div id-"commit_dlg_btn_ctnr">\
                                    <div id="send_button" class="prk_btn prk_send_btn">'+_("Send")+'</div>\
                                    <div id="cancel_button" class="prk_btn prk_cancel_btn">'+_("Cancel")+'</div>\
                            </div>\
                        </div>';
            // Show the dialog
            this.commitDlg.setContent(html);
            this.commitDlg.show();
            this.addCommitLocationTooltips();

            this.commitDlg.hideCloseIcon();
            const dlg = $('CommitDialogDiv');
            dlg.onclick = event => {
                const target = event.target;

                const attack_str = _("Send ${unit} to attack ${location}?");
                const defend_str = _("Send ${unit} to defend ${location}?");
                const permission_str = _("Request permission from ${controlling_city} for ${unit} to defend ${location}?");
                let banner_txt = null;

                if (target.id == "send_button" ) {
                    let location = dlg.getAttribute("data-location");
                    let sendto = dlg.getAttribute("data-side");
                    if (location && sendto) {
                        const errmsg = this.checkEligibleToSend(city, unit, sendto, location);
                        if (errmsg == null) {
                            new Promise((resolve,reject) => {
                                this.onSendUnit(id, city, unit, strength, sendto, location);
                                resolve();
                            });
                            this.commitDlg.destroy();
                        } else {
                            banner_txt = '<span style="color: white; font-size: larger; font-weight: bold;">'+errmsg+'</span>';
                        }
                    } else {
                        banner_txt = '<span style="color: white; font-size: larger; font-weight: bold;">'+_("You must select a location")+'</span>';
                    }
                } else if (target.id == "cancel_button") {
                    this.commitDlg.destroy();
                } else if (target.classList.contains("prk_battle_icon")) {
                    const [side, loc] = target.id.split('_');
                    const tile = new perikles.locationtile(loc);
                    dlg.setAttribute("data-location", loc);
                    dlg.setAttribute("data-side", side);
                    banner_txt = (side == "attack") ? attack_str : defend_str;
                    // need to request permission
                    if (side == "defend" && target.dataset.permission == "false") {
                        banner_txt = permission_str;
                        const owner_city = tile.getCity();
                        banner_txt = banner_txt.replace('${controlling_city}', this.spanCityName(owner_city));
                    }
                    banner_txt = banner_txt.replace('${location}', '<span style="color: var(--color_'+tile.getCity()+');">'+tile.getNameTr()+'</span>');
                    banner_txt = banner_txt.replace('${unit}', unit_str);
                    this.gamedatas.gamestate.args.permission_request = true;
                }
                if (banner_txt) {
                    $(commit_text).style.display = "block";
                    $(commit_text).innerHTML = banner_txt;
                }
            };
            this.toggleAssignmentCancelButton(true);
        },

        /**
         * Put tooltips on the location tiles in commit dialog.
         */
         addCommitLocationTooltips: function() {
            const location_tiles = $('location_area').getElementsByClassName("prk_location_tile");
            for (let b = 0; b < location_tiles.length; b++) {
                this.addCommitLocationTooltip(location_tiles[b], b+1);
            }
        },

        /**
         * Create HTML for Location tile commit dialog.
         * @param {string} location_tile_id
         * @param {int} bzone
         */
        addCommitLocationTooltip: function(location_tile, bzone) {
            const id = location_tile.id;
            const location_name = id.split("_")[0];

            // get attackers
            const stationed_units = {
                "hoplite_att": [],
                "hoplite_def": [],
                "trireme_att": [],
                "trireme_def": [],
            };
            [HOPLITE,TRIREME].forEach(type => {
                ["att","def"].forEach(side => {
                    const main_box = ["battle", bzone, type, side].join("_");
                    const ally_box = main_box+"_ally";
                    [main_box, ally_box].forEach(bx_id => {
                        const counters = $(bx_id).getElementsByClassName("prk_military");
                        stationed_units[type+"_"+side].push(...counters);
                    });
                });
            });

            let unitstr = "";
            for(const[id, units] of Object.entries(stationed_units)) {
                unitstr += id + ":"+units.length;
            }
            const loctile = new perikles.locationtile(location_name);
            const city = loctile.getCity();
            const owning_player = this.getLeader(city);
            const owning_color = this.getPlayerColor(owning_player);
            let html = '<div id="'+id+'"_commit_dlg_forces" class="prk_dlg_forces_tt" style="background-color:var(--color_'+city+');">\
                        <h2 style="background-color:'+owning_color+';">'+loctile.getNameTr()+'</h2>\
                        <div class="prk_dlg_forces">';
            html += this.createCommittedForcesDisplayRow("att", stationed_units);
            html += this.createCommittedForcesDisplayRow("def", stationed_units);
            html += '</div>';
            this.addTooltipHtml(location_name+'_tile_commit_dlg', html, '');
        },

        /**
         * Create the container row for all attacking or defending units at a location
         * @param {string} side "att" or "def"
         * @param {Object} units 
         * @returns html
         */
        createCommittedForcesDisplayRow: function(side, units) {
            const lbl = (side == "att") ? _("Attackers") : _("Defenders");
            let html = '<div class="prk_dlg_forces_container">';
                html += '<h3>'+lbl+'</h3>';
                html += '<div class="prk_dlg_forces_row" data-side="'+side+'">';
                units["hoplite_"+side].forEach(h => {
                    html += this.makeCounterForDialog(h.id);
                });
                units["trireme_"+side].forEach(t => {
                    html += this.makeCounterForDialog(t.id);
                });
            html += '</div></div>';
            return html;
        },

        /**
         * Create individual unit icon.
         * @param {string} id 
         * @return {string} html for icon
         */
        makeCounterForDialog: function(counter_id) {
            const [city,type,strength,id] = counter_id.split("_");
            const counter = new perikles.counter(city, type, strength, id);
            const icon = counter.toLogIcon();
            return icon;
        },

        /**
         * Check whether this unit can be sent to the given location. Does some pre-checks before we check on the server side.
         * @param {string} city 
         * @param {string} unit HOPLITE or TRIREME
         * @param {string} side attacker or defender
         * @param {string} location to send to
         * @returns null if it's okay to send, otherwise an error message to display
         */
        checkEligibleToSend: function(city, unit, side, location) {
            const tile = new perikles.locationtile(location);
            // are we sending a trireme to a land battle?
            if (unit == TRIREME && tile.getRounds() == "H") {
                return _("Trireme cannot be sent to a Hoplites-only battle!");
            }
            // are any units of my cities on the other side?
            const other = (side == "attack") ? "defender" : "attacker";
            const opposing = tile.getUnits(other);
            for (let c of opposing) {
                const opposing_city = c.id.split("_")[0];
                if (opposing_city == "persia") {
                    if (this.isPersianLeader(this.player_id)) {
                        return _("Units from the same city cannot fight on opposite sides!");
                    }
                } else if (this.isLeader(this.player_id, opposing_city)) {
                    return _("Cities under your control cannot fight on opposite sides of the same battle!");
                } else {
                    // check for allied cities on the other side
                    const opposingrel = this.gamedatas.wars[city][opposing_city];
                    if (opposingrel == ALLIED) {
                        return _("Units cannot join a battle on the opposite side as a city it is allied with!");
                    }
                }
            }
            // are any enemy units on the same side?
            const allies = tile.getUnits(side);
            for (let a of allies) {
                const allied_city = a.id.split("_")[0];
                const rel = this.gamedatas.wars[city][allied_city];
                if (rel == WAR) {
                    return _("Units cannot join a battle on the same side as a city it is at war with!");
                }
            }

            return null;
        },

        /**
         * Check whether a city has permission to defend a location.
         * @param {string} city of unit
         * @param {string} location tile
         * @param {bool} true if we have permission, false if not
         */
        checkPermissionToDefend: function(city, location) {
            const tile = new perikles.locationtile(location);
            if (this.isLeader(this.player_id, tile.getCity())) {
                return true;
            }
            if (this.gamedatas.permissions[location] && this.gamedatas.permissions[location].includes(city)) {
                return true;
            }
            return false;
        },

        /**
         * Check whether player can attack a city, only based on whether they are the leader.
         * Doesn't do other checks, like allied status.
         * @param {string} city 
         * @returns true if it's okay to attack
         */
        canAttack: function(city) {
            let can_attack = true;
            if (this.isLeader(this.player_id, city)) {
                can_attack = false;
            }
            return can_attack;
        },

        /**
         * Toggle the "Cancel" button for unit assignments.
         * @param {bool} enable turn it on (visible) or off (hidden)
         */
        toggleAssignmentCancelButton: function(enable) {
            const commit_cancel = $('commit_cancel_btn');
            if (!commit_cancel) {
                this.addActionButton( "commit_cancel_btn", _('Cancel'), () => {
                    this.onCancelCommit();
                }, null, null, 'red');
            }

            if (enable) {
                $('commit_cancel_btn').classList.remove('disabled');
                $('commit_cancel_btn').style['display'] = 'inline';
            } else {
                $('commit_cancel_btn').classList.add('disabled');
                $('commit_cancel_btn').style['display'] = 'none';
            }
        },

        /**
         * Memoize last cube added for special case where someone just added a cube to a city they want to propose a candidate in.
         * @param {string} player_id 
         * @param {string} city 
         */
        lastCubeAdded(player_id, city) {
            this.last_cube = {"player_id": player_id, "city": city};
        },

        /**
         * Check when the moving of last cube to a city might not have caught up yet.
         * @param {string*} player_id 
         * @param {string} city 
         * @returns true if this player just added a cube to city
         */
        isLastCubeAdded(player_id, city) {
            let is_last = false;
            if (this.last_cube) {
                is_last = (this.last_cube['player_id'] == player_id) && (this.last_cube['city'] == city);
            }
            return is_last;
        },

        /**
         * Pack all units being sent into a space-delimited string "id_attdef_location"
         * @param {Object} committed 
         */
         packCommitForcesArg: function(committed) {
            let argstr = "";
            for (const[id, selected] of Object.entries(committed)) {
                if (id != "cube") {
                    let location = selected.location;
                    let side = selected.side;
                    argstr += id+"_"+side+"_"+location+" ";
                }
            }
            return argstr;
        },

        /**
         * When browser is not refreshed, need to set all ways to base values
         */
         resetWars: function() {
            this.gamedatas.wars = {};
            this.gamedatas.wars["persia"] ={};
            for (let city of CITIES) {
                this.gamedatas.wars[city] = {};
            }
            for (let city of CITIES) {
                for (let city2 of CITIES) {
                    const rel = (city == city2) ? 1 : 0;
                    this.gamedatas.wars[city][city2] = rel;
                    this.gamedatas.wars[city2][city] = rel;
                }
                this.gamedatas.wars["persia"][city] = 0;
                this.gamedatas.wars[city]["persia"] = 0;
            }
            this.gamedatas.wars["persia"]["persia"] = 1;
        },

        /**
         * Attach event listeners to stacks and permissions buttons if we're in the military stage.
         */
        militaryPhaseDisplay: function() {
            $('military_board').style['display'] = this.isSpectator ? "none" : 'block';
            // hide previously displayed boards that we are no longer leader of
            for (let city of CITIES) {
                const board = $(city+"_military_"+this.player_id);
                if (board) {
                    board.style['display'] = this.isLeader(this.player_id, city) ? "block" : "none";
                }
            }
            const persia_board = $("persia_military_"+this.player_id);
            if (persia_board && !this.isPersianLeader(this.player_id)) {
                persia_board.style['display'] = "none";
            }

            const battleslots = $('location_area').getElementsByClassName("prk_battle");
            [...battleslots].forEach(b => {
                this.makeSplayable(b);
            });
            this.createPermissionsDisplay();
        },

        /**
         * Clear any highlighted columns on CRT table.
         */
        clearCRT: function() {
            const crtcols = document.getElementsByClassName("prk_crt");
            [...crtcols].forEach(c => {
                this.decorator.unhighlight(c);
            });
        },

        ///////////////////////////////////////////////////
        //// Component creation - create HTML elements

        /**
         * Create buttons to spend a city influence cube to send more units.
         * @returns html for cubes for cities I own; null if none possible
         */
        createSpendInfluenceDiv: function() {
            let html = null;
            let canSpend = false;
            let civ_btns = "";
            const is_persian = this.isPersianLeader(this.player_id);

            for (const city of CITIES) {
                if (is_persian || this.isLeader(this.player_id, city)) {
                    //any cubes left?
                    if (this.hasCubeInCity(city)) {
                        // persians can spend cubes from any city
                        const unit_city = is_persian ? "persia" : city;
                        if (this.hasAvailableUnits(this.player_id, unit_city)) {
                            civ_btns += this.format_block('jstpl_city_btn', {city: city, city_name: this.getCityNameTr(city)});
                            canSpend = true;
                        }
                    }
                }
            }
            if (canSpend) {
                html = '<div id="'+COMMIT_INFLUENCE_CUBES+'" style="display: inline-flex; flex-direction: row;">';
                html += civ_btns;
                html += '</div>';
            }
            return html;
        },

        /**
         * Create the div containing all the location tiles that go in a Commit Forces dialog.
         * @param unit_city city the unit is from
         * @param unit_type TRIREME or HOPLITE
         * @returns html
         */
        createCommitDialogLocationTiles: function(unit_city, unit_type) {
            let loc_html = '<div id="commit_dlg_locations_col">';
            const location_tiles = $('location_area').getElementsByClassName("prk_location_tile");
            [...location_tiles].forEach(loc => {
                const battle = loc.id.split('_')[0];
                const tile = new perikles.locationtile(battle);
                // can't attack own city
                const battle_city = tile.getCity();
                loc_html += '<div id="'+battle+'_commit_row" class="prk_dlg_loc_row">';
                if (unit_city != battle_city && this.canAttack(battle_city) && this.checkEligibleToSend(unit_city, unit_type, "attack", battle) == null) {
                    loc_html += '<div id="attack_'+battle+'" class="prk_battle_icon" data-icon="sword"></div>';
                } else {
                    loc_html += '<div class="prk_blank_icon"></div>';
                }
                const loc_tile = tile.createTile(1, 'commit_dlg');
                loc_html += loc_tile;
                if (this.checkEligibleToSend(unit_city, unit_type, "defend", battle) == null) {
                    const permitted = this.checkPermissionToDefend(unit_city, battle);
                    loc_html += '<div id="defend_'+battle+'" class="prk_battle_icon" data-icon="shield" data-permission="'+permitted+'"></div>';
                } else {
                    loc_html += '<div class="prk_blank_icon"></div>';
                }
                loc_html += '</div>';
            });
            loc_html += '</div>';
            return loc_html;
        },

        /**
         * Modify a created element to make it a shared Persian victory tile.
         * @param {Object} element 
         * @param {int} i 
         * @returns element modified as Persian victory tile
         */       
        makePersianVictoryTile: function(element, i) {
            element.id = element.id+"_"+i;
            const banner = document.createElement("span");
            banner.innerHTML = _("Persians");
            banner.classList.add("prk_persian_victory");
            dojo.place(banner, element);
            return element;
        },

        /**
         * Create buttons to afflict city with plague
         * @returns html for plague buttons
         */
         createPlagueButtons: function() {
            let html = '<div id="plague_city_div">';
            for (const city of CITIES) {
                html += this.format_block('jstpl_plague_btn', {city: city, city_name: this.getCityNameTr(city)});
            }
            html += '</div>';
            return html;
        },

        /**
         * Create buttons to move cubes with Alkibiades.
         */
        createAlkibiadesButtons: function() {
            let fromcivs = '';
            let tocivs = '';
            for (const city of CITIES) {
                fromcivs += '<div class="prk_alkibiades_row">';
                tocivs += '<div class="prk_alkibiades_row">';
                fromcivs += this.format_block('jstpl_alkibiades_from_btn', {city: city, city_name: this.getCityNameTr(city)});
                tocivs += this.format_block('jstpl_alkibiades_to_btn', {city: city, city_name: this.getCityNameTr(city)});
                for (player_id in this.gamedatas.players) {
                    const cubes_div = $(city+"_cubes_"+player_id);
                    const cubes = cubes_div.childElementCount;
                    if (cubes > 0) {
                        const cube = this.createInfluenceCube(player_id, city, 'alkibiades');
                        fromcivs += cube;
                    }
                }
                fromcivs += '</div>';
                tocivs += '</div>';
            }

            let html = '<br/><div id="alkibiades_from_cities" class="prk_alkibiades_civs">';
            html += '<h2>'+_('From')+'</h2>';
            html += fromcivs;
            html += '</div>';
            html += '<div id="alkibiades_to_cities" class="prk_alkibiades_civs">';
            html += '<h2>'+_('To')+'</h2>';
            html += tocivs;
            html += '</div><br/>';
            html += '<div id="alkibiades_selections" class="prk_alkibiades_banner"></div>';
            return html;
        },

        /**
         * Create buttons for choosing Slave Revolt location.
         * Controlling player+Battle location tiles
         * @returns html
         */
        createSlaveRevoltButtons: function() {
            let html = '<div id="slaverevolt_div">';
            const sparta_leader = this.getLeader("sparta");
            if (sparta_leader) {
                const spartabtn = this.slaverevolt.createSpartaLeaderButton(this.decorator.spanPlayerName(sparta_leader, this.isColorblind()));
                html += spartabtn;
                const locations = this.slaverevolt.getSpartanHopliteLocations();
                for (const stack of locations) {
                    const tile = new perikles.locationtile(stack.tile);
                    const locbtn = this.slaverevolt.createButton(tile.getLocation(), tile.getNameTr());
                    html += locbtn;
                }
            } else {
                html += '<span>'+_("Sparta has no leader; no revolt possible")+'</span>';
            }
            html += '</div>';
            return html;
        },

        /**
         * Get the "city unit-strength" label for a counter.
         * @param {Object} counter 
         * @returns span html
         */
         counterSpan: function(counter) {
            let unit_str = _("${city_name} ${unit}-${strength}");
            unit_str = unit_str.replace('${city_name}', '<span style="color: var(--color_'+counter.getCity()+');")>'+this.getCityNameTr(counter.getCity())+'</span>');
            unit_str = unit_str.replace('${unit}', '<b>${unit}</b>');
            unit_str = unit_str.replace('${unit}', this.getUnitTr(counter.getType()));
            unit_str = unit_str.replace('${strength}', counter.getStrength());
            return unit_str;
        },

        /**
         * Get the "city unit-strength" label for a counter.
         * @param {Object} counter 
         * @returns plain text for title
         */
        counterText: function(counter) {
            let unit_str = _("${city_name} ${unit}-${strength}");
            unit_str = unit_str.replace('${city_name}', this.getCityNameTr(counter.getCity()));
            unit_str = unit_str.replace('${unit}', this.getUnitTr(counter.getType()));
            unit_str = unit_str.replace('${strength}', counter.getStrength());
            return unit_str;
        },

        /**
         * Get translateable string for Hoplite or Trireme.
         * @param {string} type 
         * @returns {string} translateable
         */
        getUnitTr: function(type) {
            let unit = "";
            if (type == HOPLITE) {
                unit = _("Hoplite");
            } else if (type == TRIREME) {
                unit = _("Trireme");
            }
            return unit;
        },

        /**
         * Invoked when unclaimed tiles should be placed - checks if
         * there is already an inclaimed tiles box, and if not, creates it.
         */
         createUnclaimedTilesBox: function() {
            if (!document.getElementById('unclaimed')) {
                const unclaimed_div = '<div id="unclaimed">'+
                                        '<h2 class="prk_hdr" id="unclaimed_hdr">'+_("Unclaimed Tiles")+'</h2>'+
                                        '<div id="unclaimed_tiles"></div>'+
                                    '</div>';
                dojo.place(unclaimed_div, $('player_boards'));
            }
        },

        /**
         * Wrapper to check against colorblind preferences.
         * @param {string} player_id 
         * @param {bool} bHex (optional, default: True) put '#' before hex string
         * @returns player color as hex string with '#' prepended
         */
        getPlayerColor: function(player_id, bHex=true) {
            let color = this.gamedatas.players[player_id].color
            if (this.isColorblind()) {
                color = this.decorator.toColorBlind(color);
            }
            if (bHex) {
                color = '#'+color;
            }
            return color;
        },

        /**
         * Is colorblind setting set?
         * @returns {bool} true if colorblind
         */
        isColorblind: function() {
            return (this.prefs[PREF_COLORBLIND].value == 1);
        },

        ////////////////////////////////////////////////////////////////
        // PERMISSION HANDLING
        ////////////////////////////////////////////////////////////////

        /**
         * Create the entire Permissions Display, with buttons.
         * Needs to check whether it's already there.
         */
        createPermissionsDisplay: function() {
            if (!$('defenders_permission_banner')) {
                this.createPermissionsBannerHtml();

                const othercities = [...CITIES];
                if (this.existsPersianLeader()) {
                    othercities.push("persia");
                }
                for (let i = 1; i <= 7; i++) {
                    const tiles = $('location_'+i).getElementsByClassName("prk_location_tile");
                    if (tiles.length != 0) {
                        const tile = tiles[0];
                        this.createPermissionsBox(tile, othercities);
                    }
                }
            }
            this.updatePermissions();
        },

        /**
         * Create the 'defenders_permission_banner' with legend.
         */
        createPermissionsBannerHtml: function() {
            const hdr = _("Defender Permissions");
            const msg = _("Leader of the owning city may click neutral/allied cities to give permission to defend");
            const permission_banner = '<div id="defenders_permission_banner">'+
                                                '<h2 class="prk_hdr"; style="font-size: 3em; color: white;">'+hdr+' <i id="permissions_help" class="fa fa-question-circle-o"></i></h2><br/>'+
                                                '<hr style="width: 100%;"/>'+
                                                '<div id="perm_icons_row" style="display: flex; flex-direction: row; justify-content: center; align-items: center;"></div>'+
                                        '</div>';
            dojo.place(permission_banner, $('perikles_map'));
            const permissions_panel = '<div id="permissions_panel" class="whiteblock"></div>';
            dojo.place(permissions_panel, $('perikles_map'));

            const atwar = this.format_block('jstpl_permission_icon', {rel: "war", defender: "false", relationship: _("At War")});
            const ally = this.format_block('jstpl_permission_icon', {rel: "allied", defender: "false", relationship: _("Allied")});
            const neutral = this.format_block('jstpl_permission_icon', {rel: "neutral", defender: "false", relationship: _("Neutral")});
            const defender = this.format_block('jstpl_permission_icon', {rel: "neutral", defender: "true", relationship: _("May Defend")});
            const legend = '<span style="font-size: 24px; color: white; vertical-align: center; margin-right: 5px;">'+_("Legend:")+'</span>';

            dojo.place(legend, $('perm_icons_row'));
            dojo.place(atwar, $('perm_icons_row'));
            dojo.place(ally, $('perm_icons_row'));
            dojo.place(neutral, $('perm_icons_row'));
            dojo.place(defender, $('perm_icons_row'));

            const helphtml = '<span class="prk_help">'+msg+'</span>';

            this.addTooltipHtml('permissions_help', helphtml, '');
        },

        /**
         * For a single tile, create the box with permission buttons for each other eligible city.
         * @param {element} tile 
         * @param {array} cities 
         */
        createPermissionsBox: function(tile, cities) {
            const location = tile.id.split("_")[0];
            const controlling_city = new perikles.locationtile(location).getCity();
            const controlling_player = this.getLeader(controlling_city);
            
            const color = this.getPlayerColor(controlling_player);
            const bb_div = this.format_block('jstpl_permission_box', {location: location, player_name: this.decorator.spanPlayerName(controlling_player, this.isColorblind()), player_color: color});;
            dojo.place(bb_div, tile);
            const button_box = $(location+'_permissions');
            const wars = this.gamedatas.wars;
            for (let city of cities) {
                // display all other cities NOT controlled by the same player
                if (city == "persia" || (city != controlling_city && this.getLeader(city) != controlling_player)) {
                    const btn = this.format_block('jstpl_permission_btn', {location: location, city: city, city_name: this.getCityNameTr(city)});
                    const button = dojo.place(btn, button_box);
                    const is_leader = (this.player_id == controlling_player);

                    if (!is_leader) {
                        button.style["pointer-events"] = 'none';
                    }
                    const relationship = wars[controlling_city][city];
                    if (relationship == -1) {
                        button.dataset.status = "war";
                        button.style["pointer-events"] = 'none';
                    } else if (relationship == 1) {
                        button.dataset.status = "allied";
                    }
                    button.addEventListener('click', this.onClickPermissionButton.bind(this));
                }
            }
        },

        /**
         * Look for all active permissions buttons and update their status.
         * Does not create the buttons or set the event listeners/styles, only the data tags.
         * Relies on this.gamedatas.wars and this.gamedatas.permissions to be current!
         */
        updatePermissions: function() {
            const wars = this.gamedatas.wars;
            const permissions = this.gamedatas.permissions;

            // all the permission buttons attached to the location tiles
            const buttons = $('location_area').getElementsByClassName("prk_city_btn");
            [...buttons].forEach(b => {
                const [location,city,_] = b.id.split("_");
                const controlling_city = new perikles.locationtile(location).getCity();
                const relationship = wars[controlling_city][city];

                b.dataset.defender = (permissions[location] && permissions[location].includes(city));
                // war will override any permission that shouldn't have been granted
                if (relationship == WAR) {
                    b.dataset.status = "war";
                    b.dataset.defender = 'false';
                    if (permissions[location]) {
                        this.gamedatas.permissions[location] = permissions[location].replace(city, '');
                    }
                } else if (relationship == ALLIED) {
                    b.dataset.status = "allied";
                } else {
                    b.dataset.status = "neutral";
                }
            });
        },

        /**
         * Cleanup, remove all the permissions boxes.
         */
        removePermissionButtons: function() {
            $('defenders_permission_banner').remove();
            $('permissions_panel').remove();
            const permboxes = document.getElementsByClassName('prk_permission_box');
            [...permboxes].forEach(p => {
                p.remove();
            });
        },

        /**
         * Bound to a button for a City to display/grant permissions to a battle tile location.
         * Assumes data-status and data-defender has been set
         * @param {Event} evt
         */
        onClickPermissionButton: function(evt) {
            const button = evt.currentTarget;
            // cannot change permissions on a city at war
            if (button.dataset.status != "war") {
                // it's neutral or allied, we can enable defense
                const [location,city,_] = button.id.split("_");
                const toggle = !(button.dataset.defender == "true");
                this.setDefenderPermissions(location, city, toggle);
            }
        },

        /**
         * Do not send preference changes if any of these pertain.
         * @returns true if we're replaying or not active player
         */
         isReadOnly: function() {
            return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
        },

        ///////////////////////////////////////////////////
        //// Player's action

        /**
         * Connected to player preference action
         * @param {string} pref 
         * @param {int} newVal 
         */
         onPreferenceChanged: function(pref, newVal) {
            if (pref == PREF_AUTO_PASS && !this.isReadOnly()) {
                this.ajaxcall( "/perikles/perikles/actChangePref.html", { 
                    pref: pref,
                    value: newVal,
                    lock: true,
                }, this, function( result ) {  }, function( is_error) { } );
                if ($('autopass_special')) {
                    $('autopass_special').checked = (newVal == 1);
                }
            } else if (pref == PREF_LOG_FONT) {
                this.changeLogFontSize(newVal);
            }
        },

        /**
         * Change the size of log font.
         * @param {int} sz 0 for 1em, 1 for 1.5em
         */
        changeLogFontSize: function(sz) {
            const fontsize = (sz == 0) ? "1em" : "1.5em";
            // Get the root element
            const r = document.querySelector(':root');
            r.style.setProperty('--log-font', fontsize);
        },

        /**
         * Action to take an Influence card.
         * @param {string} id
         */
         takeInfluenceTile: function(card_id) {
            if (this.checkAction("takeInfluence", true)) {
                const id = parseInt(card_id.match(/\d+$/)[0]);
                this.ajaxcall( "/perikles/perikles/takeinfluence.html", { 
                    id: id,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },


        /**
         * Wrap actual method in a check for confirmation.
         * @param {string} city 
         */
        placeInfluenceCube: function(city) {
            if (this.prefs[PREF_CONFIRM_DIALOG].value == 1) {
                let add_dlg = _("Add cube to ${city}?");
                add_dlg = add_dlg.replace('${city}', this.getCityNameTr(city));
                this.confirmationDialog( add_dlg, () => {this._placeInfluenceCube(city)}, function() { return; });
            } else {
                // no confirmation
                this._placeInfluenceCube(city);
            }
        },

        /**
         * Action to place an Influence cube on a city.
         * @param {string} city 
         */
        _placeInfluenceCube: function(city) {
            if (this.checkAction("placeAnyCube", true)) {
                this.ajaxcall( "/perikles/perikles/placecube.html", { 
                    city: city,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Wrap actual method in a check for confirmation.
         * @param {string} city 
         * @param {string} player_id 
         */
        proposeCandidate: function(city, player_id) {
            if (this.prefs[PREF_CONFIRM_DIALOG].value == 1) {
                let removedlg = _("Propose ${player_id} as candidate in ${city}?");
                removedlg = removedlg.replace('${player_id}', this.decorator.spanPlayerName(player_id, this.isColorblind()));
                removedlg = removedlg.replace('${city}', this.getCityNameTr(city));
                this.confirmationDialog( removedlg, () => {this._proposeCandidate(city, player_id)}, function() { return; });
            } else {
                // no confirmation
                this._proposeCandidate(city, player_id);
            }
        },

        /**
         * Action to assign a Candidate to a city from a player
         * @param {string} city 
         * @param {string} player_id 
         */
        _proposeCandidate: function(city, player_id) {
            if (this.checkAction("proposeCandidate", true)) {
                this.ajaxcall( "/perikles/perikles/selectcandidate.html", { 
                    city: city,
                    player: player_id,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Wrap actual removal action in a check for confirmation dialog.
         * @param {string} player_id 
         * @param {string} city 
         * @param {string} c 
         */
        removeCube: function(player_id, city, c) {
            if (this.prefs[PREF_CONFIRM_DIALOG].value == 1) {
                let removedlg = _("Remove ${player_id}'s cube in ${city}?");
                removedlg = removedlg.replace('${player_id}', this.decorator.spanPlayerName(player_id, this.isColorblind()));
                removedlg = removedlg.replace('${city}', this.getCityNameTr(city));
                this.confirmationDialog( removedlg, () => {this._removeCube(player_id, city, c)}, function() { return; });
            } else {
                // no confirmation
                this._removeCube(player_id, city, c);
            }
        },

        /**
         * Action to remove a cube.
         * @param {string} player_id 
         * @param {string} city 
         * @param {string} c 
         */
        _removeCube: function(player_id, city, c) {
            if (this.checkAction("chooseRemoveCube", true)) {
                this.ajaxcall( "/perikles/perikles/removecube.html", { 
                    player: player_id,
                    city: city,
                    cube: c,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to choose next player.
         * @param {Object} event 
         */
        choosePlayer: function(event) {
            if (this.checkAction("chooseNextPlayer", true)) {
                const player_id = event.currentTarget.id.split("choose_")[1];
                this.ajaxcall( "/perikles/perikles/chooseplayer.html", { 
                    player: player_id,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to send forces
         */
        commitForces: function() {
            if (this.checkAction("assignUnits", true)) {
                let cube = this.gamedatas.gamestate.args.committed['cube'] ?? "";
                let units = this.packCommitForcesArg(this.gamedatas.gamestate.args.committed);
                this.ajaxcall( "/perikles/perikles/commitUnits.html", { 
                    units: units,
                    cube: cube,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Player chose a unit to lose as casualty.
         * @param {string} city from which casualty comes
         */
        onSelectCasualty: function(city) {
            if (this.checkPossibleActions("chooseLoss", true)) {
                this.ajaxcall( "/perikles/perikles/selectcasualty.html", {
                    city: city,
                    lock: true,
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Player chose a unit to retrieve from deadpool.
         * @param {Object} unit 
         */
        onSelectDeadpool: function(unit) {
            if (this.checkPossibleActions("chooseDeadUnits", true)) {
                this.ajaxcall( "/perikles/perikles/selectdeadpool.html", {
                    city: unit['city'],
                    type: unit['type'],
                    lock: true,
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Player plays or declines to play Special Tile.
         * @param {bool} use
         */
        specialTilePass: function(bUse) {
            if (this.checkPossibleActions("useSpecial", true)) {
                this.ajaxcall( "/perikles/perikles/passspecialtile.html", {
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
                this.restoreDescriptionOnMyTurn();
            }
        },

        /**
         * For Special Tiles applicable during battles.
         * @param {bool} bUse 
         */
        specialBattleTile: function(bUse, side=null) {
            if (this.checkPossibleActions("useSpecialBattle", true)) {
                this.ajaxcall( "/perikles/perikles/specialBattleTile.html", {
                    player: this.player_id,
                    use: bUse,
                    side: side,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
                this.restoreDescriptionOnMyTurn();
            }
        },

        /**
         * Player clicked a City to Plague.
         * @param {string} city 
         */
         onPerikles: function(city) {
            if (this.checkPossibleActions("useSpecial", true)) {
                this.ajaxcall( "/perikles/perikles/perikles.html", { 
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
                this.restoreDescriptionOnMyTurn();
            }
        },

        /**
         * Player clicked a City to Plague.
         * @param {string} city 
         */
        onPlagueCity: function(city) {
            if (this.checkPossibleActions("useSpecial", true)) {
                this.ajaxcall( "/perikles/perikles/plague.html", { 
                    city: city,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
                this.restoreDescriptionOnMyTurn();
            }
        },

        /**
         * Player clicked Confirm on Alkibiades.
         */
        onAlkibiadesMove: function() {
            if (this.checkPossibleActions("useSpecial", true)) {
                const cubes = this.getAlkibiadesCubesToMove();
                if (cubes.length == 2) {
                    this.ajaxcall( "/perikles/perikles/alkibiades.html", {
                        player1: cubes[0].player(),
                        player2: cubes[1].player(),
                        from1: cubes[0].from(),
                        from2: cubes[1].from(),
                        to1: cubes[0].to(),
                        to2: cubes[1].to(),
                        lock: true
                    }, this, function( result ) {  }, function( is_error) { } );
                    this.restoreDescriptionOnMyTurn();
                } else {
                    throw new Error("Two cubes must be selected!");
                }
            }
        },

        /**
         * Player clicked a Slave Revolt button.
         * @param {string} loc sparta or battle location
         */
        onSlaveRevolt: function(loc) {
            if (this.checkPossibleActions("useSpecial", true)) {
                this.ajaxcall( "/perikles/perikles/slaverevolt.html", {
                    location: loc,
                    lock: true
                }, this, function( result ) { }, function( is_error) { } );
            }
        },

        /**
         * Player clicked button to give defend permissions to another city.
         * @param {string} location tile to be defended
         * @param {string} city granted permission to defend
         * @param {bool} bDefend true to give, false to revoke
         */
        setDefenderPermissions: function(location, city, bDefend) {
            this.ajaxcall( "/perikles/perikles/setdefender.html", {
                location: location,
                defender: city,
                defend: bDefend,
                lock: true
            }, this, function( result ) { }, function( is_error) { } );
        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your perikles.game.php file.
        */
        setupNotifications: function() {
            // influence phase actions
            dojo.subscribe( 'influenceCardTaken', this, "notif_influenceCardTaken" );
            this.notifqueue.setSynchronous( 'influenceCardTaken', 1000 );
            dojo.subscribe( 'influenceCubes', this, "notif_addInfluenceCubes");
            this.notifqueue.setSynchronous( 'influenceCubes', 500 );
            dojo.subscribe( 'candidateProposed', this, "notif_candidateProposed");
            this.notifqueue.setSynchronous( 'candidateProposed', 1000 );
            dojo.subscribe( 'cubeRemoved', this, "notif_cubeRemoved");
            this.notifqueue.setSynchronous( 'cubeRemoved', 1000 );
            dojo.subscribe( 'candidatePromoted', this, "notif_candidatePromoted");
            this.notifqueue.setSynchronous( 'candidatePromoted', 1000 );
            dojo.subscribe( 'influenceCardDrawn', this, "notif_influenceCardDrawn");
            this.notifqueue.setSynchronous( 'influenceCardDrawn', 1000 );
            dojo.subscribe( 'election', this, "notif_election");
            this.notifqueue.setSynchronous( 'election', 1000 );
            dojo.subscribe( 'spentInfluence', this, "notif_cubeRemoved");
            this.notifqueue.setSynchronous( 'spentInfluence', 500 );
            dojo.subscribe( 'newInfluence', this, "notif_newInfluence");
            // setup
            dojo.subscribe( 'newLocations', this, "notif_newLocations");

            // cities and statues
            dojo.subscribe( 'addStatue', this, "notif_addStatue" );
            dojo.subscribe( 'cityDefeat', this, "notif_cityDefeat");
            dojo.subscribe( 'scoreCubes', this, "notif_scoreCubes" );
            this.notifqueue.setSynchronous( 'scoreCubes', 500 );
            dojo.subscribe( 'scoreStatues', this, "notif_scoreStatues" );
            this.notifqueue.setSynchronous( 'scoreStatues', 500 );

            // pre-battle/commits
            dojo.subscribe( 'takeMilitary', this, "notif_takeMilitary");
            this.notifqueue.setSynchronous( 'takeMilitary', 1000 );
            dojo.subscribe( 'takePersians', this, "notif_takePersians");
            this.notifqueue.setSynchronous( 'takePersians', 1000 );
            dojo.subscribe( 'sendBattle', this, "notif_sendBattle");
            // ignore the message to sent to everyone about your own units
            // this.notifqueue.setIgnoreNotificationCheck( 'sendBattle', (notif) => (notif.args.id == 0 && notif.args.owners.includes(this.player_id)) );
            this.notifqueue.setSynchronous( 'sendBattle', 1000 );

            //  permission requests and responses
            // dojo.subscribe( 'defendRequest', this, "notif_permissionRequest");
            // this.notifqueue.setSynchronous( 'defendRequest', 500 );
            // dojo.subscribe( 'requestCanceled', this, "notif_cancelPermissionRequest");
            // this.notifqueue.setSynchronous( 'requestCanceled', 500 );
            dojo.subscribe( 'noDefend', this, "notif_noDefend");
            this.notifqueue.setSynchronous( 'noDefend', 0 );

            // battles
            dojo.subscribe( 'unclaimedTile', this, "notif_unclaimedTile");
            this.notifqueue.setSynchronous( 'unclaimedTile', 1500 );
            dojo.subscribe( 'claimTile', this, "notif_claimTile");
            this.notifqueue.setSynchronous( 'claimTile', 1500 );
            dojo.subscribe( 'claimTilePersians', this, "notif_claimTilePersians");
            this.notifqueue.setSynchronous( 'claimTilePersians', 1500 );
            dojo.subscribe( 'returnMilitary', this, "notif_returnMilitary");
            this.notifqueue.setSynchronous( 'returnMilitary', 1000 );
            dojo.subscribe( 'returnMilitaryPool', this, "notif_returnMilitaryPool");
            this.notifqueue.setSynchronous( 'returnMilitaryPool', 1000 );
            dojo.subscribe( 'toDeadpool', this, "notif_toDeadpool");
            this.notifqueue.setSynchronous( 'toDeadpool', 1000 );
            dojo.subscribe( 'revealCounters', this, "notif_revealCounters");
            dojo.subscribe( 'rollBattle', this, "notif_rollBattle");
            this.notifqueue.setSynchronous( 'rollBattle', 1500 );
            dojo.subscribe( 'diceRoll', this, "notif_dieRoll");
            this.notifqueue.setSynchronous( 'diceRoll', 3000 );
            dojo.subscribe( 'takeToken', this, "notif_takeToken");
            this.notifqueue.setSynchronous( 'takeToken', 1500 );
            dojo.subscribe( 'resetBattleTokens', this, "notif_resetBattleTokens");
            this.notifqueue.setSynchronous( 'resetBattleTokens', 1500 );

            // permissions
            dojo.subscribe( 'givePermission', this, "notif_givePermission");
            this.notifqueue.setSynchronous( 'givePermission', 500);

            // deadpool
            dojo.subscribe( 'retrieveDeadpool', this, "notif_retrieveDeadpool");
            this.notifqueue.setSynchronous( 'retrieveDeadpool', 500);

            // special tiles
            dojo.subscribe( 'useTile', this, "notif_useTile");
            this.notifqueue.setSynchronous( 'useTile', 500 );
            dojo.subscribe( 'playSpecial', this, "notif_playSpecial");
            this.notifqueue.setSynchronous( 'notif_playSpecial', 500 );
            dojo.subscribe( 'alkibiadesMove', this, "notif_alkibiadesMove");
            this.notifqueue.setSynchronous( 'notif_alkibiadesMove', 500 );
            dojo.subscribe( 'slaveRevolt', this, "notif_slaveRevolt");

            // end of turn
            dojo.subscribe( 'endTurn', this, "notif_endTurn");
        },

        // Notification handlers

        /**
         * When Influence cubes are added to a city. Animate cubes from card to player's cube area in city.
         * @param {Object} notif 
         */
        notif_addInfluenceCubes: function( notif ) {
            const player_id = notif.args.player_id;
            const cubes = parseInt(notif.args.cubes);
            const city = notif.args.city;
            const from_div = $(player_id+'_player_cards');
            const to_div = $(city+'_cubes_'+player_id);
            const num = to_div.childElementCount;
            this.lastCubeAdded(player_id, city);
            for (let c = 0; c < cubes; c++) {
                const i = num+c+1;
                const cube = this.createInfluenceCube(player_id, city, i);
                this.moveCube(cube, from_div, to_div, 1000+(c*500));
            }
        },

        /**
         * When an Influence tile is taken. Animate card going to player's board, and drawing a new one.
         * @param {Object} notif 
         */
        notif_influenceCardTaken: function( notif )
        {
            const player_id = notif.args.player_id;
            const city = notif.args.city;
            const id = notif.args.card_id;
            const card_id = city+'_'+id;
            const slot = notif.args.slot;

            // recreate the Influence tile
            const newTile = notif.args.tile;

            $(card_id).remove();
            const player_cards = player_id+'_player_cards';
            const from_id = "influence_slot_"+slot;

            // create temp new card to move to the player board
            const fromSlot = $(from_id);
            const newcard = this.createInfluenceCard(newTile);
            let newcard_div = dojo.place(newcard, fromSlot);
            this.slide(newcard_div, player_cards, {"from": fromSlot});
        },

        /**
         * Move card from deck to empty slot.
         * @param {Object} notif 
         */
         notif_influenceCardDrawn: function(notif) {
            // take top card off influence deck first
            this.removeTooltip( INFLUENCE_PILE );
            $(INFLUENCE_PILE).lastElementChild.remove();
            const decksize = $(INFLUENCE_PILE).childElementCount;

            // create the Influence tile
            const tile = notif.args.tile;
            const tile_div = this.createInfluenceCard(tile);
            const card = dojo.place(tile_div, $(INFLUENCE_PILE).lastElementChild);
            this.slideToObjectRelative( card.id, 'influence_slot_'+tile['slot'], 1000, 500 );
            this.decorateInfluenceCard(card, tile['city'], tile['type']);

            var pile_tt = _("Influence Deck: ${num} cards remaining");
            pile_tt = pile_tt.replace('${num}', decksize);
            this.addTooltip(INFLUENCE_PILE, pile_tt, '');

        },

        /**
         * A Candidate was proposed for a city, move it from nominee's cubes to space.
         * @param {Object} notif 
         */
        notif_candidateProposed: function(notif) {
            const player_id = notif.args.candidate_id;
            const city = notif.args.city;
            const candidate = notif.args.candidate; // alpha or beta
            const c = CANDIDATES[candidate]; // "a" or "b"
            const player_cubes = $(city+"_cubes_"+player_id);

            const cube_div = this.createInfluenceCube(player_id, city, c);

            this.moveCube(cube_div, player_cubes, $(city+'_'+c), 500);
            const cube1 = player_cubes.firstChild;
            this.fadeOutAndDestroy( cube1.id, 250);
            if (c == "a") {
                this.decorator.unhighlight($(city+"_a"));
                this.decorator.highlight($(city+"_b"));
            } else {
                this.decorator.unhighlight($(city+"_b"));
                this.decorator.unhighlight($(city));
            }
        },

        /**
         * A cube was removed - may be an Influence cube or a candidate
         * @param {Object} notif 
         */
        notif_cubeRemoved: function(notif) {
            const target_id = notif.args.candidate_id;
            const city = notif.args.city;
            const candidate = notif.args.candidate; // alpha or beta or null
            if (candidate) {
                const c = CANDIDATES[candidate]; // "a" or "b"
                this.fadeOutAndDestroy( target_id+"_"+city+"_"+c, 250);
            } else {
                const player_cubes = $(city+"_cubes_"+target_id);
                const cube1 = player_cubes.firstChild;
                this.fadeOutAndDestroy( cube1.id, 250);
            }
        },

        /**
         * Move cube from B to A
         * @param {Object} notif
         */
        notif_candidatePromoted: function(notif) {
            const city = notif.args.city;
            const candidate_id = notif.args.candidate_id;
            const fromcube = [candidate_id, city, "b"].join("_");
            const to_div = $(city+"_a");
            this.slideToObjectRelative(fromcube, to_div, 1000, 1000, null, "last");
            // need to rename the cube
            $(fromcube).id = [candidate_id, city, "a"].join("_");
        },

        /**
         * Move a cube selected by Alkibiades.
         * @param {Object} notif 
         */
        notif_alkibiadesMove: function(notif) {
            const owner = notif.args.player_id;
            const fromcity = notif.args.city;
            const tocity = notif.args.city2;
            const player_cubes = $(fromcity+"_cubes_"+owner);
            const cube1 = player_cubes.firstChild;
            this.fadeOutAndDestroy( cube1.id, 250);

            const from_div = player_cubes;
            const to_div = $(tocity+'_cubes_'+owner);
            const i = to_div.childElementCount+1;
            const cube = this.createInfluenceCube(owner, tocity, i);
            this.lastCubeAdded(owner, tocity);
            this.moveCube(cube, from_div, to_div, 1000);
        },

        /**
         * Remove candidate cubes, place Leader counters.
         * @param {Object} notif 
         */
        notif_election: function(notif) {
            const player_id = notif.args.player_id;
            const city = notif.args.city;
            const cubes = parseInt(notif.args.cubes);
            // remove candidate cubes
            ["a", "b"].forEach(c => {
                const candidatecube = $(city+"_"+c).firstChild;
                if (candidatecube) {
                    this.fadeOutAndDestroy(candidatecube.id, 500);
                }
            });
            // subtract loser's cubes from winner's
            this.removeInfluenceCubes(player_id, city, cubes);
            // place Leader
            this.createLeaderCounter(player_id, city, "leader");
        },

        /**
         * Move military tokens to each Leader.
         * @param {Object} notif 
         */
        notif_takeMilitary: function(notif) {
            const city = notif.args.city;
            const military = notif.args.military;
            for (const mil of military) {
                this.counterToPlayerBoard(mil);
            }
            // reset tooltip
            this.setCityStackTooltip(city);
        },

        /**
         * Move Persian military tokens to leader.
         * @param {Object} notif 
         */
         notif_takePersians: function(notif) {
            const military = notif.args.military;
            const persianleaders = notif.args.persianleaders;

            // in case of multiple Persians, just show counters moving to first
            const player_id = this.isPersianLeader(this.player_id) ? this.player_id : persianleaders[0];

            for (let mil of military) {
                mil['location'] = player_id;
                this.counterToPlayerBoard(mil);
            }
            this.setCityStackTooltip("persia");
        },

        /**
         * Send military units to battle tiles.
         * Must update permissions status buttons.
         * @param {Object} notif 
         */
        notif_sendBattle: function(notif) {
            const player_id = notif.args.player_id;
            const id = notif.args.id; // 0 if face-down
            const city = notif.args.city;
            const type = notif.args.type;
            const strength = notif.args.strength;
            const slot = notif.args.slot;
            const location = notif.args.location;
            
            const battlepos= notif.args.battlepos;
            const counter = new perikles.counter(city, type, strength, id, location, battlepos);
            this.moveToBattle(player_id, counter, slot);

            // adjust war status
            const wars = notif.args.wars;
            Object.assign(this.gamedatas.wars, wars);
            this.updatePermissions();
        },

        // /**
        //  * A user sent a request for permission to defend.
        //  * @param {*} notif 
        //  */
        // notif_permissionRequest: function(notif) {
        //     // no-op right now
        // },

        // notif_cancelPermissionRequest: function(notif) {
        //     // no-op right now
        // },

        /**
         * Received only by the player who requested permission to defend.
         * One or more requesters denied permission. Reset state.
         * @param {*} notif 
         */
        notif_noDefend: function(notif) {
            console.log(this.player_id + " "+ " state " + this.gamedatas.gamestate.name);
            console.log(this.gamedatas.gamestate.args.committed);
            this.onCancelCommit();
        },

        /**
         * Move Influence tile to discard
         * @param {Object} notif 
         */
        notif_useTile: function(notif) {
            const city = notif.args.city;
            const id = notif.args.id;
            this.fadeOutAndDestroy(city+'_'+id, 2000, 0);
        },

        /**
         * Player played a special tile
         * @param {Object} notif 
         */
        notif_playSpecial: function(notif) {
            const player_id = notif.args.player_id;
            // get this player's special card
            const player_div = $(player_id+"_player_cards");
            const special = notif.args.tile;
            const spec = player_div.getElementsByClassName("prk_special_tile")[0];
            spec.classList.add(special);
            spec.dataset.side = "front";
            spec.dataset.status = "used";
            if (this.player_id == player_id) {
                this.removeActionButtons();
                $('player_options').remove();
            }
        },

        /**
         * Start of new turn, discard previous cards and deal new ones.
         * @param {Object} notif 
         */
        notif_newInfluence: function(notif) {
            const influencetiles = document.getElementsByClassName("prk_influence_tile");
            [...influencetiles].forEach(t => {
                this.slideToObjectAndDestroy(t, 'influence_slot_0', 500, 0);
            });
            const influence = notif.args.influence;
            const sz = toint(notif.args.decksize);
            this.setupInfluenceTiles(influence, sz);
        },

        /**
         * Start of new turn, deal out new locations.
         * @param {Object} notif 
         */
        notif_newLocations: function(notif) {
            const locations = notif.args.locations;
            this.setupLocationTiles(locations);
        },

        /**
         * When a Location tile is not claimed after a battle.
         * @param {Object} notif 
         */
        notif_unclaimedTile: function(notif) {
            this.createUnclaimedTilesBox();

            const loc = notif.args.location;
            const tile = $(loc+'_tile');
            this.moveAndScoreTile(tile, loc, 'unclaimed_tiles');
            this.postBattle(loc);
        },

        /**
         * Claim a tile after a battle.
         * @param {Object} notif 
         */
        notif_claimTile: function(notif) {
            const loc = notif.args.location;
            const tile = $(loc+'_tile');
            const player_id = notif.args.player_id;
            const vp = toint(notif.args.vp);
            this.moveAndScoreTile(tile, loc, player_id+'_player_tiles', player_id, vp);
            // refresh tile displays
            this.displayPlayerVictoryTiles();
            this.postBattle(loc);
        },

        /**
         * Multiple Persian players claim a tile.
         * @param {Object} notif 
         */
         notif_claimTilePersians: function(notif) {
            const location = notif.args.location;
            const persians = notif.args.persians;
            const vp = toint(notif.args.vp);
            const slot = notif.args.slot;

            let i = 1;
            [...Object.values(persians)].forEach(persian_player => {
                const tile = new perikles.locationtile(location);
                const div = tile.createTile();
                let tileObj = dojo.place(div, $('location_'+slot));
                tileObj = this.makePersianVictoryTile(tileObj, i);
                this.moveAndScoreTile(tileObj, location, persian_player+'_player_tiles', persian_player, vp);
                i++;
            });
            this.displayPlayerVictoryTiles();
            this.postBattle(location);
            // destroy original tile
            $(location+'_tile').remove();
        },

        /**
         * Move Leader counter to statues.
         * @param {Object} notif 
         */
        notif_addStatue: function(notif) {
            const player_id = notif.args.player_id;
            const city = notif.args.city;
            const leader = $(city+'_leader_0');
            this.slideToObjectAndDestroy(leader, city+'_statues', 1000, 0);
            this.createLeaderCounter(player_id, city, "statue");
        },

        /**
         * A city was defeated.
         * @param {Object} notif 
         */
        notif_cityDefeat: function(notif) {
            const city = notif.args.city;
            const num = toint(notif.args.defeats);
            this.addDefeatCounter(city, num);
        },

        /**
         * Return military from a battle to cities, and clean up tile.
         * @param {Object} notif 
         */
        notif_returnMilitary: function(notif) {
            const slot = notif.args.slot;
            // if null, then return ALL units
            const type = notif.args.type;
            // filter to make sure we don't asynchronously send units to deadpool and stacks
            const ids = notif.args.ids;
            // remove the listeners
            const slot_div = $('battle_zone_'+slot);
            let stacks = Array.from(slot_div.getElementsByClassName("prk_battle"));
            if (type) {
                stacks = stacks.filter(s => s.id.includes(type));
            }
            stacks.forEach(s => {
                this.makeSplayable(s, false);
            });

            const counter_type = (type) ? "prk_"+type : "prk_military";

            const counters = slot_div.getElementsByClassName(counter_type);
            const milzones = new Set();
            [...counters].forEach(c => {
                const counter_name = c.id;
                const [city, _1, _2, id] = counter_name.split('_');
                if (ids.includes(id)) {
                    const city_military = city+"_military";
                    milzones.add(city_military);

                    const slider = this.slideToObject(counter_name, city_military, 1000, 1500);
                    dojo.connect(slider, 'onEnd', (counter) => {
                        const [city, unit, strength, id] = counter.id.split('_');
                        counter.remove();
                        new perikles.counter(city, unit, strength, id).addToStack();
                        this.stacks.sortStack(city+"_military");
                    });
                    slider.play();
                } else {
                    // this is a counter sent to deadpool
                    c.remove();
                }
            });
            for (zone of milzones) {
                this.sortStack(zone);
            }
        },

        /**
         * Return military from a player's pools.
         * @param {Object} notif 
         */
         notif_returnMilitaryPool: function(notif) {
            let player_id = notif.args.player_id;
            const cities = new Set();
            // persians units are sent as slightly reformatted argument
            if (player_id == "persia") {
                const persianleaders = notif.args.persianleaders;
                if (persianleaders.includes(this.player_id)) {
                    player_id = this.player_id;
                } else {
                    player_id = persianleaders[0];
                }
                cities.add("persia");
            }
            const counters = notif.args.counters;
            [...counters].forEach(c => {
                const counter = this.militaryToCounter(c);
                const counter_div = counter.toDiv(0, 0);
                counterObj = dojo.place(counter_div, $('overall_player_board_'+player_id));
                // this animates moving counter from player board and actually puts object in city stack
                this.counterFromPlayerBoard(counterObj, counter['city'], counter['type'], counter['strength'], counter['id']);
                cities.add(counter['city']);
            });
            for (city of cities) {
                this.sortStack(city+'_military');
            }
            // hide military board
            $('military_board').style['display'] = 'none';
        },

        /**
         * Move military token from a player's board to its city
         * @param {Object} counter
         * @param {string} city
         * @param {string} type
         * @param {string} strength
         * @param {string} id
         */
         counterFromPlayerBoard: function(counter, city, type, strength, id) {
            const city_military = city+"_military";
            // this.slideToObjectAndDestroy(counter, city_military, 1000, 1500);

            const slider = this.slideToObject(counter, city_military, 1000, 1500);
            dojo.connect(slider, 'onEnd', (counter) => {
                // const counter = $(counter_id);
                counter.remove();
                new perikles.counter(city, type, strength, id).addToStack();
                this.stacks.sortStack(city_military);
            });
            slider.play();
        },

        /**
         * Someone sent or revoked permission to defend.
         * Need to update gamedatas.permissions
         * @param {Object} notif 
         */
        notif_givePermission: function(notif) {
            const location = notif.args.location;
            // only a string for this one location
            const permissions = notif.args.permissions;
            this.gamedatas.permissions[location] = permissions;
            this.updatePermissions();
        },

        /**
         * Flip all the counters face up at a battle zone during the fight.
         * @param {Object} notif 
         */
        notif_revealCounters: function(notif) {
            const slot = notif.args.slot;
            const military = notif.args.military;

            // clear the old ones.
            // TODO: animate flipping
            const oldcounters = $('battle_zone_'+slot).getElementsByClassName("prk_military");
            [...oldcounters].forEach(c => {
                c.remove();
            });
            military.forEach(m => {
                counter = this.militaryToCounter(m);
                counter.placeBattle();
            });
       },

        /**
         * Counter moved to Deadpool (or back to stack, for Persians).
         * @param {Object} notif 
         */
        notif_toDeadpool: function(notif) {
            const id = notif.args.id;
            const city = notif.args.city;
            const type = notif.args.type;
            const strength = notif.args.strength;
            const counter_id = [city, type, strength, id].join("_");
            // Persians just go back to Persian stack
            const bPersian = (city == "persia");
            let to_loc = "";
            if (bPersian) {
                to_loc = "persia_military";
            } else {
                to_loc = [city, type, DEAD_POOL].join("_")
                this.createMilitaryArea(DEAD_POOL, city);
            }

            const slider = this.slideToObject(counter_id, to_loc, 1000, 1500);
            dojo.connect(slider, 'onEnd', (counter_id) => {
                const counter = $(counter_id);
                counter.remove();
                if (bPersian) {
                    new perikles.counter(city, type, strength, id).addToStack();
                    this.stacks.sortStack("persia_military");
                } else {
                    const counter = new perikles.counter(city, type, strength, id, DEAD_POOL);
                    const counterObj = counter.placeCounterInContainer(DEAD_POOL);
                    counterObj.setAttribute("title", this.counterText(counter));
                    this.stacks.sortStack([city, type, DEAD_POOL].join("_"), false);
                }
            });
            slider.play();
        },

        /**
         * Counter moved from Deadpool to player's board.
         * @param {Object} notif 
         */
        notif_retrieveDeadpool: function(notif) {
            const player_id = notif.args.player_id;
            const id = notif.args.id;
            const city = notif.args.city;
            const type = notif.args.type;
            const strength = notif.args.strength;
            const mil = {'city': city, 'type': type, 'strength': strength, 'id': id, 'location': player_id, 'battlepos': 0};
            this.counterToPlayerBoard(mil, true);
        },

       /**
        * A single Hoplite counter needs to be flipped and moved back to Sparta
        * @param {Object} notif 
        */
       notif_slaveRevolt: function(notif) {
            const counter = notif.args.military;
            const location = notif.args.return_from;
            const sparta_player = notif.args.sparta_player;
            const counter_id = ["sparta_hoplite", counter['strength'], counter.id].join("_");

            let hoplite = null;
            if (location == "sparta") {
                // comes from player's pool
                if (this.player_id == sparta_player) {
                    // get the counter
                    hoplite = $(counter_id);
                } else {
                    // create counter on player's board
                    const tempcounter = new perikles.counter('sparta', HOPLITE, counter["strength"], counter["id"]);
                    const counter_div = tempcounter.toDiv(0, 0);
                    hoplite = dojo.place(counter_div, $('overall_player_board_'+sparta_player));
                }
            } else {
                // comes from a battle tile
                if (this.player_id == sparta_player) {
                    // get correct counter
                    hoplite = $(counter_id);
                } else {
                    // "flip" the top Hoplite counter
                    hoplite = $('sparta_hoplite_0_0_'+location);
                }
            }
            // now move it back to Sparta
            this.slideToObjectAndDestroy(hoplite, 'sparta_military', 1000, 500);
            new perikles.counter('sparta', HOPLITE, counter['strength'], counter['id']).addToStack();
            this.stacks.sortStack("sparta_military");
        },

        /**
         * Highlight the odds column for this battle.
         * @param {Object} notif 
         */
         notif_rollBattle: function(notif) {
            const crt = notif.args.crt;
            const type = notif.args.type;
            const slot = notif.args.slot;
            const counters = $('battle_zone_'+slot).getElementsByClassName("prk_"+type);
            [...counters].forEach(c => {
                c.dataset.highlight = "true";
            });
            const crt_col = $('crt_'+crt);
            this.decorator.highlight(crt_col);
        },

        /**
         * Animate rolling dice
         * @param {Object} notif 
         */
        notif_dieRoll: function(notif) {
            this.dice.clearResultHighlights();
            const attd1 = toint(notif.args.attacker_1);
            const attd2 = toint(notif.args.attacker_2);
            const defd1 = toint(notif.args.defender_1);
            const defd2 = toint(notif.args.defender_2);
            const atthit = notif.args.attacker_result;
            const defhit = notif.args.defender_result;
            $('attacker-die-1').addEventListener('transitionend', () => {
                this.highlightResult("attacker", atthit);
            });
            this.dice.rollDice("attacker", attd1, attd2);

            $('defender-die-1').addEventListener('transitionend', () => {
                this.highlightResult("defender", defhit);
            });
            this.dice.rollDice("defender", defd1, defd2);
        },

        /**
         * Attacker or defender takes a Battle Token.
         * @param {Object} notif 
         */
        notif_takeToken: function(notif) {
            // "attacker" or "defender"
            const side = notif.args.side;
            this.moveTokenToBattleSide(side);
        },

        /**
         * Send Battle tokens back to center for next battle.
         * Leave one if there was a previous winner.
         * Also clear CRT and battle highlighting.
         * @param {Object} notif
         */
        notif_resetBattleTokens: function(notif) {
            this.initializeBattleTokens();
            const winner = notif.args.winner;
            this.returnBattleTokens(winner);
            this.clearCRT();
            const counters = $('location_area').getElementsByClassName("prk_military");
            [...counters].forEach(c => {
                delete c.dataset.highlight;
            });
        },

        /**
         * Display scoring of cubes at endgame.
         * @param {Object} notif 
         */
        notif_scoreCubes: function(notif) {
            const player_id = notif.args.player_id;
            const vp = toint(notif.args.vp);
            const city = notif.args.city;
            const scoring_delay = toint(notif.args.scoring_delay);
            const player_cubes = city+'_cubes_'+player_id;
            const player_color = this.getPlayerColor(player_id, false);
            this.displayScoring( player_cubes, player_color, vp, scoring_delay*SCORING_ANIMATION );
            this.scoreCtrl[ player_id ].incValue( vp );
        },

        /**
         * Display scoring of statues at endgame.
         * @param {Object} notif 
         */
        notif_scoreStatues: function(notif) {
            const player_id = notif.args.player_id;
            const vp = toint(notif.args.vp);
            const statues = toint(notif.args.statues);
            const city = notif.args.city;
            const scoring_delay = toint(notif.args.scoring_delay);
            const player_color = this.getPlayerColor(player_id, false);
            for (let s = 0; s < statues; s++) {
                const statue_id = city+'_statue_'+s;
                this.displayScoring( statue_id, player_color, vp, s*scoring_delay*SCORING_ANIMATION );
                this.scoreCtrl[ player_id ].incValue( vp );
            }
        },

        /**
         * End of turn cleanup.
         * @param {Object} notif 
         */
        notif_endTurn: function(notif) {
            // remove battle tokens
            const tokens = $('perikles_map').getElementsByClassName('prk_battle_token');
            [...tokens].forEach(token => {
                this.fadeOutAndDestroy(token, 100);
            });
            this.clearCRT();
            // all military counters on my board should be deleted
            const my_counters = $('military_board').getElementsByClassName("prk_military");
            [...my_counters].forEach(counter => {
                counter.remove();
            });
            // reset city stacks
            for (let city of CITIES) {
                this.setCityStackTooltip(city);
            }
            this.setCityStackTooltip("persia");
        },
    });
});
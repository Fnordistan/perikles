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
const MILITARY_ROW = {'argos': 0, 'athens': 1, 'corinth': 2, 'megara': 3, 'sparta': 4, 'thebes': 5, 'persia': 6};

const INFLUENCE_ROW = {'athens' : 0, 'sparta' : 1, 'argos' : 2, 'corinth' : 3, 'thebes' : 4, 'megara' : 5, 'any' : 6};
const INFLUENCE_COL = {'influence' : 0, 'candidate' : 1, 'assassin' : 2};

const INFLUENCE_PILE = "influence_slot_0";

const COMMIT_INFLUENCE_CUBES = "commit_influence_cubes";

const PLAYER_INF_MARGIN = "2px";

const SPECIAL_TILES = ['perikles', 'persianfleet', 'slaverevolt', 'brasidas', 'thessalanianallies', 'alkibiades', 'phormio', 'plague'];

const MILITARY_DISPLAY_STATES = ['spartanChoice', 'nextPlayerCommit', 'commitForces', 'deadPool', 'takeDead', 'resolveBattles'];

const HOPLITE = "hoplite";
const TRIREME = "trireme";

const CANDIDATES = {
    "\u{003B1}" : "a",
    "\u{003B2}" : "b"
}

const MIL_DIM = {
    "l": 100,
    "s": 62
}

const PLAYER_COLORS = {
    "E53738" : "red",
    "37BC4C" : "green",
    "39364F" : "black",
    "E5A137" : "orange",
    "FFF" : "white",
}

const WHITE_OUTLINE = 'text-shadow: 1px 1px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;';

// row,column
const LOCATION_TILES = {
    "amphipolis" : {xy: [1,1], city: "athens", "rounds": "TH", "vp": 6, "intrinsic": "dh"},
    "lesbos" : {xy: [1,2], city: "athens", "rounds": "HT", "vp": 4, "intrinsic": "aht"},
    "plataea" :  {xy: [1,3], city: "athens", "rounds": "H", "vp": 4, "intrinsic": "dh"},
    "naupactus" : {xy: [1,4], city: "athens", "rounds": "TH", "vp": 4, "intrinsic": null},
    "potidea" : {xy: [1,5], city: "athens", "rounds": "TH", "vp": 5, "intrinsic": "ah"},
    "acarnania" : {xy: [1,6], city: "athens", "rounds": "TH", "vp": 3, "intrinsic": "dh"},
    "attica" : {xy: [1,7], city: "athens", "rounds": "H", "vp": 4, "intrinsic": null},
    "melos" : {xy: [2,1], city: "sparta", "rounds": "HT", "vp": 3, "intrinsic": "dht"},
    "epidaurus" : {xy: [2,2], city: "sparta", "rounds": "TH", "vp": 4, "intrinsic": null},
    "pylos" : {xy: [2,3], city: "sparta", "rounds": "TH", "vp": 4, "intrinsic": null},
    "sicily" : {xy: [2,4], city: "sparta", "rounds": "TH", "vp": 7, "intrinsic": "dht"},
    "cephallenia" : {xy: [2,5], city: "sparta", "rounds": "HT", "vp": 4, "intrinsic": null},
    "cythera" : {xy: [2,6], city: "sparta", "rounds": "HT", "vp": 3, "intrinsic": null},
    "spartolus" : {xy: [2,7], city: "sparta", "rounds": "TH", "vp": 4, "intrinsic": "ah"},
    "megara" : {xy: [3,1], city: "megara", "rounds": "TH", "vp": 5, "intrinsic": null},
    "mantinea" : {xy: [3,2], city: "argos", "rounds": "H", "vp": 5, "intrinsic": null},
    "delium" : {xy: [3,3], city: "thebes", "rounds": "TH", "vp": 5, "intrinsic": null},
    "aetolia" : {xy: [3,4], city: "thebes", "rounds": "TH", "vp": 3, "intrinsic": null},
    "corcyra" : {xy: [3,5], city: "corinth", "rounds": "HT", "vp": 3, "intrinsic": "aht"},
    "leucas" : {xy: [3,6], city: "corinth", "rounds": "HT", "vp": 4, "intrinsic": null},
    "solygeia" : {xy: [3,7], city: "corinth", "rounds": "HT", "vp": 4, "intrinsic": null},
}

// match MAIN/ALLY ATT/DEF constants in php
const BATTLE_POS = {
    1: "att",
    2: "att_ally",
    3: "def",
    4: "def_ally"
}

// tracks cubes being moved by Alkibiades Special Tile
const ALKIBIADES_CUBES = "alkibiades_cubes"

const DEAD_POOL = "deadpool";

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.perikles", ebg.core.gamegui, {
        constructor: function(){
            this.influence_h = 199;
            this.influence_w = 128;
            this.location_w = 124;
            this.location_h = 195;
            this.location_s = 0.55;
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
            this.setupSpecialTiles(gamedatas.players, gamedatas.specialtiles);
            this.setupInfluenceTiles(gamedatas.influencetiles, parseInt(gamedatas.decksize));
            this.setupInfluenceCubes(gamedatas.influencecubes);
            this.setupLocationTiles(gamedatas.locationtiles);
            this.setupCandidates(gamedatas.candidates);
            this.setupLeaders(gamedatas.leaders);
            this.setupStatues(gamedatas.statues);
            this.setupMilitary(gamedatas.military);
            this.setupDefeats(gamedatas.defeats);
            this.setupCities();

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
            // refresh any Alkibiades cubes
            this.ALKIBIADES_CUBES = [];
        },

        /**
         * Set up special tiles
         * @param {Array} players 
         * @param {Array} specialtiles 
         */
        setupSpecialTiles: function(players, specialtiles) {
            const special_scale = 0.2;

            for (const player_id in players) {
                const spec = parseInt(specialtiles[player_id]);

                // add flex row for cards
                const player_cards = this.format_block('jstpl_influence_cards', {id: player_id, scale: special_scale});
                const player_cards_div = dojo.place(player_cards, $('player_board_'+player_id));

                let used = false;
                if (spec == 0) {
                    var specialtile = this.format_block('jstpl_special_back', {id: player_id, scale: special_scale});
                } else {
                    const spec_i = SPECIAL_TILES[Math.abs(spec)-1];
                    specialtile = this.format_block('jstpl_special_tile', {special: spec_i, scale: special_scale, margin: PLAYER_INF_MARGIN});
                    used = (spec < 0 || player_id != this.player_id);
                }
                const tile = dojo.place(specialtile, player_cards_div);
                if (used) {
                    tile.classList.add("prk_special_tile_used");
                }
                if (spec == 0) {
                    let ttext = _("${player_name}'s Special tile");
                    const player_name = this.spanPlayerName(player_id);
                    ttext = ttext.replace('${player_name}', player_name);
                    this.addTooltip(tile.id, ttext, '');
                } else {
                    const thtml = this.createSpecialTileTooltip(SPECIAL_TILES[Math.abs(spec)-1]);
                    this.addTooltipHtml(tile.id, thtml, '');
                }
            }
        },

        /**
         * HTML for Special tile tooltip.
         * @param {string} tilenum 
         * @returns 
         */
        createSpecialTileTooltip: function(special) {
            const TITLES = {
                'perikles': _("PERIKLES"),
                'persianfleet': _("PERSIAN FLEET"),
                'slaverevolt': _("SLAVE REVOLT"),
                'brasidas': _("BRASIDAS"),
                'thessalanianallies': _("THESSALANIAN ALLIES"),
                'alkibiades': _("ALKIBIADES"),
                'phormio': _("PHORMIO"),
                'plague': _("PLAGUE")
            };
            const DESC = {
                'perikles': _("Place two Influence cubes in Athens. This tile can be played when it is your turn to select an Influence tile, either just before or just after taking the tile."),
                'persianfleet': _("This tile can be played just before a trireme battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                'slaverevolt': _("This tile can be played when it is your turn to commit forces to a location. Take one Spartan hoplite counter, either from the board or from the controlling player, and place it back in Sparta. That counter cannot be involved in combat this turn. You cannot examine the counter you remove. The counter will come back into play in the next turn."),
                'brasidas': _("This tile can be played just before a hoplite battle is about to be resolved. All Spartan hoplite counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                'thessalanianallies': _("This tile can be played just before a hoplite battle is about to be resolved. Choose one side in that battle to start with one battle token. This cannot be played to gain an automatic victory; i.e. it cannot be played for a side that already has a token due to winning the first round of combat."),
                'alkibiades': _("Player can take two Influence cubes of any color from any city/cities and move them to any city of their choice. These cubes may not be moved from a candidate space, nor may they be moved to one."),
                'phormio': _("This tile can be played just before a trireme battle is about to be resolved. All Athenian trireme counters in that battle have their strengths doubled. Intrinsic attackers/defenders are not doubled."),
                'plague': _("This tile can be played during the Influence Tile phase. Select one city. All players remove half (rounded down) of their Influence cubes from that city.")
            };

            const title = TITLES[special];
            const text = DESC[special];
            const tt = this.format_block('jstpl_special_tt', {header: title, special: special, text: text, scale: 0.5});
            return tt;
        },

        /**
         * Create the HTML tooltip for Location cards.
         */
        createLocationTileTooltip: function(location) {
            const battlename = this.getBattleNameTr(location);

            let desc = this.createBattleDescription(LOCATION_TILES[location].rounds);
            desc += '<br/>';
            desc += this.createBonusDescription(LOCATION_TILES[location].intrinsic);
            desc += '<br/>';
            let vpstr = _("${vp} Victory Points");
            vpstr = vpstr.replace('${vp}', LOCATION_TILES[location].vp);
            desc += vpstr;
            const city = LOCATION_TILES[location].city;
            const x = -1 * (LOCATION_TILES[location].xy[1]-1) * this.location_w * this.location_s;
            const y = -1 * (LOCATION_TILES[location].xy[0]-1) * this.location_h * this.location_s;

            let defendingcity = _("Defender: ${cityname}");
            defendingcity = defendingcity.replace('${cityname}', this.getCityNameTr(city));
            const tt = this.format_block('jstpl_location_tt', {defender: defendingcity, battle: battlename, text: desc, x: x, y: y});
            return tt;
        },

        /**
         * Translated battle description string
         * @param {string} rounds TH/HT/H 
         */
        createBattleDescription: function(rounds) {
            let battlestr = _("Order of Battle: ${units}");
            const trireme = _("Triremes");
            const hoplite = _("Hoplites");
            const desc = {
                "H": hoplite,
                "HT": hoplite+'&#10142;'+trireme,
                "TH": trireme+'&#10142;'+hoplite,
            };
            battlestr = battlestr.replace('${units}', desc[rounds]);
            return battlestr;
        },

        /**
         * Translated string describing location tile native attackers/defenders.
         * @param {string} intrinsic 
         */
        createBonusDescription: function(intrinsic) {
            let desc = "";
            if (intrinsic != null) {
                const attacker = _("Attacker");
                const defender = _("Defender");
                const both = _("Hoplites and Triremes");
                const hoplite = _("Hoplites");
                let bonusstr = _("${combatant} adds 1 to ${unit} strength");
                let combatant = "";
                let units = "";
                switch (intrinsic) {
                    case "ah":
                        combatant = attacker;
                        units = hoplite;
                        break;
                    case "dh":
                        combatant = defender;
                        units = hoplite;
                        break;
                    case "aht":
                        combatant = attacker;
                        units = both;
                        break;
                    case "dht":
                        combatant = defender;
                        units = both;
                        break;
               }
               bonusstr = bonusstr.replace('${combatant}', combatant);
               bonusstr = bonusstr.replace('${unit}', units);
               desc = bonusstr;
            }
            return desc;
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
            const card_div = this.format_block('jstpl_influence_tile', {id: id, city: city, x: xoff, y: yoff, margin: PLAYER_INF_MARGIN});
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
         * Return translatable location name text.
         * @param {string} battle 
         * @returns translatable string
         */
        getBattleNameTr: function(battle) {
            const locationnames = {
                "amphipolis" : _("Amphipolis"),
                "lesbos" : _("Lesbos"),
                "plataea" : _("Plataea"),
                "naupactus" : _("Naupactus"),
                "potidea" : _("Potidea"),
                "acarnania" : _("Acarnania"),
                "attica" : _("Attica"),
                "melos" : _("Melos"),
                "epidaurus" : _("Epidaurus"),
                "pylos" : _("Pylos"),
                "sicily" : _("Sicily"),
                "cephallenia" : _("Cephallenia"),
                "cythera" : _("Cythera"),
                "spartolus" : _("Spartolus"),
                "megara" : _("Megara"),
                "mantinea" : _("Mantinea"),
                "delium" : _("Delium"),
                "aetolia" : _("Aetolia"),
                "corcyra" : _("Corcyra"),
                "leucas" : _("Leucas"),
                "solygeia" : _("Solygeia"),
            };
            return locationnames[battle];
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
         * @param {Array} locationtiles 
         */
        setupLocationTiles: function(locationtiles) {
            for (const loc of locationtiles) {
                const slot = loc['slot'];
                const battle = loc['battle'];
                const location = loc['loc'];
                const loc_html = this.createLocationTile(battle, 0);
                if (location == "board") {
                    const tile = dojo.place(loc_html, $("location_"+slot));
                    const lochtml = this.createLocationTileTooltip(battle);
                    this.addTooltipHtml(tile.id, lochtml, '');
                } else if (location == "unclaimed") {
                    const tile = dojo.place(loc_html, $("unclaimed_tiles"));
                    tile.style.margin = null;
                } else {
                    // player claimed
                }
            }
        },

        /**
         * Create location tile
         * @param {int} location 
         * @param {int} m margin
         * @returns html div
         */
        createLocationTile: function(location, m) {
            const x = -1 * (LOCATION_TILES[location].xy[1]-1) * this.location_w * this.location_s;
            const y = -1 * (LOCATION_TILES[location].xy[0]-1) * this.location_h * this.location_s;
            const loc_html = this.format_block('jstpl_location_tile', {id: location, x: x, y: y, m: m});
            return loc_html;
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
                const leader = this.createLeaderCounter(player_id, city, "leader", 1);
                dojo.place(leader, $(city+"_leader"));
            }
        },

        /**
         * Place all statues in city statue areas.
         * @param {Object} statues 
         */
        setupStatues: function(statues) {
            for (const city of CITIES) {
                const citystatues = statues[city];
                if (citystatues) {
                    let s = 0;
                    for (const [player_id, num] of Object.entries(citystatues)) {
                        for (let i = 1; i <= parseInt(num); i++) {
                            const statue_div = this.createLeaderCounter(player_id, city, "statue", s+1);
                            const statue = dojo.place(statue_div, $(city+"_statues"));
                            statue.style.bottom = (s*22)+"px";
                            statue.style.left = (s*6)+"px";
                            s++;
                        }
                    }
                }
            }
        },

        /**
         * For creating Leader and Statue counters.
         * @param {int} player_id 
         * @param {string} city 
         * @param {string} type 
         * @param {int} n 
         * @returns statue or leader div
         */
        createLeaderCounter: function(player_id, city, type, n) {
            const counter = this.format_block('jstpl_leader', {city: city, type: type, num: n, color: this.playerColor(player_id)});
            return counter;
        },

        /**
         * Create a cube div in player's color.
         * @param {int} player_id
         * @param {string} city
         * @param {string} tag
         * @returns html for colored influence cube
         */
        createInfluenceCube: function(player_id, city, tag) {
            const player = this.gamedatas.players[player_id];
            const color = player.color;
            const id = player_id+"_"+city+"_"+tag;
            const cube = this.format_block('jstpl_cube', {id: id, color: color});
            return cube;
        },

        /**
         * Place all military counters
         * @param {Object} military 
         */
        setupMilitary: function(military) {
            this.military_zones = {};
            let mz = 'persia_military';
            this.decorateMilitaryStacks("persia", mz);
            this.military_zones[mz] = {'spread': false};
            for (const city of CITIES) {
                mz = city+"_military";
                this.decorateMilitaryStacks(city, mz);
                this.military_zones[mz] = {'spread': false};
            }

            for(const i in military) {
                const mil = military[i];
                const city = mil['city'];
                const unit = mil['type'];
                const strength = mil['strength'];
                const location = mil['location'];
                let [xoff, yoff] = this.counterOffsets(city, strength, unit);
                if (location == city && mil['battlepos'] == 0) {
                    // in a city stack
                    this.placeCityStack(city, unit, strength, mil['id']);
                } else if (location == DEAD_POOL) {
                    // in the dead pool

                } else if (Object.keys(LOCATION_TILES).includes(location)) {
                    // sent to a battle
                    this.placeCounterAtBattle(mil, i);
                } else {
                    // it's in a player pool
                    const player_id = location;
                    if (player_id == this.player_id) {
                        this.createMilitaryArea(player_id, city);
                        const m = 1;
                        const counter_div = this.format_block('jstpl_military_counter', {city: city, type: unit, s: strength, id: mil['id'], x: xoff, y: yoff, m: m, t: 0});
                        const mil_zone = city+"_"+unit+"_"+player_id;
                        const counter = dojo.place(counter_div, $(mil_zone));
                        Object.assign(counter.style, {position: "relative"});
                    }
                }
            }
        },

        /**
         * Place a military counter on the battle stacks at a location tile.
         * @param {Object} counter 
         * @param {string} stackpos
         */
        placeCounterAtBattle: function(counter, stackpos) {
            const slotid = $(counter['location']+"_tile").parentNode.id;
            const slot = slotid[slotid.length-1];
            const unit = counter['type'];
            const city = counter['city'];
            const strength = counter['strength'];
            const place = "battle_"+slot+"_"+unit+"_"+BATTLE_POS[counter['battlepos']];
            const stackct = $(place).childElementCount;
            let [xoff, yoff] = this.counterOffsets(city, strength, unit);
            const battlecounter = this.format_block('jstpl_battle_counter', {city: city, type: unit, s: strength, id: "counter_"+stackpos, x: xoff, y: yoff, m: 8*stackct, t: 0});
            dojo.place(battlecounter, $(place));
        },

        /**
         * Put a military unit on a city stack.
         * @param {string} city 
         * @param {string} unit 
         * @param {string} strength 
         * @param {string} id 
         */
        placeCityStack: function(city, unit, strength, id) {
            const city_military = $(city+"_military");
            const ct = city_military.childElementCount;
            const top = (unit == TRIREME) ? MIL_DIM.s : 0;
            const [xoff, yoff] = this.counterOffsets(city, strength, unit);
            const counter = this.format_block('jstpl_military_counter', {city: city, type: unit, s: strength, id: id, x: xoff, y: yoff, m: 2*ct, t: top});
            dojo.place(counter, city_military);
        },

        /**
         * Create array[2] with background-position offsets for a military counter.
         * @param {*} city 
         * @param {*} strength 
         * @param {*} unit
         * @param returns [x,y] values
         */
        counterOffsets: function(city, strength, unit) {
            var xdim, ydim;
            if (unit == HOPLITE) {
                xdim = MIL_DIM.s;
                ydim = MIL_DIM.l;
            } else if (unit == TRIREME) {
                xdim = MIL_DIM.l;
                ydim = MIL_DIM.s;
            } else {
                throw Error("invalid unit type: "+ unit);
            }
            let xoff = -1 * strength * xdim;
            let yoff = -1 * MILITARY_ROW[city] * ydim;
            return [xoff,yoff];
        },

        /**
         * Make military display available counters
         */
        decorateMilitaryStacks: function(city, city_mil_id) {
            const city_mil = $(city_mil_id);
            city_mil.addEventListener('click', () => {
                if (this.isSpread(city_mil_id)) {
                    this.unspread(city_mil);
                } else {
                    this.spreadMilitaryUnits(city_mil);
                }
            });

            let tt = _("${city} military: click to inspect stack");
            tt = tt.replace('${city}', this.getCityNameTr(city) );
            this.addTooltip(city_mil_id, tt, '');
            city_mil.addEventListener('mouseleave', () => {
                this.unspread(city_mil);
            });
        },

        /**
         * Are the military units in the city spread already?
         * @param {Object} city_mil 
         */
        isSpread: function(city_mil) {
            return this.military_zones[city_mil]['spread'];
        },

        /**
         * Unspread military units.
         * @param {Object} city_mil 
         */
        unspread: function(city_mil) {
            for (const mil of city_mil.children) {
                Object.assign(mil.style, {'transform' : null, 'z-index': null});
            }
            this.military_zones[city_mil.id]['spread'] = false;
        },

        /**
         * Spread out all Hoplite and Trireme counters
         */
        spreadMilitaryUnits: function(city_mil) {
            const hoplites = [];
            const triremes = [];
            for (const mil of city_mil.children) {
                if (mil.classList.contains("prk_hoplite")) {
                    hoplites.push(mil.id);
                } else if  (mil.classList.contains("prk_trireme")) {
                    triremes.push(mil.id);
                }
            }
            let n = 0;
            // Athens spreads to left
            let athens_off = 0;
            if (city_mil.id == "athens_military") {
                athens_off = -1 * Math.max((hoplites.length * MIL_DIM.s), (triremes.length * MIL_DIM.l));
            }
            for (hop of hoplites) {
                let xoff = athens_off+(n*MIL_DIM.s);
                let yoff = n*-2;
                Object.assign($(hop).style, {'transform' : "translate("+xoff+"px,"+yoff+"px)", 'z-index': 1});
                n++;
            }
            const rec = city_mil.getBoundingClientRect();
            n = 0;
            for (tri of triremes) {
                let tridim = $(tri).getBoundingClientRect();
                let xoff = (-2 * hoplites.length) + athens_off+(n*MIL_DIM.l);
                let yoff = 22 + rec.bottom - tridim.top;
                Object.assign($(tri).style, {'transform' : "translate("+xoff+"px,"+yoff+"px)", 'z-index': 1});
                n++;
            }
            this.military_zones[city_mil.id]['spread'] = true;
        },

        /**
         * Place Defeat counters on cities.
         * @param {Object} defeats 
         */
        setupDefeats: function(defeats) {
            for (const [city, num] of Object.entries(defeats)) {
                for (let d = 1; d <= num; d++) {
                    const def_ctr = this.format_block('jstpl_defeat', {city: city, num: d} );
                    const def_div = $(city+'_defeat_slot_'+d);
                    dojo.place(def_ctr, def_div);
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

        ///////////////////////////////////////////////////
        //// Display methods

        /* @Override */
        format_string_recursive : function(log, args) {
            try {
                if (log && args && !args.processed) {
                    args.processed = true;
                    if (args.player_name) {
                        args.player_name = this.spanPlayerName(args.player_id);
                    }
                    if (args.actplayer) {
                        args.actplayer = args.actplayer.replace('color:#FFF;', 'color:#FFF;'+WHITE_OUTLINE);
                    }
                    if (args.candidate_name) {
                        args.candidate_name  = this.spanPlayerName(args.candidate_id);
                    }
                    if (args.city_name) {
                        args.city_name = this.spanCityName(args.city);
                    }
                    if (args.attd1) {
                        args.attd1 = this.diceIcon(args.attd1);
                        args.attd2 = this.diceIcon(args.attd2);
                        args.defd1 = this.diceIcon(args.defd1, true);
                        args.defd2 = this.diceIcon(args.defd2, true);
                        args.atttotal = '<span class="prk_dicetotal">['+args.atttotal+']</span>';
                        args.deftotal = '<span class="prk_dicetotal">['+args.deftotal+']</span>';
                    }
                        args.atttotal = '<span>'+args.atttotal+'</span>';
                    if (!this.isSpectator) {
                        log = log.replace("You", this.spanYou());

                        if (args.committed) {
                            const commit_log = this.createCommittedUnits(args.committed);
                            log = log.replace('committed_forces', commit_log);
                        }
                        if (args.plague) {
                            const plague_btns = this.createPlagueButtons();
                            log += plague_btns;
                        }
                        if (args.alkibiades) {
                            const alkibiades_btsn = this.createAlkibiadesButtons();
                            log += alkibiades_btsn;
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
                    let mil_html = this.createMilitaryCounterRelative(id+"_dlg", selected.city, selected.strength, selected.unit);
                    mil_html = this.prependStyle(mil_html, 'display: inline-block');
                    commit_str = commit_str.replace('${unit}', mil_html);
                    let loc_html = this.createLocationTile(selected.location, 0);
                    loc_html = this.prependStyle(loc_html, 'display: inline-block');
                    commit_str = commit_str.replace('${location}', loc_html);

                    if (selected.cube) {
                        extra_forces += commit_str+'<br/>';
                    } else {
                        commit_log += commit_str+'<br/>';
                    }
                    counters++;
                }
            }
            // option to spend Influence cube
            if (counters >= 2) {
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
                        cubehtml = this.prependStyle(cubehtml, "display: inline-block; margin-left: 5px;");
                        spend_cubes_div = _("You may spend an Influence cube to send 1 or 2 units from that city")+cubehtml+'<br/>'+spend_cubes_div;
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
         * @param {bool} def is defensive dice
         * @returns html icon
         */
        diceIcon: function(val, def=false) {
            const roll = toint(val);
            const xoff = -33 * (roll-1);
            let die_icon = this.format_block('jstpl_die', {x: xoff});
            if (def) {
                const def_color = "filter: sepia(100%)";
                die_icon = this.prependStyle(die_icon, def_color);
            }
            return die_icon;
        },
        /**
         * Create span with Player's name in color.
         * @param {int} player 
         */
         spanPlayerName: function(player_id) {
            const player = this.gamedatas.players[player_id];
            const color_bg = this.colorBg(player);
            const pname = "<span style=\"font-weight:bold;color:#" + player.color + ";" + color_bg + "\">" + player.name + "</span>";
            return pname;
        },

        /**
         * From BGA Cookbook. Return "You" in this player's color
         */
         spanYou: function() {
            const player = this.gamedatas.players[this.player_id]; 
            const color = player.color;
            const color_bg = this.colorBg(player);
            const you = "<span style=\"font-weight:bold;color:#" + color + ";" + color_bg + "\">" + __("lang_mainsite", "You") + "</span>";
            return you;
        },

        /**
         * Take a city name and put it in colored text and translate the name.
         * @param {string} city 
         * @returns decorated HTML div
         */
        spanCityName: function(city) {
            return '<div class="prk_city_name" style="color:var(--color_'+city+');">'+this.getCityNameTr(city)+'</div>';
        },

        /**
         * Get the style tag for background-color for a player name (shadow for white text)
         * @param {Object} player 
         * @returns css tag or empty string
         */
        colorBg: function(player) {
            let color_bg = "";
            if (player.color_back) {
                color_bg = "background-color:#"+player.color_back+";";
            } else if (player.color == "FFF") {
                color_bg = WHITE_OUTLINE;
            }
            return color_bg;
        },

        /**
         * Put a new style element into an HTML div's style attribute
         * @param {string} html 
         * @param {string} style 
         * @returns 
         */
        prependStyle: function(html, style) {
            html = html.replace('style="', 'style="'+style+';');
            return html;
        },

        /**
         * Customized player colors per player_id
         * @param {string} player_id 
         */
        playerColor: function(player_id) {
            const player = this.gamedatas.players[player_id];
            const color = player.color;
            return PLAYER_COLORS[color];
        },

        /**
         * Puts top banner for active player.
         * @param {string} text
         * @param {Array} moreargs
         */
         setDescriptionOnMyTurn: function(text, moreargs) {
            this.gamedatas.gamestate.descriptionmyturn = text;
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
                tpl.you = this.spanYou();
            }
            if (text !== null) {
                title = this.format_string_recursive(text, tpl);
            }
            if (title == "") {
                this.setMainTitle("&nbsp;");
            } else {
                this.setMainTitle(title);
            }
        },

        /**
         * Change the title banner.
         * @param {string} text 
         */
         setMainTitle : function(text) {
            $('pagemaintitletext').innerHTML = text;
        },

        /**
         * Create a military area for player, if it does not already exist
         * @param {string} player_id 
         * @param {strin} city 
         * @returns id of military div
         */
        createMilitaryArea: function(player_id, city) {
            const city_mil = city+'_military_'+player_id;
            if (!document.getElementById(city_mil)) {
                const mil_div = this.format_block('jstpl_military_area', {city: city, id: player_id, cityname: this.getCityNameTr(city)});
                dojo.place(mil_div, $('mymilitary'));
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
            this.slideToObjectRelative(mobile, to_div, 1000, delay, null, "last")
        },

        /**
         * Move a military token from the city to stack to the player'sboard
         * @param {Object} military
         */
        moveMilitary: function(military) {
            const city = military['city'];
            const unit = military['type'];
            const strength = military['strength'];
            const id = military['id'];
            const player_id = military['location'];
            const counter = $(city+'_'+unit+'_'+strength+'_'+id);
            if (player_id == this.player_id) {
                this.createMilitaryArea(player_id, city);
                const mil_zone = city+"_"+unit+"_"+player_id;
                this.slideToObjectRelative(counter, $(mil_zone), 500, 500, null, "last");
            } else {
                this.slideToObjectAndDestroy(counter, $('player_board_'+player_id), 500, 500);
            }
        },

        /**
         * Move an object to a battle tile
         * @param {*} military 
         */
        moveToBattle: function(player_id, city, unit, strength, id, slot, pos) {
            if (player_id == this.player_id) {
                $(city+'_'+unit+'_'+strength+'_'+id).remove();
            }

            // move from city to battle
            let [xoff, yoff] = this.counterOffsets(city, strength, unit);
            const battlepos = "battle_"+slot+"_"+unit+"_"+BATTLE_POS[pos];
            const stackct = $(battlepos).childElementCount;
            const counter_html = this.format_block('jstpl_battle_counter', {city: city, type: unit, s: strength, id: "counter_"+id, x: xoff, y: yoff, m: 8*stackct, t: 0});
            const milzone = $(city+"_military");
            const counter = dojo.place(counter_html, milzone);
            this.slide(counter, battlepos, {from: milzone});
        },

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
                this.stripPosition(token);
                token.classList.remove('moving_token');
                // Perikles additions
                if (token.classList.contains("prk_military")) {
                    token.style.position = "relative";
                    token.style.margin = "1px";
                }
                if (onEnd) onEnd(token);
            }, duration, delay);
        },

        /**
         * This method will attach mobile to a new_parent without destroying, unlike original attachToNewParent which destroys mobile and
         * all its connectors (onClick, etc)
         */
         attachToNewParentNoDestroy: function (mobile_in, new_parent_in, relation, place_position) {
            //console.log("attaching ",mobile,new_parent,relation);
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
         * This method will remove all inline style added to element that affect positioning
         */
         stripPosition: function (token) {
            // console.log(token + " STRIPPING");
            // remove any added positioning style
            token = $(token);

            token.style.removeProperty("display");
            token.style.removeProperty("top");
            token.style.removeProperty("bottom");
            token.style.removeProperty("left");
            token.style.removeProperty("right");
            token.style.removeProperty("position");
            // dojo.style(token, "transform", null);
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
                        mycubes.classList.add("prk_cubes_active");
                    } else {
                        mycubes.classList.remove("prk_cubes_active");
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
                    const mycubes = $(city+"_cubes_"+this.player_id);
                    mycubes.classList.remove("prk_cubes_active");
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
                    if (cube_div.hasChildNodes() && $(city).classList.contains("prk_city_active")) {
                        cube_div.classList.add("prk_cubes_active");
                    }
                } else {
                    cube_div.classList.remove("prk_cubes_active");
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
                    if ($(city).classList.contains("prk_city_active") && this.hasCubeInCity(city, true)) {
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
                    card.classList.add("prk_influence_tile_active");
                } else {
                    card.classList.remove("prk_influence_tile_active");
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
         * @param {*} id 
         * @param {*} city 
         * @param {*} unit 
         * @param {*} strength 
         * @param {*} side 
         * @param {*} battle 
         */
         onSendUnit: function(id, city, unit, strength, side, battle) {
            // is this an extra unit sent with a cube?
            const commit_city = this.gamedatas.gamestate.args.committed['cube'];
            const is_extra = (commit_city != null) && (commit_city == city);
            this.gamedatas.gamestate.args.committed[id] = {city: city, side: side, location: battle, strength: strength, unit: unit, cube: is_extra};
            this.setDescriptionOnMyTurn(_("You must commit forces")+'<br/>committed_forces');
            // add event listeners
            const city_btns = document.getElementsByClassName("prk_city_btn");
            [...city_btns].forEach(btn => {
                btn.addEventListener('click', this.onCommitExtraForces.bind(this));
            });
            // hide unit on military board
            $(city+'_'+unit+'_'+strength+'_'+id).style.display = "none";

            // don't forget the cube is one of the keys
            const len = Object.keys(this.gamedatas.gamestate.args.committed).length;
            // don't unselect anything if < 2
            if (len > 1) {
                let selectable_city = null;
                if (len > 2) {
                    if (commit_city) {
                        if (len == 4) {
                            selectable_city = commit_city;
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
            const sz = Object.keys(this.gamedatas.gamestate.args.committed).length;
            if (sz == 0) {
                this.confirmationDialog( _("You have not selected any forces"), dojo.hitch( this, function() {
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
            const civ_mils = $(city+'_military_'+this.player_id).getElementsByClassName('prk_military');
            [...civ_mils].forEach(ctr => this.makeSelectable(ctr));
        },

        /**
         * Clear "committed" gamedatas
         */
        onResetForces: function() {
            this.gamedatas.gamestate.args['committed'] = {};
            this.setDescriptionOnMyTurn(_("You must commit forces"));

            const mils = $('mymilitary').getElementsByClassName('prk_military ');
            [...mils].forEach(m => {
                // redisplay counters that were hidden before
                m.style.display = "block";
                // reenable deselected counters
                if (m.getAttribute("data-selectable") == "false") {
                    this.makeSelectable(m);
                }
            });
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( this.player_id + ' Entering state: '+stateName );
            
            switch( stateName ) {
                case 'choosePlaceInfluence':
                    if( this.isCurrentPlayerActive() ) {
                        let cities = document.getElementsByClassName("prk_city");
                        [...cities].forEach(c => c.classList.add("prk_city_active"));
                    }
                    break;
                case 'proposeCandidates':
                    if (this.isCurrentPlayerActive()) {
                        for (const city of CITIES) {
                            const candidate_space = this.openCandidateSpace(city);
                            if (candidate_space && this.hasCubeInCity(city, true)) {
                                const city_div = $(city);
                                city_div.classList.add("prk_city_active");
                                candidate_space.classList.add("prk_candidate_space_active");
                            }
                        }
                    }
                    break;
                case 'assassinate':
                    if (this.isCurrentPlayerActive()) {
                        let cubes = document.getElementsByClassName("prk_cube");
                        [...cubes].forEach( c => c.classList.add("prk_cubes_remove"));
                    }
                    break;
                case 'commitForces':
                    if (this.isCurrentPlayerActive()) {
                        const mils = $('mymilitary').getElementsByClassName("prk_military");
                        [...mils].forEach(m => {
                            this.makeSelectable(m);
                        });
                        this.gamedatas.gamestate.args = {};
                        this.gamedatas.gamestate.args.committed = {};
                    }
                    const battleslots = $('location_area').getElementsByClassName("prk_battle");
                    [...battleslots].forEach(b => {
                        this.makeSplayable(b);
                    });

                    break;
                case 'dummmy':
                    break;
            }
            if (MILITARY_DISPLAY_STATES.includes(stateName)) {
                $('military_board').style['display'] = 'block';
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName ) {
                case 'choosePlaceInfluence':
                    this.stripClassName("prk_city_active");
                    break;
                case 'proposeCandidates':
                    this.stripClassName("prk_city_active");
                    this.stripClassName("prk_candidate_space_active");
                    this.stripClassName("prk_cubes_active");
                    break;
                case 'assassinate':
                    this.stripClassName("prk_cubes_remove");
                    break;
                case 'commitForces':
                    const mils = $('mymilitary').getElementsByClassName("prk_military");
                    [...mils].forEach(m => {
                        this.makeSelectable(m, false);
                    });
                    this.gamedatas.gamestate.args = {};
                    this.gamedatas.gamestate.args.committed = {};
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
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {
                switch( stateName ) {
                    case 'spartanChoice':
                        for (player_id in this.gamedatas.players) {
                            this.addActionButton( 'choose_'+player_id, this.spanPlayerName(player_id), 'choosePlayer', null, false, 'gray' );
                        }
                        break;
                    case 'commitForces':
                        this.addActionButton( "commit_send_btn", _('Commit Forces'), () => {
                            this.onCommitForces();
                        });
                        this.addActionButton( "commit_cancel_btn", _('Cancel'), () => {
                            this.onResetForces();
                        }, null, null, 'red');
                        break;
                    case 'specialTile':
                        this.addActionButton( 'pass_btn', _("Pass"), () => {
                            this.specialTile(false);
                        }, null, false, 'red' );
                        break;
                }
            }
            // buttons that can be added even for non-current player
            switch( stateName ) {
                case 'takeInfluence':
                case 'specialTile':
                    if (args._private.special) {
                        const buttonlbl = this.getSpecialButtonLabel(this.player_id);
                        this.addActionButton( 'play_special_btn', buttonlbl, () => {
                            console.log("click 1");
                            this.specialTileWrapper();
                        }, null, false, 'blue' );
                    }
                    break;
        }

        },

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
         * @returns label.
         */
        getPlayerSpecial: function(player_id) {
            const mycards = $(player_id+"_player_cards");
            const myspecial = mycards.getElementsByClassName("prk_special_tile")[0];
            for (const s of SPECIAL_TILES) {
                if (myspecial.classList.contains(s)) {
                    return s;
                }
            }
            return null;
        },

        /**
         * Check cards before submitting to the specialTile function.
         */
        specialTileWrapper: function() {
            const special = this.getPlayerSpecial(this.player_id);
            console.log("click 2");
            if (special == "plague") {
                this.addPlagueButtons();
            } else if(special == "alkibiades") {
                this.addAlkibiadesButtons();
            } else {
                this.specialTile(true);
            }
        },

        ///////////////////////////////////////////////////
        //// Handling Special Tiles
        ///////////////////////////////////////////////////

        // PLAGUE

        /**
         * Player clicked "Use Plague" button.
         */
        addPlagueButtons: function() {
            this.setDescriptionOnMyTurn(_("Select a city to be struck with plague"), {'plague': true});
            this.removeActionButtons();
            let plaguebuttons = document.getElementsByClassName("prk_plague_btn");
            [...plaguebuttons].forEach( p => p.addEventListener('click', () => {
                const city = p.id.split("_")[0];
                this.onPlagueCity(city);
            }));

            this.addSpecialTileCancel("cancel");
        },

        // ALKIBIADES

        /**
         * Player clicked "Use Alkibiades" button.
         */
         addAlkibiadesButtons: function() {
            this.setDescriptionOnMyTurn(_("Select 2 cubes to move"), {'alkibiades': true});
            this.removeActionButtons();

            // recolor the to_civ buttons
            const to_civs = $('alkibiades_to_cities').getElementsByClassName('prk_alkibiades_btn');
            [...to_civs].forEach(civ => {
                civ.style['background-color'] = 'white';
                civ.addEventListener('mouseenter', () => this.enterCivBtnAlkibiades(civ));
                civ.addEventListener('mouseleave', () => this.leaveCivBtnAlkibiades(civ));
                civ.addEventListener('click', () => this.clickCivBtnAlkibiades(civ));
            });

            let alkibiadescubes = $('alkibiades_from_cities').getElementsByClassName('prk_cube');
            [...alkibiadescubes].forEach( c => this.addAlkibiadesCubesEventListeners(c));

            // for (player_id in this.gamedatas.players) {
            //     for (city of CITIES) {
            //         this.decorateInfluenceCubes(city, player_id, true);
            //     }
            // }

            this.addSpecialTileCancel("alkibiades");
        },

        /**
         * Add listeners to cubes in the Alkibiades banner.
         * Makes them light up and spin when hovered, or add to local ALKIBIADES_CUBES array if selected.
         * @param {Object} cube 
         */
         addAlkibiadesCubesEventListeners: function(cube) {
            cube.classList.add("prk_cube_alibiades");
            cube.addEventListener('click', () => {
                const selectedcube = cube.id.split("_").slice(0,2);
                const len = this.ALKIBIADES_CUBES.push(selectedcube);
                this.decorateAlkibiadesToDiv(selectedcube[0]);
                console.log("Alkibiades cubes selected: "+len);
            });
            cube.addEventListener('mouseenter', () => {
                cube.classList.add("prk_cube_alkibiades_active");
            });
            cube.addEventListener('mouseleave', () => {
                cube.classList.remove("prk_cube_alkibiades_active");
            });
        },

        /**
         * Highlight the Alkibiades "To" div with player cube color.
         * @param {string} player_id 
         */
        decorateAlkibiadesToDiv: function(player_id) {
            let pcolor = this.playerColor(player_id);
            if (pcolor == 'white') {
                pcolor = 'gray';
            }
            const to_city_container = $('alkibiades_to_cities');
            to_city_container.style['box-shadow'] = '2px 2px 15px 5px '+pcolor;
        },

        /**
         * When entering one of the Alkibiades To buttons, color it if it's not the from city.
         * @param {element} tociv 
         */
        enterCivBtnAlkibiades: function(tociv) {
            if (this.ALKIBIADES_CUBES.length > 0) {
                const movecube = this.ALKIBIADES_CUBES[this.ALKIBIADES_CUBES.length-1];
                const tocity = tociv.id.split("_")[0];
                if (movecube[1] != tocity) {
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
            tociv.style['background-color'] = 'white';
        },

        /**
         * Dropping a cube on a city to move it there.
         * @param {element} tociv
         */
         clickCivBtnAlkibiades: function(tociv) {
            if (this.ALKIBIADES_CUBES.length > 0) {
                const [player_id, fromcity] = this.ALKIBIADES_CUBES[this.ALKIBIADES_CUBES.length-1];
                const tocity = tociv.id.split("_")[0];
                if (fromcity != tocity) {
                    tociv.style['background-color'] = 'white';
                    const cubehtml = this.createInfluenceCube(player_id, fromcity, 'move');
                    dojo.place(cubehtml, tociv);
                }
            }
        },

        /**
         * When Special tile is canceled, re-add it.
         * @param {string} special
         */
        addSpecialTileCancel: function(special) {
            let desc = _("${player} must take an Influence tile");
            const player = this.isCurrentPlayerActive() ? this.spanYou() : this.spanPlayerName(this.getActivePlayerId());
            desc = desc.replace('${player}', player);
            this.addActionButton( special+"_cancel_btn", _('Cancel'), () => {
                this.setDescriptionOnMyTurn(desc);
                this.removeActionButtons();
                this.addActionButton( 'play_special_btn', this.getSpecialButtonLabel(this.player_id), () => {
                this.specialTileWrapper();
                }, null, false, 'blue' );
            }, null, null, 'red');
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /**
         * Is this player leader of this city?
         * @param {string} player_id 
         * @param {string} city 
         */
        isLeader: function(player_id, city) {
            let isLeader = false;
            const leader = $(city+'_leader').firstChild;
            if (leader) {
                const color = this.playerColor(player_id);
                if (leader.classList.contains('prk_leader_'+color)) {
                    isLeader = true;
                }
            }
            return isLeader;
        },

        /**
         * Does the player have any uncommitted military units in that city?
         * @param {string} player_id 
         * @param {string} city 
         */
        hasAvailableUnits: function(player_id, city) {
            for (let u of [HOPLITE, TRIREME]) {
                if ($(city+'_'+u+'_'+player_id).childElementCount > 0) {
                    return true;
                }
            };
            return false;
        },

        /**
         * Does current player have a cube in the city?
         * @param {string} city 
         * @param {bool} candidates including candidates? default: false
         * @returns true if this player has a cube in city
         */
        hasCubeInCity: function(city, candidates=false) {
            const player_id = this.player_id;
            if (candidates) {
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
         * Strip all elements of the document of a given class name
         * @param {string} cls className
         */
        stripClassName: function(cls) {
            let actdiv = document.getElementsByClassName(cls);
            [...actdiv].forEach( a => a.classList.remove(cls));
        },

        /**
         * Remove the bottommost of a player's Influence cubes in a city
         * @param {string} player_id 
         * @param {string} city
         * @param {int} num
         */
        removeInfluenceCubes: function(player_id, city, num) {
            const from_div = $(city+'_cubes_'+player_id);
            for (let i = 0; i < num; i++) {
                const toremove = from_div.lastChild;
                this.fadeOutAndDestroy(toremove, 500);
            }
        },

        /**
         * For military counters, makes them selectable/unselectable
         * @param {DOM} counter 
         * @param {bool} selectable (default true)
         */
        makeSelectable: function(counter, selectable=true) {
            counter.setAttribute("data-selectable", selectable);
            counter.style.outline = selectable ? "3px red dashed" : null;
            if (selectable) {
                this.connect(counter, 'mouseenter', this.hoverUnit);
                this.connect(counter, 'mouseleave', this.unhoverUnit);
                this.connect(counter, 'click', this.sendUnit.bind(this));
            } else {
                this.disconnect(counter, 'mouseenter');
                this.disconnect(counter, 'mouseleave');
                this.disconnect(counter, 'click');
            }
        },

        /**
         * 
         * @param {*} evt 
         */
         hoverUnit: function(evt) {
            evt.currentTarget.classList.add("prk_military_active");
        },
        /**
         * 
         * @param {*} evt 
         */
        unhoverUnit: function(evt) {
            evt.currentTarget.classList.remove("prk_military_active");
        },

        /**
         * For places to stack units at battles.
         * @param {DOM} battleslot 
         * @param {bool} splay 
         */
        makeSplayable: function(battleslot, splay=true) {
            if (splay) {
                this.connect(battleslot, 'click', this.splayUnits);
                this.connect(battleslot, 'mouseenter', this.splayUnits);
                this.connect(battleslot, 'mouseleave', this.unsplayUnits);
            } else {
                this.disconnect(battleslot, 'click', this.splayUnits);
                this.disconnect(battleslot, 'mouseenter', this.splayUnits);
                this.disconnect(battleslot, 'mouseleave', this.unsplayUnits);
            }
        },

        /**
         * Connected to military counters at battles.
         * @param {*} evt 
         */
        splayUnits: function(evt) {
            const units = evt.currentTarget.getElementsByClassName("prk_at_battle");
            let i = 0;
            [...units].forEach(u => {
                if (u.classList.contains("prk_hoplite")) {
                    let hoffset = 8+(i*50);
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, "+hoffset+", -20)";
                } else {
                    let toffset = -8+(i*80);
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, "+toffset+", -4)";
                }
                u.style['outline'] = "solid white 3px";
                u.style['z-index'] = "99";
                i++;
            });
        },

        /**
         * Connected to military counters at battles.
         * @param {*} evt 
         */
        unsplayUnits: function(evt) {
            const units = evt.currentTarget.getElementsByClassName("prk_at_battle");
            [...units].forEach(u => {
                if (u.classList.contains("prk_hoplite")) {
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, 8, -20) rotate(90deg)";
                } else {
                    u.style['transform'] = "matrix(0.8, 0, 0, 0.8, -8, -4)";
                }
                Object.assign(u.style, {
                    'outline': null,
                    'z-index': null
                });
            });
        },

        /**
         * Button to send a unit to a battle.
         * @param {Object} evt event
         */
        sendUnit: function(evt) {
            const selectedUnit = evt.currentTarget;

            this.commitDlg = new ebg.popindialog();
            this.commitDlg.create( 'commitDlg' );

            const unitc = this.createCopyCounter(selectedUnit);
            const [city,unit,strength,id] = selectedUnit.id.split('_');
            let unit_str = _("${city_name} ${unit}-${strength}");
            unit_str = unit_str.replace('${city_name}', '<span style="color: var(--color_'+city+');")>'+this.getCityNameTr(city)+'</span>');
            unit_str = unit_str.replace('${unit}', '<b>${unit}</b>');
            unit_str = unit_str.replace('${unit}', unit == HOPLITE ? _("Hoplite") : _("Trireme"));
            unit_str = unit_str.replace('${strength}', strength);

            this.commitDlg.setTitle( _("Commit Forces") );
            this.commitDlg.setMaxWidth( 720 );
            const html = '<div id="CommitDialogDiv" style="display: flex; flex-direction: column; top: 50px;">\
                            <div style="display: flex; flex-direction: row; align-items: center;">'
                            +unitc + this.createLocationTileIcons(city)+
                            '</div>\
                            <div id="commit_text" style="margin: 2px; padding: 2px; text-align: center; color: #fff; background-color: #4992D2; display: none;"></div>\
                            <div style="display: flex; flex-direction: row; justify-content: space-evenly;">\
                                <div id="send_button" class="prk_btn prk_send_btn">'+_("Send Unit")+'</div>\
                                <div id="cancel_button" class="prk_btn prk_cancel_btn">'+_("Cancel")+'</div>\
                            </div>\
                        </div>';
            // Show the dialog
            this.commitDlg.setContent( html );
            this.commitDlg.show();
            this.commitDlg.hideCloseIcon();
            const dlg = $('CommitDialogDiv');
            dlg.onclick = event => {
                const target = event.target;

                const attack_str = _("Send ${unit} to attack ${location}?");
                const defend_str = _("Send ${unit} to defend ${location}?");
                let banner_txt = null;

                if (target.id == "send_button" ) {
                    let location = dlg.getAttribute("data-location");
                    let sendto = dlg.getAttribute("data-side");
                    if (location && sendto) {
                        this.onSendUnit(id, city, unit, strength, sendto, location);
                        this.commitDlg.destroy();
                    } else {
                        banner_txt = '<span style="color: white; font-size: larger; font-weight: bold;">'+_("You must select a location")+'</span>';
                    }
                } else if (target.id == "cancel_button") {
                    this.commitDlg.destroy();
                } else if (target.classList.contains("prk_battle_icon")) {
                    const [side, loc] = target.id.split('_');
                    dlg.setAttribute("data-location", loc);
                    dlg.setAttribute("data-side", side);
                    banner_txt = side == "attack" ? attack_str : defend_str;
                    banner_txt = banner_txt.replace('${location}', '<span style="color: var(--color_'+LOCATION_TILES[loc].city +');">'+this.getBattleNameTr(loc)+'</span>');
                    banner_txt = banner_txt.replace('${unit}', unit_str);
                }
                if (banner_txt) {
                    $(commit_text).style.display = "block";
                    $(commit_text).innerHTML = banner_txt;
                }
            };
        },

        /**
         * Check whether player can attack a city
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
            for (const city of CITIES) {
                if (this.isLeader(this.player_id, city)) {
                    //any cubes left?
                    if (this.hasCubeInCity(city) && this.hasAvailableUnits(this.player_id, city)) {
                        civ_btns += this.format_block('jstpl_city_btn', {city: city, city_name: this.getCityNameTr(city)});
                        canSpend = true;
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
         * Copy a military counter as a dialog icon from an existing one
         * @param {*} counter 
         * @returns relative div
         */
         createCopyCounter: function(counter) {
            const [city,unit,strength,id] = counter.id.split('_');
            let counter_html = this.createMilitaryCounterRelative(id+"_copy", city, strength, unit);
            return counter_html;
        },

        /**
         * Create a military counter with relative dimensions.
         * @param {*} id 
         * @param {*} city 
         * @param {*} strength 
         * @param {*} unit 
         * @returns relative html div
         */
        createMilitaryCounterRelative: function(id, city, strength, unit) {
            const [xoff, yoff] = this.counterOffsets(city, strength, unit);
            let counter_html = this.format_block('jstpl_military_counter', {city: city, type: unit, s: strength, id: id, x: xoff, y: yoff, m: 5, t: 0});
            counter_html = this.prependStyle(counter_html, 'position: relative');
            return counter_html;
        },

        /**
         * Create the div containing all the location tiles that go in a Commit Forces dialog.
         * @param unit_city city the unit is from
         * @returns html
         */
        createLocationTileIcons: function(unit_city) {
            let loc_html = '<div style="display: flex; flex-direction: column; margin: 10px;">';
            const location_tiles = $('location_area').getElementsByClassName("prk_location_tile");
            [...location_tiles].forEach(loc => {
                loc_html += '<div style="display: flex; flex-direction: row; align-items: center;">';
                const battle = loc.id.split('_')[0];
                // can't attack own city
                const battle_city = LOCATION_TILES[battle].city;
                if (unit_city != battle_city && this.canAttack(battle_city)) {
                    loc_html += '<div id="attack_'+battle+'" class="prk_battle_icon prk_sword"></div>';
                } else {
                    loc_html += '<div class="prk_blank_icon"></div>';
                }
                const loc_tile = this.createLocationTile(battle, 1);
                loc_html += loc_tile;
                loc_html += '<div id="defend_'+battle+'" class="prk_battle_icon prk_shield"></div>';
                loc_html += '</div>';
            });
            loc_html += '</div>';
            return loc_html;
        },

        /**
         * Create buttons to afflict city with plague
         * @returns html for plague buttons
         */
         createPlagueButtons: function() {
            let plaguecivs = "";
            for (const city of CITIES) {
                plaguecivs += this.format_block('jstpl_plague_btn', {city: city, city_name: this.getCityNameTr(city)});
            }
            let html = '<div id="plague_city_div" style="display: inline-flex; flex-direction: row;">';
            html += plaguecivs;
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
                fromcivs += this.format_block('jstpl_alkibiades_btn', {city: city, city_name: this.getCityNameTr(city), tag: "from"});
                tocivs += this.format_block('jstpl_alkibiades_btn', {city: city, city_name: this.getCityNameTr(city),  tag: "to"});
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
            let html = '<br/><div id="alkibiades_from_cities" class="prk_alkibiades_civs" style="background-color: lightgray;">';
            html += '<h2 style="font-family: \'Bodoni Moda\';">'+_('From')+'</h2>';
            html += fromcivs;
            html += '</div>';
            html += '<div id="alkibiades_to_cities" class="prk_alkibiades_civs" style="background-color: lightgray;">';
            html += '<h2 style="font-family: \'Bodoni Moda\';">'+_('To')+'</h2>';
            html += tocivs;
            html += '</div><br/>';
            return html;
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
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
         * Action to place an Influence cube on a city.
         * @param {string} city 
         */
        placeInfluenceCube: function(city) {
            if (this.checkAction("placeAnyCube", true)) {
                this.ajaxcall( "/perikles/perikles/placecube.html", { 
                    city: city,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to assign a Candidate to a city from a player
         * @param {string} city 
         * @param {string} player_id 
         */
        proposeCandidate: function(city, player_id) {
            if (this.checkAction("proposeCandidate", true)) {
                this.ajaxcall( "/perikles/perikles/selectcandidate.html", { 
                    city: city,
                    player: player_id,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
            }
        },

        /**
         * Action to remove a cube.
         * @param {string} player_id 
         * @param {string} city 
         * @param {string} c 
         */
        removeCube: function(player_id, city, c) {
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
         * Player plays or declines to play Special Tile.
         * @param {bool} use
         */
        specialTile: function(bUse) {
            console.log("click 3");
            if (this.checkPossibleActions("useSpecial", true)) {
                console.log(this.player_id + " clicked Special "+bUse);
                this.ajaxcall( "/perikles/perikles/specialTile.html", {
                    player: this.player_id,
                    use: bUse,
                    lock: true 
                }, this, function( result ) {  }, function( is_error) { } );
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
            }
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

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your perikles.game.php file.
        
        */
        setupNotifications: function() {
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
            dojo.subscribe( 'takeMilitary', this, "notif_takeMilitary");
            this.notifqueue.setSynchronous( 'election', 500 );
            dojo.subscribe( 'useTile', this, "notif_useTile");
            this.notifqueue.setSynchronous( 'useTile', 500 );
            dojo.subscribe( 'spentInfluence', this, "notif_cubeRemoved");
            this.notifqueue.setSynchronous( 'spentInfluence', 500 );
            dojo.subscribe( 'sendMilitary', this, "notif_sendBattle");
            this.notifqueue.setSynchronous( 'sendMilitary', 1000 );
            dojo.subscribe( 'newInfluence', this, "notif_newInfluence");
            dojo.subscribe( 'newLocations', this, "notif_newLocations");
            dojo.subscribe( 'unclaimedTile', this, "notif_unclaimedTile");
            dojo.subscribe( 'returnMilitary', this, "notif_returnMilitary");
            dojo.subscribe( 'playSpecial', this, "notif_playSpecial");
            this.notifqueue.setSynchronous( 'notif_playSpecial', 500 );

            

            dojo.subscribe( 'revealCounters', this, "notif_revealCounters");
            dojo.subscribe( 'battle', this, "notif_battle");
            dojo.subscribe( 'crtOdds', this, "notif_crtOdds");
            this.notifqueue.setSynchronous( 'crtOdds', 500 );
            dojo.subscribe( 'diceRoll', this, "notif_diceRoll");
            dojo.subscribe( 'resetBattleTokens', this, "notif_resetBattleTokens");
            
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
            const cube1 = player_cubes.lastChild;

            const cube = this.createInfluenceCube(player_id, city, c);
            this.moveCube(cube, player_cubes, $(city+'_'+c), 500);
            this.fadeOutAndDestroy( cube1.id, 250);
            if (c == "a") {
                $(city+"_a").classList.remove("prk_candidate_space_active");
                $(city+"_b").classList.add("prk_candidate_space_active");
            } else {
                $(city+"_b").classList.remove("prk_candidate_space_active");
                $(city).classList.remove("prk_city_active")
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
            const fromcube = candidate_id+"_"+city+"_b";
            const to_div = $(city+"_a");
            this.slideToObjectRelative(fromcube, to_div, 1000, 1000, null, "last")
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
                if ($(city+"_"+c).hasChildNodes) {
                    const cand = $(city+"_"+c).lastElementChild;
                    this.fadeOutAndDestroy(cand.id, 500);
                }
            });
            // subtract loser's cubes from winner's
            this.removeInfluenceCubes(player_id, city, cubes);
            // place Leader
            const leader = this.createLeaderCounter(player_id, city, "leader", 1);
            dojo.place(leader, $(city+"_leader"));
        },

        /**
         * Move military tokens to each Leader.
         * @param {Object} notif 
         */
        notif_takeMilitary: function(notif) {
            const military = notif.args.military;
            for (const mil of military) {
                this.moveMilitary(mil);
            }
        },

        /**
         * Send military units to battle tiles.
         * @param {Object} notif 
         */
        notif_sendBattle: function(notif) {
            const player_id = notif.args.player_id;
            const id = notif.args.unit;
            const city = notif.args.city;
            const type = notif.args.type;
            const strength = notif.args.strength;
            const slot = notif.args.slot;
            const battlepos= notif.args.battlepos;
            this.moveToBattle(player_id, city, type, strength, id, slot, battlepos);
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
            const spec = player_div.getElementsByClassName("prk_special_tile")[0];
            spec.classList.remove("prk_special_tile_back");
            spec.classList.add("prk_special_tile_front", "prk_special_tile_used");
            // remove button
            if (this.player_id == player_id) {
                $('play_special_btn').remove();
            }
        },

        /**
         * Start of new turn, move all cards to Deck and deal new ones.
         * @param {Object} notif 
         */
        notif_newInfluence: function(notif) {
            const influencetiles = document.getElementsByClassName("prk_influence_tile");
            [...influencetiles].forEach(t => {
                this.slideToObjectAndDestroy(t, 'influence_slot_0', 500, 0);
            });
            const influence = notif.args.influence;
            const sz = parseInt(notif.args.decksize);
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
            const loc = notif.args.location;
            const tile = $(loc+'_tile');
            // clear margin before putting in box
            tile.style.margin = null;
            this.slideToObjectRelative(tile.id, 'unclaimed_tiles', 500, 0);
        },

        /**
         * Return military from a battle to cities
         * @param {Object} notif 
         */
        notif_returnMilitary: function(notif) {
            slot = notif.args.slot;
            const counters = $('battle_zone_'+slot).getElementsByClassName("prk_military");
            [...counters].forEach(c => {
                const counter_name = c.id;
                const [city, unit, strength, _, id] = counter_name.split('_');
                const city_military = city+"_military";
                this.slideToObjectAndDestroy(c, city_military, 1000, 500);
                this.placeCityStack(city, unit, strength, id);
            });
        },

        /**
         * Flip all the counters face up at a battle zone during the fight.
         * @param {Object} notif 
         */
        notif_revealCounters: function(notif) {
            const slot = notif.args.slot;
            const military = notif.args.military;
            // clear the old ones. TODO: animate flipping
            const oldcounters = $('battle_zone_'+slot).getElementsByClassName("prk_military");
            [...oldcounters].forEach(c => {
                c.remove();
            });
            let i = 0;
            military.forEach(m => {
                this.placeCounterAtBattle(m, i++);
            });
       },

        notif_crtOdds: function(notif) {
            const slot = notif.args.slot;
            const crt = notif.args.crt;
            const crt_col = $('crt_'+crt);
            debugger;
            crt_col.classList.add("prk_crt_active");
        },

        /**
         * 
         * @param {Object} notif 
         */
        notif_diceRoll: function(notif) {

        },

        notif_resetBattleTokens: function(notif) {
            const tokens = document.getElementsByClassName("prk_battle_token");
            [...tokens].forEach(t => {
                t.remove();
            });
        },

        notif_battle: function(notif) {
            debugger;
            for (i = 0; i < 4; i++) {
                const token = '<div class="prk_battle_token"></div>';
                dojo.place(token, $('battle_tokens'));
            }
        },
   });
});

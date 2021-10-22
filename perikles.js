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

const PLAYER_INF_MARGIN = "2px";

const SPECIAL_TILES = ['perikles', 'persianfleet', 'slaverevolt', 'brasidas', 'thessalanianallies', 'alkibiades', 'phormio', 'plague'];

const HOPLITE = "hoplite";
const TRIREME = "trireme";

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

// row,column
const LOCATIONS = {
    "amphipolis" : [1,1],
    "lesbos" : [1,2],
    "plataea" :  [1,3],
    "naupactus" : [1,4],
    "potidea" : [1,5],
    "acarnania" : [1,6],
    "attica" : [1,7],
    "melos" : [2,1],
    "epidaurus" : [2,2],
    "pylos" : [2,3],
    "sicily" : [2,4],
    "cephallenia" : [2,5],
    "cythera" : [2,6],
    "spartolus" : [2,7],
    "megara" : [3,1],
    "mantinea" : [3,2],
    "delium" : [3,3],
    "aetolia" : [3,4],
    "corcyra" : [3,5],
    "leucas" : [3,6],
    "solygeia" : [3,7],
}

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.perikles", ebg.core.gamegui, {
        constructor: function(){
            console.log('perikles constructor');
            this.influence_h = 199;
            this.influence_w = 128;
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
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
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

            console.log( "Ending game setup" );
        },

        /**
         * Set up special tiles
         * @param {Array} players 
         * @param {Array} specialtiles 
         */
        setupSpecialTiles: function(players, specialtiles) {
            const special_scale = 0.2;

            for (const player_id in players) {
                const player_board_div = document.getElementById('player_board_'+player_id);
                const spec = parseInt(specialtiles[player_id]);

                // add flex row for cards
                const player_cards = this.format_block('jstpl_influence_cards', {id: player_id, scale: special_scale});
                const player_cards_div = dojo.place(player_cards, player_board_div);

                if (spec == 0) {
                    var specialtile = this.format_block('jstpl_special_back', {id: player_id, scale: special_scale});
                } else {
                    const spec_i = SPECIAL_TILES[Math.abs(spec-1)];
                    specialtile = this.format_block('jstpl_special_tile', {special: spec_i, scale: special_scale, margin: PLAYER_INF_MARGIN});
                    const used = (spec < 0 || player_id != this.player_id);
                    if (used) {
                        tile.classList.add("per_special_tile_used");
                    }
                }
                const tile = dojo.place(specialtile, player_cards_div);
                if (spec == 0) {
                    let ttext = _("${player_name}'s Special tile");
                    const player_name = this.spanPlayerName(player_id);
                    ttext = ttext.replace('${player_name}', player_name);
                    this.addTooltip(tile.id, ttext, '');
                } else {
                    const thtml = this.createSpecialTileTooltip(players[player_id], SPECIAL_TILES[Math.abs(spec-1)]);
                    this.addTooltipHtml(tile.id, thtml, '');
                }
            }
        },

        /**
         * HTML for Special tile tooltip.
         * @param {Object} player 
         * @param {string} tilenum 
         * @returns 
         */
        createSpecialTileTooltip: function(player, special) {
            
            const TITLES = {
                'perikles': _("Perikles"),
                'persianfleet': _("Persian Fleet"),
                'slaverevolt': _("Slave Revolt"),
                'brasidas': _("Brasidas"),
                'thessalanianallies': _("Thessalanian Allies"),
                'alkibiades': _("Alkibiades"),
                'phormio': _("Phormio"),
                'plague': _("Plague")
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
                    this.placeInfluencePlayerBoard(tile);
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
            const id = tile['id'];
            const city = tile['city'];
            const s = tile['slot'];
            const slot = document.getElementById("influence_slot_"+s);
            const xoff = -1 * INFLUENCE_COL[tile['type']] * INFLUENCE_SCALE * this.influence_w;
            const yoff = -1 * INFLUENCE_ROW[city] * INFLUENCE_SCALE * this.influence_h;
            const card_div = this.format_block('jstpl_influence_tile', {city: city, id: id, x: xoff, y: yoff, margin: "auto"});
            const card = dojo.place(card_div, slot);
            let ttext = "";
            if (city == "any") {
                ttext = helptext["any"];
            } else {
                ttext = helptext[tile['type']];
            }
            const cityname = this.getCityNameTr(city);
            ttext = ttext.replace('${city}', cityname);
            const tooltip = this.format_block('jstpl_influence_tt', {city: cityname, label: helplbl[tile['type']], text: ttext, x: xoff, y: yoff});
            this.addTooltipHtml(card.id, tooltip, '');
            this.decorateInfluenceCard(card.id);
        },

        /**
         * 
         * @param {Object} tile 
         */
        placeInfluencePlayerBoard: function(tile) {
            const loc = tile['location'];
            const player_cards = loc+'_player_cards';
            const player_card_div = document.getElementById(player_cards);
            const card_div = this.createPlayerInfluenceCard(tile);
            dojo.place(card_div, player_card_div);
        },

        /**
         * 
         * @param {Object} tile 
         * @returns Div string
         */
        createPlayerInfluenceCard: function(tile) {
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
            const pile = document.getElementById(INFLUENCE_PILE);
            for (let c = 1; c <= decksize; c++) {
                const cardback = this.format_block('jstpl_influence_back', {id: c, x: -1 * INFLUENCE_SCALE * this.influence_w, y: -6 * INFLUENCE_SCALE * this.influence_h, m: c-1});
                dojo.place(cardback, pile);
            }
            var pile_tt = _("Influence Deck: ${num} cards remaining");
            pile_tt = pile_tt.replace('${num}', decksize);
            this.addTooltip(INFLUENCE_PILE, pile_tt, '');
        },

        /**
         * Return translatable city name text
         * @param {string} city 
         */
        getCityNameTr: function(city) {
            const citynames = {
                "argos" : _("Argos"),
                "athens": _("Athens"),
                "corinth": _("Corinth"),
                "megara": _("Megara"),
                "sparta": _("Sparta"),
                "thebes": _("Thebes"),
                "any": _("Any City")
            };
            return citynames[city];
        },

        /**
         * For Influence cards on display, add Event listeners.
         * @param {string} id 
         */
         decorateInfluenceCard: function(id) {
            const card = document.getElementById(id);
            card.addEventListener('click', () => {
                this.onInfluenceCardSelected(id);
            });
            card.addEventListener('mouseenter', () => {
                this.onInfluenceCardHover(id, true);
            });
            card.addEventListener('mouseout', () => {
                this.onInfluenceCardHover(id, false);
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
                        const column = document.getElementById(city+"_cubes_"+player_id);
                        dojo.place(cube, column);
                    }
                }

            }
        },

        /**
         * Put all the Location tiles in their slots.
         * @param {Array} locationtiles 
         */
        setupLocationTiles: function(locationtiles) {
            const locw = 124;
            const loch = 195;
            const scale = 0.55;
        
            for (const loc of locationtiles) {
                const slot = loc['slot'];
                const battle = loc['location'];
                const loc_slot = document.getElementById("location_"+slot);
                x = -1 * (LOCATIONS[battle][1]-1) * locw * scale;
                y = -1 * (LOCATIONS[battle][0]-1) * loch * scale;
                const loc_tile = this.format_block('jstpl_location_tile', {id: battle, x: x, y: y});
                dojo.place(loc_tile, loc_slot);
            }
        },

        /**
         * Put cubes in candidate spaces.
         * @param {Object} candidates 
         */
        setupCandidates: function(candidates) {
            for (const [cand, player_id] of Object.entries(candidates)) {
                const candidate_space = document.getElementById(cand);
                const cube = this.createInfluenceCube(player_id, city, "cand");
                dojo.place(cube, candidate_space);
            }
        },

        /**
         * Place Leader tokens on cities.
         * @param {Object} leaders 
         */
        setupLeaders: function(leaders) {
            for (const [city, player_id] of Object.entries(leaders)) {
                const leader = this.createLeaderCounter(player_id, city, "leader", 1);
                const leader_slot = document.getElementById(city+"_leader");
                dojo.place(leader, leader_slot);
            }
        },

        /**
         * Place all statues in city statue areas.
         * @param {Object} statues 
         */
        setupStatues: function(statues) {
            for (let city of CITIES) {
                const citystatues = statues[city];
                if (citystatues) {
                    const statue_area = document.getElementById(city+"_statues");
                    let s = 0;
                    for (const [player_id, num] of Object.entries(citystatues)) {
                        for (let i = 1; i <= parseInt(num); i++) {
                            const statue_div = this.createLeaderCounter(player_id, city, "statue", s+1);
                            const statue = dojo.place(statue_div, statue_area);
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
         * @returns colored influence cube
         */
        createInfluenceCube: function(player_id, city, tag) {
            const player = this.gamedatas.players[player_id];
            const color = player.color;
            const id = player_id+"_"+city+"_"+tag;
            const cube = this.format_block('jstpl_cube', {id: id, color: color});
            return cube;
        },

        /**
         * For military pools only, not conflict zone
         * @param {Object} military 
         */
        setupMilitary: function(military) {
            const dim_l = 100;
            const dim_s = 62;
            this.military_zones = {};
            let mz = 'persia_military';
            this.militaryAreaEvents(mz);
            this.military_zones[mz] = {'spread': false};
            for (city of CITIES) {
                mz = city+"_military";
                this.militaryAreaEvents(mz);
                this.military_zones[mz] = {'spread': false};
            }

            for(const i in military) {
                const counter = military[i];
                const city = counter['city'];
                const unit = counter['type'];
                const strength = counter['strength'];
                const location = counter['location'];
                var xdim, ydim;
                if (unit == HOPLITE) {
                    xdim = dim_s;
                    ydim = dim_l;
                } else if (unit == TRIREME) {
                    xdim = dim_l;
                    ydim = dim_s;
                } else {
                    throw Error("invalid unit type: "+ unit);
                }
                let xoff = -1 * strength * xdim;
                let yoff = -1 * MILITARY_ROW[city] * ydim;
                if (location == city) {
                    const city_military = document.getElementById(city+"_military");
                    const ct = city_military.childElementCount;
                    const top = (unit == TRIREME) ? ydim/2 : 0;
                    const tile_div = this.format_block('jstpl_military', {city: city, type: unit, s: strength, id: counter['id'], x: xoff, y: yoff, m: 2*ct, t: top}); 
                    dojo.place(tile_div, city_military);
                }
            }
        },

        /**
         * Make military display available counters
         */
        militaryAreaEvents: function(city_mil_id) {
            const city_mil = document.getElementById(city_mil_id);
            city_mil.addEventListener('click', () => {
                if (this.isSpread(city_mil_id)) {
                    this.unspread(city_mil);
                } else {
                    this.spreadMilitaryUnits(city_mil);
                }
            });
            city_mil.addEventListener('mouseenter', () => {
                this.spreadMilitaryUnits(city_mil);
            });
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
                if (mil.classList.contains("per_hoplite")) {
                    hoplites.push(mil.id);
                } else if  (mil.classList.contains("per_trireme")) {
                    triremes.push(mil.id);
                }
            }
            let n = 0;
            // Athens spreads to left
            let athens_off = 0;
            if (city_mil.id == "athens_military") {
                athens_off = -1 * Math.max((hoplites.length * MIL_DIM.s), (triremes.length * MIL_DIM.l));
            }
            for (h of hoplites) {
                const hop = document.getElementById(h);
                let xoff = athens_off+(n*MIL_DIM.s);
                let yoff = n*-2;
                Object.assign(hop.style, {'transform' : "translate("+xoff+"px,"+yoff+"px)", 'z-index': 1});
                n++;
            }
            const rec = city_mil.getBoundingClientRect();
            n = 0;
            for (t of triremes) {
                const tri = document.getElementById(t);
                let tridim = tri.getBoundingClientRect();
                let xoff = (-2 * hoplites.length) + athens_off+(n*MIL_DIM.l);
                let yoff = 22 + rec.bottom - tridim.top;
                Object.assign(tri.style, {'transform' : "translate("+xoff+"px,"+yoff+"px)", 'z-index': 1});
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
                    const def_div = document.getElementById(city+'_defeat_slot_'+d);
                    dojo.place(def_ctr, def_div);
                }
            }
        },

        /**
         * Add event listeners to city divs.
         */
        setupCities: function() {
            for (city of CITIES) {
                const city_div = document.getElementById(city);
                city_div.addEventListener('mouseenter', (event) => {
                    this.onCityHover(event);
                });
                city_div.addEventListener('mouseleave', (event) => {
                    this.onCityExit(event);
                });
                city_div.addEventListener('click', (event) => {
                    this.onCityClick(event);
                });
            }
        },

        /**
         * For a city, returns the div for candidate a if it's empty, else b if it's empty, else null
         * @param {string} city 
         * @returns a DOM Element or else null
         */
        openCandidateSpace: function(city) {
            let candidate_space = null;
            const citya = document.getElementById(city+"_a");
            if (!citya.hasChildNodes()) {
                candidate_space = citya;
            } else {
                const cityb = document.getElementById(city+"_b");
                if (!cityb.hasChildNodes()) {
                    candidate_space = cityb; 
                }
            }
            return candidate_space;
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
                        args.actplayer = args.actplayer.replace('color:#FFF;', 'color:#FFF; '+'background-color:#ccc; text-shadow: -1px -1px #000;');
                    }
                    if (!this.isSpectator) {
                        log = log.replace("You", this.spanYou());
                    }
                }
            } catch (e) {
                console.error(log, args, "Exception thrown", e.stack);
            }
            return this.inherited(arguments);
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
         * Get the style tag for background-color for a player name (shadow for white text)
         * @param {Object} player 
         * @returns css tag or empty string
         */
        colorBg: function(player) {
            let color_bg = "";
            if (player.color_back) {
                color_bg = "background-color:#"+player.color_back+";";
            } else if (player.color == "FFF") {
                color_bg = "background-color:#ccc; text-shadow: -1px -1px #000;";
            }
            return color_bg;
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

        ///////////////////////////////////////////////////
        //// Event listeners

        onCityHover: function(event) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("placeAnyCube", true)) {
                    const city = event.target.id;
                    const mycubes = document.getElementById(city+"_cubes_"+this.player_id);
                    mycubes.classList.add("per_cubes_hover");
                } else if (this.checkAction("chooseCandidate", true)) {
                    console.log(event.currentTarget.id);
                }
            }
        },

        onCityExit: function(event) {
            // debugger;
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("placeAnyCube", true)) {
                    const city = event.target.id;
                    const mycubes = document.getElementById(city+"_cubes_"+this.player_id);
                    mycubes.classList.remove("per_cubes_hover");
                } else if (this.checkAction("chooseCandidate", true)) {
                }
            }
        },

        onCityClick: function(event) {
            if( this.isCurrentPlayerActive() ) {
                if (this.checkAction("placeAnyCube", true)) {
                    const city = event.target.id;
                    this.placeInfluenceCube(city);
                    const mycubes = document.getElementById(city+"_cubes_"+this.player_id);
                    mycubes.classList.remove("per_cubes_hover");
                } else if (this.checkAction("chooseCandidate", true)) {
                }
            }
        },

        /**
         * When Influence card is taken.
         * @param {string} id 
         */
         onInfluenceCardSelected: function(id) {
            if (this.checkAction("takeInfluence", true)) {
                this.takeInfluenceTile(id);
            }
       },

       /**
        * 
        * @param {string} id 
        * @param {bool} hover 
        */
        onInfluenceCardHover: function(id, hover) {
            if (this.checkAction("takeInfluence", true)) {
                const card = document.getElementById(id);
                card.style['transform'] = hover ? 'scale(1.1)' : '';
                card.style['transition'] = 'transform 0.5s';
                card.style['box-shadow'] = hover ? 'rgba(0, 0, 0, 0.25) 0px 54px 55px, rgba(0, 0, 0, 0.12) 0px -12px 30px, rgba(0, 0, 0, 0.12) 0px 4px 6px, rgba(0, 0, 0, 0.17) 0px 12px 13px, rgba(0, 0, 0, 0.09) 0px -3px 5px' : '';
            }
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName ) {
                case 'choosePlaceInfluence':
                    if( this.isCurrentPlayerActive() ) {
                        for (let city_div of document.getElementsByClassName("per_city")) {
                            city_div.classList.add("per_city_active");
                        }
                    }
                    break;
                case 'proposeCandidates':
                    if (this.isCurrentPlayerActive()) {
                        for (city of CITIES) {
                            const candidate_space = this.openCandidateSpace(city);
                            if (candidate_space) {
                                candidate_space.classList.add("per_candidate_space_active");
                            }
                        }
                    }
                case 'dummmy':
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
                case 'choosePlaceInfluence':
                    for (let city_div of document.getElementsByClassName("per_city")) {
                        city_div.classList.remove("per_city_active");
                    }
                    break;
                case 'proposeCandidates':
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
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods

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
            document.getElementById(parent_id).appendChild(mobile);
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

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your perikles.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'influenceCardTaken', this, "notif_influenceCardTaken" );
            this.notifqueue.setSynchronous( 'influenceCardTaken', 1000 );
            dojo.subscribe( 'influenceCubes', this, "notif_addInfluenceCubes");
            this.notifqueue.setSynchronous( 'influenceCubes', 1000 );
        },  
        
        // Notification handlers


        notif_addInfluenceCubes: function( notif ) {
            const player_id = notif.args.player_id;
            const cubes = parseInt(notif.args.cubes);
            const city = notif.args.city;
            const from_div = document.getElementById(player_id+'_player_cards');
            const to_div = document.getElementById(city+'_cubes_'+player_id);
            const player_cubes_div = document.getElementById(city+"_cubes_"+player_id);
            const num = player_cubes_div.childElementCount;
            for (let c = 0; c < cubes; c++) {
                const i = num+c+1;
                const cube = this.createInfluenceCube(player_id, city, i);
                const cube_div = dojo.place(cube, from_div);
                this.slideToObjectRelative(cube_div.id, player_cubes_div, 1000, 1000, null, "last")
            }
        },
        
        notif_influenceCardTaken: function( notif )
        {
            console.log( notif );
            const player_id = notif.args.player_id;
            const city = notif.args.city;
            const id = notif.args.card_id;
            const card_id = city+'_'+id;
            const card_div = document.getElementById(card_id);
            const slot = notif.args.slot;

            // create the Influence tile
            const newTile = this.getTileById(id);
            if (newTile == null) {
                throw "Unknown Influence Tile: " + id;
            }

            card_div.remove();
            const player_cards = player_id+'_player_cards';
            const from_id = "influence_slot_"+slot;

            // create temp new card to move to the player board
            const fromSlot = document.getElementById(from_id);
            const newcard = this.createPlayerInfluenceCard(newTile);
            let newcard_div = dojo.place(newcard, fromSlot);
            this.slide(newcard_div, player_cards, {"from": fromSlot});
        },

        /**
         * Get tile from what should be in gamedatas.influencetiles
         * @param {string} id 
         * @returns tile
         */
        getTileById: function(id) {
            for (const tile of this.gamedatas.influencetiles) {
                if (tile['id'] == id) {
                    return tile;
                }
            }
            return null;
        },

   });
});
